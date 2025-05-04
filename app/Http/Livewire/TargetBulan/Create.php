<?php

namespace App\Http\Livewire\TargetBulan;

use Livewire\Component;
use App\Models\TargetBulan;
use App\Models\TahunAnggaran;

class Create extends Component
{
    public $tahunAnggaranId;
    public $nama_kelompok;
    public $bulan = [];
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
        'nama_kelompok' => 'required|string|max:100',
        'bulan' => 'required|array|min:1',
        'bulan.*' => 'integer|min:1|max:12',
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
        $totalPersentase = TargetBulan::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->sum('persentase');
            
        if ($totalPersentase + $this->persentase > 100) {
            session()->flash('error', 'Total persentase tidak boleh melebihi 100%. Sisa persentase yang tersedia: ' . (100 - $totalPersentase) . '%');
            return;
        }
        
        TargetBulan::create([
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'nama_kelompok' => $this->nama_kelompok,
            'bulan' => json_encode($this->bulan),
            'persentase' => $this->persentase,
        ]);
        
        session()->flash('message', 'Target bulan berhasil ditambahkan.');
        return redirect()->route('target-bulan.index');
    }
    
    public function render()
    {
        return view('livewire.target-bulan.create');
    }
}
