<?php

namespace App\Http\Livewire\TahunAnggaran;

use Livewire\Component;
use App\Models\TahunAnggaran;

class Create extends Component
{
    public $tahun;
    public $tanggal_penetapan;
    public $keterangan;
    public $is_active = false;
   
    protected $rules = [
        'tahun' => 'required|numeric|min:2000|max:2100',
        'tanggal_penetapan' => 'nullable|date',
        'keterangan' => 'nullable|string|max:255',
        'is_active' => 'boolean',
    ];
    
    public function mount()
    {
        $this->tanggal_penetapan = now()->format('Y-m-d');
    }
   
    public function save()
    {
        $this->validate();
        
        // Cek apakah tahun dengan jenis murni sudah ada
        $existing = TahunAnggaran::where('tahun', $this->tahun)
            ->where('jenis_anggaran', 'murni')
            ->first();
            
        if ($existing) {
            $this->addError('tahun', 'APBD Murni untuk tahun ' . $this->tahun . ' sudah ada.');
            return;
        }
       
        if ($this->is_active) {
            // Nonaktifkan semua tahun anggaran
            TahunAnggaran::where('is_active', true)->update(['is_active' => false]);
        }
       
        TahunAnggaran::create([
            'tahun' => $this->tahun,
            'jenis_anggaran' => 'murni', // Default selalu murni
            'tanggal_penetapan' => $this->tanggal_penetapan,
            'keterangan' => $this->keterangan,
            'is_active' => $this->is_active,
        ]);
       
        session()->flash('message', 'APBD Murni tahun ' . $this->tahun . ' berhasil ditambahkan.');
        return redirect()->route('tahun-anggaran.index');
    }
   
    public function render()
    {
        return view('livewire.tahun-anggaran.create');
    }
}