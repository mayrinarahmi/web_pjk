<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Penerimaan</h5>
            <a href="{{ route('penerimaan.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Penerimaan
            </a>
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
                    <label for="kodeRekeningId" class="form-label">Kode Rekening</label>
                    <select wire:model="kodeRekeningId" id="kodeRekeningId" class="form-select">
                        <option value="">Semua Kode Rekening</option>
                        @foreach($kodeRekeningLevel4 as $kode)
                            <option value="{{ $kode->id }}">{{ $kode->kode }} - {{ $kode->nama }}</option>
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
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari..." wire:model="search">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-outline-secondary" wire:click="resetFilter">
                        <i class="bx bx-reset"></i> Reset Filter
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Tanggal</th>
                            <th width="15%">Kode Rekening</th>
                            <th>Uraian</th>
                            <th width="15%">Jumlah</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($penerimaan as $key => $p)
                        <tr>
                            <td>{{ $penerimaan->firstItem() + $key }}</td>
                            <td>{{ $p->tanggal->format('d-m-Y') }}</td>
                            <td>{{ $p->kodeRekening->kode }}</td>
                            <td>{{ $p->kodeRekening->nama }}</td>
                            <td class="text-end">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('penerimaan.edit', $p->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click="delete({{ $p->id }})" wire:loading.attr="disabled">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $penerimaan->links() }}
            </div>
        </div>
    </div>
</div>
