<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Ringkasan Penerimaan Daerah</title>
    <style>
        @page {
            margin: 14mm 22mm 14mm 22mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7pt;
            color: #1a1a1a;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .header .sub {
            font-size: 8pt;
            margin-top: 2px;
            color: #333;
        }
        .header .cetak {
            font-size: 6.5pt;
            color: #888;
            margin-top: 2px;
            font-style: italic;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        thead th {
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 4px 5px;
            border: 0.5pt solid #777;
            font-size: 6.5pt;
            white-space: nowrap;
        }

        tbody td {
            padding: 3px 5px;
            border: 0.5pt solid #bbb;
            vertical-align: middle;
            font-size: 6.5pt;
        }

        tfoot td {
            padding: 4px 5px;
            border: 0.5pt solid #888;
            font-weight: bold;
            font-size: 6.5pt;
        }

        .text-right  { text-align: right; white-space: nowrap; }
        .text-center { text-align: center; white-space: nowrap; }
        .text-left   { text-align: left; }
        .wrap        { word-wrap: break-word; text-align: left; }

        .h-blue   { background-color: #4472C4; color: #fff; }
        .h-green  { background-color: #70AD47; color: #fff; }
        .h-orange { background-color: #ED7D31; color: #fff; }
        .h-purple { background-color: #7030A0; color: #fff; }
        .h-red    { background-color: #C00000; color: #fff; }
        .h-teal   { background-color: #00B0A0; color: #fff; }
        .h-main   { background-color: #4472C4; color: #fff; }

        .row-even  { background-color: #F2F7FD; }
        .row-total { background-color: #BDD7EE; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h2>Laporan Ringkasan Penerimaan Daerah</h2>
    <p class="sub">
        @if($tahunMulai == $tahunSelesai)
            Tahun {{ $tahunMulai }}
        @else
            Tahun {{ $tahunMulai }} s/d {{ $tahunSelesai }}
        @endif
    </p>
    <p class="cetak">Dicetak pada: {{ $tanggalCetak }}</p>
</div>

@php
    $yearColors = ['h-blue','h-green','h-orange','h-purple','h-red','h-teal'];
    $numYears   = count($years);
@endphp

<table>
    <thead>
        <tr>
            <th rowspan="2" class="h-main">NO</th>
            <th rowspan="2" class="h-main" style="text-align:center">Unit Kerja</th>
            @foreach($years as $i => $year)
                @php $cls = $yearColors[$i % count($yearColors)]; @endphp
                <th colspan="3" class="{{ $cls }}">{{ $year }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($years as $i => $year)
                @php $cls = $yearColors[$i % count($yearColors)]; @endphp
                <th class="{{ $cls }}">Target</th>
                <th class="{{ $cls }}">Realisasi</th>
                <th class="{{ $cls }}">%</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @forelse($rows as $no => $row)
            <tr class="{{ $no % 2 === 1 ? 'row-even' : '' }}">
                <td class="text-center">{{ $no + 1 }}</td>
                <td class="wrap">{{ $row['nama'] }}</td>
                @foreach($years as $year)
                    @php $d = $row['per_tahun'][$year] ?? ['target'=>0,'realisasi'=>0,'persen'=>0]; @endphp
                    <td class="text-right">{{ number_format($d['target'],    0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($d['realisasi'], 0, ',', '.') }}</td>
                    <td class="text-right">
                        @if($d['target'] > 0)
                            {{ number_format($d['persen'], 2, ',', '.') }}%
                        @else
                            -
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ 2 + $numYears * 3 }}" class="text-center" style="padding:10px">
                    Tidak ada data
                </td>
            </tr>
        @endforelse
    </tbody>

    <tfoot>
        <tr class="row-total">
            <td colspan="2" class="text-center">TOTAL PAD SELURUHNYA</td>
            @foreach($years as $year)
                @php $d = $totalRow['per_tahun'][$year] ?? ['target'=>0,'realisasi'=>0,'persen'=>0]; @endphp
                <td class="text-right">{{ number_format($d['target'],    0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($d['realisasi'], 0, ',', '.') }}</td>
                <td class="text-right">
                    @if($d['target'] > 0)
                        {{ number_format($d['persen'], 2, ',', '.') }}%
                    @else
                        -
                    @endif
                </td>
            @endforeach
        </tr>
    </tfoot>
</table>

</body>
</html>
