<?php

namespace App\Http\Livewire\LaporanRangkuman;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\Skpd;
use App\Models\TargetAnggaran;
use App\Models\TahunAnggaran;
use App\Exports\LaporanRangkumanExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class Index extends Component
{
    public $tahunMulai;
    public $tahunSelesai;
    public $availableTahuns = [];

    protected $queryString = [
        'tahunMulai' => ['except' => ''],
        'tahunSelesai' => ['except' => ''],
    ];

    public function mount()
    {
        if (!auth()->user()->hasRole(['Super Admin', 'Administrator', 'Kepala Badan'])) {
            abort(403);
        }

        $currentYear = Carbon::now()->year;
        $this->tahunSelesai = $this->tahunSelesai ?: $currentYear;
        $this->tahunMulai   = $this->tahunMulai   ?: ($currentYear - 2);

        // Ambil tahun yang tersedia dari data penerimaan + tahun anggaran
        $tahunPenerimaan = Penerimaan::selectRaw('DISTINCT tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        $tahunAnggaran = TahunAnggaran::selectRaw('DISTINCT tahun')
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        $this->availableTahuns = array_unique(
            array_merge($tahunPenerimaan, $tahunAnggaran, [$currentYear])
        );
        rsort($this->availableTahuns);
    }

    public function updatedTahunMulai()
    {
        // Pastikan tahunMulai tidak melebihi tahunSelesai
        if ($this->tahunMulai > $this->tahunSelesai) {
            $this->tahunSelesai = $this->tahunMulai;
        }
    }

    public function updatedTahunSelesai()
    {
        if ($this->tahunSelesai < $this->tahunMulai) {
            $this->tahunMulai = $this->tahunSelesai;
        }
    }

    private function getRangkumanData()
    {
        $years = range((int) $this->tahunMulai, (int) $this->tahunSelesai);

        // Batas maksimal 10 tahun
        if (count($years) > 10) {
            $years = array_slice($years, 0, 10);
        }

        // Load semua SKPD aktif
        $skpds = Skpd::where('status', 'aktif')
            ->orderBy('nama_opd')
            ->get();

        // Cache TahunAnggaran per tahun (prioritas: aktif > perubahan > murni)
        $tahunAnggaranCache = [];
        foreach ($years as $year) {
            $ta = TahunAnggaran::where('tahun', $year)->where('is_active', 1)->first();
            if (!$ta) {
                $ta = TahunAnggaran::where('tahun', $year)
                    ->orderByRaw("FIELD(jenis_anggaran, 'perubahan', 'murni')")
                    ->first();
            }
            $tahunAnggaranCache[$year] = $ta;
        }

        $rows   = [];
        $totals = array_fill_keys($years, ['target' => 0, 'realisasi' => 0]);

        foreach ($skpds as $skpd) {
            $level6Ids = $skpd->getLevel6KodeRekeningIds();
            $row = [
                'nama' => $skpd->nama_opd,
                'per_tahun' => [],
            ];

            foreach ($years as $year) {
                $ta = $tahunAnggaranCache[$year];
                $tahunAnggaranId = $ta ? $ta->id : null;

                // Target: sum TargetAnggaran untuk kode rekening SKPD
                $target = 0;
                if ($tahunAnggaranId && !empty($level6Ids)) {
                    $target = TargetAnggaran::whereIn('kode_rekening_id', $level6Ids)
                        ->where('tahun_anggaran_id', $tahunAnggaranId)
                        ->sum('jumlah');
                }

                // Realisasi: sum penerimaan SKPD di tahun tersebut
                $realisasi = Penerimaan::where('skpd_id', $skpd->id)
                    ->where('tahun', $year)
                    ->sum('jumlah');

                // Persentase
                $persen = ($target > 0) ? round(($realisasi / $target) * 100, 2) : 0;

                $row['per_tahun'][$year] = [
                    'target'    => $target,
                    'realisasi' => $realisasi,
                    'persen'    => $persen,
                ];

                // Akumulasi total
                $totals[$year]['target']    += $target;
                $totals[$year]['realisasi'] += $realisasi;
            }

            // Hanya tampilkan SKPD yang ada data (realisasi atau target > 0)
            $hasData = false;
            foreach ($row['per_tahun'] as $yearData) {
                if ($yearData['target'] > 0 || $yearData['realisasi'] > 0) {
                    $hasData = true;
                    break;
                }
            }

            if ($hasData) {
                $rows[] = $row;
            }
        }

        // Hitung persentase total
        $totalRow = ['per_tahun' => []];
        foreach ($years as $year) {
            $t = $totals[$year]['target'];
            $r = $totals[$year]['realisasi'];
            $totalRow['per_tahun'][$year] = [
                'target'    => $t,
                'realisasi' => $r,
                'persen'    => ($t > 0) ? round(($r / $t) * 100, 2) : 0,
            ];
        }

        return [
            'rows'     => $rows,
            'totalRow' => $totalRow,
            'years'    => $years,
        ];
    }

    public function exportExcel()
    {
        $result = $this->getRangkumanData();

        return Excel::download(
            new LaporanRangkumanExport(
                $result['rows'],
                $result['totalRow'],
                $result['years'],
                $this->tahunMulai,
                $this->tahunSelesai
            ),
            'laporan-ringkasan-' . $this->tahunMulai . '-' . $this->tahunSelesai . '.xlsx'
        );
    }

    public function exportPdf()
    {
        $result = $this->getRangkumanData();

        $pdf = Pdf::loadView('exports.laporan-rangkuman-pdf', [
            'rows'       => $result['rows'],
            'totalRow'   => $result['totalRow'],
            'years'      => $result['years'],
            'tahunMulai' => $this->tahunMulai,
            'tahunSelesai' => $this->tahunSelesai,
            'tanggalCetak' => Carbon::now()->isoFormat('D MMMM Y'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'laporan-ringkasan-' . $this->tahunMulai . '-' . $this->tahunSelesai . '.pdf');
    }

    public function render()
    {
        $result = $this->getRangkumanData();

        return view('livewire.laporan-rangkuman.index', [
            'rows'          => $result['rows'],
            'totalRow'      => $result['totalRow'],
            'years'         => $result['years'],
            'availableTahuns' => $this->availableTahuns,
        ]);
    }
}
