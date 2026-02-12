<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Kode Rekening</h5>
            <div class="d-flex gap-2">
                <!-- Import Button -->
                <button type="button" class="btn btn-success btn-sm" wire:click="toggleImportModal">
                    <i class="bx bx-upload"></i> Import Excel
                </button>
                {{-- Tombol Tambah - Hide untuk Viewer --}}
                @can('create-kode-rekening')
                <a href="{{ route('kode-rekening.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i> Tambah Kode Rekening
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">PENCARIAN</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari kode/nama..." 
                               wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">LEVEL</label>
                    <select class="form-select" wire:model.live="levelFilter">
                        <option value="">Semua Level</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}">Level {{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">STATUS</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">BERLAKU MULAI</label>
                    <select class="form-select" wire:model.live="berlakuMulaiFilter">
                        <option value="">Semua Tahun</option>
                        @foreach($berlakuMulaiYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="form-label">TAMPILKAN</label>
                    <select class="form-select" wire:model.live="perPage">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }} baris</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                        <i class="bx bx-reset"></i> Reset
                    </button>
                </div>
            </div>
            
            <!-- Info Messages -->
            @if($search || $levelFilter || $statusFilter)
                <div class="alert alert-info py-2 mb-3">
                    <i class="bx bx-filter"></i> Filter aktif: 
                    @if($search) Pencarian: "{{ $search }}" @endif
                    @if($levelFilter) | Level: {{ $levelFilter }} @endif
                    @if($statusFilter !== '') | Status: {{ $statusFilter == 1 ? 'Aktif' : 'Tidak Aktif' }} @endif
                </div>
            @endif
            
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
            
            @if(session()->has('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session()->has('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <!-- Loading indicator -->
            <div wire:loading wire:target="search, levelFilter, statusFilter, perPage, gotoPage, nextPage, previousPage" class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Memuat data...</div>
                </div>
            </div>
            
            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">NO</th>
                            <th width="15%">KODE</th>
                            <th width="35%">NAMA</th>
                            <th width="8%" class="text-center">LEVEL</th>
                            <th width="10%" class="text-center">BERLAKU MULAI</th>
                            <th width="8%" class="text-center">STATUS</th>
                            {{-- Kolom Aksi - Hide untuk Viewer --}}
                            @canany(['edit-kode-rekening', 'delete-kode-rekening'])
                            <th width="15%" class="text-center">AKSI</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kodeRekening as $kode)
                        <tr class="level-{{ $kode->level }}">
                            <td class="text-center">{{ ($kodeRekening->currentPage() - 1) * $kodeRekening->perPage() + $loop->iteration }}</td>
                            <td>
                                <code class="text-primary">{{ $kode->kode }}</code>
                            </td>
                            <td>
                                <div style="padding-left: {{ ($kode->level - 1) * 15 }}px;">
                                    {{ $kode->nama }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $kode->level == 1 ? 'primary' : ($kode->level == 2 ? 'info' : ($kode->level == 3 ? 'success' : ($kode->level == 4 ? 'warning' : ($kode->level == 5 ? 'danger' : 'secondary')))) }}">
                                    LEVEL {{ $kode->level }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-outline-dark">{{ $kode->berlaku_mulai ?? '-' }}</span>
                            </td>
                            <td class="text-center">
                                @if($kode->is_active)
                                    <span class="badge bg-success">AKTIF</span>
                                @else
                                    <span class="badge bg-secondary">TIDAK AKTIF</span>
                                @endif
                            </td>
                            {{-- Tombol Aksi - Hide untuk Viewer --}}
                            @canany(['edit-kode-rekening', 'delete-kode-rekening'])
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('edit-kode-rekening')
                                    <a href="{{ route('kode-rekening.edit', $kode->id) }}" class="btn btn-primary">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                    @endcan
                                    @can('delete-kode-rekening')
                                    <button type="button" class="btn btn-danger" 
                                            onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" 
                                            wire:click="delete({{ $kode->id }})" 
                                            wire:loading.attr="disabled">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
                                    @endcan
                                </div>
                            </td>
                            @endcanany
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canany(['edit-kode-rekening', 'delete-kode-rekening']) ? '7' : '6' }}" 
                                class="text-center py-4">
                                <i class="bx bx-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Tidak ada data kode rekening</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Info & Links -->
            <div class="row mt-3">
                <div class="col-md-6">
                    @if($kodeRekening->total() > 0)
                        <p class="text-muted mb-0">
                            Menampilkan {{ $kodeRekening->firstItem() }} - {{ $kodeRekening->lastItem() }} 
                            dari {{ $kodeRekening->total() }} data
                        </p>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end align-items-center">
                        <span class="text-muted me-3">
                            Showing {{ $kodeRekening->firstItem() ?? 0 }} to {{ $kodeRekening->lastItem() ?? 0 }} of {{ $kodeRekening->total() }} results
                        </span>
                        {{ $kodeRekening->onEachSide(1)->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Import Modal -->
    <div class="modal fade @if($showImportModal) show @endif" 
         tabindex="-1" 
         style="display: @if($showImportModal) block @else none @endif;" 
         aria-modal="true" 
         role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Kode Rekening dari Excel</h5>
                    <button type="button" class="btn-close" wire:click="toggleImportModal"></button>
                </div>
                <form wire:submit.prevent="import">
                    <div class="modal-body">
                        @if(count($importErrors) > 0)
                            <div class="alert alert-danger">
                                <h6 class="alert-heading">Error saat import:</h6>
                                <ul class="mb-0">
                                    @foreach($importErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="mb-3">
                            <label class="form-label">File Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('importFile') is-invalid @enderror" 
                                   wire:model="importFile" accept=".xlsx,.xls">
                            @error('importFile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            <div wire:loading wire:target="importFile" class="mt-2">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 100%">
                                        Uploading...
                                    </div>
                                </div>
                            </div>
                            
                            @if($importFile)
                                <small class="text-success mt-1 d-block">
                                    <i class="bx bx-check"></i> File siap: {{ $importFile->getClientOriginalName() }}
                                </small>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Berlaku Mulai (Tahun) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" wire:model="importBerlakuMulai"
                                   min="2020" max="2099" placeholder="Contoh: 2026">
                            <small class="text-muted">Tahun berlaku kode rekening yang diimport. Default: 2022 (kode lama)</small>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="bx bx-info-circle"></i> Format Excel yang dibutuhkan:</h6>
                            <hr>
                            <ul class="mb-0">
                                <li>Header harus <strong>huruf kecil</strong>: kode, nama, level</li>
                                <li>Kolom A: <strong>kode</strong> (contoh: 4, 4.1, 4.1.01)</li>
                                <li>Kolom B: <strong>nama</strong></li>
                                <li>Kolom C: <strong>level</strong> (1-6)</li>
                                <li>Kolom D (opsional): <strong>berlaku_mulai</strong> (tahun, contoh: 2026)</li>
                                <li>Semua kode akan diimport dengan status aktif</li>
                                <li>Parent harus ada sebelum child (dalam generasi yang sama)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" wire:click="downloadTemplate">
                            <i class="bx bx-download"></i> Download Template
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="toggleImportModal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" 
                                wire:loading.attr="disabled"
                                {{ !$importFile ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="import">
                                <i class="bx bx-upload"></i> Import
                            </span>
                            <span wire:loading wire:target="import">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @if($showImportModal)
        <div class="modal-backdrop fade show"></div>
    @endif
    
    <style>
        /* Level row colors */
        tr.level-1 { background-color: rgba(13, 110, 253, 0.05) !important; }
        tr.level-2 { background-color: rgba(13, 202, 240, 0.05) !important; }
        tr.level-3 { background-color: rgba(25, 135, 84, 0.05) !important; }
        tr.level-4 { background-color: rgba(255, 193, 7, 0.05) !important; }
        tr.level-5 { background-color: rgba(220, 53, 69, 0.05) !important; }
        tr.level-6 { background-color: rgba(108, 117, 125, 0.05) !important; }
        
        /* Badge styling to match reference */
        .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            font-weight: 600;
        }
        
        /* Table header styling */
        .table thead th {
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #495057;
        }
    </style>
</div>