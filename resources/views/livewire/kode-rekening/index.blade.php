<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Kode Rekening</h5>
            <a href="{{ route('kode-rekening.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Kode Rekening
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari kode atau nama..." wire:model="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model="level">
                        <option value="">Semua Level</option>
                        <option value="1">Level 1</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5</option>
                        <option value="6">Level 6</option>
                    </select>
                </div>
                <div class="col-md-5 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetFilters">
                        <i class="bx bx-reset"></i> Reset Filter
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Kode</th>
                            <th>Nama</th>
                            <th width="10%">Level</th>
                            <th width="10%">Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kodeRekening as $key => $kode)
                        <tr>
                            <td>{{ $kodeRekening->firstItem() + $key }}</td>
                            <td>{{ $kode->kode }}</td>
                            <td>{{ $kode->nama }}</td>
                            <td>{{ $kode->level }}</td>
                            <td>
                                @if($kode->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('kode-rekening.edit', $kode->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click="delete({{ $kode->id }})" wire:loading.attr="disabled">
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
                {{ $kodeRekening->links() }}
            </div>
        </div>
    </div>
</div>
