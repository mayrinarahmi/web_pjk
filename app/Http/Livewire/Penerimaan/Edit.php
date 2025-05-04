<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;

class Edit extends Component
{
    public $penerimaanId;
    public $tanggal;
    public $kode_rekening_id;
    public $tahun_anggaran_id;
    public $jumlah;
    public $keterangan;
    
    public $kodeRekeningLevel4 = [];
    public $tahunAnggaran = [];
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'tahun_anggaran_id' => 'required|exists:tahun_anggaran,id',
        'jumlah' => 'required|numeric|min:0',
        'keterangan' => 'nullable|string|max:255',
    ];
    
    public function mount($id)
    {
        $penerimaan = Penerimaan::findOrFail($id);
        $this->penerimaanId = $penerimaan->id;
        $this->tanggal = $penerimaan->tanggal->format('Y-m-d');
        $this->kode_rekening_id = $penerimaan->kode_rekening_id;
        $this->tahun_anggaran_id = $penerimaan->tahun_anggaran_id;
        $this->jumlah = $penerimaan->jumlah;
        $this->keterangan = $penerimaan->keterangan;
        
        $this->kodeRekeningLevel4 = KodeRekening::where('level', 4)
            ->orderBy('kode')
            ->get();
            
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
    }
    
    public function update()
    {
        $this->validate();
        
        // Validasi tambahan: pastikan kode rekening adalah level 4
        $kodeRekening = KodeRekening::find($this->kode_rekening_id);
        if ($kodeRekening->level != 4) {
            session()->flash('error', 'Penerimaan hanya dapat diinput untuk kode rekening level 4.');
            return;
        }
        
        $penerimaan = Penerimaan::find($this->penerimaanId);
        $penerimaan->update([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun_anggaran_id' => $this->tahun_anggaran_id,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
        ]);
        
        session()->flash('message', 'Data penerimaan berhasil diperbarui.');
        return redirect()->route('penerimaan.index');
    }
    
    public function render()
    {
        return view('livewire.penerimaan.edit');
    }
}

