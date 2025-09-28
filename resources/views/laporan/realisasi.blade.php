@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="page-header">
        <h4 class="page-title">Laporan Realisasi Anggaran</h4>
    </div>
    
    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('laporan.realisasi') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label>Tahun</label>
                        <select name="tahun" class="form-control">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $tahun == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal Awal</label>
                        <input type="date" name="tanggal_awal" class="form-control" 
                               value="{{ $tanggalAwal }}">
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" class="form-control" 
                               value="{{ $tanggalAkhir }}">
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label><br>
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                        <a href="#" class="btn btn-success">Export Excel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>KODE REKENING</th>
                            <th>URAIAN</th>
                            <th class="text-right">ANGGARAN</th>
                            <th class="text-right">REALISASI</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $row)
                        <tr class="level-{{ $row['level'] }}">
                            <td>{{ $row['kode'] }}</td>
                            <td style="padding-left: {{ ($row['level']-1) * 20 }}px">
                                {{ $row['nama'] }}
                            </td>
                            <td class="text-right">
                                {{ number_format($row['target'], 0, ',', '.') }}
                            </td>
                            <td class="text-right">
                                {{ number_format($row['realisasi'], 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                {{ number_format($row['persentase'], 2) }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .level-1 { font-weight: bold; background: #e8f4f8; }
    .level-2 { font-weight: bold; background: #f0f7fa; }
    .level-3 { font-weight: 600; }
</style>
@endsection