<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Carbon\Carbon;

class Create extends Component
{
    public $tanggal;
    public $kode_rekening_id;
    public $jumlah;
    public $keterangan;
    public $tahun; // Ubah dari tahun_anggaran_id ke tahun
    
    public $kodeRekeningLevel5 = [];
    public $availableYears = [];
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'jumlah' => 'required|numeric|min:0',
        'keterangan' => 'nullable|string|max:255',
        'tahun' => 'required|integer|min:2000|max:2100',
    ];
    
    public function mount()
    {
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
        
        $this->kodeRekeningLevel5 = KodeRekening::where('level', 5)
                                               ->where('is_active', true)
                                               ->orderBy('kode')
                                               ->get();
    }
    
    public function save()
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
        
        Penerimaan::create([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun' => $this->tahun, // Simpan tahun langsung
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
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