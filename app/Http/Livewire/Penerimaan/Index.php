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
use App\Exports\LaporanRealisasiExport;  // TAMBAHAN IMPORT
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

    public function mount()
    {
        $this->hideEmpty = true;
        
        $user = auth()->user();
        
        if ($user->hasRole(['Super Admin', 'Administrator'])) {
            $this->skpdList = Skpd::where('status', 'aktif')
                                  ->orderBy('nama_opd')
                                  ->get();
            $this->userSkpdInfo = 'Super Admin - Semua SKPD';
            
            $this->kodeRekeningLevel6 = KodeRekening::where('level', 6)
                                                    ->where('is_active', true)
                                                    ->orderBy('kode')
                                                    ->get();
                                                    
        } elseif ($user->hasRole('Kepala Badan')) {
            $this->skpdList = Skpd::where('status', 'aktif')
                                  ->orderBy('nama_opd')
                                  ->get();
            $this->userSkpdInfo = 'Kepala BPKPAD - Semua SKPD';
            
            $this->kodeRekeningLevel6 = KodeRekening::where('level', 6)
                                                    ->where('is_active', true)
                                                    ->orderBy('kode')
                                                    ->get();
                                                    
        } elseif ($user->skpd) {
            $this->selectedSkpdId = $user->skpd_id;
            $this->userSkpdInfo = 'SKPD: ' . $user->skpd->nama_opd;
            
            $skpdAccess = $user->skpd->kode_rekening_access ?? [];
            
            if (!empty($skpdAccess)) {
                $this->kodeRekeningLevel6 = KodeRekening::whereIn('id', $skpdAccess)
                                                        ->where('level', 6)
                                                        ->where('is_active', true)
                                                        ->orderBy('kode')
                                                        ->get();
            } else {
                $this->kodeRekeningLevel6 = collect([]);
                session()->flash('warning', 'SKPD Anda belum memiliki kode rekening yang di-assign.');
            }
        } else {
            $this->kodeRekeningLevel6 = collect([]);
            session()->flash('error', 'Anda tidak terdaftar di SKPD manapun.');
        }
        
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        $this->importTahun = $this->tahun;
        
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
        
        $this->setTanggalByTahun();
    }
    
    private function setTanggalByTahun()
    {
        if ($this->tahun) {
            $this->tanggalMulai = $this->tahun . '-01-01';
            $this->tanggalSelesai = $this->tahun . '-12-31';
        } else {
            $this->tanggalMulai = null;
            $this->tanggalSelesai = null;
        }
    }

   /**
     * Export Laporan Realisasi Anggaran
     */
    public function exportLaporanRealisasi()
    {
        try {
            // Validasi dan ambil parameter
            $tahun = $this->tahun;
            $tanggalAwal = $this->tanggalMulai;
            $tanggalAkhir = $this->tanggalSelesai;
            
            // Validasi tahun harus ada
            if (!$tahun) {
                session()->flash('error', 'Silakan pilih tahun terlebih dahulu untuk export LRA');
                return;
            }
            
            // Set default tanggal jika kosong
            $tanggalAwal = $tanggalAwal ?: "{$tahun}-01-01";
            $tanggalAkhir = $tanggalAkhir ?: "{$tahun}-12-31";
            
            // Generate filename
            $filename = 'LRA_' . $tahun;
            
            // Determine SKPD for filter and filename
            $skpdId = null;
            $user = auth()->user();
            
            // Check user role and SKPD
            if ($user->skpd_id && !$user->hasRole(['Super Admin', 'Administrator'])) {
                // Operator SKPD - use their SKPD
                $skpdId = $user->skpd_id;
                if ($user->skpd && $user->skpd->kode_opd) {
                    $filename .= '_' . str_replace([' ', '/'], '_', $user->skpd->kode_opd);
                }
            } elseif ($this->selectedSkpdId) {
                // Admin with selected SKPD filter
                $skpdId = $this->selectedSkpdId;
                $skpd = Skpd::find($this->selectedSkpdId);
                if ($skpd && $skpd->kode_opd) {
                    $filename .= '_' . str_replace([' ', '/'], '_', $skpd->kode_opd);
                }
            } else {
                // All SKPD
                $filename .= '_KONSOLIDASI';
            }
            
            // Add timestamp
            $filename .= '_' . date('YmdHis') . '.xlsx';
            
            // Log export action
            Log::info('Export LRA', [
                'user' => $user->name,
                'tahun' => $tahun,
                'tanggal_awal' => $tanggalAwal,
                'tanggal_akhir' => $tanggalAkhir,
                'skpd_id' => $skpdId,
                'filename' => $filename
            ]);
            
            // Download file
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
    
    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
    public function updatedTahun()
    {
        $this->resetPage();
        $this->setTanggalByTahun();
    }
    
    public function updatedKodeRekeningId()
    {
        $this->resetPage();
    }
    
    public function updatedTanggalMulai()
    {
        $this->resetPage();
    }
    
    public function updatedTanggalSelesai()
    {
        $this->resetPage();
    }
    
    public function updatedSelectedSkpdId()
    {
        $this->resetPage();
        
        $user = auth()->user();
        
        if ($user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
            if ($this->selectedSkpdId) {
                $selectedSkpd = Skpd::find($this->selectedSkpdId);
                
                if ($selectedSkpd) {
                    $skpdAccess = $selectedSkpd->kode_rekening_access ?? [];
                    
                    if (!empty($skpdAccess)) {
                        $this->kodeRekeningLevel6 = KodeRekening::whereIn('id', $skpdAccess)
                                                                ->where('level', 6)
                                                                ->where('is_active', true)
                                                                ->orderBy('kode')
                                                                ->get();
                    } else {
                        $this->kodeRekeningLevel6 = collect([]);
                        session()->flash('info', 'SKPD ' . $selectedSkpd->nama_opd . ' belum memiliki kode rekening yang di-assign.');
                    }
                }
            } else {
                $this->kodeRekeningLevel6 = KodeRekening::where('level', 6)
                                                        ->where('is_active', true)
                                                        ->orderBy('kode')
                                                        ->get();
            }
            
            $this->kodeRekeningId = null;
        }
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function resetFilter()
    {
        $this->search = '';
        $this->kodeRekeningId = null;
        
        $user = auth()->user();
        if ($user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
            $this->selectedSkpdId = '';
        }
        
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        $this->setTanggalByTahun();
        
        $this->showLevel1 = true;
        $this->showLevel2 = true;
        $this->showLevel3 = true;
        $this->showLevel4 = true;
        $this->showLevel5 = true;
        $this->showLevel6 = true;
        
        $this->hideEmpty = true;
        
        $this->resetPage();
    }
    
    public function resetTanggalToFullYear()
    {
        if ($this->tahun) {
            $this->tanggalMulai = $this->tahun . '-01-01';
            $this->tanggalSelesai = $this->tahun . '-12-31';
            $this->resetPage();
            
            session()->flash('message', 'Filter tanggal direset ke tahun penuh: ' . $this->tahun);
        }
    }

public function delete($id)
    {
        $penerimaan = Penerimaan::find($id);
        if ($penerimaan) {
            $user = auth()->user();
            if (!$user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan']) && 
                $penerimaan->skpd_id != $user->skpd_id) {
                session()->flash('error', 'Anda tidak memiliki akses untuk menghapus data ini.');
                return;
            }
            
            $penerimaan->delete();
            session()->flash('message', 'Data penerimaan berhasil dihapus.');
            $this->dispatch('penerimaanDeleted');
            
            if ($this->showDetailModal && $this->selectedKodeRekening) {
                $this->loadDetailPenerimaan($this->selectedKodeRekening->id);
            }
        } else {
            session()->flash('error', 'Data penerimaan tidak ditemukan.');
        }
    }
    
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
        
        if ($user->skpd_id && !$user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
            $query->where('skpd_id', $user->skpd_id);
        } elseif ($this->selectedSkpdId) {
            $query->where('skpd_id', $this->selectedSkpdId);
        }
        
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
  
    public function render()
    {
        $user = auth()->user();
        
        // Get visible levels
        $visibleLevels = [];
        if ($this->showLevel1) $visibleLevels[] = 1;
        if ($this->showLevel2) $visibleLevels[] = 2;
        if ($this->showLevel3) $visibleLevels[] = 3;
        if ($this->showLevel4) $visibleLevels[] = 4;
        if ($this->showLevel5) $visibleLevels[] = 5;
        if ($this->showLevel6) $visibleLevels[] = 6;
        
        // Get allowed kode rekening IDs for operator SKPD
        $allowedKodeIds = [];
        if ($user->skpd_id && !$user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
            $skpdAccess = $user->skpd->kode_rekening_access ?? [];
            
            if (!empty($skpdAccess)) {
                $allowedLevel6 = KodeRekening::whereIn('id', $skpdAccess)->get();
                
                foreach ($allowedLevel6 as $kode6) {
                    $allowedKodeIds[] = $kode6->id;
                    
                    $currentKode = $kode6;
                    while ($currentKode->parent_id) {
                        $allowedKodeIds[] = $currentKode->parent_id;
                        $currentKode = $currentKode->parent;
                    }
                }
                
                $allowedKodeIds = array_unique($allowedKodeIds);
            }
        }
        
        // Base query for kode rekening
        $query = KodeRekening::where('is_active', true);
        
        if (!empty($allowedKodeIds)) {
            $query->whereIn('id', $allowedKodeIds);
        }
        
        if (!empty($visibleLevels)) {
            $query->whereIn('level', $visibleLevels);
        }
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->kodeRekeningId) {
            $selectedKode = KodeRekening::find($this->kodeRekeningId);
            if ($selectedKode) {
                $query->where('kode', 'like', $selectedKode->kode . '%');
            }
        }
        
        $allKodeRekening = $query->orderBy('kode', 'asc')->get();

        // Calculate totals for each kode rekening
        foreach ($allKodeRekening as $kode) {
            $penerimaanQuery = Penerimaan::query();
            
            if ($kode->level == 6) {
                $penerimaanQuery->where('kode_rekening_id', $kode->id);
            } else {
                $pattern = $kode->kode . '%';
                $penerimaanQuery->whereHas('kodeRekening', function($q) use ($pattern) {
                    $q->where('kode', 'like', $pattern)
                      ->where('level', 6);
                });
            }
            
            // Apply SKPD filter
            if ($user->skpd_id && !$user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
                $penerimaanQuery->where('skpd_id', $user->skpd_id);
            } elseif ($this->selectedSkpdId && $user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
                $penerimaanQuery->where('skpd_id', $this->selectedSkpdId);
            }
            
            // Apply filters
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
        
        // Filter out empty if hideEmpty is true
        if ($this->hideEmpty) {
            $allKodeRekening = $allKodeRekening->filter(function($kode) {
                return $kode->total_penerimaan != 0;
            });
        }

        // Pagination
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
        
        // Calculate grand total
        $grandTotalQuery = DB::table('penerimaan')
            ->when($this->tahun, function($q) {
                $q->where('tahun', $this->tahun);
            })
            ->when($this->tanggalMulai && $this->tanggalSelesai, function($q) {
                $q->whereDate('tanggal', '>=', $this->tanggalMulai)
                  ->whereDate('tanggal', '<=', $this->tanggalSelesai);
            });
            
        // Apply SKPD filter to grand total
        if ($user->skpd_id && !$user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
            $grandTotalQuery->where('skpd_id', $user->skpd_id);
        } elseif ($this->selectedSkpdId && $user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
            $grandTotalQuery->where('skpd_id', $this->selectedSkpdId);
        }
        
        // Filter by allowed kode rekening for operator SKPD
        if (!empty($allowedKodeIds)) {
            $level6AllowedIds = KodeRekening::whereIn('id', $allowedKodeIds)
                                           ->where('level', 6)
                                           ->pluck('id')
                                           ->toArray();
            if (!empty($level6AllowedIds)) {
                $grandTotalQuery->whereIn('kode_rekening_id', $level6AllowedIds);
            }
        }
        
        $grandTotal = $grandTotalQuery->sum('jumlah');
        
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
