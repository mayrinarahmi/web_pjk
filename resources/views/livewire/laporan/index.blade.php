<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Laporan Realisasi Penerimaan</h5>
            <div>
                <button class="btn btn-success btn-sm me-1" wire:click="exportExcel">
                    <i class="bx bx-file"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm" wire:click="exportPdf">
                    <i class="bx bx-file-pdf"></i> Export PDF
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="tahunAnggaranId" class="form-label">Tahun Anggaran</label>
                    <select wire:model="tahunAnggaranId" id="tahunAnggaranId" class="form-select">
                        <option value="">Pilih Tahun Anggaran</option>
                        @foreach($tahunAnggaran as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tanggalMulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" wire:model="tanggalMulai" id="tanggalMulai" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="tanggalSelesai" class="form-label">Tanggal Selesai</label>
                    <input type="date" wire:model="tanggalSelesai" id="tanggalSelesai" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="persentaseTarget" class="form-label">Persentase Target</label>
                    <div class="input-group">
                        <input type="number" wire:model="persentaseTarget" id="persentaseTarget" class="form-control" min="1" max="100" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('bulanan')">Bulanan</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan1')">Triwulan I</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan2')">Triwulan II</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan3')">Triwulan III</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan4')">Triwulan IV</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('tahunan')">Tahunan</button>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle">Kode Rekening</th>
                            <th rowspan="2" class="align-middle">Uraian</th>
                            <th rowspan="2" class="align-middle text-center">%</th>
                            <th rowspan="2" class="align-middle text-center">Pagu Anggaran</th>
                            <th rowspan="2" class="align-middle text-center">Target {{ $persentaseTarget }}%</th>
                            <th rowspan="2" class="align-middle text-center">Kurang dr Target {{ $persentaseTarget }}%</th>
                            <th rowspan="2" class="align-middle text-center">Penerimaan per Rincian Objek</th>
                            <th colspan="{{ count(array_filter($data[0]['penerimaan_per_bulan'] ?? [], function($value, $key) { return $key <= Carbon\Carbon::parse($tanggalSelesai)->month; }, ARRAY_FILTER_USE_BOTH)) }}" class="text-center">Rincian Penerimaan</th>
                        </tr>
                        <tr>
                            @for($i = 1; $i <= Carbon\Carbon::parse($tanggalSelesai)->month; $i++)
                                <th class="text-center">{{ Carbon\Carbon::create()->month($i)->format('F') }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $item)
                            <tr>
                                <td>{{ $item['kode'] }}</td>
                                <td class="ps-{{ $item['level'] }}">{{ $item['uraian'] }}</td>
                                <td class="text-end">{{ number_format($item['persentase'], 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['target_anggaran'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['target_sd_bulan_ini'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['kurang_dari_target'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($item['realisasi_sd_bulan_ini'], 0, ',', '.') }}</td>
                                @for($i = 1; $i <= Carbon\Carbon::parse($tanggalSelesai)->month; $i++)
                                    <td class="text-end">{{ number_format($item['penerimaan_per_bulan'][$i], 0, ',', '.') }}</td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
