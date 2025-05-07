<?php

namespace App\Http\Livewire\TargetPeriode;

use Livewire\Component;
use App\Models\TargetPeriode;
use App\Models\TahunAnggaran;

class Edit extends Component
{
    public $targetPeriodeId;
    public $tahunAnggaranId;
    public $nama_periode;
    public $bulan_awal;
    public $bulan_akhir;
    public $persentase;
    
    public $tahunAnggaran = [];
    public $daftarBulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
    
    protected $rules = [
        'nama_periode' => 'required|string|max:100',
        'bulan_awal' => 'required|integer|min:1|max:12',
        'bulan_akhir' => 'required|integer|min:1|max:12|gte:bulan_awal',
        'persentase' => 'required|numeric|min:0.01|max:100',
    ];
    
    public function mount($id)
    {
        $this->targetPeriodeId = $id;
        
        $targetPeriode = TargetPeriode::findOrFail($id);
        $this->tahunAnggaranId = $targetPeriode->tahun_anggaran_id;
        $this->nama_periode = $targetPeriode->nama_periode;
        $this->bulan_awal = $targetPeriode->bulan_awal;
        $this->bulan_akhir = $targetPeriode->bulan_akhir;
        $this->persentase = $targetPeriode->persentase;
        
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
    }
    
    public function update()
    {
        $this->validate();
        
        $targetPeriode = TargetPeriode::find($this->targetPeriodeId);
        
        // Validasi total persentase tidak melebihi 100%
        $totalPersentase = TargetPeriode::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->where('id', '!=', $this->targetPeriodeId)
            ->sum('persentase');
            
        if ($totalPersentase + $this->persentase > 100) {
            session()->flash('error', 'Total persentase tidak boleh melebihi 100%. Sisa persentase yang tersedia: ' . (100 - $totalPersentase) . '%');
            return;
        }
        
        // Validasi bulan tidak overlap dengan target periode yang sudah ada
        if (TargetPeriode::isOverlap($this->tahunAnggaranId, $this->bulan_awal, $this->bulan_akhir, $this->targetPeriodeId)) {
            session()->flash('error', 'Periode yang Anda pilih overlap dengan periode yang sudah ada. Setiap bulan hanya boleh termasuk dalam satu periode.');
            return;
        }
        
        // Update data
        $targetPeriode->update([
            'nama_periode' => $this->nama_periode,
            'bulan_awal' => $this->bulan_awal,
            'bulan_akhir' => $this->bulan_akhir,
            'persentase' => $this->persentase,
        ]);
        
        session()->flash('message', 'Target periode berhasil diperbarui.');
        return redirect()->route('target-periode.index');
    }
    
    public function render()
    {
        return view('livewire.target-periode.edit');
    }
}