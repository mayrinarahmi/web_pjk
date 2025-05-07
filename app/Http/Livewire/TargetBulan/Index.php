<?php

namespace App\Http\Livewire\TargetBulan;

use Livewire\Component;
use App\Models\TargetBulan;
use App\Models\TahunAnggaran;
use Livewire\WithPagination;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;
    
    public $tahunAnggaranId;
    public $tahunAnggaran = [];
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['targetBulanDeleted' => '$refresh'];
    
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
    
    public function delete($id)
    {
        TargetBulan::find($id)->delete();
        session()->flash('message', 'Target bulan berhasil dihapus.');
        
        $this->dispatch('targetBulanDeleted');
    }
    
    public function render()
    {
        $targetBulan = collect();
        $totalPersentase = 0;
        
        if ($this->tahunAnggaranId) {
            $targetBulan = TargetBulan::where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->orderBy('id')
                ->paginate(10);
                
            $totalPersentase = TargetBulan::where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->sum('persentase');
        }
        
        return view('livewire.target-bulan.index', [
            'targetBulan' => $targetBulan,
            'totalPersentase' => $totalPersentase
        ]);
    }
}
