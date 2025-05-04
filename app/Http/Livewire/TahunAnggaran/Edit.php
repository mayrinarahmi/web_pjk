<?php

namespace App\Http\Livewire\TahunAnggaran;

use Livewire\Component;
use App\Models\TahunAnggaran;

class Edit extends Component
{
    public $tahunAnggaranId;
    public $tahun;
    public $is_active;
    
    protected function rules()
    {
        return [
            'tahun' => 'required|numeric|min:2000|max:2100|unique:tahun_anggaran,tahun,' . $this->tahunAnggaranId,
            'is_active' => 'boolean',
        ];
    }
    
    public function mount($id)
    {
        $tahunAnggaran = TahunAnggaran::findOrFail($id);
        $this->tahunAnggaranId = $tahunAnggaran->id;
        $this->tahun = $tahunAnggaran->tahun;
        $this->is_active = $tahunAnggaran->is_active;
    }
    
    public function update()
    {
        $this->validate();
        
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        
        if ($this->is_active && !$tahunAnggaran->is_active) {
            // Nonaktifkan semua tahun anggaran
            TahunAnggaran::where('is_active', true)->update(['is_active' => false]);
        }
        
        $tahunAnggaran->update([
            'tahun' => $this->tahun,
            'is_active' => $this->is_active,
        ]);
        
        session()->flash('message', 'Tahun anggaran berhasil diperbarui.');
        return redirect()->route('tahun-anggaran.index');
    }
    
    public function render()
    {
        return view('livewire.tahun-anggaran.edit');
    }
}
