<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;

class Create extends Component
{
    public $tahunAnggaranId;
    public $kodeRekeningId;
    public $jumlah = 0;
    
    protected $rules = [
        'tahunAnggaranId' => 'required',
        'kodeRekeningId' => 'required',
        'jumlah' => 'required|numeric|min:0',
    ];
    
    public function mount()
    {
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
    }
    
    public function save()
    {
        $this->validate();
        
        // Pastikan kode rekening adalah level 5
        $kodeRekening = KodeRekening::find($this->kodeRekeningId);
        if (!$kodeRekening || $kodeRekening->level != 5) {
            session()->flash('error', 'Target anggaran hanya bisa diinput untuk kode rekening level 5.');
            return;
        }
        
        // Cek apakah target anggaran sudah ada
        $existingTarget = TargetAnggaran::where('kode_rekening_id', $this->kodeRekeningId)
            ->where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->first();
            
        if ($existingTarget) {
            // Update target yang sudah ada
            $existingTarget->jumlah = $this->jumlah;
            $existingTarget->save();
            session()->flash('message', 'Target anggaran berhasil diperbarui.');
        } else {
            // Buat target baru
            TargetAnggaran::create([
                'kode_rekening_id' => $this->kodeRekeningId,
                'tahun_anggaran_id' => $this->tahunAnggaranId,
                'jumlah' => $this->jumlah,
            ]);
            session()->flash('message', 'Target anggaran berhasil ditambahkan.');
        }
        
        return redirect()->route('target-anggaran.index');
    }
    
    public function render()
    {
        $tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        
        // Hanya tampilkan kode rekening level 5
        $kodeRekening = KodeRekening::where('level', 5)
            ->orderBy('kode', 'asc')
            ->get();
            
        return view('livewire.target-anggaran.create', [
            'tahunAnggaran' => $tahunAnggaran,
            'kodeRekening' => $kodeRekening
        ]);
    }
}
