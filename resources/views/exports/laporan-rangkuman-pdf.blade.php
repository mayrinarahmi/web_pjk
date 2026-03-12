<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Ringkasan Penerimaan Daerah</title>
    <style>
        @page {
            margin: 14mm 18mm 14mm 18mm;
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

        /* Tabel di tengah, lebar menyesuaikan konten */
        .table-wrap {
            text-align: center;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0 auto;
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

    /*
     * Landscape A4: 297mm, margin 18mm×2 → usable 261mm
     *
     * Kolom angka dihitung cukup untuk angka terpanjang ~16 karakter
     * (mis. "2.429.556.718.338") + padding.
     * % cukup untuk "107,08%".
     * NO cukup untuk "10".
     * Unit Kerja: sisa atau minimal 40mm.
     */
    $noMm = 9;

    // Lebar data kolom per tahun (Target, Realisasi, %)
    if ($numYears <= 1)     { $tMm = 33; $rMm = 33; $pMm = 14; }
    elseif ($numYears <= 2) { $tMm = 30; $rMm = 30; $pMm = 13; }
    elseif ($numYears <= 3) { $tMm = 26; $rMm = 26; $pMm = 11; }
    elseif ($numYears <= 4) { $tMm = 22; $rMm = 22; $pMm = 10; }
    else                    { $tMm = 19; $rMm = 19; $pMm = 9;  }

    $dataColsMm = $numYears * ($tMm + $rMm + $pMm);

    // Sisakan ruang untuk Unit Kerja minimal 38mm
    $usable = 261;
    $ukMm   = max(38, $usable - $noMm - $dataColsMm);

    // Total lebar tabel
    $tableMm = $noMm + $ukMm + $dataColsMm;
@endphp

<div class="table-wrap">
<table style="width: {{ $tableMm }}mm">
    <colgroup>
        <col style="width: {{ $noMm }}mm">
        <col style="width: {{ $ukMm }}mm">
        @foreach($years as $year)
            <col style="width: {{ $tMm }}mm">
            <col style="width: {{ $rMm }}mm">
            <col style="width: {{ $pMm }}mm">
        @endforeach
    </colgroup>

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
</div>

</body>
</html>
