<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use Illuminate\Support\Facades\Log;

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
    
    // Penting: Definisikan queryString untuk URL state
    protected $queryString = [
        'tahunAnggaranId' => ['except' => ''],
        'search' => ['except' => ''],
        'showLevel1' => ['except' => true],
        'showLevel2' => ['except' => true],
        'showLevel3' => ['except' => true],
        'showLevel4' => ['except' => true],
        'showLevel5' => ['except' => true],
        'showLevel6' => ['except' => true],
    ];
    
    public function mount()
    {
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        // Debug info
        Log::info('TargetAnggaran mounted', [
            'tahunAnggaranId' => $this->tahunAnggaranId,
            'search' => $this->search
        ]);
    }
    
    // Handler untuk saat nilai search berubah (Livewire 3 - updated, bukan updating)
    public function updated($field)
    {
        if ($field === 'search') {
            Log::info('Search updated to: ' . $this->search);
            $this->resetPage();
        } else if ($field === 'tahunAnggaranId') {
            Log::info('Tahun Anggaran updated to: ' . $this->tahunAnggaranId);
            $this->resetPage();
        }
    }
    
    public function toggleLevel($level)
    {
        $property = "showLevel$level";
        $this->$property = !$this->$property;
        $this->resetPage();
    }
    
    public function performSearch()
    {
        // Method khusus untuk search - dapat dipanggil secara eksplisit
        Log::info('Performing search with: ' . $this->search);
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->showLevel1 = true;
        $this->showLevel2 = true;
        $this->showLevel3 = true;
        $this->showLevel4 = true;
        $this->showLevel5 = true;
        $this->showLevel6 = true;
        $this->resetPage();
    }
    
    public function render()
    {
        $tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        
        Log::info('Rendering with filters', [
            'search' => $this->search,
            'tahunAnggaranId' => $this->tahunAnggaranId,
            'levels' => [
                $this->showLevel1, $this->showLevel2, $this->showLevel3,
                $this->showLevel4, $this->showLevel5, $this->showLevel6
            ]
        ]);
        
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