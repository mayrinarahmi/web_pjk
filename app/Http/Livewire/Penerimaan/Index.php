<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\Skpd;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Imports\PenerimaanImport;
use App\Exports\LaporanRealisasiExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    public $search = '';
    public $tanggalMulai;
    public $tanggalSelesai;
    public $kodeRekeningId;
    public $tahun;
    
    // Display properties untuk info box
    public $displayTanggalMulai;
    public $displayTanggalSelesai;
    public $latestPenerimaanDate;
    
    // Filter SKPD
    public $selectedSkpdId = '';
    public $skpdList = [];
    public $userSkpdInfo = '';
    
    // Toggle level visibility
    public $showLevel1 = true;
    public $showLevel2 = true;
    public $showLevel3 = true;
    public $showLevel4 = true;
    public $showLevel5 = true;
    public $showLevel6 = true;
    
    public $hideEmpty = true;
    
    // Detail modal
    public $showDetailModal = false;
    public $selectedKodeRekening = null;
    public $detailPenerimaan = [];
    
    // Pagination
    public $perPage = 50;
    public $perPageOptions = [25, 50, 100, 200, 500];
    
    public $availableYears = [];
    public $kodeRekeningLevel6 = [];
    
    // Import properties
    public $showImportModal = false;
    public $importFile;
    public $importTahun;
    
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['penerimaanDeleted' => '$refresh'];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'tanggalMulai' => ['except' => ''],
        'tanggalSelesai' => ['except' => ''],
        'kodeRekeningId' => ['except' => ''],
        'tahun' => ['except' => ''],
        'selectedSkpdId' => ['except' => ''],
        'showLevel1' => ['except' => true],
        'showLevel2' => ['except' => true],
        'showLevel3' => ['except' => true],
        'showLevel4' => ['except' => true],
        'showLevel5' => ['except' => true],
        'showLevel6' => ['except' => true],
        'hideEmpty' => ['except' => true],
        'perPage' => ['except' => 50],
    ];

    // ==========================================
    // LIFECYCLE METHODS
    // ==========================================
    
    public function mount()
    {
        $this->hideEmpty = true;
        
        $user = auth()->user();
        
        // Initialize berdasarkan role user
        $this->initializeUserContext($user);
        
        // Set tahun aktif
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        $this->importTahun = $this->tahun;
        
        // Load available years
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
        
        // =============================================
        // AUTO-SET TANGGAL KE DATA TERAKHIR
        // =============================================
        $this->setTanggalToLatestData();
    }
    
    /**
     * Initialize user context berdasarkan role
     * Set SKPD list dan kode rekening access
     */
    private function initializeUserContext($user)
    {
        if ($user->canViewAllSkpd()) {
            // Super Admin atau Kepala Badan - bisa lihat semua SKPD
            $this->skpdList = Skpd::where('status', 'aktif')
                                  ->orderBy('nama_opd')
                                  ->get();
            
            $roleName = $user->isSuperAdmin() ? 'Super Admin' : 'Kepala BPKPAD';
            $this->userSkpdInfo = $roleName . ' - Semua SKPD';
            
            // Load semua kode rekening level 6
            $this->kodeRekeningLevel6 = KodeRekening::where('level', 6)
                                                    ->where('is_active', true)
                                                    ->orderBy('kode')
                                                    ->get();
                                                    
        } elseif ($user->skpd_id && $user->skpd) {
            // Operator SKPD - hanya SKPD sendiri
            $this->selectedSkpdId = $user->skpd_id;
            $this->userSkpdInfo = 'SKPD: ' . $user->skpd->nama_opd;
            
            // Load kode rekening yang di-assign ke SKPD
            $this->kodeRekeningLevel6 = $user->getAccessibleLevel6KodeRekening();
            
            // Warning jika belum ada assignment
            if ($this->kodeRekeningLevel6->isEmpty()) {
                session()->flash('warning', 'SKPD Anda belum memiliki kode rekening yang di-assign. Silakan hubungi administrator.');
            }
            
        } else {
            // User tanpa SKPD - tidak ada akses
            $this->kodeRekeningLevel6 = collect([]);
            $this->userSkpdInfo = 'Tidak terdaftar di SKPD';
            session()->flash('error', 'Anda tidak terdaftar di SKPD manapun. Silakan hubungi administrator.');
        }
    }
    
    /**
     * Set tanggal ke data penerimaan terakhir
     * Auto-set filter tanggal berdasarkan data terakhir yang diinput
     * ✅ FIXED: Convert Carbon object ke string format Y-m-d
     */
    private function setTanggalToLatestData()
    {
        if (!$this->tahun) {
            $this->tanggalMulai = null;
            $this->tanggalSelesai = null;
            $this->displayTanggalMulai = null;
            $this->displayTanggalSelesai = null;
            $this->latestPenerimaanDate = null;
            return;
        }
        
        // Set tanggal mulai = awal tahun
        $this->tanggalMulai = $this->tahun . '-01-01';
        
        // Query: Cari tanggal penerimaan terakhir
        $latestPenerimaanQuery = Penerimaan::query()
            ->where('tahun', $this->tahun);
        
        // Apply SKPD filter
        $user = auth()->user();
        if ($user->skpd_id && !$user->canViewAllSkpd()) {
            $latestPenerimaanQuery->where('skpd_id', $user->skpd_id);
        } elseif ($this->selectedSkpdId && $user->canViewAllSkpd()) {
            $latestPenerimaanQuery->where('skpd_id', $this->selectedSkpdId);
        }
        
        $latestPenerimaan = $latestPenerimaanQuery->orderBy('tanggal', 'desc')->first();
        
        // Set tanggal selesai berdasarkan data terakhir
        if ($latestPenerimaan && $latestPenerimaan->tanggal) {
            // Ada data → gunakan tanggal penerimaan terakhir
            // ✅ PERBAIKAN: Convert Carbon object ke string format Y-m-d
            $tanggalTerakhir = $latestPenerimaan->tanggal;
            
            if ($tanggalTerakhir instanceof \Carbon\Carbon) {
                // Jika sudah Carbon object
                $this->tanggalSelesai = $tanggalTerakhir->format('Y-m-d');
                $this->latestPenerimaanDate = $tanggalTerakhir->format('Y-m-d');
            } else {
                // Jika masih string, parse dulu
                $this->tanggalSelesai = Carbon::parse($tanggalTerakhir)->format('Y-m-d');
                $this->latestPenerimaanDate = Carbon::parse($tanggalTerakhir)->format('Y-m-d');
            }
            
        } else {
            // Belum ada data → gunakan tanggal hari ini atau akhir tahun
            $today = Carbon::now()->format('Y-m-d');
            $endOfYear = $this->tahun . '-12-31';
            $this->tanggalSelesai = (Carbon::now()->year == $this->tahun) ? $today : $endOfYear;
            $this->latestPenerimaanDate = null;
        }
        
        // Set display dates untuk info box
        $this->displayTanggalMulai = $this->tanggalMulai;
        $this->displayTanggalSelesai = $this->tanggalSelesai;
    }

    // ==========================================
    // EVENT HANDLERS - UPDATERS
    // ==========================================
    
    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
    public function updatedTahun()
    {
        $this->resetPage();
        
        // Auto-set tanggal ke data terakhir
        $this->setTanggalToLatestData();
    }
    
    public function updatedKodeRekeningId()
    {
        $this->resetPage();
    }
    
    public function updatedTanggalMulai()
    {
        // Update display date
        $this->displayTanggalMulai = $this->tanggalMulai;
        $this->resetPage();
    }
    
    public function updatedTanggalSelesai()
    {
        // Update display date
        $this->displayTanggalSelesai = $this->tanggalSelesai;
        $this->resetPage();
    }
    
    /**
     * Handler ketika user memilih SKPD dari dropdown
     * CRITICAL METHOD untuk dynamic filter
     */
    public function updatedSelectedSkpdId()
    {
        $this->resetPage();
        
        $user = auth()->user();
        
        // Hanya Super Admin dan Kepala Badan yang bisa ganti SKPD
        if (!$user->canViewAllSkpd()) {
            return;
        }
        
        // Reset kode rekening filter
        $this->kodeRekeningId = null;
        
        if ($this->selectedSkpdId) {
            // Ada SKPD yang dipilih - load kode rekening SKPD tersebut
            $selectedSkpd = Skpd::find($this->selectedSkpdId);
            
            if ($selectedSkpd) {
                $skpdAccess = $selectedSkpd->kode_rekening_access ?? [];
                
                if (!empty($skpdAccess)) {
                    // Load kode rekening level 6 yang di-assign
                    $this->kodeRekeningLevel6 = KodeRekening::whereIn('id', $skpdAccess)
                                                            ->where('level', 6)
                                                            ->where('is_active', true)
                                                            ->orderBy('kode')
                                                            ->get();
                } else {
                    // SKPD belum ada assignment
                    $this->kodeRekeningLevel6 = collect([]);
                    session()->flash('info', 'SKPD ' . $selectedSkpd->nama_opd . ' belum memiliki kode rekening yang di-assign.');
                }
            }
        } else {
            // "Semua SKPD" dipilih - load semua kode rekening
            $this->kodeRekeningLevel6 = KodeRekening::where('level', 6)
                                                    ->where('is_active', true)
                                                    ->orderBy('kode')
                                                    ->get();
        }
        
        // Update latest date based on new SKPD filter
        if ($this->tahun) {
            $latestPenerimaanQuery = Penerimaan::query()
                ->where('tahun', $this->tahun);
            
            if ($this->selectedSkpdId) {
                $latestPenerimaanQuery->where('skpd_id', $this->selectedSkpdId);
            }
            
            $latestPenerimaan = $latestPenerimaanQuery->orderBy('tanggal', 'desc')->first();
            
            if ($latestPenerimaan && $latestPenerimaan->tanggal) {
                // ✅ PERBAIKAN: Convert ke string
                $tanggalTerakhir = $latestPenerimaan->tanggal;
                
                if ($tanggalTerakhir instanceof \Carbon\Carbon) {
                    $this->latestPenerimaanDate = $tanggalTerakhir->format('Y-m-d');
                } else {
                    $this->latestPenerimaanDate = Carbon::parse($tanggalTerakhir)->format('Y-m-d');
                }
            } else {
                $this->latestPenerimaanDate = null;
            }
        }
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    // ==========================================
    // TOGGLE METHODS
    // ==========================================
    
    public function toggleLevel($level)
    {
        $property = "showLevel$level";
        $this->$property = !$this->$property;
        $this->resetPage();
    }
    
    public function toggleHideEmpty()
    {
        $this->hideEmpty = !$this->hideEmpty;
        $this->resetPage();
    }
    
    // ==========================================
    // RESET METHODS
    // ==========================================
    
    public function resetFilter()
    {
        $this->search = '';
        $this->kodeRekeningId = null;
        
        $user = auth()->user();
        
        // Reset SKPD filter hanya untuk admin
        if ($user->canViewAllSkpd()) {
            $this->selectedSkpdId = '';
            
            // Load semua kode rekening
            $this->kodeRekeningLevel6 = KodeRekening::where('level', 6)
                                                    ->where('is_active', true)
                                                    ->orderBy('kode')
                                                    ->get();
        }
        
        // Reset tahun ke aktif
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        
        // Auto-set tanggal ke data terakhir
        $this->setTanggalToLatestData();
        
        // Reset level visibility
        $this->showLevel1 = true;
        $this->showLevel2 = true;
        $this->showLevel3 = true;
        $this->showLevel4 = true;
        $this->showLevel5 = true;
        $this->showLevel6 = true;
        
        $this->hideEmpty = true;
        
        $this->resetPage();
        
        session()->flash('message', 'Filter berhasil direset.');
    }
    
    public function resetTanggalToFullYear()
    {
        if ($this->tahun) {
            $this->tanggalMulai = $this->tahun . '-01-01';
            $this->tanggalSelesai = $this->tahun . '-12-31';
            
            // Update display dates
            $this->displayTanggalMulai = $this->tanggalMulai;
            $this->displayTanggalSelesai = $this->tanggalSelesai;
            
            // Reset latest date info karena sekarang full year
            $this->latestPenerimaanDate = null;
            
            $this->resetPage();
            
            session()->flash('message', 'Filter tanggal direset ke tahun penuh: ' . $this->tahun);
        }
    }

    // ==========================================
    // EXPORT METHOD
    // ==========================================
    
    /**
     * Export Laporan Realisasi Anggaran
     */
    public function exportLaporanRealisasi()
    {
        try {
            $tahun = $this->tahun;
            $tanggalAwal = $this->tanggalMulai;
            $tanggalAkhir = $this->tanggalSelesai;
            
            if (!$tahun) {
                session()->flash('error', 'Silakan pilih tahun terlebih dahulu untuk export LRA');
                return;
            }
            
            $tanggalAwal = $tanggalAwal ?: "{$tahun}-01-01";
            $tanggalAkhir = $tanggalAkhir ?: "{$tahun}-12-31";
            
            $filename = 'LRA_' . $tahun;
            
            $skpdId = null;
            $user = auth()->user();
            
            if ($user->skpd_id && !$user->canViewAllSkpd()) {
                $skpdId = $user->skpd_id;
                if ($user->skpd && $user->skpd->kode_opd) {
                    $filename .= '_' . str_replace([' ', '/'], '_', $user->skpd->kode_opd);
                }
            } elseif ($this->selectedSkpdId) {
                $skpdId = $this->selectedSkpdId;
                $skpd = Skpd::find($this->selectedSkpdId);
                if ($skpd && $skpd->kode_opd) {
                    $filename .= '_' . str_replace([' ', '/'], '_', $skpd->kode_opd);
                }
            } else {
                $filename .= '_KONSOLIDASI';
            }
            
            $filename .= '_' . date('YmdHis') . '.xlsx';
            
            Log::info('Export LRA', [
                'user' => $user->name,
                'tahun' => $tahun,
                'tanggal_awal' => $tanggalAwal,
                'tanggal_akhir' => $tanggalAkhir,
                'skpd_id' => $skpdId,
                'filename' => $filename
            ]);
            
            return Excel::download(
                new LaporanRealisasiExport($tahun, $tanggalAwal, $tanggalAkhir, $skpdId),
                $filename
            );
            
        } catch (\Exception $e) {
            Log::error('Export LRA Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Gagal export laporan: ' . $e->getMessage());
            return redirect()->back();
        }
    }

  // ==========================================
// DELETE METHOD
// ==========================================

public function delete($id)
{
    // ... existing code ...
}

// ========================================
// TAMBAHAN BARU: DELETE ALL METHOD
// ========================================

/**
 * Hapus semua data penerimaan
 * HANYA SUPER ADMIN yang bisa akses
 */
public function deleteAllPenerimaan()
{
    // Authorization check - SUPER ADMIN ONLY
    if (!auth()->user()->isSuperAdmin()) {
        session()->flash('error', 'Unauthorized. Hanya Super Admin yang dapat menghapus semua data.');
        return;
    }
    
    try {
        // Count total records
        $totalRecords = Penerimaan::count();
        
        if ($totalRecords == 0) {
            session()->flash('info', 'Tidak ada data penerimaan untuk dihapus.');
            return;
        }
        
        // Log activity
        Log::critical('DELETE ALL PENERIMAAN', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'user_email' => auth()->user()->email,
            'records_deleted' => $totalRecords,
            'ip_address' => request()->ip(),
            'timestamp' => now()
        ]);
        
        // Hard delete ALL records from database
        DB::beginTransaction();
        
        try {
            Penerimaan::query()->delete();
            DB::commit();
            
            Log::info("Successfully deleted {$totalRecords} penerimaan records");
            
            session()->flash('success', "Berhasil menghapus {$totalRecords} data penerimaan secara permanen dari database.");
            
            // Redirect to refresh page
            return redirect()->route('penerimaan.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
    } catch (\Exception $e) {
        Log::error('Error deleting all penerimaan', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        session()->flash('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
    }
}
    
    // ==========================================
    // DETAIL MODAL METHODS
    // ==========================================
    
    public function showDetail($kodeRekeningId)
    {
        $this->selectedKodeRekening = KodeRekening::find($kodeRekeningId);
        
        if ($this->selectedKodeRekening) {
            $this->loadDetailPenerimaan($kodeRekeningId);
            $this->showDetailModal = true;
        }
    }
    
    private function loadDetailPenerimaan($kodeRekeningId)
    {
        $user = auth()->user();
        
        $query = Penerimaan::where('kode_rekening_id', $kodeRekeningId);
        
        // Apply SKPD filter
        if ($user->skpd_id && !$user->canViewAllSkpd()) {
            $query->where('skpd_id', $user->skpd_id);
        } elseif ($this->selectedSkpdId) {
            $query->where('skpd_id', $this->selectedSkpdId);
        }
        
        // Apply date filter
        if ($this->tahun) {
            $query->where('tahun', $this->tahun);
        }
        
        if ($this->tanggalMulai && $this->tanggalSelesai) {
            $query->whereDate('tanggal', '>=', $this->tanggalMulai)
                  ->whereDate('tanggal', '<=', $this->tanggalSelesai);
        }
        
        $this->detailPenerimaan = $query->orderBy('tanggal', 'desc')->get();
    }
    
    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedKodeRekening = null;
        $this->detailPenerimaan = [];
    }

    // ==========================================
    // IMPORT METHODS
    // ==========================================
    
    public function openImportModal()
    {
        $this->showImportModal = true;
        $this->importTahun = $this->tahun;
        $this->reset('importFile');
    }
    
    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->reset(['importFile']);
    }
    
    public function downloadTemplate()
    {
        return response()->download(
            public_path('templates/template_penerimaan.xlsx'), 
            'template_penerimaan.xlsx'
        );
    }
    
    public function import()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls|max:10240',
            'importTahun' => 'required|integer|min:2020|max:2030'
        ], [
            'importFile.required' => 'File Excel harus dipilih',
            'importFile.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'importFile.max' => 'Ukuran file maksimal 10MB',
            'importTahun.required' => 'Tahun harus dipilih'
        ]);
        
        try {
            $filename = 'import_penerimaan_' . now()->format('YmdHis') . '.' . $this->importFile->extension();
            $path = $this->importFile->storeAs('temp', $filename);
            
            $import = new PenerimaanImport($this->importTahun, auth()->user()->skpd_id, auth()->id());
            Excel::import($import, $path);
            
            $processedCount = $import->getProcessedCount();
            $skippedCount = $import->getSkippedCount();
            
            Storage::delete($path);
            
            if ($processedCount > 0) {
                session()->flash('message', "Import berhasil! {$processedCount} data berhasil diimport, {$skippedCount} data dilewati.");
                $this->closeImportModal();
                $this->dispatch('$refresh');
            } else {
                session()->flash('error', 'Tidak ada data yang berhasil diimport. Periksa format file.');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
  
    // ==========================================
    // RENDER METHOD - MAIN LOGIC
    // ==========================================
    
    public function render()
    {
        $user = auth()->user();
        
        // ========================================
        // STEP 1: Get Visible Levels
        // ========================================
        $visibleLevels = [];
        if ($this->showLevel1) $visibleLevels[] = 1;
        if ($this->showLevel2) $visibleLevels[] = 2;
        if ($this->showLevel3) $visibleLevels[] = 3;
        if ($this->showLevel4) $visibleLevels[] = 4;
        if ($this->showLevel5) $visibleLevels[] = 5;
        if ($this->showLevel6) $visibleLevels[] = 6;
        
        // ========================================
        // STEP 2: Get Allowed Kode Rekening IDs
        // ========================================
        $allowedKodeIds = [];
        $level6AllowedIds = []; // Untuk filter penerimaan
        
        if ($user->canViewAllSkpd()) {
            // Super Admin / Kepala Badan
            if ($this->selectedSkpdId) {
                // Ada SKPD yang dipilih - filter by SKPD
                $selectedSkpd = Skpd::find($this->selectedSkpdId);
                if ($selectedSkpd) {
                    $allowedKodeIds = $selectedSkpd->getHierarchicalKodeRekeningIds();
                    $level6AllowedIds = $selectedSkpd->getLevel6KodeRekeningIds();
                }
            }
            // Jika tidak ada SKPD dipilih, allowedKodeIds tetap empty = no restriction
            
        } else {
            // Operator SKPD - hanya kode yang di-assign
            if ($user->skpd_id && $user->skpd) {
                $allowedKodeIds = $user->skpd->getHierarchicalKodeRekeningIds();
                $level6AllowedIds = $user->skpd->getLevel6KodeRekeningIds();
            }
        }
        
        // ========================================
        // STEP 3: Build Kode Rekening Query
        // ========================================
        $query = KodeRekening::where('is_active', true);
        
        // Apply allowed kode filter
        if (!empty($allowedKodeIds)) {
            $query->whereIn('id', $allowedKodeIds);
        }
        
        // Apply visible levels
        if (!empty($visibleLevels)) {
            $query->whereIn('level', $visibleLevels);
        }
        
        // Apply search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }
        
        // Apply kode rekening filter
        if ($this->kodeRekeningId) {
            $selectedKode = KodeRekening::find($this->kodeRekeningId);
            if ($selectedKode) {
                $query->where('kode', 'like', $selectedKode->kode . '%');
            }
        }
        
        // Get all kode rekening
        $allKodeRekening = $query->orderBy('kode', 'asc')->get();

        // ========================================
        // STEP 4: Calculate Totals for Each Kode
        // ========================================
        foreach ($allKodeRekening as $kode) {
            $penerimaanQuery = Penerimaan::query();
            
            if ($kode->level == 6) {
                // Level 6 - direct match
                $penerimaanQuery->where('kode_rekening_id', $kode->id);
            } else {
                // Level 1-5 - sum dari children Level 6
                $pattern = $kode->kode . '%';
                $penerimaanQuery->whereHas('kodeRekening', function($q) use ($pattern) {
                    $q->where('kode', 'like', $pattern)
                      ->where('level', 6);
                });
            }
            
            // Apply SKPD filter to penerimaan
            if ($user->skpd_id && !$user->canViewAllSkpd()) {
                $penerimaanQuery->where('skpd_id', $user->skpd_id);
            } elseif ($this->selectedSkpdId && $user->canViewAllSkpd()) {
                $penerimaanQuery->where('skpd_id', $this->selectedSkpdId);
            }
            
            // Apply date filters
            if ($this->tahun) {
                $penerimaanQuery->where('tahun', $this->tahun);
            }
            
            if ($this->tanggalMulai && $this->tanggalSelesai) {
                $penerimaanQuery->whereDate('tanggal', '>=', $this->tanggalMulai)
                               ->whereDate('tanggal', '<=', $this->tanggalSelesai);
            }
            
            // Calculate totals
            $kode->total_penerimaan = $penerimaanQuery->sum('jumlah');
            $kode->jumlah_transaksi = $penerimaanQuery->count();
            $kode->tanggal_terakhir = $penerimaanQuery->max('tanggal');
        }
        
        // ========================================
        // STEP 5: Filter Empty if Needed
        // ========================================
        if ($this->hideEmpty) {
            $allKodeRekening = $allKodeRekening->filter(function($kode) {
                return $kode->total_penerimaan != 0;
            });
        }

        // ========================================
        // STEP 6: Pagination
        // ========================================
        $currentPage = $this->getPage() ?? 1;
        $perPage = $this->perPage;
        
        $penerimaan = new LengthAwarePaginator(
            $allKodeRekening->forPage($currentPage, $perPage),
            $allKodeRekening->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
        
        $penerimaan->withPath('');
        
        // ========================================
        // STEP 7: Calculate Grand Total
        // ========================================
        $grandTotalQuery = DB::table('penerimaan')
            ->when($this->tahun, function($q) {
                $q->where('tahun', $this->tahun);
            })
            ->when($this->tanggalMulai && $this->tanggalSelesai, function($q) {
                $q->whereDate('tanggal', '>=', $this->tanggalMulai)
                  ->whereDate('tanggal', '<=', $this->tanggalSelesai);
            });
            
        // Apply SKPD filter to grand total
        if ($user->skpd_id && !$user->canViewAllSkpd()) {
            $grandTotalQuery->where('skpd_id', $user->skpd_id);
        } elseif ($this->selectedSkpdId && $user->canViewAllSkpd()) {
            $grandTotalQuery->where('skpd_id', $this->selectedSkpdId);
        }
        
        // Filter by Level 6 allowed IDs (untuk operator SKPD)
        if (!empty($level6AllowedIds)) {
            $grandTotalQuery->whereIn('kode_rekening_id', $level6AllowedIds);
        }
        
        $grandTotal = $grandTotalQuery->sum('jumlah');
        
        // ========================================
        // STEP 8: Return View
        // ========================================
        return view('livewire.penerimaan.index', [
            'penerimaan' => $penerimaan,
            'grandTotal' => $grandTotal,
            'perPageOptions' => $this->perPageOptions,
            'userSkpdInfo' => $this->userSkpdInfo,
            'hideEmpty' => $this->hideEmpty,
            'skpdList' => $this->skpdList,
            'availableYears' => $this->availableYears,
            'kodeRekeningLevel6' => $this->kodeRekeningLevel6
        ]);
    }
}