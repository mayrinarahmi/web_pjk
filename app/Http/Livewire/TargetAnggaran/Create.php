<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\TargetAnggaran;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;

class Create extends Component
{
    public $tahunAnggaranId;
    public $kodeRekeningId;
    public $jumlah;
    
    public $tahunAnggaran = [];
    public $kodeRekening = [];
    public $level = 4; // Default level 4
    
    protected $rules = [
        'tahunAnggaranId' => 'required|exists:tahun_anggaran,id',
        'kodeRekeningId' => 'required|exists:kode_rekening,id',
        'jumlah' => 'required|numeric|min:0',
    ];
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
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
    
    public function save()
    {
        $this->validate();
        
        // Cek apakah target anggaran sudah ada
        $exists = TargetAnggaran::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->where('kode_rekening_id', $this->kodeRekeningId)
            ->exists();
            
        if ($exists) {
            session()->flash('error', 'Target anggaran untuk kode rekening ini sudah ada.');
            return;
        }
        
        TargetAnggaran::create([
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'kode_rekening_id' => $this->kodeRekeningId,
            'jumlah' => $this->jumlah,
        ]);
        
        session()->flash('message', 'Target anggaran berhasil ditambahkan.');
        return redirect()->route('target-anggaran.index');
    }
    
    public function render()
    {
        return view('livewire.target-anggaran.create');
    }
}

