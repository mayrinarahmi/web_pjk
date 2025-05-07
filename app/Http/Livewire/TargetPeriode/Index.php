<?php

namespace App\Http\Livewire\TargetPeriode;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TargetPeriode;
use App\Models\TahunAnggaran;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;
    
    public $tahunAnggaranId;
    public $tahunAnggaran = [];
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
    }
    
    public function delete($id)
    {
        TargetPeriode::destroy($id);
        session()->flash('message', 'Target periode berhasil dihapus.');
    }
    
    public function getTotalPersentaseProperty()
    {
        if (!$this->tahunAnggaranId) {
            return 0;
        }
        
        return TargetPeriode::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->sum('persentase');
    }
    
    public function render()
    {
        $targetPeriode = TargetPeriode::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->orderBy('bulan_awal')
            ->paginate(10);
        
        return view('livewire.target-periode.index', [
            'targetPeriode' => $targetPeriode
        ]);
    }
}