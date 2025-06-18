<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Carbon\Carbon;

class Edit extends Component
{
    public $penerimaanId;
    public $tanggal;
    public $kode_rekening_id;
    public $tahun; // Ubah dari tahun_anggaran_id ke tahun
    public $jumlah;
    public $keterangan;
    
    public $kodeRekeningLevel5 = [];
    public $availableYears = [];
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'tahun' => 'required|integer|min:2000|max:2100',
        'jumlah' => 'required|numeric|min:0',
        'keterangan' => 'nullable|string|max:255',
    ];
    
    public function mount($id)
    {
        $penerimaan = Penerimaan::findOrFail($id);
        $this->penerimaanId = $penerimaan->id;
        $this->tanggal = $penerimaan->tanggal->format('Y-m-d');
        $this->kode_rekening_id = $penerimaan->kode_rekening_id;
        $this->tahun = $penerimaan->tahun; // Ambil dari kolom tahun
        $this->jumlah = $penerimaan->jumlah;
        $this->keterangan = $penerimaan->keterangan;
        
        $this->kodeRekeningLevel5 = KodeRekening::where('level', 5)
                                               ->where('is_active', true)
                                               ->orderBy('kode')
                                               ->get();
        
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
    }
    
    public function update()
    {
        $this->validate();
        
        // Validasi tambahan: pastikan kode rekening adalah level 5
        $kodeRekening = KodeRekening::find($this->kode_rekening_id);
        if ($kodeRekening->level != 5) {
            session()->flash('error', 'Penerimaan hanya dapat diinput untuk kode rekening level 5.');
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
        $penerimaan->update([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun' => $this->tahun, // Update kolom tahun
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
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