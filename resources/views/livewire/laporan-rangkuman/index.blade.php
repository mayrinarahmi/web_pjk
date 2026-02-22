<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Ringkasan Penerimaan Daerah</h5>
            <div>
                <button class="btn btn-success btn-sm me-1" wire:click="exportExcel" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportExcel">
                        <i class="bx bx-file"></i> Export Excel
                    </span>
                    <span wire:loading wire:target="exportExcel">
                        <span class="spinner-border spinner-border-sm me-1"></span> Memproses...
                    </span>
                </button>
                <button class="btn btn-danger btn-sm" wire:click="exportPdf" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportPdf">
                        <i class="bx bx-file-pdf"></i> Export PDF
                    </span>
                    <span wire:loading wire:target="exportPdf">
                        <span class="spinner-border spinner-border-sm me-1"></span> Memproses...
                    </span>
                </button>
            </div>
        </div>

        <div class="card-body">
            {{-- Filter --}}
            <div class="row mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tahun Mulai</label>
                    <select wire:model.live="tahunMulai" class="form-select">
                        @foreach($availableTahuns as $tahun)
                            <option value="{{ $tahun }}">{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tahun Selesai</label>
                    <select wire:model.live="tahunSelesai" class="form-select">
                        @foreach($availableTahuns as $tahun)
                            <option value="{{ $tahun }}">{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info mb-0 py-2">
                        <i class="bx bx-info-circle me-1"></i>
                        Menampilkan data tahun <strong>{{ $tahunMulai }}</strong> s/d <strong>{{ $tahunSelesai }}</strong>
                        ({{ count($years) }} tahun, {{ count($years) * 3 }} kolom data)
                    </div>
                </div>
            </div>

            {{-- Loading --}}
            <div wire:loading wire:target="tahunMulai, tahunSelesai" class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <div>Memuat data ringkasan...</div>
                </div>
            </div>

            {{-- Tabel --}}
            <div class="table-responsive" wire:loading.remove wire:target="tahunMulai, tahunSelesai">
                @if(count($rows) > 0 || true)
                <table class="table table-bordered table-sm table-hover align-middle" id="tabelRingkasan">
                    <thead>
                        {{-- Header Baris 1: NO | Unit Kerja | [Tahun X colspan=3] ... --}}
                        <tr>
                            <th rowspan="2" class="text-center align-middle bg-header-main" style="width: 45px; min-width:40px;">NO</th>
                            <th rowspan="2" class="align-middle bg-header-main" style="min-width: 220px;">Unit Kerja</th>
                            @foreach($years as $i => $year)
                                @php
                                    $headerColors = ['bg-header-blue', 'bg-header-green', 'bg-header-orange', 'bg-header-purple', 'bg-header-red', 'bg-header-teal'];
                                    $colorClass = $headerColors[$i % count($headerColors)];
                                @endphp
                                <th colspan="3" class="text-center {{ $colorClass }}" style="min-width: 180px;">
                                    {{ $year }}
                                </th>
                            @endforeach
                        </tr>
                        {{-- Header Baris 2: Target | Realisasi | % per tahun --}}
                        <tr>
                            @foreach($years as $i => $year)
                                @php
                                    $headerColors = ['bg-header-blue', 'bg-header-green', 'bg-header-orange', 'bg-header-purple', 'bg-header-red', 'bg-header-teal'];
                                    $colorClass = $headerColors[$i % count($headerColors)];
                                @endphp
                                <th class="text-center {{ $colorClass }}" style="min-width: 110px;">Target</th>
                                <th class="text-center {{ $colorClass }}" style="min-width: 110px;">Realisasi</th>
                                <th class="text-center {{ $colorClass }}" style="min-width: 55px;">%</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $no => $row)
                            <tr>
                                <td class="text-center">{{ $no + 1 }}</td>
                                <td>{{ $row['nama'] }}</td>
                                @foreach($years as $year)
                                    @php
                                        $d = $row['per_tahun'][$year] ?? ['target'=>0,'realisasi'=>0,'persen'=>0];
                                    @endphp
                                    <td class="text-end">{{ number_format($d['target'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($d['realisasi'], 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        @if($d['target'] > 0)
                                            <span class="{{ $d['persen'] >= 100 ? 'text-success fw-bold' : ($d['persen'] >= 75 ? 'text-warning' : 'text-danger') }}">
                                                {{ number_format($d['persen'], 2, ',', '.') }}%
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + count($years) * 3 }}" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle me-1"></i> Tidak ada data untuk ditampilkan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    {{-- Footer: TOTAL PAD SELURUHNYA --}}
                    <tfoot>
                        <tr class="fw-bold bg-total-row">
                            <td colspan="2" class="text-center">TOTAL PAD SELURUHNYA</td>
                            @foreach($years as $year)
                                @php
                                    $d = $totalRow['per_tahun'][$year] ?? ['target'=>0,'realisasi'=>0,'persen'=>0];
                                @endphp
                                <td class="text-end">{{ number_format($d['target'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($d['realisasi'], 0, ',', '.') }}</td>
                                <td class="text-end">
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
                @endif
            </div>
        </div>
    </div>

    <style>
        /* Header utama (NO & Unit Kerja) */
        .bg-header-main {
            background-color: #4472C4 !important;
            color: #fff !important;
            font-weight: 600;
        }

        /* Warna header per tahun - bergantian */
        .bg-header-blue   { background-color: #4472C4 !important; color: #fff !important; font-weight: 600; }
        .bg-header-green  { background-color: #70AD47 !important; color: #fff !important; font-weight: 600; }
        .bg-header-orange { background-color: #ED7D31 !important; color: #fff !important; font-weight: 600; }
        .bg-header-purple { background-color: #7030A0 !important; color: #fff !important; font-weight: 600; }
        .bg-header-red    { background-color: #C00000 !important; color: #fff !important; font-weight: 600; }
        .bg-header-teal   { background-color: #00B0A0 !important; color: #fff !important; font-weight: 600; }

        /* Baris total */
        .bg-total-row {
            background-color: #BDD7EE !important;
        }

        /* Sticky header */
        #tabelRingkasan thead tr th {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        /* Hover effect */
        #tabelRingkasan tbody tr:hover {
            background-color: rgba(68, 114, 196, 0.08) !important;
        }
    </style>
</div>
