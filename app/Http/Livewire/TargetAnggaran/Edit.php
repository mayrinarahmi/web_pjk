<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;

class Edit extends Component
{
    public $targetAnggaranId;
    public $tahunAnggaranId;
    public $kodeRekeningId;
    public $jumlah = 0;
    
    protected $rules = [
        'tahunAnggaranId' => 'required',
        'kodeRekeningId' => 'required',
        'jumlah' => 'required|numeric|min:0',
    ];
    
    public function mount($id)
    {
        $targetAnggaran = TargetAnggaran::findOrFail($id);
        $this->targetAnggaranId = $targetAnggaran->id;
        $this->tahunAnggaranId = $targetAnggaran->tahun_anggaran_id;
        $this->kodeRekeningId = $targetAnggaran->kode_rekening_id;
        $this->jumlah = $targetAnggaran->jumlah;
    }
    
    public function update()
    {
        $this->validate();
        
        // Pastikan kode rekening adalah level 5
        $kodeRekening = KodeRekening::find($this->kodeRekeningId);
        if (!$kodeRekening || $kodeRekening->level != 5) {
            session()->flash('error', 'Target anggaran hanya bisa diinput untuk kode rekening level 5.');
            return;
        }
        
        $targetAnggaran = TargetAnggaran::find($this->targetAnggaranId);
        $targetAnggaran->update([
            'kode_rekening_id' => $this->kodeRekeningId,
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'jumlah' => $this->jumlah,
        ]);
        
        session()->flash('message', 'Target anggaran berhasil diperbarui.');
        return redirect()->route('target-anggaran.index');
    }
    
    public function render()
    {
        $tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        
        // Hanya tampilkan kode rekening level 5
        $kodeRekening = KodeRekening::where('level', 5)
            ->orderBy('kode', 'asc')
            ->get();
            
        return view('livewire.target-anggaran.edit', [
            'tahunAnggaran' => $tahunAnggaran,
            'kodeRekening' => $kodeRekening
        ]);
    }
}
