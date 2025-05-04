<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\TargetAnggaran;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    
    public $tahunAnggaranId;
    public $level = '';
    public $search = '';
    public $tahunAnggaran = [];
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['targetAnggaranDeleted' => '$refresh'];
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
    }
    
    public function updatedTahunAnggaranId()
    {
        $this->resetPage();
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingLevel()
    {
        $this->resetPage();
    }
    
    public function delete($id)
    {
        $targetAnggaran = TargetAnggaran::find($id);
        
        // Cek apakah ada penerimaan terkait
        $kodeRekening = $targetAnggaran->kodeRekening;
        if ($kodeRekening->level == 4 && $kodeRekening->penerimaan()->where('tahun_anggaran_id', $targetAnggaran->tahun_anggaran_id)->count() > 0) {
            session()->flash('error', 'Target anggaran tidak dapat dihapus karena memiliki data penerimaan terkait.');
            return;
        }
        
        $targetAnggaran->delete();
        session()->flash('message', 'Target anggaran berhasil dihapus.');
        
        $this->emit('targetAnggaranDeleted');
    }
    
    public function render()
    {
        $query = TargetAnggaran::query();
        
        if ($this->tahunAnggaranId) {
            $query->where('tahun_anggaran_id', $this->tahunAnggaranId);
        }
        
        if ($this->search) {
            $query->whereHas('kodeRekening', function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->level) {
            $query->whereHas('kodeRekening', function($q) {
                $q->where('level', $this->level);
            });
        }
        
        $targetAnggaran = $query->with('kodeRekening', 'tahunAnggaran')
            ->orderBy('id', 'desc')
            ->paginate(15);
        
        return view('livewire.target-anggaran.index', [
            'targetAnggaran' => $targetAnggaran
        ]);
    }
}

