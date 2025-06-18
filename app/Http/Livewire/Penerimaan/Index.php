<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Imports\PenerimaanImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    public $search = '';
    public $tanggalMulai;
    public $tanggalSelesai;
    public $kodeRekeningId;
    public $tahun;
    
    public $availableYears = [];
    public $kodeRekeningLevel5 = [];
    
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
    ];
    
    public function mount()
    {
        // Ambil tahun dari tahun anggaran yang aktif
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        $this->importTahun = $this->tahun; // Set default import tahun
        
        // Ambil semua tahun yang tersedia dari tahun anggaran
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
        
        $this->kodeRekeningLevel5 = KodeRekening::where('level', 5)->orderBy('kode')->get();
        
        // PERBAIKAN: Set tanggal berdasarkan tahun yang dipilih
        $this->setTanggalByTahun();
        
        Log::info('Penerimaan Index mounted', [
            'tahun' => $this->tahun,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai
        ]);
    }
    
    /**
     * PERBAIKAN: Method untuk set tanggal berdasarkan tahun
     */
    private function setTanggalByTahun()
    {
        if ($this->tahun) {
            $this->tanggalMulai = $this->tahun . '-01-01';
            $this->tanggalSelesai = $this->tahun . '-12-31';
        } else {
            // Jika tidak ada tahun, kosongkan filter tanggal
            $this->tanggalMulai = null;
            $this->tanggalSelesai = null;
        }
    }
    
    // PERBAIKAN: Trigger saat perubahan tahun - update tanggal otomatis
    public function updatedTahun()
    {
        $this->resetPage();
        $this->setTanggalByTahun(); // Auto-adjust tanggal berdasarkan tahun
        
        Log::info('Tahun updated', [
            'tahun' => $this->tahun,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai
        ]);
    }
    
    public function updatedKodeRekeningId()
    {
        $this->resetPage();
        Log::info('Kode Rekening updated', ['kodeRekeningId' => $this->kodeRekeningId]);
    }
    
    public function updatedTanggalMulai()
    {
        $this->resetPage();
        Log::info('Tanggal Mulai updated', ['tanggalMulai' => $this->tanggalMulai]);
    }
    
    public function updatedTanggalSelesai()
    {
        $this->resetPage();
        Log::info('Tanggal Selesai updated', ['tanggalSelesai' => $this->tanggalSelesai]);
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
        Log::info('Search updating', ['search' => $this->search]);
    }
    
    public function resetFilter()
    {
        $this->search = '';
        $this->kodeRekeningId = null;
        
        // PERBAIKAN: Reset ke tahun aktif dan set tanggal berdasarkan tahun
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        $this->setTanggalByTahun();
        
        $this->resetPage();
        
        Log::info('Filter reset', [
            'tahun' => $this->tahun,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai
        ]);
    }
    
    /**
     * PERBAIKAN: Method untuk reset tanggal ke full year
     */
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
            $penerimaan->delete();
            session()->flash('message', 'Data penerimaan berhasil dihapus.');
            $this->dispatch('penerimaanDeleted');
            Log::info('Penerimaan deleted', ['id' => $id]);
        } else {
            session()->flash('error', 'Data penerimaan tidak ditemukan.');
            Log::warning('Failed to delete penerimaan', ['id' => $id]);
        }
    }
    
    /**
     * Import Methods
     */
    public function openImportModal()
    {
        $this->showImportModal = true;
        $this->importTahun = $this->tahun; // Default ke tahun yang sedang aktif
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
            // Store file temporarily
            $filename = 'import_penerimaan_' . now()->format('YmdHis') . '.' . $this->importFile->extension();
            $path = $this->importFile->storeAs('temp', $filename);
            
            Log::info('Starting penerimaan import', [
                'filename' => $filename,
                'tahun' => $this->importTahun
            ]);
            
            // Process import
            $import = new PenerimaanImport($this->importTahun);
            Excel::import($import, $path);
            
            // Get counts
            $processedCount = $import->getProcessedCount();
            $skippedCount = $import->getSkippedCount();
            
            // Clean up temp file
            Storage::delete($path);
            
            Log::info('Penerimaan import completed', [
                'processed' => $processedCount,
                'skipped' => $skippedCount
            ]);
            
            if ($processedCount > 0) {
                session()->flash('message', "Import berhasil! {$processedCount} data berhasil diimport, {$skippedCount} data dilewati.");
                $this->closeImportModal();
                $this->dispatch('$refresh'); // Refresh data
            } else {
                session()->flash('error', 'Tidak ada data yang berhasil diimport. Periksa format file.');
            }
            
        } catch (\Exception $e) {
            Log::error('Penerimaan import failed', [
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        Log::info('Rendering with filters', [
            'tahun' => $this->tahun,
            'kodeRekeningId' => $this->kodeRekeningId,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
            'search' => $this->search
        ]);
        
        $query = Penerimaan::query();
        
        // PERBAIKAN: Filter tahun wajib jika ada
        if ($this->tahun) {
            $query->where('tahun', $this->tahun);
        }
        
        if ($this->kodeRekeningId) {
            $query->where('kode_rekening_id', $this->kodeRekeningId);
        }
        
        // PERBAIKAN: Filter tanggal hanya jika kedua field terisi
        if ($this->tanggalMulai && $this->tanggalSelesai) {
            $query->whereDate('tanggal', '>=', $this->tanggalMulai);
            $query->whereDate('tanggal', '<=', $this->tanggalSelesai);
        } elseif ($this->tanggalMulai) {
            // Jika hanya tanggal mulai yang terisi
            $query->whereDate('tanggal', '>=', $this->tanggalMulai);
        } elseif ($this->tanggalSelesai) {
            // Jika hanya tanggal selesai yang terisi
            $query->whereDate('tanggal', '<=', $this->tanggalSelesai);
        }
        
        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('kodeRekening', function($q) {
                    $q->where('kode', 'like', '%' . $this->search . '%')
                      ->orWhere('nama', 'like', '%' . $this->search . '%');
                })->orWhere('keterangan', 'like', '%' . $this->search . '%');
            });
        }
        
        $penerimaan = $query->with('kodeRekening')
            ->orderBy('tanggal', 'desc')
            ->paginate(15);
        
        Log::info('Query results', [
            'count' => $penerimaan->total(),
            'currentPage' => $penerimaan->currentPage(),
            'sql' => $query->toSql()
        ]);
        
        return view('livewire.penerimaan.index', [
            'penerimaan' => $penerimaan
        ]);
    }
}