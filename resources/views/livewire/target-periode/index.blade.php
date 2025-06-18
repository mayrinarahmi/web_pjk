<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Target Periode</h5>
            <a href="{{ route('target-periode.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Target Periode
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="tahunAnggaranId" class="form-label">Tahun Anggaran</label>
                    <!-- PERBAIKAN: Tambah .live untuk reactive update -->
                    <select wire:model.live="tahunAnggaranId" id="tahunAnggaranId" class="form-select">
                        <option value="">Pilih Tahun Anggaran</option>
                        @foreach($tahunAnggaran as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <div class="alert {{ $this->totalPersentase == 100 ? 'alert-success' : 'alert-warning' }} mt-4">
                        <div class="d-flex align-items-center">
                            <i class="bx {{ $this->totalPersentase == 100 ? 'bx-check-circle' : 'bx-error' }} me-2"></i>
                            <div>Total Persentase: <strong>{{ $this->totalPersentase }}%</strong> {{ $this->totalPersentase == 100 ? '(Valid)' : '(Belum 100%)' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Periode</th>
                            <th>Bulan</th>
                            <th width="15%">Persentase</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($targetPeriode as $key => $target)
                        <tr>
                            <td>{{ $targetPeriode->firstItem() + $key }}</td>
                            <td>{{ $target->nama_periode }}</td>
                            <td>
                                @php
                                    $daftarBulan = [
                                        1 => 'Januari',
                                        2 => 'Februari',
                                        3 => 'Maret',
                                        4 => 'April',
                                        5 => 'Mei',
                                        6 => 'Juni',
                                        7 => 'Juli',
                                        8 => 'Agustus',
                                        9 => 'September',
                                        10 => 'Oktober',
                                        11 => 'November',
                                        12 => 'Desember'
                                    ];
                                    
                                    $bulanText = [];
                                    for ($i = $target->bulan_awal; $i <= $target->bulan_akhir; $i++) {
                                        $bulanText[] = $daftarBulan[$i];
                                    }
                                @endphp
                                {{ implode(', ', $bulanText) }}
                            </td>
                            <td>{{ $target->persentase }}%</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('target-periode.edit', $target->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click="delete({{ $target->id }})" wire:loading.attr="disabled">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $targetPeriode->links() }}
            </div>
        </div>
    </div>
</div>