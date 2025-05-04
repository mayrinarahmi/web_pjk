<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Tahun Anggaran</h5>
            <a href="{{ route('tahun-anggaran.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Tahun Anggaran
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari..." wire:model="search">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Tahun</th>
                            <th width="15%">Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tahunAnggaran as $key => $ta)
                        <tr>
                            <td>{{ $tahunAnggaran->firstItem() + $key }}</td>
                            <td>{{ $ta->tahun }}</td>
                            <td>
                                @if($ta->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @if(!$ta->is_active)
                                    <button type="button" class="btn btn-success btn-sm" wire:click="setActive({{ $ta->id }})" wire:loading.attr="disabled">
                                        <i class="bx bx-check"></i> Aktifkan
                                    </button>
                                    @endif
                                    <a href="{{ route('tahun-anggaran.edit', $ta->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click="delete({{ $ta->id }})" wire:loading.attr="disabled">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $tahunAnggaran->links() }}
            </div>
        </div>
    </div>
</div>
