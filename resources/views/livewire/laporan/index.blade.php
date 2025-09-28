<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Laporan Realisasi Penerimaan</h5>
            <div>
                <button class="btn btn-success btn-sm me-1" wire:click="exportExcel" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportExcel">
                        <i class="bx bx-file"></i> Export Excel
                    </span>
                    <span wire:loading wire:target="exportExcel">
                        <span class="spinner-border spinner-border-sm me-1"></span> Processing...
                    </span>
                </button>
                <button class="btn btn-danger btn-sm" wire:click="exportPdf" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="exportPdf">
                        <i class="bx bx-file-pdf"></i> Export PDF
                    </span>
                    <span wire:loading wire:target="exportPdf">
                        <span class="spinner-border spinner-border-sm me-1"></span> Processing...
                    </span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="tahunAnggaranId" class="form-label">Tahun Anggaran</label>
                    <select wire:model.live="tahunAnggaranId" id="tahunAnggaranId" class="form-select">
                        <option value="">Pilih Tahun Anggaran</option>
                        @foreach($tahunAnggaran as $ta)
                            <option value="{{ $ta->id }}">
                                {{ $ta->tahun }} - {{ $ta->jenis_anggaran == 'murni' ? 'MURNI' : 'PERUBAHAN' }}
                                {{ $ta->is_active ? '(AKTIF)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tanggalMulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" wire:model.live="tanggalMulai" id="tanggalMulai" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="tanggalSelesai" class="form-label">Tanggal Selesai</label>
                    <input type="date" wire:model.live="tanggalSelesai" id="tanggalSelesai" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="persentaseTarget" class="form-label">Persentase Target</label>
                    <div class="input-group">
                        <input type="number" wire:model.live="persentaseTarget" id="persentaseTarget" class="form-control" min="1" max="100" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
            
            <!-- Mode Tampilan -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Mode Tampilan:</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="viewMode" id="specificMode" 
                               wire:click="setViewMode('specific')" {{ $viewMode == 'specific' ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="specificMode">Hanya Triwulan Terpilih</label>
                        
                        <input type="radio" class="btn-check" name="viewMode" id="cumulativeMode" 
                               wire:click="setViewMode('cumulative')" {{ $viewMode == 'cumulative' ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="cumulativeMode">Kumulatif s/d Triwulan</label>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm {{ $tipeFilter == 'triwulan1' ? 'active' : '' }}" 
                                wire:click="setFilter('triwulan1')">Triwulan I</button>
                        <button type="button" class="btn btn-outline-primary btn-sm {{ $tipeFilter == 'triwulan2' ? 'active' : '' }}" 
                                wire:click="setFilter('triwulan2')">Triwulan II</button>
                        <button type="button" class="btn btn-outline-primary btn-sm {{ $tipeFilter == 'triwulan3' ? 'active' : '' }}" 
                                wire:click="setFilter('triwulan3')">Triwulan III</button>
                        <button type="button" class="btn btn-outline-primary btn-sm {{ $tipeFilter == 'triwulan4' ? 'active' : '' }}" 
                                wire:click="setFilter('triwulan4')">Triwulan IV</button>
                    </div>
                </div>
            </div>
            
            <!-- Per Page Selector dan Info -->
            <div class="row mb-3">
                <div class="col-md-6">
                    @if($data->total() > 0)
                        <div class="text-muted">
                            Menampilkan {{ $data->firstItem() }} - {{ $data->lastItem() }} dari {{ $data->total() }} baris data
                        </div>
                    @endif
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-flex align-items-center">
                        <label class="me-2">Tampilkan:</label>
                        <select class="form-select form-select-sm" style="width: auto;" wire:model.live="perPage">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        <span class="ms-2">data per halaman</span>
                    </div>
                </div>
            </div>
            
            <!-- Loading indicator -->
            <div wire:loading wire:target="tahunAnggaranId, tanggalMulai, tanggalSelesai, persentaseTarget, setViewMode, setFilter, perPage, gotoPage, nextPage, previousPage" class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Memuat data laporan...</div>
                </div>
            </div>
            
            @php
                $bulanAwal = $bulanAwal ?? 1;
                $bulanAkhir = $bulanAkhir ?? 12;
                $tampilkanDariBulan = $viewMode === 'specific' ? $bulanAwal : 1;
                $tampilkanSampaiBulan = $bulanAkhir;
                $jumlahKolomBulan = $tampilkanSampaiBulan - $tampilkanDariBulan + 1;
            @endphp
            
            <div class="table-responsive">
                @if($data->count() > 0)
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th rowspan="2" class="align-middle">Kode Rekening</th>
                            <th rowspan="2" class="align-middle">Uraian</th>
                            <th rowspan="2" class="align-middle text-center">%</th>
                            <th rowspan="2" class="align-middle text-center">Pagu Anggaran</th>
                            <th rowspan="2" class="align-middle text-center">Target {{ $persentaseTarget }}%</th>
                            <th rowspan="2" class="align-middle text-center">Kurang dr Target {{ $persentaseTarget }}%</th>
                            <th rowspan="2" class="align-middle text-center">Penerimaan per Rincian Objek</th>
                            @if($jumlahKolomBulan > 0)
                                <th colspan="{{ $jumlahKolomBulan }}" class="text-center">Rincian Penerimaan</th>
                            @endif
                        </tr>
                        <tr>
                            @for($i = $tampilkanDariBulan; $i <= $tampilkanSampaiBulan; $i++)
                                <th class="text-center">{{ Carbon\Carbon::create()->month($i)->format('M') }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $item)
                            @php
                                $levelClass = '';
                                switch($item['level']) {
                                    case 1: $levelClass = 'table-primary fw-bold'; break;
                                    case 2: $levelClass = 'table-info'; break;
                                    case 3: $levelClass = 'table-light'; break;
                                    case 4: $levelClass = 'table-warning'; break;
                                    case 5: $levelClass = 'table-success'; break;
                                    case 6: $levelClass = ''; break;
                                }
                            @endphp
                            <tr class="{{ $levelClass }}">
                                <td>{{ $item['kode'] }}</td>
                                <td style="padding-left: {{ ($item['level'] - 1) * 20 }}px;">
                                    {{ $item['uraian'] }}
                                </td>
                                <td class="text-end">{{ number_format($item['persentase'], 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['target_anggaran'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['target_sd_bulan_ini'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['kurang_dari_target'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['realisasi_sd_bulan_ini'], 0, ',', '.') }}</td>
                                @for($i = $tampilkanDariBulan; $i <= $tampilkanSampaiBulan; $i++)
                                    <td class="text-end">{{ number_format($item['penerimaan_per_bulan'][$i] ?? 0, 0, ',', '.') }}</td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <!-- Pagination Links -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        @if($data->total() > 0)
                            <p class="text-muted mb-0">
                                Menampilkan {{ $data->firstItem() }} - {{ $data->lastItem() }} dari {{ $data->total() }} baris
                                <br>
                                <small class="text-info">
                                    <i class="bx bx-info-circle"></i> Export Excel/PDF akan mengekspor seluruh data, bukan hanya halaman ini
                                </small>
                            </p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end">
                            {{ $data->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
                
                @else
                <div class="alert alert-info">
                    <i class="bx bx-info-circle"></i> Tidak ada data untuk ditampilkan. Silakan pilih tahun anggaran dan tentukan periode laporan.
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- CSS untuk tabel level -->
    <style>
        .table-primary { background-color: rgba(13, 110, 253, 0.1) !important; }
        .table-info { background-color: rgba(13, 202, 240, 0.1) !important; }
        .table-light { background-color: rgba(248, 249, 250, 0.5) !important; }
        .table-warning { background-color: rgba(255, 193, 7, 0.1) !important; }
        .table-success { background-color: rgba(25, 135, 84, 0.1) !important; }
        
        /* Sticky header saat scroll */
        .table thead.sticky-top th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }
        
        /* Hover effect */
        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05) !important;
            cursor: pointer;
        }
        
        /* Loading overlay */
        div[wire\:loading] {
            position: relative;
        }
    </style>
</div>