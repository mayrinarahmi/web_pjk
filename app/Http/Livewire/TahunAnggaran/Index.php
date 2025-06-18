<?php

namespace App\Http\Livewire\TahunAnggaran;

use Livewire\Component;
use App\Models\TahunAnggaran;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
   
    public $search = '';
    public $filterJenis = ''; // Filter untuk jenis anggaran
    protected $paginationTheme = 'bootstrap';
   
    protected $queryString = [
        'search' => ['except' => ''],
        'filterJenis' => ['except' => ''],
    ];
   
    protected $listeners = ['tahunAnggaranDeleted' => '$refresh'];
   
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingFilterJenis()
    {
        $this->resetPage();
    }
   
    public function setActive($id)
    {
        // Nonaktifkan semua tahun anggaran
        TahunAnggaran::where('is_active', true)->update(['is_active' => false]);
       
        // Aktifkan tahun anggaran yang dipilih
        $tahunAnggaran = TahunAnggaran::find($id);
        if ($tahunAnggaran) {
            $tahunAnggaran->is_active = true;
            $tahunAnggaran->save();
           
            session()->flash('message', 'Tahun anggaran ' . $tahunAnggaran->display_name . ' berhasil diaktifkan.');
        }
    }
    
    public function createPerubahan($id)
    {
        $tahunAnggaranMurni = TahunAnggaran::find($id);
        
        if (!$tahunAnggaranMurni || $tahunAnggaranMurni->jenis_anggaran != 'murni') {
            session()->flash('error', 'Tahun anggaran murni tidak ditemukan.');
            return;
        }
        
        // Cek apakah sudah ada perubahan untuk tahun ini
        $existingPerubahan = TahunAnggaran::where('tahun', $tahunAnggaranMurni->tahun)
            ->where('jenis_anggaran', 'perubahan')
            ->first();
            
        if ($existingPerubahan) {
            session()->flash('error', 'APBD Perubahan untuk tahun ' . $tahunAnggaranMurni->tahun . ' sudah ada.');
            return;
        }
        
        // Buat APBD Perubahan
        $tahunAnggaranPerubahan = TahunAnggaran::create([
            'tahun' => $tahunAnggaranMurni->tahun,
            'jenis_anggaran' => 'perubahan',
            'parent_tahun_anggaran_id' => $tahunAnggaranMurni->id,
            'tanggal_penetapan' => now(),
            'is_active' => false,
        ]);
        
        // Copy semua target anggaran dari murni ke perubahan
        $tahunAnggaranPerubahan->copyTargetAnggaranFrom($tahunAnggaranMurni->id);
        
        session()->flash('message', 'APBD Perubahan tahun ' . $tahunAnggaranMurni->tahun . ' berhasil dibuat dan target anggaran telah dicopy.');
    }
   
    public function delete($id)
    {
        $tahunAnggaran = TahunAnggaran::find($id);
       
        if (!$tahunAnggaran) {
            session()->flash('error', 'Tahun anggaran tidak ditemukan.');
            return;
        }
       
        // Cek apakah tahun anggaran memiliki data terkait
        if ($tahunAnggaran->targetAnggaran()->count() > 0) {
            session()->flash('error', 'Tahun anggaran tidak dapat dihapus karena memiliki data target anggaran terkait.');
            return;
        }
       
        if ($tahunAnggaran->penerimaan()->count() > 0) {
            session()->flash('error', 'Tahun anggaran tidak dapat dihapus karena memiliki data penerimaan terkait.');
            return;
        }
        
        // Cek apakah memiliki APBD Perubahan (jika ini murni)
        if ($tahunAnggaran->jenis_anggaran == 'murni' && $tahunAnggaran->perubahan()->count() > 0) {
            session()->flash('error', 'APBD Murni tidak dapat dihapus karena memiliki APBD Perubahan terkait.');
            return;
        }
       
        if ($tahunAnggaran->is_active) {
            session()->flash('error', 'Tahun anggaran aktif tidak dapat dihapus.');
            return;
        }
       
        $tahunAnggaran->delete();
        session()->flash('message', 'Tahun anggaran berhasil dihapus.');
       
        $this->dispatch('tahunAnggaranDeleted');
    }
   
    public function render()
    {
        $query = TahunAnggaran::with('parent');
       
        if ($this->search) {
            $query->where('tahun', 'like', '%' . $this->search . '%');
        }
        
        if ($this->filterJenis) {
            $query->where('jenis_anggaran', $this->filterJenis);
        }
       
        $tahunAnggaran = $query->orderBy('tahun', 'desc')
            ->orderBy('jenis_anggaran', 'asc')
            ->paginate(10);
       
        return view('livewire.tahun-anggaran.index', [
            'tahunAnggaran' => $tahunAnggaran
        ]);
    }
}