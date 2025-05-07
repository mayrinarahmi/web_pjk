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
            <!-- <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('mingguan')">Minggu Ini</button>
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('minggu_lalu')">Minggu Lalu</button>
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('bulanan')">Bulanan</button> -->
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan1')">Triwulan I</button>
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan2')">Triwulan II</button>
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan3')">Triwulan III</button>
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('triwulan4')">Triwulan IV</button>
            <!-- <button type="button" class="btn btn-outline-primary btn-sm" wire:click="setFilter('tahunan')">Tahunan</button> -->
        </div>
    </div>
</div>

<!-- Form Filter Kustom -->
<!-- <div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Filter Kustom</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="customTanggalMulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" id="customTanggalMulai" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="customTanggalSelesai" class="form-label">Tanggal Selesai</label>
                        <input type="date" id="customTanggalSelesai" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" onclick="applyCustomFilter()">Terapkan Filter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->

<script>
    function applyCustomFilter() {
        const tanggalMulai = document.getElementById('customTanggalMulai').value;
        const tanggalSelesai = document.getElementById('customTanggalSelesai').value;
        
        if (tanggalMulai && tanggalSelesai) {
            @this.setCustomFilter('custom', tanggalMulai, tanggalSelesai);
        } else {
            alert('Silakan pilih tanggal mulai dan tanggal selesai');
        }
    }
</script>
            <!-- <div class="row mb-3">
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
            </div> -->
            
            @php
                // Definisikan variabel default untuk menghindari error
                $tanggalAkhir = $tanggalSelesai ?? now()->format('Y-m-d');
                $bulanAkhir = Carbon\Carbon::parse($tanggalAkhir)->month;
                
                // Hitung jumlah kolom bulan jika data tersedia
                $jumlahKolomBulan = 0;
                if (!empty($data) && isset($data[0]['penerimaan_per_bulan'])) {
                    $jumlahKolomBulan = count(array_filter($data[0]['penerimaan_per_bulan'], 
                        function($value, $key) use ($bulanAkhir) { 
                            return $key <= $bulanAkhir; 
                        }, 
                        ARRAY_FILTER_USE_BOTH
                    ));
                }
            @endphp
            
            <div class="table-responsive">
                @if(!empty($data))
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
                            @if($jumlahKolomBulan > 0)
                                <th colspan="{{ $jumlahKolomBulan }}" class="text-center">Rincian Penerimaan</th>
                            @endif
                        </tr>
                        <tr>
                            @for($i = 1; $i <= $bulanAkhir; $i++)
                                <th class="text-center">{{ Carbon\Carbon::create()->month($i)->format('M') }}</th>
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
                                @for($i = 1; $i <= $bulanAkhir; $i++)
                                    <td class="text-end">{{ number_format($item['penerimaan_per_bulan'][$i] ?? 0, 0, ',', '.') }}</td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="alert alert-info">
                    Tidak ada data untuk ditampilkan. Silakan pilih tahun anggaran dan tentukan periode laporan.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>