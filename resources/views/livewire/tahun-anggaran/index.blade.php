<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Tahun Anggaran</h5>
            {{-- Tombol Tambah - Hide untuk Viewer --}}
            @can('create-tahun-anggaran')
            <a href="{{ route('tahun-anggaran.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah APBD Murni
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari tahun..." wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterJenis">
                        <option value="">Semua Jenis</option>
                        <option value="murni">APBD Murni</option>
                        <option value="perubahan">APBD Perubahan</option>
                    </select>
                </div>
            </div>
            
            <!-- Feedback message -->
            @if(session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <!-- Loading indicator -->
            <div wire:loading wire:target="search, setActive, delete, createPerubahan, filterJenis" class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Memuat data...</div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Tahun</th>
                            <th width="15%">Jenis</th>
                            <th width="15%">Tanggal Penetapan</th>
                            <th width="20%">Keterangan</th>
                            <th width="10%">Status</th>
                            {{-- Kolom Aksi - Hide untuk Viewer --}}
                            @canany(['edit-tahun-anggaran', 'delete-tahun-anggaran', 'create-tahun-anggaran'])
                            <th width="25%">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tahunAnggaran as $key => $ta)
                        <tr>
                            <td>{{ $tahunAnggaran->firstItem() + $key }}</td>
                            <td>{{ $ta->tahun }}</td>
                            <td>
                                @if($ta->jenis_anggaran == 'murni')
                                    <span class="badge bg-primary">APBD MURNI</span>
                                @else
                                    <span class="badge bg-warning">APBD PERUBAHAN</span>
                                    @if($ta->parent)
                                        <br><small class="text-muted">dari: {{ $ta->parent->tahun }} Murni</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                {{ $ta->tanggal_penetapan ? $ta->tanggal_penetapan->format('d-m-Y') : '-' }}
                            </td>
                            <td>{{ $ta->keterangan ?? '-' }}</td>
                            <td>
                                @if($ta->is_active)
                                    <span class="badge bg-success">AKTIF</span>
                                @else
                                    <span class="badge bg-secondary">TIDAK AKTIF</span>
                                @endif
                            </td>
                            {{-- Tombol Aksi - Hide untuk Viewer --}}
                            @canany(['edit-tahun-anggaran', 'delete-tahun-anggaran', 'create-tahun-anggaran'])
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    {{-- Tombol Aktifkan - hanya untuk yang punya permission edit --}}
                                    @can('edit-tahun-anggaran')
                                        @if(!$ta->is_active)
                                            <button type="button" class="btn btn-success" 
                                                wire:click="setActive({{ $ta->id }})" 
                                                wire:loading.attr="disabled"
                                                title="Aktifkan">
                                                <i class="bx bx-check"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    
                                    {{-- Tombol Buat Perubahan - hanya untuk yang punya permission create --}}
                                    @can('create-tahun-anggaran')
                                        @if($ta->jenis_anggaran == 'murni' && !$ta->perubahan->count())
                                            <button type="button" class="btn btn-warning" 
                                                wire:click="createPerubahan({{ $ta->id }})"
                                                wire:loading.attr="disabled"
                                                onclick="confirm('Apakah Anda yakin ingin membuat APBD Perubahan untuk tahun {{ $ta->tahun }}? Semua target anggaran akan dicopy.') || event.stopImmediatePropagation()"
                                                title="Buat APBD Perubahan">
                                                <i class="bx bx-copy"></i> Perubahan
                                            </button>
                                        @endif
                                    @endcan
                                    
                                    {{-- Tombol Edit --}}
                                    @can('edit-tahun-anggaran')
                                    <a href="{{ route('tahun-anggaran.edit', $ta->id) }}" 
                                        class="btn btn-primary"
                                        title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    @endcan
                                    
                                    {{-- Tombol Hapus --}}
                                    @can('delete-tahun-anggaran')
                                    <button type="button" class="btn btn-danger" 
                                        onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" 
                                        wire:click="delete({{ $ta->id }})" 
                                        wire:loading.attr="disabled"
                                        title="Hapus">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                            @endcanany
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canany(['edit-tahun-anggaran', 'delete-tahun-anggaran', 'create-tahun-anggaran']) ? '7' : '6' }}" class="text-center">Tidak ada data</td>
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