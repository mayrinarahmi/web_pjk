<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    public $tanggal;
    public $kode_rekening_id;
    public $jumlah;
    public $keterangan;
    public $tahun;
    
    public $kodeRekeningLevel6 = []; // PERUBAHAN: dari Level5 ke Level6
    public $availableYears = [];
    
    // TAMBAHAN: Info SKPD
    public $userSkpdInfo = '';
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'jumlah' => 'required|numeric', // PERUBAHAN: Hapus min:0 untuk support nilai minus
        'keterangan' => 'nullable|string|max:255',
        'tahun' => 'required|integer|min:2000|max:2100',
    ];
    
    public function mount()
    {
        // TAMBAHAN: Set user SKPD info
        $user = auth()->user();
        if ($user->skpd) {
            $this->userSkpdInfo = 'Input untuk: ' . $user->skpd->nama_opd;
        } elseif ($user->isSuperAdmin()) {
            $this->userSkpdInfo = 'Super Admin - Input untuk BPKPAD';
        }
        
        $this->tanggal = date('Y-m-d');
        
        // Ambil tahun dari tahun anggaran aktif
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        
        // Ambil semua tahun yang tersedia
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
        
        if (empty($this->availableYears)) {
            session()->flash('error', 'Tidak ada tahun anggaran yang tersedia. Silakan buat tahun anggaran terlebih dahulu.');
            return redirect()->route('tahun-anggaran.index');
        }
        
        // PERUBAHAN: Get kode rekening level 6 instead of level 5
        // Filter based on user's SKPD access if needed
        $query = KodeRekening::where('level', 6) // PERUBAHAN: dari 5 ke 6
                             ->where('is_active', true);
        
        // TAMBAHAN: Filter kode rekening based on SKPD access (if configured)
        if ($user->skpd && $user->skpd->kode_rekening_access) {
            $allowedKodes = $user->skpd->kode_rekening_access;
            if (!empty($allowedKodes)) {
                $query->whereIn('id', $allowedKodes);
            }
        }
        
        $this->kodeRekeningLevel6 = $query->orderBy('kode')->get();
        
        Log::info('Penerimaan Create mounted', [
            'user' => $user->name,
            'skpd' => $user->skpd ? $user->skpd->nama_opd : 'No SKPD',
            'available_kode_rekening' => $this->kodeRekeningLevel6->count()
        ]);
    }
    
    public function save()
    {
        $this->validate();
        
        // Validasi tambahan: pastikan kode rekening adalah level 6
        $kodeRekening = KodeRekening::find($this->kode_rekening_id);
        if ($kodeRekening->level != 6) { // PERUBAHAN: dari 5 ke 6
            session()->flash('error', 'Penerimaan hanya dapat diinput untuk kode rekening level 6.');
            return;
        }
        
        // Validasi tahun tersedia di tahun anggaran
        $tahunExists = TahunAnggaran::where('tahun', $this->tahun)->exists();
        if (!$tahunExists) {
            session()->flash('error', 'Tahun ' . $this->tahun . ' tidak tersedia di tahun anggaran.');
            return;
        }
        
        // Extract tahun dari tanggal untuk validasi konsistensi
        $tahunDariTanggal = Carbon::parse($this->tanggal)->year;
        if ($tahunDariTanggal != $this->tahun) {
            session()->flash('error', 'Tahun pada tanggal (' . $tahunDariTanggal . ') harus sama dengan tahun anggaran (' . $this->tahun . ').');
            return;
        }
        
        // TAMBAHAN: Auto-inject SKPD ID and Created By
        $user = auth()->user();
        $skpdId = null;
        
        if ($user->skpd_id) {
            $skpdId = $user->skpd_id;
        } elseif ($user->isSuperAdmin()) {
            // Super Admin input untuk BPKPAD
            $bpkpad = \App\Models\Skpd::where('kode_opd', '5.02.0.00.0.00.05.0000')->first();
            $skpdId = $bpkpad ? $bpkpad->id : null;
        }
        
        Penerimaan::create([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun' => $this->tahun,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
            'skpd_id' => $skpdId,          // TAMBAHAN
            'created_by' => $user->id,     // TAMBAHAN
        ]);
        
        Log::info('Penerimaan created', [
            'user' => $user->name,
            'skpd_id' => $skpdId,
            'kode_rekening' => $kodeRekening->kode,
            'jumlah' => $this->jumlah
        ]);
        
        session()->flash('message', 'Data penerimaan berhasil disimpan.');
        return redirect()->route('penerimaan.index');
    }
    
    // Method untuk auto-update tahun berdasarkan tanggal
    public function updatedTanggal()
    {
        if ($this->tanggal) {
            $tahunDariTanggal = Carbon::parse($this->tanggal)->year;
            
            // Cek apakah tahun tersedia di availableYears
            if (in_array($tahunDariTanggal, $this->availableYears)) {
                $this->tahun = $tahunDariTanggal;
            }
        }
    }
    
    public function render()
    {
        return view('livewire.penerimaan.create');
    }
}