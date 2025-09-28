<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Target Anggaran</h5>
            {{-- Tombol Actions --}}
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" wire:click="toggleImportModal">
                    <i class="bx bx-upload"></i> Import Excel
                </button>
                @can('create-target')
                <a href="{{ route('target-anggaran.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i> Tambah Target
                </a>
                @endcan
                @can('edit-target')
                <button type="button" class="btn btn-info btn-sm" wire:click="updateHierarchi" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateHierarchi">
                        <i class="bx bx-refresh"></i> Update Hierarki
                    </span>
                    <span wire:loading wire:target="updateHierarchi">
                        <span class="spinner-border spinner-border-sm me-1"></span> Updating...
                    </span>
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <!-- Info tentang tahun anggaran aktif -->
            @php
                $tahunAktif = $tahunAnggaran->where('is_active', true)->first();
            @endphp
            @if($tahunAktif)
                <div class="alert alert-info mb-3">
                    <i class="bx bx-info-circle"></i> 
                    Tahun Anggaran Aktif: <strong>{{ $tahunAktif->display_name }}</strong>
                </div>
            @endif
            
            <!-- Feedback messages -->
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
            
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="tahunAnggaranId" class="form-label">Tahun Anggaran</label>
                    <select class="form-select" id="tahunAnggaranId" wire:model.live="tahunAnggaranId">
                        <option value="">Pilih Tahun Anggaran</option>
                        @foreach($tahunAnggaran as $ta)
                            <option value="{{ $ta->id }}">
                                {{ $ta->tahun }} - {{ $ta->jenis_anggaran == 'murni' ? 'MURNI' : 'PERUBAHAN' }}
                                {{ $ta->is_active ? '(AKTIF)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari kode atau nama rekening..." 
                               wire:model.live.debounce.500ms="search">
                        <button class="btn btn-outline-primary" type="button" wire:click="performSearch">
                            Cari
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter Level</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn {{ $showLevel1 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(1)">1</button>
                        <button type="button" class="btn {{ $showLevel2 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(2)">2</button>
                        <button type="button" class="btn {{ $showLevel3 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(3)">3</button>
                        <button type="button" class="btn {{ $showLevel4 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(4)">4</button>
                        <button type="button" class="btn {{ $showLevel5 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(5)">5</button>
                        <button type="button" class="btn {{ $showLevel6 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(6)">6</button>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetFilters">
                        <i class="bx bx-reset"></i> Reset Filter
                    </button>
                </div>
            </div>
            
            <!-- Info Hierarki -->
            @if($tahunAnggaranId)
                <div class="alert alert-light border mb-3">
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="mb-2"><i class="bx bx-info-circle"></i> Informasi Hierarki Target Anggaran:</h6>
                            <ul class="mb-0">
                                <li><strong>Level 6:</strong> Input manual (kode rekening detail)</li>
                                <li><strong>Level 1-5:</strong> Otomatis dihitung dari SUM children</li>
                                <li><span class="badge bg-success">✓</span> Konsisten - <span class="badge bg-warning">⚠</span> Tidak konsisten</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Loading indicator -->
            <div wire:loading wire:target="search, tahunAnggaranId, toggleLevel, updateHierarchi, performSearch" class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Memuat data...</div>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">Kode</th>
                            <th width="40%">Nama Rekening</th>
                            <th width="10%">Level</th>
                            <th width="20%">Pagu Anggaran</th>
                            <th width="5%">Status</th>
                            {{-- Kolom Aksi - Hide untuk Viewer --}}
                            @canany(['edit-target', 'create-target'])
                            <th width="10%">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kodeRekening as $kr)
                        <tr class="level-{{ $kr->level }} {{ $kr->level == 1 ? 'table-primary' : ($kr->level == 2 ? 'table-info' : ($kr->level == 3 ? 'table-light' : ($kr->level == 4 ? 'table-warning' : ($kr->level == 5 ? 'table-success' : 'table-secondary')))) }}">
                            <td>
                                <code>{{ $kr->kode }}</code>
                            </td>
                            <td>
                                <div style="padding-left: {{ ($kr->level - 1) * 15 }}px;">
                                    {{ $kr->nama }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $kr->level }}</span>
                            </td>
                            <td class="text-end">
                                @if(isset($kr->manual_target))
                                    <strong>Rp {{ number_format($kr->manual_target, 0, ',', '.') }}</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tahunAnggaranId)
                                    @if($kr->level == 6)
                                        <span class="badge bg-primary" title="Input Manual">M</span>
                                    @else
                                        @if(isset($kr->is_consistent))
                                            @if($kr->is_consistent)
                                                <span class="badge bg-success" title="Konsisten">✓</span>
                                            @else
                                                <span class="badge bg-warning" title="Tidak Konsisten">⚠</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary" title="Tidak Ada Data">-</span>
                                        @endif
                                    @endif
                                @endif
                            </td>
                            {{-- Tombol Aksi - Hide untuk Viewer --}}
                            @canany(['edit-target', 'create-target'])
                            <td>
                                @if($kr->level == 6 && $tahunAnggaranId)
                                    @php
                                        $targetAnggaranObj = App\Models\TargetAnggaran::where('kode_rekening_id', $kr->id)
                                            ->where('tahun_anggaran_id', $tahunAnggaranId)
                                            ->first();
                                    @endphp
                                    
                                    @if($targetAnggaranObj)
                                        @can('edit-target')
                                        <a href="{{ route('target-anggaran.edit', $targetAnggaranObj->id) }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        @endcan
                                    @else
                                        @can('create-target')
                                        <a href="{{ route('target-anggaran.create') }}?kode_rekening_id={{ $kr->id }}" class="btn btn-success btn-sm">
                                            <i class="bx bx-plus"></i>
                                        </a>
                                        @endcan
                                    @endif
                                @elseif($kr->level < 6)
                                    <span class="badge bg-info" title="Otomatis dari children">Auto</span>
                                @endif
                            </td>
                            @endcanany
                        </tr>
                        @empty
                        <tr class="no-data-row">
                            <td colspan="{{ auth()->user()->canany(['edit-target', 'create-target']) ? '6' : '5' }}" class="text-center">
                                @if($tahunAnggaranId)
                                    Tidak ada data untuk filter yang dipilih.
                                @else
                                    Pilih tahun anggaran untuk melihat data.
                                @endif
                            </td>
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
    
    <!-- Legend -->
    <div class="card mt-3">
        <div class="card-body">
            <h6>Keterangan Warna Level:</h6>
            <div class="row">
                <div class="col-md-2"><span class="badge bg-primary">Level 1</span> Utama</div>
                <div class="col-md-2"><span class="badge bg-info">Level 2</span> Kelompok</div>
                <div class="col-md-2"><span class="badge bg-light text-dark">Level 3</span> Jenis</div>
                <div class="col-md-2"><span class="badge bg-warning">Level 4</span> Objek</div>
                <div class="col-md-2"><span class="badge bg-success">Level 5</span> Rincian Objek</div>
                <div class="col-md-2"><span class="badge bg-secondary">Level 6</span> Sub Rincian</div>
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
                    <h5 class="modal-title">Import Pagu Anggaran dari Excel</h5>
                    <button type="button" class="btn-close" wire:click="toggleImportModal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="import">
                    <div class="modal-body">
                        {{-- Error display --}}
                        @if(count($importErrors) > 0)
                            <div class="alert alert-danger">
                                <h6>Error saat import:</h6>
                                <ul class="mb-0">
                                    @foreach($importErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="importTahunAnggaranId" class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                                <select class="form-select @error('tahunAnggaranId') is-invalid @enderror" 
                                        wire:model="tahunAnggaranId" disabled>
                                    <option value="">Pilih Tahun Anggaran</option>
                                    @foreach($tahunAnggaran as $ta)
                                        <option value="{{ $ta->id }}">
                                            {{ $ta->tahun }} - {{ strtoupper($ta->jenis_anggaran) }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($tahunAnggaranId)
                                    @php
                                        $selectedTahun = \App\Models\TahunAnggaran::find($tahunAnggaranId);
                                    @endphp
                                    <small class="text-muted">
                                        Import akan dilakukan untuk: {{ $selectedTahun->tahun }} - {{ strtoupper($selectedTahun->jenis_anggaran) }}
                                    </small>
                                @else
                                    <small class="text-danger">Pilih tahun anggaran di filter terlebih dahulu!</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="importFile" class="form-label">File Excel <span class="text-danger">*</span></label>
                                <input type="file" 
                                       class="form-control @error('importFile') is-invalid @enderror" 
                                       wire:model="importFile" 
                                       accept=".xlsx,.xls"
                                       @if(!$tahunAnggaranId) disabled @endif>
                                @error('importFile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                <div wire:loading wire:target="importFile" class="mt-2">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" 
                                             style="width: 100%">
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
                        </div>
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="bx bx-info-circle"></i> Petunjuk Import</h6>
                            <hr>
                            <ul class="mb-0">
                                <li>Download template Excel terlebih dahulu</li>
                                <li>Kolom yang wajib diisi: <strong>kode</strong> dan <strong>pagu_anggaran</strong></li>
                                <li>Kode rekening harus sudah terdaftar di sistem</li>
                                <li>Format angka bisa dengan atau tanpa "Rp" dan pemisah ribuan</li>
                                <li>Import hanya untuk kode rekening level 6</li>
                                <li>Hierarki level 1-5 akan otomatis dihitung ulang setelah import</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Format Template:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>kode</th>
                                            <th>pagu_anggaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">
                                                <em>Template kosong - isi sesuai data yang akan diimport</em>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">
                                * Header harus <strong>huruf kecil</strong><br>
                                * Hanya kode level 6 yang diimport, level 1-5 akan dihitung otomatis
                            </small>
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
                                wire:target="import"
                                {{ !$tahunAnggaranId || !$importFile ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="import">
                                <i class="bx bx-upload"></i> Import
                            </span>
                            <span wire:loading wire:target="import">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Backdrop -->
    @if($showImportModal)
        <div class="modal-backdrop fade show"></div>
    @endif
    
    <!-- Styles -->
    <style>
        .table-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
        }
        .table-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        .table-info {
            background-color: rgba(13, 202, 240, 0.1) !important;
        }
        .table-primary {
            background-color: rgba(13, 110, 253, 0.1) !important;
        }
        .table-secondary {
            background-color: rgba(108, 117, 125, 0.1) !important;
        }
        
        /* Modal animations */
        .modal.show {
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</div>