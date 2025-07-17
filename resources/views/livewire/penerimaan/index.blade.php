<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Penerimaan</h5>
            {{-- Tombol Import & Create - Hide untuk Viewer --}}
            @canany(['create-penerimaan', 'import-penerimaan'])
            <div class="d-flex gap-2">
                @can('import-penerimaan')
                <button type="button" class="btn btn-success btn-sm" wire:click="openImportModal">
                    <i class="bx bx-upload"></i> Import Excel
                </button>
                @endcan
                @can('create-penerimaan')
                <a href="{{ route('penerimaan.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i> Tambah Penerimaan
                </a>
                @endcan
            </div>
            @endcanany
        </div>
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="tahun" class="form-label">Tahun</label>
                    <select class="form-select form-select-sm" wire:model.live="tahun">
                        <option value="">Pilih Tahun</option>
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="kodeRekeningId" class="form-label">Kode Rekening</label>
                    <select class="form-select form-select-sm" wire:model.live="kodeRekeningId">
                        <option value="">Semua Kode Rekening</option>
                        @foreach($kodeRekeningLevel5 as $kr)
                            <option value="{{ $kr->id }}">{{ $kr->kode }} - {{ Str::limit($kr->nama, 30) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tanggalMulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="tanggalMulai">
                </div>
                <div class="col-md-2">
                    <label for="tanggalSelesai" class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="tanggalSelesai">
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari keterangan..." wire:model.live.debounce.300ms="search">
                        <button class="btn btn-outline-secondary" type="button" wire:click="resetFilter" title="Reset Filter">
                            <i class="bx bx-reset"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- PERBAIKAN: Tambah tombol helper dan info filter -->
            <div class="row mb-3">
                <div class="col-md-6">
                    @if($tahun)
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-info">Filter Aktif: Tahun {{ $tahun }}</span>
                            @if($tanggalMulai && $tanggalSelesai)
                                <span class="badge bg-secondary">{{ Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y') }}</span>
                            @endif
                            <button class="btn btn-outline-primary btn-sm" wire:click="resetTanggalToFullYear" title="Tampilkan data sepanjang tahun {{ $tahun }}">
                                <i class="bx bx-calendar"></i> Full Year
                            </button>
                        </div>
                    @endif
                </div>
                <div class="col-md-6 text-end">
                    <!-- Debug info (hanya tampil jika ada masalah) -->
                    @if($tahun && (!$tanggalMulai || !$tanggalSelesai))
                        <small class="text-warning">
                            <i class="bx bx-info-circle"></i> 
                            Filter tanggal kosong - menampilkan semua data tahun {{ $tahun }}
                        </small>
                    @endif
                </div>
            </div>
            
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
            
            <!-- Loading indicator -->
            <div wire:loading wire:target="search, tahun, kodeRekeningId, tanggalMulai, tanggalSelesai" class="mb-3">
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
                            <th width="10%">Tanggal</th>
                            <th width="15%">Kode Rekening</th>
                            <th width="25%">Uraian</th>
                            <th width="8%">Tahun</th>
                            <th width="15%">Jumlah</th>
                            <th width="15%">Keterangan</th>
                            {{-- Kolom Aksi - Hide untuk Viewer --}}
                            @canany(['edit-penerimaan', 'delete-penerimaan'])
                            <th width="7%">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($penerimaan as $key => $p)
                        <tr>
                            <td>{{ $penerimaan->firstItem() + $key }}</td>
                            <td>{{ $p->tanggal->format('d-m-Y') }}</td>
                            <td>
                                <code>{{ $p->kodeRekening->kode }}</code>
                            </td>
                            <td>
                                <small>{{ $p->kodeRekening->nama }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $p->tahun }}</span>
                            </td>
                            <td class="text-end">
                                <strong>Rp {{ number_format($p->jumlah, 0, ',', '.') }}</strong>
                            </td>
                            <td>{{ $p->keterangan ?? '-' }}</td>
                            {{-- Tombol Aksi - Hide untuk Viewer --}}
                            @canany(['edit-penerimaan', 'delete-penerimaan'])
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('edit-penerimaan')
                                    <a href="{{ route('penerimaan.edit', $p->id) }}" 
                                        class="btn btn-primary"
                                        title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete-penerimaan')
                                    <button type="button" class="btn btn-danger" 
                                        onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" 
                                        wire:click="delete({{ $p->id }})" 
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
                            <td colspan="{{ auth()->user()->canany(['edit-penerimaan', 'delete-penerimaan']) ? '8' : '7' }}" class="text-center py-4">
                                @if($tahun || $kodeRekeningId || $tanggalMulai || $tanggalSelesai || $search)
                                    <div class="text-muted">
                                        <i class="bx bx-search-alt-2 fs-1 mb-2"></i>
                                        <p class="mb-1">Tidak ada data penerimaan yang sesuai dengan filter.</p>
                                        <small>
                                            Filter aktif: 
                                            @if($tahun) Tahun {{ $tahun }} @endif
                                            @if($kodeRekeningId) | Kode Rekening @endif
                                            @if($tanggalMulai || $tanggalSelesai) | Rentang Tanggal @endif
                                            @if($search) | Pencarian: "{{ $search }}" @endif
                                        </small>
                                        <br>
                                        <button class="btn btn-outline-primary btn-sm mt-2" wire:click="resetFilter">
                                            <i class="bx bx-reset"></i> Reset Filter
                                        </button>
                                    </div>
                                @else
                                    <div class="text-muted">
                                        <i class="bx bx-data fs-1 mb-2"></i>
                                        <p>Belum ada data penerimaan.</p>
                                        @can('create-penerimaan')
                                        <a href="{{ route('penerimaan.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-plus"></i> Tambah Data Pertama
                                        </a>
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Row -->
            @if($penerimaan->count() > 0)
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong>Total Penerimaan:</strong> 
                            Rp {{ number_format($penerimaan->sum('jumlah'), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-secondary">
                            <strong>Jumlah Transaksi:</strong> {{ $penerimaan->total() }} transaksi
                            @if($tahun)
                                <br><small class="text-muted">Tahun {{ $tahun }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="mt-3">
                {{ $penerimaan->links() }}
            </div>
        </div>
    </div>
    
    <!-- Import Modal - Hanya tampil jika user punya permission import -->
    @can('import-penerimaan')
    <div class="modal fade @if($showImportModal) show @endif" 
         tabindex="-1" 
         style="display: @if($showImportModal) block @else none @endif;" 
         aria-modal="true" 
         role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Data Penerimaan</h5>
                    <button type="button" class="btn-close" wire:click="closeImportModal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="import">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="importTahun" class="form-label">Tahun <span class="text-danger">*</span></label>
                                <select class="form-select @error('importTahun') is-invalid @enderror" 
                                        wire:model="importTahun">
                                    <option value="">Pilih Tahun</option>
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                                @error('importTahun')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="importFile" class="form-label">File Excel <span class="text-danger">*</span></label>
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
                        </div>
                        
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="bx bx-info-circle"></i> Petunjuk Import</h6>
                            <hr>
                            <ul class="mb-0">
                                <li>Download template Excel terlebih dahulu</li>
                                <li>Kolom yang wajib diisi: <strong>kode</strong>, <strong>tanggal</strong>, <strong>jumlah</strong></li>
                                <li>Format tanggal: <code>DD-MM-YYYY</code> atau <code>DD/MM/YYYY</code></li>
                                <li>Tanggal harus sesuai dengan tahun yang dipilih ({{ $importTahun ?: '...' }})</li>
                                <li>Kode rekening harus sudah terdaftar dan aktif di sistem</li>
                                <li>Jumlah bisa menggunakan format angka biasa atau format rupiah</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Format Template:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>kode</th>
                                            <th>tanggal</th>
                                            <th>jumlah</th>
                                            <th>keterangan</th>
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
                                {{ !$importFile ? 'disabled' : '' }}>
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
    @endcan
</div>