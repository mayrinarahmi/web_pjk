<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;

class Index extends Component
{
    use WithPagination;
    
    public $tahunAnggaranId;
    public $search = '';
    public $showLevel1 = true;
    public $showLevel2 = true;
    public $showLevel3 = true;
    public $showLevel4 = true;
    public $showLevel5 = true;
    public $showLevel6 = true;
    
    protected $paginationTheme = 'bootstrap';
    
    public function mount()
    {
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatedTahunAnggaranId()
    {
        $this->resetPage();
    }
    
    public function toggleLevel($level)
    {
        $property = "showLevel$level";
        $this->$property = !$this->$property;
    }
    
    public function render()
    {
        $tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        
        $query = KodeRekening::query();
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }
        
        $visibleLevels = [];
        if ($this->showLevel1) $visibleLevels[] = 1;
        if ($this->showLevel2) $visibleLevels[] = 2;
        if ($this->showLevel3) $visibleLevels[] = 3;
        if ($this->showLevel4) $visibleLevels[] = 4;
        if ($this->showLevel5) $visibleLevels[] = 5;
        if ($this->showLevel6) $visibleLevels[] = 6;
        
        if (!empty($visibleLevels)) {
            $query->whereIn('level', $visibleLevels);
        }
        
        $kodeRekening = $query->orderBy('kode', 'asc')->paginate(50);
        
        return view('livewire.target-anggaran.index', [
            'kodeRekening' => $kodeRekening,
            'tahunAnggaran' => $tahunAnggaran
        ]);
    }
}
