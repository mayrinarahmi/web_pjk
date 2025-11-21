<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Target Anggaran (Pagu Anggaran)</h5>
            {{-- Tombol Actions --}}
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" wire:click="openImportModal">
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
                
                {{-- Tombol Hapus Semua Data --}}
                @if(auth()->user()->isSuperAdmin())
                <button type="button" 
                        class="btn btn-danger btn-sm"
                        onclick="confirmDeleteAllPagu()"
                        title="Hapus Semua Data Pagu Anggaran">
                    <i class="bx bx-trash"></i> Hapus Semua Data
                </button>
                @endif
            </div>
        </div>
        
        <div class="card-body">
            {{-- ================================================ --}}
            {{-- USER INFO & SKPD BADGE --}}
            {{-- ================================================ --}}
            @if($userSkpdInfo)
            <div class="alert {{ auth()->user()->skpd_id && !auth()->user()->canViewAllSkpd() ? 'alert-warning' : 'alert-info' }} py-2 mb-3">
                <i class="bx bx-info-circle"></i> {{ $userSkpdInfo }}
                
                @if(!auth()->user()->canViewAllSkpd() && auth()->user()->skpd)
                    <br>
                    <small class="text-muted">
                        Anda dapat mengakses 
                        <strong class="text-primary">{{ $kodeRekeningLevel6->count() }} kode rekening</strong> 
                        yang telah di-assign ke SKPD Anda
                    </small>
                @endif
            </div>
            @endif
            
            {{-- Info tentang tahun anggaran aktif --}}
            @php
                $tahunAktif = $tahunAnggaran->where('is_active', true)->first();
            @endphp
            @if($tahunAktif)
                <div class="alert alert-info mb-3">
                    <i class="bx bx-info-circle"></i> 
                    Tahun Anggaran Aktif: <strong>{{ $tahunAktif->display_name }}</strong>
                </div>
            @endif
            
            {{-- Feedback messages --}}
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
            
            {{-- ================================================ --}}
            {{-- FILTER SECTION --}}
            {{-- ================================================ --}}
            <div class="row mb-3">
                {{-- FILTER SKPD - TAMBAHAN BARU ✅ --}}
                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        <i class="bx bx-building"></i> FILTER SKPD
                    </label>
                    
                    @if(auth()->user()->canViewAllSkpd())
                        {{-- SUPER ADMIN & KEPALA BADAN - ADA DROPDOWN --}}
                        <select wire:model.live="selectedSkpdId" class="form-select">
                            <option value="">Semua SKPD (Konsolidasi)</option>
                            @foreach($skpdList as $skpd)
                                <option value="{{ $skpd->id }}">{{ $skpd->nama_opd }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Pilih SKPD untuk filter data</small>
                    @else
                        {{-- OPERATOR SKPD - READONLY FIELD --}}
                        <input type="text" 
                               class="form-control bg-light" 
                               value="{{ auth()->user()->skpd ? auth()->user()->skpd->nama_opd : 'Tidak ada SKPD' }}" 
                               readonly 
                               disabled>
                        <small class="text-muted">
                            <i class="bx bx-lock-fill"></i> Anda hanya dapat melihat data SKPD Anda
                        </small>
                    @endif
                </div>
                
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
                
                <div class="col-md-3">
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
            
            {{-- Active Filters Info --}}
            @if($tahunAnggaranId || $selectedSkpdId)
                <div class="d-flex align-items-center gap-2 mb-3">
                    @if($tahunAnggaranId)
                        @php
                            $selectedTahun = $tahunAnggaran->firstWhere('id', $tahunAnggaranId);
                        @endphp
                        @if($selectedTahun)
                            <span class="badge bg-info">Filter: {{ $selectedTahun->display_name }}</span>
                        @endif
                    @endif
                    
                    @if($selectedSkpdId && auth()->user()->canViewAllSkpd())
                        @php
                            $selectedSkpd = $skpdList->firstWhere('id', $selectedSkpdId);
                        @endphp
                        @if($selectedSkpd)
                            <span class="badge bg-warning">SKPD: {{ $selectedSkpd->nama_opd }}</span>
                        @endif
                    @endif
                </div>
            @endif
            
            {{-- Loading indicator --}}
            <div wire:loading wire:target="search, tahunAnggaranId, selectedSkpdId, toggleLevel, updateHierarchi, performSearch" class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Memuat data...</div>
                </div>
            </div>
            
            {{-- Data Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">Kode</th>
                            <th width="40%">Nama Rekening</th>
                            <th width="10%">Level</th>
                            <th width="20%">Pagu Anggaran</th>
                            <th width="5%">Status</th>
                            {{-- Kolom Aksi --}}
                            @canany(['edit-target', 'create-target'])
                            <th width="10%">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kodeRekening as $kr)
                        <tr class="level-{{ $kr->level }}">
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
                                        <span class="badge bg-primary" title="Input Manual per SKPD">M</span>
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
                            {{-- Tombol Aksi --}}
                            @canany(['edit-target', 'create-target'])
                            <td>
                                @if($kr->level == 6 && $tahunAnggaranId)
                                    @php
                                        // Cek target anggaran untuk SKPD ini
                                        $skpdIdForCheck = $selectedSkpdId ?: (auth()->user()->skpd_id ?? null);
                                        
                                        $targetAnggaranObj = null;
                                        if ($skpdIdForCheck) {
                                            $targetAnggaranObj = App\Models\TargetAnggaran::where('kode_rekening_id', $kr->id)
                                                ->where('tahun_anggaran_id', $tahunAnggaranId)
                                                ->where('skpd_id', $skpdIdForCheck)
                                                ->first();
                                        }
                                    @endphp
                                    
                                    @if($targetAnggaranObj)
                                        @can('edit-target')
                                        <a href="{{ route('target-anggaran.edit', $targetAnggaranObj->id) }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-edit"></i> Edit
                                        </a>
                                        @endcan
                                    @else
                                        @can('create-target')
                                        <a href="{{ route('target-anggaran.create') }}?kode_rekening_id={{ $kr->id }}" class="btn btn-success btn-sm">
                                            <i class="bx bx-plus"></i> Input
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
    
    {{-- ================================================ --}}
    {{-- IMPORT MODAL - FIXED ✅ --}}
    {{-- ================================================ --}}
    <div class="modal fade @if($showImportModal) show @endif" 
         tabindex="-1" 
         style="display: @if($showImportModal) block @else none @endif;" 
         aria-modal="true" 
         role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Pagu Anggaran dari Excel</h5>
                    <button type="button" class="btn-close" wire:click="closeImportModal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="import">
                    <div class="modal-body">
                        {{-- Info SKPD untuk Operator SKPD --}}
                        @if(auth()->user()->skpd_id && !auth()->user()->canViewAllSkpd())
                            <div class="alert alert-warning py-2 mb-3">
                                <i class="bx bx-info-circle"></i> 
                                Data akan diimport untuk: <strong>{{ auth()->user()->skpd->nama_opd }}</strong>
                            </div>
                        @endif
                        
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
                            {{-- SKPD SELECTOR - Hanya untuk Super Admin --}}
                            @if(auth()->user()->isSuperAdmin())
                            <div class="col-md-6">
                                <label for="importSkpdId" class="form-label">
                                    SKPD Target <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('importSkpdId') is-invalid @enderror" 
                                        wire:model="importSkpdId">
                                    <option value="">Pilih SKPD</option>
                                    @foreach($skpdList as $skpd)
                                        <option value="{{ $skpd->id }}">{{ $skpd->nama_opd }}</option>
                                    @endforeach
                                </select>
                                @error('importSkpdId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Pilih SKPD yang akan diimport datanya</small>
                            </div>
                            @endif
                            
                            <div class="col-md-{{ auth()->user()->isSuperAdmin() ? '6' : '12' }}">
                                <label for="importTahun" class="form-label">
                                    Tahun Anggaran <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control bg-light" 
                                       value="{{ $importTahun ? $tahunAnggaran->firstWhere('id', $importTahun)->display_name : 'Pilih tahun anggaran di filter' }}" 
                                       readonly disabled>
                                <small class="text-muted">Tahun anggaran dari filter yang dipilih</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="importFile" class="form-label">
                                File Excel <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   class="form-control @error('importFile') is-invalid @enderror" 
                                   wire:model="importFile" 
                                   accept=".xlsx,.xls">
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
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="bx bx-info-circle"></i> Petunjuk Import</h6>
                            <hr>
                            <ul class="mb-0">
                                <li>Download template Excel terlebih dahulu</li>
                                @if(auth()->user()->isSuperAdmin())
                                <li><strong>Pilih SKPD target</strong> sebelum upload file</li>
                                @else
                                <li>Data akan diimport untuk <strong>{{ auth()->user()->skpd->nama_opd }}</strong></li>
                                @endif
                                <li>Kolom wajib: <strong>kode</strong> dan <strong>pagu_anggaran</strong></li>
                                <li>Kode rekening harus <strong>Level 6</strong></li>
                                <li>Format angka bisa dengan atau tanpa "Rp" dan pemisah ribuan</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" wire:click="downloadTemplate">
                            <i class="bx bx-download"></i> Download Template
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="closeImportModal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" 
                                wire:loading.attr="disabled"
                                wire:target="import"
                                {{ !$importTahun || !$importFile ? 'disabled' : '' }}>
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
    
    {{-- Modal Backdrop --}}
    @if($showImportModal)
        <div class="modal-backdrop fade show"></div>
    @endif
    
    {{-- JavaScript Konfirmasi --}}
    @push('scripts')
    <script>
    function confirmDeleteAllPagu() {
        if (!confirm('⚠️ PERINGATAN!\n\nAnda akan menghapus SEMUA DATA PAGU ANGGARAN dari database!\n\nTindakan ini TIDAK BISA DIBATALKAN!\n\nLanjutkan?')) {
            return;
        }
        
        if (!confirm('⚠️ KONFIRMASI TERAKHIR!\n\nApakah Anda BENAR-BENAR YAKIN?\n\nKlik OK untuk melanjutkan penghapusan PERMANEN.')) {
            return;
        }
        
        @this.call('deleteAllPaguAnggaran');
    }
    </script>
    @endpush
</div>