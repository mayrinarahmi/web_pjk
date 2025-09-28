<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Edit extends Component
{
    public $penerimaanId;
    public $tanggal;
    public $kode_rekening_id;
    public $tahun;
    public $jumlah;
    public $keterangan;
    
    public $kodeRekeningLevel6 = []; // PERUBAHAN: dari Level5 ke Level6
    public $availableYears = [];
    
    // TAMBAHAN: Info SKPD
    public $userSkpdInfo = '';
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'tahun' => 'required|integer|min:2000|max:2100',
        'jumlah' => 'required|numeric', // PERUBAHAN: Hapus min:0 untuk support nilai minus
        'keterangan' => 'nullable|string|max:255',
    ];
    
    public function mount($id)
    {
        $penerimaan = Penerimaan::findOrFail($id);
        
        // TAMBAHAN: Cek akses user ke data ini
        $user = auth()->user();
        if (!$user->hasRole(['Super Admin', 'Administrator', 'Kepala Badan']) && 
            $penerimaan->skpd_id != $user->skpd_id) {
            session()->flash('error', 'Anda tidak memiliki akses untuk mengedit data ini.');
            return redirect()->route('penerimaan.index');
        }
        
        // TAMBAHAN: Set user SKPD info
        if ($user->skpd) {
            $this->userSkpdInfo = 'Edit untuk: ' . $user->skpd->nama_opd;
        } elseif ($user->isSuperAdmin()) {
            $this->userSkpdInfo = 'Super Admin - Edit untuk BPKPAD';
        }
        
        $this->penerimaanId = $penerimaan->id;
        $this->tanggal = $penerimaan->tanggal->format('Y-m-d');
        $this->kode_rekening_id = $penerimaan->kode_rekening_id;
        $this->tahun = $penerimaan->tahun;
        $this->jumlah = $penerimaan->jumlah;
        $this->keterangan = $penerimaan->keterangan;
        
        // PERUBAHAN: Get kode rekening level 6 instead of level 5
        $query = KodeRekening::where('level', 6) // PERUBAHAN: dari 5 ke 6
                             ->where('is_active', true);
        
        // TAMBAHAN: Filter kode rekening based on SKPD access
        if ($user->skpd && $user->skpd->kode_rekening_access) {
            $allowedKodes = $user->skpd->kode_rekening_access;
            if (!empty($allowedKodes)) {
                $query->whereIn('id', $allowedKodes);
            }
        }
        
        $this->kodeRekeningLevel6 = $query->orderBy('kode')->get();
        
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
                                           
        Log::info('Penerimaan Edit mounted', [
            'user' => $user->name,
            'penerimaan_id' => $id,
            'skpd' => $user->skpd ? $user->skpd->nama_opd : 'No SKPD'
        ]);
    }
    
    public function update()
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
        
        $penerimaan = Penerimaan::find($this->penerimaanId);
        
        // TAMBAHAN: Update dengan tracking
        $user = auth()->user();
        
        $penerimaan->update([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun' => $this->tahun,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
            'updated_by' => $user->id, // TAMBAHAN: track siapa yang update
        ]);
        
        Log::info('Penerimaan updated', [
            'user' => $user->name,
            'penerimaan_id' => $this->penerimaanId,
            'kode_rekening' => $kodeRekening->kode,
            'jumlah' => $this->jumlah
        ]);
        
        session()->flash('message', 'Data penerimaan berhasil diperbarui.');
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
        return view('livewire.penerimaan.edit');
    }
}