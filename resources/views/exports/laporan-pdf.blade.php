<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Realisasi Penerimaan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt; /* Ukuran font lebih kecil */
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt; /* Ukuran font tabel lebih kecil */
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 2px; /* Padding lebih kecil */
        }
        .table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        /* Lebar kolom disesuaikan */
        .col-kode { width: 10%; }
        .col-uraian { width: 20%; }
        .col-persen { width: 5%; }
        .col-angka { width: 12%; }
        .col-bulan { width: 8%; }
        
        .indent-1 { padding-left: 0px; }
        .indent-2 { padding-left: 10px; }
        .indent-3 { padding-left: 15px; }
        .indent-4 { padding-left: 20px; }
        .indent-5 { padding-left: 25px; }
        .indent-6 { padding-left: 30px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .footer {
            margin-top: 20px;
        }
        .signature {
            float: right;
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        // Tentukan rentang bulan berdasarkan mode tampilan
        $tampilkanDariBulan = isset($viewMode) && $viewMode === 'specific' ? (isset($bulanAwal) ? $bulanAwal : Carbon\Carbon::parse($tanggalMulai)->month) : 1;
        $tampilkanSampaiBulan = $bulanAkhir;
        
        // Tentukan label mode
        $modeTitle = isset($viewMode) && $viewMode === 'specific' ? 'Triwulan Spesifik' : 'Kumulatif s/d Triwulan';
    @endphp
    
    <div class="header">
        <h2 style="margin-bottom: 5px;">LAPORAN REALISASI PENERIMAAN</h2>
        <h3 style="margin-top: 0; margin-bottom: 5px;">BPKPAD KOTA BANJARMASIN</h3>
        <h4 style="margin-top: 0;">Periode: {{ date('d-m-Y', strtotime($tanggalMulai)) }} s/d {{ date('d-m-Y', strtotime($tanggalSelesai)) }}</h4>
        <h5 style="margin-top: 0;">(Mode: {{ $modeTitle }})</h5>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th class="col-kode" rowspan="2">Kode Rekening</th>
                <th class="col-uraian" rowspan="2">Uraian</th>
                <th class="col-persen" rowspan="2">%</th>
                <th class="col-angka" rowspan="2">Pagu Anggaran</th>
                <th class="col-angka" rowspan="2">Target {{ $persentaseTarget }}%</th>
                <th class="col-angka" rowspan="2">Kurang dr Target {{ $persentaseTarget }}%</th>
                <th class="col-angka" rowspan="2">Penerimaan per Rincian Objek</th>
                <th colspan="{{ $tampilkanSampaiBulan - $tampilkanDariBulan + 1 }}">Rincian Penerimaan</th>
            </tr>
            <tr>
                @for($i = $tampilkanDariBulan; $i <= $tampilkanSampaiBulan; $i++)
                    <th class="col-bulan">{{ Carbon\Carbon::create()->month($i)->translatedFormat('M') }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
                <tr>
                    <td>{{ $item['kode'] }}</td>
                    <td class="indent-{{ $item['level'] }}">{{ $item['uraian'] }}</td>
                    <td class="right">{{ number_format($item['persentase'], 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($item['target_anggaran'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item['target_sd_bulan_ini'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item['kurang_dari_target'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item['realisasi_sd_bulan_ini'], 0, ',', '.') }}</td>
                    @for($i = $tampilkanDariBulan; $i <= $tampilkanSampaiBulan; $i++)
                        <td class="right">{{ number_format($item['penerimaan_per_bulan'][$i] ?? 0, 0, ',', '.') }}</td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <div class="signature">
            <p>Banjarmasin, {{ date('d F Y') }}</p>
            <p>Kepala BPKPAD</p>
            <br><br><br>
            <p>___________________</p>
            <p>NIP. </p>
        </div>
    </div>
</body>
</html>