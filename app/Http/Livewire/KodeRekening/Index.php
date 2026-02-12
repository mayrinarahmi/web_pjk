<?php

namespace App\Http\Livewire\KodeRekening;

use Livewire\Component;
use App\Models\KodeRekening;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Imports\KodeRekeningImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    // Search & Filter - DITAMBAHKAN
    public $search = '';
    public $levelFilter = '';
    public $statusFilter = '';
    
    // Pagination - DITAMBAHKAN
    public $perPage = 25;
    public $perPageOptions = [10, 25, 50, 100, 200, 500];
    
    // Existing properties
    public $showHierarchy = false; // DIUBAH default ke false untuk table view
    public $importFile;
    public $showImportModal = false;
    public $importErrors = [];
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['kodeRekeningDeleted' => '$refresh'];
    
    // Query String - DITAMBAHKAN
    protected $queryString = [
        'search' => ['except' => ''],
        'levelFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'perPage' => ['except' => 25],
    ];
    
    protected $rules = [
        'importFile' => 'required|file|mimes:xlsx,xls|max:10240',
    ];
    
    // Update methods - DITAMBAHKAN
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatedLevelFilter()
    {
        $this->resetPage();
    }
    
    public function updatedStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
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
        $this->importErrors = [];
        
        try {
            // Import data
            $import = new KodeRekeningImport;
            Excel::import($import, $this->importFile);
            
            // Get import statistics
            $processedCount = $import->getProcessedCount();
            $skippedCount = $import->getSkippedCount();
            $errors = $import->errors();
            
            // Handle errors
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->importErrors[] = $error;
                }
            }
            
            // Show appropriate message
            if (count($errors) > 0) {
                session()->flash('warning', "Import selesai dengan beberapa warning. Berhasil: {$processedCount}, Dilewati: {$skippedCount}");
            } else if ($skippedCount > 0) {
                session()->flash('info', "Import selesai. Berhasil: {$processedCount}, Dilewati: {$skippedCount} (data sudah ada)");
            } else {
                session()->flash('success', "Data kode rekening berhasil diimport. Total: {$processedCount} data.");
                $this->showImportModal = false;
                $this->importFile = null;
            }
            
            $this->dispatch('kodeRekeningDeleted'); // refresh data
            
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Handle validation errors dari Laravel Excel
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
            // Log error untuk debugging
            Log::error('Import Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Parse error message
            $errorMessage = 'Terjadi kesalahan: ';
            
            if (strpos($e->getMessage(), 'Undefined array key') !== false) {
                $errorMessage .= 'Format kolom Excel tidak sesuai. Pastikan headers: kode, nama, level (huruf kecil)';
            } elseif (strpos($e->getMessage(), 'Parent kode tidak ditemukan') !== false) {
                $errorMessage .= $e->getMessage();
            } else {
                $errorMessage .= $e->getMessage();
            }
            
            session()->flash('error', $errorMessage);
            $this->importErrors[] = $errorMessage;
        }
    }
    
    public function downloadTemplate()
    {
        $templatePath = public_path('templates/template_kode_rekening.xlsx');
        
        if (!file_exists($templatePath)) {
            // Create template on the fly if not exists
            return $this->createAndDownloadTemplate();
        }
        
        return response()->download($templatePath, 'template_kode_rekening.xlsx');
    }
    
    protected function createAndDownloadTemplate()
    {
        $headers = ['kode', 'nama', 'level'];
        $data = [
            ['4', 'PENDAPATAN DAERAH', 1],
            ['4.1', 'PENDAPATAN ASLI DAERAH (PAD)', 2],
            ['4.1.01', 'Pajak Daerah', 3],
            ['4.1.01.09', 'Pajak Reklame', 4],
            ['4.1.01.09.01', 'Pajak Reklame Papan/Billboard/Videotron/Megatron', 5],
            ['4.1.01.09.01.0001', 'Pajak Reklame Papan/Billboard/Videotron/Megatron', 6],
        ];
        
        // Create temporary file
        $filename = 'template_kode_rekening_' . date('YmdHis') . '.xlsx';
        $filePath = storage_path('app/public/' . $filename);
        
        // You might need to use a package like PhpSpreadsheet here
        // For now, return error
        session()->flash('error', 'Template tidak dapat dibuat. Silakan hubungi administrator.');
        return back();
    }
    
    // Reset Filters - DIUPDATE
    public function resetFilters()
    {
        $this->search = '';
        $this->levelFilter = '';
        $this->statusFilter = '';
        $this->perPage = 25;
        $this->showHierarchy = false;
        $this->resetPage();
    }
    
    public function delete($id)
    {
        $kodeRekening = KodeRekening::find($id);
        
        if (!$kodeRekening) {
            session()->flash('error', 'Kode rekening tidak ditemukan.');
            return;
        }
        
        if ($kodeRekening->children()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki sub-kode rekening.');
            return;
        }
        
        // Check for related data based on level
        if ($kodeRekening->level == 6 && $kodeRekening->penerimaan()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki data penerimaan terkait.');
            return;
        }
        
        if ($kodeRekening->targetAnggaran()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki data target anggaran terkait.');
            return;
        }
        
        $kodeRekening->delete();
        session()->flash('message', 'Kode rekening berhasil dihapus.');
        
        $this->dispatch('kodeRekeningDeleted');
    }
    
    public function render()
    {
        if ($this->showHierarchy) {
            // Get root level (level 1) with nested eager loading
            $kodeRekening = KodeRekening::where('level', 1)
                ->with(['children' => function($query) {
                    $query->orderBy('kode');
                    $query->with(['children' => function($query) {
                        $query->orderBy('kode');
                        $query->with(['children' => function($query) {
                            $query->orderBy('kode');
                            $query->with(['children' => function($query) {
                                $query->orderBy('kode');
                                $query->with(['children' => function($query) {
                                    $query->orderBy('kode');
                                }]);
                            }]);
                        }]);
                    }]);
                }])
                ->orderBy('kode')
                ->get();
                
            return view('livewire.kode-rekening.index-hierarchy', [
                'kodeRekening' => $kodeRekening
            ]);
        } else {
            // Table view with pagination - DIUPDATE
            $query = KodeRekening::query();
            
            // Apply filters
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('kode', 'like', '%' . $this->search . '%')
                      ->orWhere('nama', 'like', '%' . $this->search . '%');
                });
            }
            
            if ($this->levelFilter) {
                $query->where('level', $this->levelFilter);
            }
            
            if ($this->statusFilter !== '') {
                $query->where('is_active', $this->statusFilter);
            }
            
            // Order and paginate
            $kodeRekening = $query->orderBy('kode')
                                  ->paginate($this->perPage);
            
            return view('livewire.kode-rekening.index', [
                'kodeRekening' => $kodeRekening,
                'levels' => [1, 2, 3, 4, 5, 6],
                'perPageOptions' => $this->perPageOptions
            ]);
        }
    }
}