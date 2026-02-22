<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Ringkasan Penerimaan Daerah</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5pt;
            color: #1a1a1a;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p {
            font-size: 9pt;
            margin-top: 2px;
            color: #444;
        }

        .header .cetak {
            font-size: 7.5pt;
            color: #888;
            margin-top: 4px;
            font-style: italic;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 4px 3px;
            border: 0.5pt solid #888;
            word-wrap: break-word;
        }

        tbody td {
            padding: 3px 4px;
            border: 0.5pt solid #bbb;
            vertical-align: middle;
        }

        tfoot td {
            padding: 4px;
            border: 0.5pt solid #888;
            font-weight: bold;
            text-align: center;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-left   { text-align: left; }

        /* Warna header per tahun */
        .h-blue   { background-color: #4472C4; color: #fff; }
        .h-green  { background-color: #70AD47; color: #fff; }
        .h-orange { background-color: #ED7D31; color: #fff; }
        .h-purple { background-color: #7030A0; color: #fff; }
        .h-red    { background-color: #C00000; color: #fff; }
        .h-teal   { background-color: #00B0A0; color: #fff; }
        .h-main   { background-color: #4472C4; color: #fff; }

        .row-even { background-color: #F2F7FD; }
        .row-total { background-color: #BDD7EE; font-weight: bold; }

        .no-col    { width: 28px; }
        .nama-col  { width: 160px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Ringkasan Penerimaan Daerah</h2>
        <p>
            @if($tahunMulai == $tahunSelesai)
                Tahun {{ $tahunMulai }}
            @else
                Tahun {{ $tahunMulai }} s/d {{ $tahunSelesai }}
            @endif
        </p>
        <p class="cetak">Dicetak pada: {{ $tanggalCetak }}</p>
    </div>

    @php
        $yearColors = ['h-blue', 'h-green', 'h-orange', 'h-purple', 'h-red', 'h-teal'];
        $numYears   = count($years);
        // Hitung lebar kolom angka (dalam %, sisakan untuk NO dan Nama)
        $fixedWidth = 28 + 160; // px equiv, bukan persen
        // Persentase untuk kolom angka
        $namaColPct  = 25;
        $noColPct    = 4;
        $remainPct   = 100 - $namaColPct - $noColPct;
        $colPerYear  = $remainPct / max($numYears, 1);
        $targetColPct    = $colPerYear * 0.38;
        $realisasiColPct = $colPerYear * 0.38;
        $persenColPct    = $colPerYear * 0.24;
    @endphp

    <table>
        <colgroup>
            <col style="width: {{ $noColPct }}%">
            <col style="width: {{ $namaColPct }}%">
            @foreach($years as $year)
                <col style="width: {{ $targetColPct }}%">
                <col style="width: {{ $realisasiColPct }}%">
                <col style="width: {{ $persenColPct }}%">
            @endforeach
        </colgroup>
        <thead>
            {{-- Baris 1: NO | Unit Kerja | [Tahun colspan=3] --}}
            <tr>
                <th rowspan="2" class="h-main no-col">NO</th>
                <th rowspan="2" class="h-main">Unit Kerja</th>
                @foreach($years as $i => $year)
                    @php $cls = $yearColors[$i % count($yearColors)]; @endphp
                    <th colspan="3" class="{{ $cls }}">{{ $year }}</th>
                @endforeach
            </tr>
            {{-- Baris 2: Target | Realisasi | % per tahun --}}
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
                    <td class="text-left">{{ $row['nama'] }}</td>
                    @foreach($years as $year)
                        @php
                            $d = $row['per_tahun'][$year] ?? ['target'=>0,'realisasi'=>0,'persen'=>0];
                        @endphp
                        <td class="text-right">{{ number_format($d['target'], 0, ',', '.') }}</td>
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
                    <td colspan="{{ 2 + $numYears * 3 }}" class="text-center" style="padding: 10px;">
                        Tidak ada data
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="row-total">
                <td colspan="2" class="text-center">TOTAL PAD SELURUHNYA</td>
                @foreach($years as $year)
                    @php
                        $d = $totalRow['per_tahun'][$year] ?? ['target'=>0,'realisasi'=>0,'persen'=>0];
                    @endphp
                    <td class="text-right">{{ number_format($d['target'], 0, ',', '.') }}</td>
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
