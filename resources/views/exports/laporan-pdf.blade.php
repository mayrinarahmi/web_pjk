<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Realisasi Penerimaan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 3px;
        }
        .table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .indent-1 { padding-left: 0px; }
        .indent-2 { padding-left: 10px; }
        .indent-3 { padding-left: 20px; }
        .indent-4 { padding-left: 30px; }
        .indent-5 { padding-left: 40px; }
        .indent-6 { padding-left: 50px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .footer {
            margin-top: 30px;
        }
        .signature {
            float: right;
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN REALISASI PENERIMAAN</h2>
        <h3>BPKPAD KOTA BANJARMASIN</h3>
        <h4>Periode: {{ date('d-m-Y', strtotime($tanggalMulai)) }} s/d {{ date('d-m-Y', strtotime($tanggalSelesai)) }}</h4>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th rowspan="2">Kode Rekening</th>
                <th rowspan="2">Uraian</th>
                <th rowspan="2">%</th>
                <th rowspan="2">Pagu Anggaran</th>
                <th rowspan="2">Target {{ $persentaseTarget }}%</th>
                <th rowspan="2">Kurang dr Target {{ $persentaseTarget }}%</th>
                <th rowspan="2">Penerimaan per Rincian Objek</th>
                <th colspan="{{ count(array_filter($data[0]['penerimaan_per_bulan'], function($value, $key) { return $key <= Carbon\Carbon::parse($tanggalSelesai)->month; }, ARRAY_FILTER_USE_BOTH)) }}">Rincian Penerimaan</th>
            </tr>
            <tr>
                @for($i = 1; $i <= Carbon\Carbon::parse($tanggalSelesai)->month; $i++)
                    <th>{{ date('F', mktime(0, 0, 0, $i, 1)) }}</th>
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
                    @foreach(array_filter($item['penerimaan_per_bulan'], function($value, $key) use ($tanggalSelesai) { return $key <= Carbon\Carbon::parse($tanggalSelesai)->month; }, ARRAY_FILTER_USE_BOTH) as $bulan => $nilai)
                        <td class="right">{{ number_format($nilai, 0, ',', '.') }}</td>
                    @endforeach
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
