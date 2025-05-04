<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\TargetAnggaran;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;

class Edit extends Component
{
    public $targetAnggaranId;
    public $tahunAnggaranId;
    public $kodeRekeningId;
    public $jumlah;
    
    public $tahunAnggaran = [];
    public $kodeRekening = [];
    public $level;
    
    protected $rules = [
        'tahunAnggaranId' => 'required|exists:tahun_anggaran,id',
        'kodeRekeningId' => 'required|exists:kode_rekening,id',
        'jumlah' => 'required|numeric|min:0',
    ];
    
    public function mount($id)
    {
        $targetAnggaran = TargetAnggaran::with('kodeRekening')->findOrFail($id);
        $this->targetAnggaranId = $targetAnggaran->id;
        $this->tahunAnggaranId = $targetAnggaran->tahun_anggaran_id;
        $this->kodeRekeningId = $targetAnggaran->kode_rekening_id;
        $this->jumlah = $targetAnggaran->jumlah;
        $this->level = $targetAnggaran->kodeRekening->level;
        
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $this->loadKodeRekening();
    }
    
    public function updatedLevel()
    {
        $this->loadKodeRekening();
        $this->kodeRekeningId = null;
    }
    
    public function loadKodeRekening()
    {
        $this->kodeRekening = KodeRekening::where('level', $this->level)
            ->orderBy('kode')
            ->get();
    }
    
    public function update()
    {
        $this->validate();
        
        // Cek apakah target anggaran sudah ada untuk kode rekening lain
        $exists = TargetAnggaran::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->where('kode_rekening_id', $this->kodeRekeningId)
            ->where('id', '!=', $this->targetAnggaranId)
            ->exists();
            
        if ($exists) {
            session()->flash('error', 'Target anggaran untuk kode rekening ini sudah ada.');
            return;
        }
        
        $targetAnggaran = TargetAnggaran::find($this->targetAnggaranId);
        $targetAnggaran->update([
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'kode_rekening_id' => $this->kodeRekeningId,
            'jumlah' => $this->jumlah,
        ]);
        
        session()->flash('message', 'Target anggaran berhasil diperbarui.');
        return redirect()->route('target-anggaran.index');
    }
    
    public function render()
    {
        return view('livewire.target-anggaran.edit');
    }
}
