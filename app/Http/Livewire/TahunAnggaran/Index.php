<?php

namespace App\Http\Livewire\TahunAnggaran;

use Livewire\Component;
use App\Models\TahunAnggaran;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    
    public $search = '';
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['tahunAnggaranDeleted' => '$refresh'];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function setActive($id)
    {
        // Nonaktifkan semua tahun anggaran
        TahunAnggaran::where('is_active', true)->update(['is_active' => false]);
        
        // Aktifkan tahun anggaran yang dipilih
        $tahunAnggaran = TahunAnggaran::find($id);
        $tahunAnggaran->is_active = true;
        $tahunAnggaran->save();
        
        session()->flash('message', 'Tahun anggaran ' . $tahunAnggaran->tahun . ' berhasil diaktifkan.');
    }
    
    public function delete($id)
    {
        $tahunAnggaran = TahunAnggaran::find($id);
        
        // Cek apakah tahun anggaran memiliki data terkait
        if ($tahunAnggaran->targetAnggaran()->count() > 0) {
            session()->flash('error', 'Tahun anggaran tidak dapat dihapus karena memiliki data target anggaran terkait.');
            return;
        }
        
        if ($tahunAnggaran->penerimaan()->count() > 0) {
            session()->flash('error', 'Tahun anggaran tidak dapat dihapus karena memiliki data penerimaan terkait.');
            return;
        }
        
        if ($tahunAnggaran->is_active) {
            session()->flash('error', 'Tahun anggaran aktif tidak dapat dihapus.');
            return;
        }
        
        $tahunAnggaran->delete();
        session()->flash('message', 'Tahun anggaran berhasil dihapus.');
        
        $this->emit('tahunAnggaranDeleted');
    }
    
    public function render()
    {
        $query = TahunAnggaran::query();
        
        if ($this->search) {
            $query->where('tahun', 'like', '%' . $this->search . '%');
        }
        
        $tahunAnggaran = $query->orderBy('tahun', 'desc')->paginate(10);
        
        return view('livewire.tahun-anggaran.index', [
            'tahunAnggaran' => $tahunAnggaran
        ]);
    }
}
