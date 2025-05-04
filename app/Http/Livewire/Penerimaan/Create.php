<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;

class Create extends Component
{
    public $tanggal;
    public $kode_rekening_id;
    public $jumlah;
    public $keterangan;
    
    public $kodeRekeningLevel4 = [];
    public $tahunAnggaran;
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'jumlah' => 'required|numeric|min:0',
        'keterangan' => 'nullable|string|max:255',
    ];
    
    public function mount()
    {
        $this->tanggal = date('Y-m-d');
        $this->tahunAnggaran = TahunAnggaran::where('is_active', true)->first();
        
        if (!$this->tahunAnggaran) {
            session()->flash('error', 'Tidak ada tahun anggaran aktif. Silakan aktifkan tahun anggaran terlebih dahulu.');
            return redirect()->route('tahun-anggaran.index');
        }
        
        $this->kodeRekeningLevel4 = KodeRekening::where('level', 4)
            ->orderBy('kode')
            ->get();
    }
    
    public function save()
    {
        $this->validate();
        
        // Validasi tambahan: pastikan kode rekening adalah level 4
        $kodeRekening = KodeRekening::find($this->kode_rekening_id);
        if ($kodeRekening->level != 4) {
            session()->flash('error', 'Penerimaan hanya dapat diinput untuk kode rekening level 4.');
            return;
        }
        
        Penerimaan::create([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun_anggaran_id' => $this->tahunAnggaran->id,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
        ]);
        
        session()->flash('message', 'Data penerimaan berhasil disimpan.');
        return redirect()->route('penerimaan.index');
    }
    
    public function render()
    {
        return view('livewire.penerimaan.create');
    }
}

