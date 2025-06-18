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
        // PERBAIKAN: Ambil tahun yang unik saja, prioritaskan APBD Murni
        $this->tahunAnggaran = TahunAnggaran::select('tahun')
            ->selectRaw('MIN(id) as id') // Ambil ID terkecil (biasanya Murni)
            ->groupBy('tahun')
            ->orderBy('tahun', 'desc')
            ->get();
            
        // Set default ke tahun dari APBD yang aktif
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        if ($activeTahun) {
            // Cari tahun yang sama di list tahunAnggaran
            $selectedTahun = $this->tahunAnggaran->where('tahun', $activeTahun->tahun)->first();
            $this->tahunAnggaranId = $selectedTahun ? $selectedTahun->id : null;
        }
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