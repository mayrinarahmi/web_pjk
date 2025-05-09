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
                        <input type="text" class="form-control" placeholder="Cari..." wire:model.live.debounce.300ms="search">
                    </div>
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
            <div wire:loading wire:target="search, setActive, delete" class="mb-3">
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
                                <span class="badge bg-success">AKTIF</span>
                                @else
                                <span class="badge bg-secondary">TIDAK AKTIF</span>
                                @endif
                            </td>
                            <td>
                                <!-- Perbaikan: Menggunakan layout yang konsisten untuk tombol aksi -->
                                <div class="action-container">
                                    @if(!$ta->is_active)
                                    <div class="action-wrapper">
                                        <button type="button" class="btn btn-success btn-sm action-button" wire:click="setActive({{ $ta->id }})" wire:loading.attr="disabled">
                                            <i class="bx bx-check"></i> Aktifkan
                                        </button>
                                    </div>
                                    @else
                                    <div class="action-wrapper"></div> <!-- Placeholder untuk menjaga layout -->
                                    @endif
                                    
                                    <div class="action-wrapper">
                                        <a href="{{ route('tahun-anggaran.edit', $ta->id) }}" class="btn btn-primary btn-sm action-button">
                                            <i class="bx bx-edit"></i> Edit
                                        </a>
                                    </div>
                                    
                                    <div class="action-wrapper">
                                        <button type="button" class="btn btn-danger btn-sm action-button" 
                                            onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" 
                                            wire:click="delete({{ $ta->id }})" 
                                            wire:loading.attr="disabled">
                                            <i class="bx bx-trash"></i> Hapus
                                        </button>
                                    </div>
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

    <style>
    /* CSS untuk konsistensi tombol */
    .action-container {
        display: flex;
        gap: 8px;
        justify-content: flex-start;
    }
    
    .action-wrapper {
        width: 100px;
        min-width: 100px;
    }
    
    .action-button {
        width: 100%;
    }
    
    /* Memastikan tombol memiliki tinggi yang sama */
    .btn-sm {
        height: 31px;
        padding-top: 4px;
        padding-bottom: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Pastikan badge status memiliki lebar yang konsisten */
    .badge-fixed-width {
        min-width: 80px;
        display: inline-block;
        text-align: center;
    }
    </style>
</div>