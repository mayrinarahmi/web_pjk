<?php

namespace App\Http\Livewire\TahunAnggaran;

use Livewire\Component;
use App\Models\TahunAnggaran;

class Edit extends Component
{
    public $tahunAnggaranId;
    public $tahun;
    public $jenis_anggaran;
    public $tanggal_penetapan;
    public $keterangan;
    public $is_active;
    public $tahunAnggaranData;
   
    protected function rules()
    {
        return [
            'tahun' => 'required|numeric|min:2000|max:2100',
            'tanggal_penetapan' => 'nullable|date',
            'keterangan' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }
   
    public function mount($id)
    {
        $tahunAnggaran = TahunAnggaran::findOrFail($id);
        $this->tahunAnggaranId = $tahunAnggaran->id;
        $this->tahun = $tahunAnggaran->tahun;
        $this->jenis_anggaran = $tahunAnggaran->jenis_anggaran;
        $this->tanggal_penetapan = $tahunAnggaran->tanggal_penetapan ? $tahunAnggaran->tanggal_penetapan->format('Y-m-d') : null;
        $this->keterangan = $tahunAnggaran->keterangan;
        $this->is_active = $tahunAnggaran->is_active;
        $this->tahunAnggaranData = $tahunAnggaran;
    }
   
    public function update()
    {
        $this->validate();
        
        // Cek apakah tahun dengan jenis yang sama sudah ada (kecuali yang sedang diedit)
        $existing = TahunAnggaran::where('tahun', $this->tahun)
            ->where('jenis_anggaran', $this->jenis_anggaran)
            ->where('id', '!=', $this->tahunAnggaranId)
            ->first();
            
        if ($existing) {
            $jenisLabel = $this->jenis_anggaran == 'murni' ? 'Murni' : 'Perubahan';
            $this->addError('tahun', 'APBD ' . $jenisLabel . ' untuk tahun ' . $this->tahun . ' sudah ada.');
            return;
        }
       
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
       
        if ($this->is_active && !$tahunAnggaran->is_active) {
            // Nonaktifkan semua tahun anggaran
            TahunAnggaran::where('is_active', true)->update(['is_active' => false]);
        }
       
        $tahunAnggaran->update([
            'tahun' => $this->tahun,
            'tanggal_penetapan' => $this->tanggal_penetapan,
            'keterangan' => $this->keterangan,
            'is_active' => $this->is_active,
        ]);
       
        session()->flash('message', 'Tahun anggaran berhasil diperbarui.');
        return redirect()->route('tahun-anggaran.index');
    }
   
    public function render()
    {
        return view('livewire.tahun-anggaran.edit', [
            'tahunAnggaranData' => $this->tahunAnggaranData
        ]);
    }
}