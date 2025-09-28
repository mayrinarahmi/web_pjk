<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Imports\TargetAnggaranImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    public $tahunAnggaranId;
    public $search = '';
    public $showLevel1 = true;
    public $showLevel2 = true;
    public $showLevel3 = true;
    public $showLevel4 = true;
    public $showLevel5 = true;
    public $showLevel6 = true;
    
    // Import properties
    public $showImportModal = false;
    public $importFile;
    public $importErrors = [];
    
    protected $paginationTheme = 'bootstrap';
    
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
    
    protected $listeners = ['targetUpdated' => 'refreshHierarchi'];
    
    protected $rules = [
        'importFile' => 'required|file|mimes:xlsx,xls|max:10240',
    ];
    
    public function mount()
    {
        // Set default ke tahun anggaran yang aktif
        $activeTahun = TahunAnggaran::getActive();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        Log::info('TargetAnggaran mounted', [
            'tahunAnggaranId' => $this->tahunAnggaranId,
            'search' => $this->search
        ]);
    }
    
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
    
    public function updateHierarchi()
    {
        if (!$this->tahunAnggaranId) {
            session()->flash('error', 'Pilih tahun anggaran terlebih dahulu.');
            return;
        }
        
        try {
            KodeRekening::updateHierarchiTargets($this->tahunAnggaranId);
            session()->flash('message', 'Hierarki target anggaran berhasil diperbarui.');
            
            Log::info('Hierarki updated for tahun anggaran: ' . $this->tahunAnggaranId);
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memperbarui hierarki: ' . $e->getMessage());
            Log::error('Failed to update hierarki: ' . $e->getMessage());
        }
    }
    
    public function refreshHierarchi()
    {
        $this->updateHierarchi();
    }
    
    // Import Methods
    public function toggleImportModal()
    {
        $this->showImportModal = !$this->showImportModal;
        $this->importFile = null;
        $this->importErrors = [];
        $this->resetValidation();
    }
    
    public function import()
    {
        $this->validate();
        
        if (!$this->tahunAnggaranId) {
            session()->flash('error', 'Pilih tahun anggaran terlebih dahulu.');
            return;
        }
        
        $this->importErrors = [];
        
        try {
            $import = new TargetAnggaranImport($this->tahunAnggaranId);
            Excel::import($import, $this->importFile);
            
            $processedCount = $import->getProcessedCount();
            $skippedCount = $import->getSkippedCount();
            
            if ($skippedCount > 0) {
                session()->flash('warning', "Import selesai. {$processedCount} data berhasil diimport, {$skippedCount} data dilewati (kode rekening tidak ditemukan).");
            } else {
                session()->flash('success', "Data pagu anggaran berhasil diimport. Total: {$processedCount} data.");
            }
            
            $this->showImportModal = false;
            $this->importFile = null;
            
            // Update hierarki setelah import
            $this->updateHierarchi();
            
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            foreach ($failures as $failure) {
                $errors = $failure->errors();
                $row = $failure->row();
                
                foreach ($errors as $error) {
                    $this->importErrors[] = "Baris {$row}: {$error}";
                }
            }
            
            session()->flash('error', 'Import gagal karena validasi error.');
            
        } catch (\Exception $e) {
            Log::error('Import Error: ' . $e->getMessage());
            
            $errorMessage = 'Terjadi kesalahan: ';
            if (strpos($e->getMessage(), 'Undefined array key') !== false) {
                $errorMessage .= 'Format kolom Excel tidak sesuai. Pastikan headers: kode, pagu_anggaran';
            } else {
                $errorMessage .= $e->getMessage();
            }
            
            session()->flash('error', $errorMessage);
            $this->importErrors[] = $errorMessage;
        }
    }
    
    public function downloadTemplate()
    {
        $templatePath = public_path('templates/template_pagu_anggaran.xlsx');
        
        if (!file_exists($templatePath)) {
            session()->flash('error', 'Template file tidak ditemukan.');
            return;
        }
        
        return response()->download($templatePath, 'template_pagu_anggaran.xlsx');
    }
    
    public function render()
    {
        // Query tahun anggaran dengan informasi jenis
        $tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')
            ->orderBy('jenis_anggaran', 'asc')
            ->get();
        
        Log::info('Rendering with filters', [
            'search' => $this->search,
            'tahunAnggaranId' => $this->tahunAnggaranId,
            'levels' => [
                $this->showLevel1, $this->showLevel2, $this->showLevel3,
                $this->showLevel4, $this->showLevel5, $this->showLevel6
            ]
        ]);
        
        $visibleLevels = [];
        if ($this->showLevel1) $visibleLevels[] = 1;
        if ($this->showLevel2) $visibleLevels[] = 2;
        if ($this->showLevel3) $visibleLevels[] = 3;
        if ($this->showLevel4) $visibleLevels[] = 4;
        if ($this->showLevel5) $visibleLevels[] = 5;
        if ($this->showLevel6) $visibleLevels[] = 6;
        
        // Perbaikan: Gunakan query yang benar
        $query = KodeRekening::where('is_active', true);
        
        if (!empty($visibleLevels)) {
            $query->whereIn('level', $visibleLevels);
        }
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }
        
        // Order by kode untuk hierarchical order
        $allKodeRekening = $query->orderBy('kode', 'asc')->get();
        
        // Manual pagination untuk hierarchical data
        $currentPage = request()->get('page', 1);
        $perPage = 50;
        $offset = ($currentPage - 1) * $perPage;
        
        $kodeRekeningItems = $allKodeRekening->slice($offset, $perPage);
        
        // Create manual paginator
        $kodeRekening = new LengthAwarePaginator(
            $kodeRekeningItems,
            $allKodeRekening->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
                'fragment' => null,
            ]
        );
        
        // Get manual target untuk setiap item (tanpa calculated_target)
        if ($this->tahunAnggaranId) {
            foreach ($kodeRekening as $kr) {
                $kr->manual_target = TargetAnggaran::getTargetAnggaran($kr->id, $this->tahunAnggaranId);
                
                // Cek konsistensi dengan children
                $childrenSum = $kr->children()
                    ->where('is_active', true)
                    ->get()
                    ->sum(function($child) {
                        return TargetAnggaran::getTargetAnggaran($child->id, $this->tahunAnggaranId);
                    });
                
                // Update konsistensi check untuk level 6
                $kr->is_consistent = ($kr->level >= 5) || (abs($childrenSum - $kr->manual_target) <= 1);
            }
        }
        
        return view('livewire.target-anggaran.index', [
            'kodeRekening' => $kodeRekening,
            'tahunAnggaran' => $tahunAnggaran
        ]);
    }
}