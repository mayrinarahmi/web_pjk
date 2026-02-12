<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\Skpd;
use App\Imports\TargetAnggaranImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    protected $paginationTheme = 'bootstrap';
    
    // Properties
    public $tahunAnggaranId;
    public $search = '';
    
    // Level visibility toggles
    public $showLevel1 = true;
    public $showLevel2 = true;
    public $showLevel3 = true;
    public $showLevel4 = true;
    public $showLevel5 = true;
    public $showLevel6 = true;
    
    // SKPD Management Properties
    public $selectedSkpdId;
    public $skpdList = [];
    public $userSkpdInfo = '';
    
    // Import properties
    public $showImportModal = false;
    public $importFile;
    public $importTahun;
    public $importSkpdId;
    public $importErrors = [];
    
    // Data collections
    public $tahunAnggaran;
    public $kodeRekeningLevel6;
    
    protected $listeners = [
        'targetCreated' => 'refreshData',
        'targetUpdated' => 'refreshData',
    ];
    
    public function mount()
    {
        $user = auth()->user();
        
        // Initialize user context
        $this->initializeUserContext($user);
        
        // Load tahun anggaran
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')
            ->orderBy('jenis_anggaran', 'asc')
            ->get();
        
        // Set default active tahun
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        // Load kode rekening Level 6
        $this->loadKodeRekeningLevel6();
        
        Log::info('TargetAnggaran Index mounted', [
            'user' => $user->name,
            'skpd' => $user->skpd ? $user->skpd->nama_opd : 'No SKPD',
            'can_view_all' => $user->canViewAllSkpd()
        ]);
    }
    
    /**
     * Initialize user context (SKPD info)
     */
    private function initializeUserContext($user)
    {
        if ($user->canViewAllSkpd()) {
            // Super Admin / Kepala Badan
            $this->skpdList = Skpd::where('status', 'aktif')
                                  ->orderBy('nama_opd')
                                  ->get();
            
            $roleName = $user->isSuperAdmin() ? 'Super Admin' : 'Kepala Badan';
            $this->userSkpdInfo = $roleName . ' - Dapat melihat semua SKPD';
            
            // No default SKPD selection (show konsolidasi)
            $this->selectedSkpdId = null;
            
        } elseif ($user->skpd_id && $user->skpd) {
            // Operator SKPD
            $this->selectedSkpdId = $user->skpd_id;
            $this->userSkpdInfo = 'Anda dapat mengakses data untuk SKPD: ' . $user->skpd->nama_opd;
            
        } else {
            // User tanpa SKPD
            $this->userSkpdInfo = 'Anda tidak terdaftar di SKPD manapun';
        }
    }
    
    /**
     * Load kode rekening Level 6 berdasarkan SKPD access
     */
    private function loadKodeRekeningLevel6()
    {
        $user = auth()->user();
        $query = KodeRekening::where('level', 6)
                             ->where('is_active', true);
        
        if ($user->skpd_id && !$user->canViewAllSkpd()) {
            // Operator SKPD - filter by assigned kode rekening
            $skpd = $user->skpd;
            if ($skpd && $skpd->kode_rekening_access) {
                $allowedKodes = $skpd->kode_rekening_access;
                
                if (is_string($allowedKodes)) {
                    $allowedKodes = json_decode($allowedKodes, true) ?? [];
                }
                
                if (is_array($allowedKodes) && !empty($allowedKodes)) {
                    $query->whereIn('id', $allowedKodes);
                } else {
                    $this->kodeRekeningLevel6 = collect([]);
                    return;
                }
            }
        }
        
        $this->kodeRekeningLevel6 = $query->orderBy('kode')->get();
    }
    
    /**
     * Open import modal - PATTERN PENERIMAAN
     */
    public function openImportModal()
    {
        $this->showImportModal = true;
        $this->importTahun = $this->tahunAnggaranId;
        $this->reset('importFile', 'importErrors');
        
        // Set default SKPD untuk import
        $user = auth()->user();
        if ($user->skpd_id && !$user->canViewAllSkpd()) {
            $this->importSkpdId = $user->skpd_id;
        }
    }
    
    /**
     * Close import modal
     */
    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->reset('importFile', 'importErrors', 'importSkpdId');
    }
    
    /**
     * Download template Excel
     */
    public function downloadTemplate()
    {
        try {
            $fileName = 'template_target_anggaran.xlsx';
            
            // Create new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $sheet->setCellValue('A1', 'kode');
            $sheet->setCellValue('B1', 'pagu_anggaran');
            
            // Example data
            $sheet->setCellValue('A2', '4.1.2.01.01.0001');
            $sheet->setCellValue('B2', '1000000');
            $sheet->setCellValue('A3', '4.1.2.01.01.0002');
            $sheet->setCellValue('B3', '2000000');
            
            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ];
            $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);
            
            // Auto size columns
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            
            // Create writer
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'template_');
            $writer->save($tempFile);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate template: ' . $e->getMessage());
            session()->flash('error', 'Gagal generate template: ' . $e->getMessage());
        }
    }
    
    /**
     * Import Excel - PATTERN PENERIMAAN (SIMPLIFIED)
     */
    public function import()
    {
        // Validasi basic
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls|max:10240',
        ], [
            'importFile.required' => 'File Excel wajib diupload',
            'importFile.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'importFile.max' => 'Ukuran file maksimal 10MB',
        ]);
        
        if (!$this->importTahun) {
            session()->flash('error', 'Pilih tahun anggaran terlebih dahulu');
            return;
        }
        
        $user = auth()->user();
        
        // Determine SKPD ID (otomatis untuk Operator SKPD)
        $importSkpdId = null;
        
        if ($user->isSuperAdmin()) {
            if (!$this->importSkpdId) {
                session()->flash('error', 'Super Admin harus memilih SKPD target');
                return;
            }
            $importSkpdId = $this->importSkpdId;
            
        } elseif ($user->skpd_id) {
            // Operator SKPD - otomatis
            $importSkpdId = $user->skpd_id;
            
        } else {
            session()->flash('error', 'Tidak dapat menentukan SKPD untuk import');
            return;
        }
        
        $skpd = Skpd::find($importSkpdId);
        if (!$skpd) {
            session()->flash('error', 'SKPD tidak ditemukan');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Import
            $import = new TargetAnggaranImport($this->importTahun, $importSkpdId);
            Excel::import($import, $this->importFile);
            
            // Update hierarki after import
            // KodeRekening::updateHierarchiTargets($this->importTahun);
            
            DB::commit();
            
            // Results
            $processed = $import->getProcessedCount();
            $skipped = $import->getSkippedCount();
            $zeroValue = $import->getZeroValueCount();
            
            $message = "Import berhasil untuk SKPD: {$skpd->nama_opd}! ";
            $message .= "Data berhasil diproses: {$processed} baris.";
            
            if ($skipped > 0) {
                $message .= " Data dilewati: {$skipped} baris.";
            }
            
            if ($zeroValue > 0) {
                $message .= " Data dengan nilai 0: {$zeroValue} baris.";
            }
            
            session()->flash('success', $message);
            
            // Show details
            $details = $import->getSkippedDetails();
            if (!empty($details)) {
                $warning = 'Beberapa data dilewati: ' . implode('; ', array_slice($details, 0, 5));
                if (count($details) > 5) {
                    $warning .= ' ... dan ' . (count($details) - 5) . ' lainnya';
                }
                session()->flash('warning', $warning);
            }
            
            Log::info('Target Anggaran imported successfully', [
                'user' => $user->name,
                'skpd' => $skpd->nama_opd,
                'tahun' => $this->importTahun,
                'processed' => $processed,
                'skipped' => $skipped
            ]);
            
            // Close modal
            $this->closeImportModal();
            
            // Refresh
            $this->refreshData();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->importErrors[] = $e->getMessage();
            session()->flash('error', 'Import gagal: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete all pagu anggaran - Super Admin only
     */
    public function deleteAllPaguAnggaran()
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin()) {
            session()->flash('error', 'Hanya Super Admin yang dapat menghapus semua data');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            $count = TargetAnggaran::count();
            TargetAnggaran::truncate();
            
            DB::commit();
            
            session()->flash('success', "Berhasil menghapus {$count} data pagu anggaran");
            
            Log::warning('All pagu anggaran deleted', [
                'user' => $user->name,
                'count' => $count
            ]);
            
            $this->refreshData();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete all pagu: ' . $e->getMessage());
            session()->flash('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    
    /**
     * Update hierarki
     */
    public function updateHierarchi()
    {
        if (!$this->tahunAnggaranId) {
            session()->flash('error', 'Pilih tahun anggaran terlebih dahulu');
            return;
        }
        
        try {
            KodeRekening::updateHierarchiTargets($this->tahunAnggaranId);
            session()->flash('success', 'Hierarki target anggaran berhasil diperbarui!');
            $this->refreshData();
        } catch (\Exception $e) {
            Log::error('Failed to update hierarchy: ' . $e->getMessage());
            session()->flash('error', 'Gagal memperbarui hierarki: ' . $e->getMessage());
        }
    }
    
    /**
     * Refresh data
     */
    public function refreshData()
    {
        $this->loadKodeRekeningLevel6();
        $this->resetPage();
    }
    
    /**
     * Updated tahun anggaran
     */
    public function updatedTahunAnggaranId()
    {
        $this->refreshData();
    }
    
    /**
     * Updated selected SKPD
     */
    public function updatedSelectedSkpdId()
    {
        $this->refreshData();
    }
    
    /**
     * Reset filters
     */
    public function resetFilters()
    {
        $this->reset(['search', 'selectedSkpdId']);
        $this->showLevel1 = true;
        $this->showLevel2 = true;
        $this->showLevel3 = true;
        $this->showLevel4 = true;
        $this->showLevel5 = true;
        $this->showLevel6 = true;
        $this->refreshData();
    }
    
    /**
     * Toggle level visibility
     */
    public function toggleLevel($level)
    {
        $property = 'showLevel' . $level;
        $this->$property = !$this->$property;
        $this->refreshData();
    }
    
    /**
     * Perform search
     */
    public function performSearch()
    {
        $this->refreshData();
    }
    
    public function render()
    {
        $user = auth()->user();
        
        // Build query
        $query = KodeRekening::where('is_active', true);
        
        // Filter levels
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
        
        // Search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }
        
        // SKPD filter untuk Operator SKPD
        if ($user->skpd_id && !$user->canViewAllSkpd()) {
            $skpd = $user->skpd;
            if ($skpd && $skpd->kode_rekening_access) {
                $allowedKodes = $skpd->kode_rekening_access;
                
                if (is_string($allowedKodes)) {
                    $allowedKodes = json_decode($allowedKodes, true) ?? [];
                }
                
                if (is_array($allowedKodes) && !empty($allowedKodes)) {
                    // Get hierarchy
                    $allKodeIds = [];
                    foreach ($allowedKodes as $kodeId) {
                        $kode = KodeRekening::find($kodeId);
                        if ($kode) {
                            $allKodeIds[] = $kode->id;
                            $parent = $kode->parent;
                            while ($parent) {
                                $allKodeIds[] = $parent->id;
                                $parent = $parent->parent;
                            }
                        }
                    }
                    $allKodeIds = array_unique($allKodeIds);
                    
                    if (!empty($allKodeIds)) {
                        $query->whereIn('id', $allKodeIds);
                    } else {
                        // No access
                        $kodeRekening = collect([]);
                        return view('livewire.target-anggaran.index', [
                            'kodeRekening' => $kodeRekening,
                            'tahunAnggaran' => $this->tahunAnggaran,
                            'skpdList' => $this->skpdList,
                            'userSkpdInfo' => $this->userSkpdInfo,
                        ]);
                    }
                }
            }
        }
        
        $kodeRekening = $query->simpleKodeOrder()->paginate(50);
        
        // Attach target data (✅ UPDATED untuk recursive)
if ($this->tahunAnggaranId) {
    foreach ($kodeRekening as $kr) {
        // ✅ Gunakan method model untuk recursive calculation
        $kr->manual_target = $kr->getTargetAnggaranForTahun(
            $this->tahunAnggaranId, 
            $this->selectedSkpdId
        );
        
        // Check consistency for parent levels (OPTIONAL - bisa di-comment jika tidak perlu)
        if ($kr->level < 6 && $this->tahunAnggaranId) {
            $kr->is_consistent = $kr->validateTargetHierarchi(
                $this->tahunAnggaranId,
                $this->selectedSkpdId
            );
        }
    }
}
        
        return view('livewire.target-anggaran.index', [
            'kodeRekening' => $kodeRekening,
            'tahunAnggaran' => $this->tahunAnggaran,
            'skpdList' => $this->skpdList,
            'userSkpdInfo' => $this->userSkpdInfo,
        ]);
    }
}