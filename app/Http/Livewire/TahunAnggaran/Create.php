<?php

namespace App\Http\Livewire\TahunAnggaran;

use Livewire\Component;
use App\Models\TahunAnggaran;

class Create extends Component
{
    public $tahun;
    public $is_active = false;
    
    protected $rules = [
        'tahun' => 'required|numeric|min:2000|max:2100|unique:tahun_anggaran,tahun',
        'is_active' => 'boolean',
    ];
    
    public function save()
    {
        $this->validate();
        
        if ($this->is_active) {
            // Nonaktifkan semua tahun anggaran
            TahunAnggaran::where('is_active', true)->update(['is_active' => false]);
        }
        
        TahunAnggaran::create([
            'tahun' => $this->tahun,
            'is_active' => $this->is_active,
        ]);
        
        session()->flash('message', 'Tahun anggaran berhasil ditambahkan.');
        return redirect()->route('tahun-anggaran.index');
    }
    
    public function render()
{
    return view('livewire.tahun-anggaran.create');
}
}
