<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Livewire\WithPagination;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;
    
    public $search = '';
    public $tanggalMulai;
    public $tanggalSelesai;
    public $kodeRekeningId;
    public $tahunAnggaranId;
    
    public $tahunAnggaran = [];
    public $kodeRekeningLevel4 = [];
    
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['penerimaanDeleted' => '$refresh'];
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        $this->kodeRekeningLevel4 = KodeRekening::where('level', 4)->orderBy('kode')->get();
        
        // Default tanggal filter (bulan ini)
        $this->tanggalMulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggalSelesai = Carbon::now()->format('Y-m-d');
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function resetFilter()
    {
        $this->search = '';
        $this->tanggalMulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggalSelesai = Carbon::now()->format('Y-m-d');
        $this->kodeRekeningId = null;
        $this->resetPage();
    }
    
    public function delete($id)
    {
        $penerimaan = Penerimaan::find($id);
        $penerimaan->delete();
        
        session()->flash('message', 'Data penerimaan berhasil dihapus.');
        $this->emit('penerimaanDeleted');
    }
    
    public function render()
    {
        $query = Penerimaan::query();
        
        if ($this->tahunAnggaranId) {
            $query->where('tahun_anggaran_id', $this->tahunAnggaranId);
        }
        
        if ($this->search) {
            $query->whereHas('kodeRekening', function($q) {
                $q->where('kode', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%');
            })->orWhere('keterangan', 'like', '%' . $this->search . '%');
        }
        
        if ($this->tanggalMulai) {
            $query->whereDate('tanggal', '>=', $this->tanggalMulai);
        }
        
        if ($this->tanggalSelesai) {
            $query->whereDate('tanggal', '<=', $this->tanggalSelesai);
        }
        
        if ($this->kodeRekeningId) {
            $query->where('kode_rekening_id', $this->kodeRekeningId);
        }
        
        $penerimaan = $query->with('kodeRekening')
            ->orderBy('tanggal', 'desc')
            ->paginate(15);
        
        return view('livewire.penerimaan.index', [
            'penerimaan' => $penerimaan
        ]);
    }
}

