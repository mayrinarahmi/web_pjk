<?php

namespace App\Http\Livewire\TargetPeriode;

use Livewire\Component;
use App\Models\TargetPeriode;
use App\Models\TahunAnggaran;

class Create extends Component
{
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
        'tahunAnggaranId' => 'required|exists:tahun_anggaran,id',
        'nama_periode' => 'required|string|max:100',
        'bulan_awal' => 'required|integer|min:1|max:12',
        'bulan_akhir' => 'required|integer|min:1|max:12|gte:bulan_awal',
        'persentase' => 'required|numeric|min:0.01|max:100',
    ];
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
    }
    
    public function save()
    {
        $this->validate();
        
        // Validasi total persentase tidak melebihi 100%
        $totalPersentase = TargetPeriode::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->sum('persentase');
            
        if ($totalPersentase + $this->persentase > 100) {
            session()->flash('error', 'Total persentase tidak boleh melebihi 100%. Sisa persentase yang tersedia: ' . (100 - $totalPersentase) . '%');
            return;
        }
        
        // Validasi bulan tidak overlap dengan target periode yang sudah ada
        if (TargetPeriode::isOverlap($this->tahunAnggaranId, $this->bulan_awal, $this->bulan_akhir)) {
            session()->flash('error', 'Periode yang Anda pilih overlap dengan periode yang sudah ada. Setiap bulan hanya boleh termasuk dalam satu periode.');
            return;
        }
        
        // Simpan data
        TargetPeriode::create([
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'nama_periode' => $this->nama_periode,
            'bulan_awal' => $this->bulan_awal,
            'bulan_akhir' => $this->bulan_akhir,
            'persentase' => $this->persentase,
        ]);
        
        session()->flash('message', 'Target periode berhasil ditambahkan.');
        return redirect()->route('target-periode.index');
    }
    
    public function render()
    {
        return view('livewire.target-periode.create');
    }
}