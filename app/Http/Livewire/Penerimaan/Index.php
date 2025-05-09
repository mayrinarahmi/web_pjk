<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination;
    
    public $search = '';
    public $tanggalMulai;
    public $tanggalSelesai;
    public $kodeRekeningId;
    public $tahunAnggaranId;
    
    public $tahunAnggaran = [];
    public $kodeRekeningLevel5 = [];
    
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['penerimaanDeleted' => '$refresh'];
    
    // Tambahkan queryString untuk mempertahankan state filter saat paginasi
    protected $queryString = [
        'search' => ['except' => ''],
        'tanggalMulai' => ['except' => ''],
        'tanggalSelesai' => ['except' => ''],
        'kodeRekeningId' => ['except' => ''],
        'tahunAnggaranId' => ['except' => ''],
    ];
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        $this->kodeRekeningLevel5 = KodeRekening::where('level', 5)->orderBy('kode')->get();
        
        // Default tanggal filter (bulan ini)
        $this->tanggalMulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggalSelesai = Carbon::now()->format('Y-m-d');
        
        // Log untuk debug
        Log::info('Penerimaan Index mounted', [
            'tahunAnggaranId' => $this->tahunAnggaranId,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai
        ]);
    }
    
    // Trigger saat perubahan filter
    public function updatedTahunAnggaranId()
    {
        $this->resetPage();
        Log::info('Tahun Anggaran updated', ['tahunAnggaranId' => $this->tahunAnggaranId]);
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
        $this->tanggalMulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggalSelesai = Carbon::now()->format('Y-m-d');
        $this->kodeRekeningId = null;
        $this->resetPage();
        
        Log::info('Filter reset');
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
    
    public function render()
    {
        // Log untuk debug render
        Log::info('Rendering with filters', [
            'tahunAnggaranId' => $this->tahunAnggaranId,
            'kodeRekeningId' => $this->kodeRekeningId,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
            'search' => $this->search
        ]);
        
        $query = Penerimaan::query();
        
        // Apply filters
        if ($this->tahunAnggaranId) {
            $query->where('tahun_anggaran_id', $this->tahunAnggaranId);
        }
        
        if ($this->kodeRekeningId) {
            $query->where('kode_rekening_id', $this->kodeRekeningId);
        }
        
        if ($this->tanggalMulai) {
            $query->whereDate('tanggal', '>=', $this->tanggalMulai);
        }
        
        if ($this->tanggalSelesai) {
            $query->whereDate('tanggal', '<=', $this->tanggalSelesai);
        }
        
        // Perbaikan query pencarian
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
        
        // Log hasil query
        Log::info('Query results', [
            'count' => $penerimaan->total(),
            'currentPage' => $penerimaan->currentPage()
        ]);
        
        return view('livewire.penerimaan.index', [
            'penerimaan' => $penerimaan
        ]);
    }
}