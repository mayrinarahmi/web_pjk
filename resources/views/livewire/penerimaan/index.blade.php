<div> {{-- Single root div untuk Livewire --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Penerimaan</h5>
            {{-- Tombol Import, Export & Create --}}
            @canany(['create-penerimaan', 'import-penerimaan', 'view-laporan'])
            <div class="d-flex gap-2">
                {{-- Tombol Import Excel --}}
                @can('import-penerimaan')
                <button type="button" class="btn btn-success btn-sm" wire:click="openImportModal">
                    <i class="bx bx-upload"></i> Import Excel
                </button>
                @endcan
                
                {{-- Tombol Export LRA --}}
                @can('view-laporan')
                <button type="button" 
                        class="btn btn-warning btn-sm" 
                        wire:click="exportLaporanRealisasi"
                        wire:loading.attr="disabled"
                        wire:target="exportLaporanRealisasi"
                        @if(!$tahun) disabled title="Pilih tahun terlebih dahulu" @endif>
                    <span wire:loading.remove wire:target="exportLaporanRealisasi">
                        <i class="bx bx-download"></i> Export LRA
                    </span>
                    <span wire:loading wire:target="exportLaporanRealisasi">
                        <span class="spinner-border spinner-border-sm me-1"></span> Exporting...
                    </span>
                </button>
                @endcan
                
                {{-- Tombol Tambah Penerimaan --}}
                @can('create-penerimaan')
                <a href="{{ route('penerimaan.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i> Tambah Penerimaan
                </a>
                @endcan
            </div>
            @endcanany
        </div>
        

        <div class="card-body">
            <!-- TAMBAHAN: SKPD Info dan Filter -->
            @if($userSkpdInfo)
            <div class="alert {{ auth()->user()->skpd_id && !auth()->user()->isSuperAdmin() ? 'alert-warning' : 'alert-info' }} py-2">
                <i class="bx bx-info-circle"></i> {{ $userSkpdInfo }}
            </div>
            @endif
            
            <!-- Filter Section -->
            <div class="row mb-3">
                <!-- TAMBAHAN: SKPD Filter untuk Super Admin/Kepala Badan -->
                @if((auth()->user()->isSuperAdmin() || auth()->user()->isKepalaBadan()) && count($skpdList) > 0)
                <div class="col-md-3">
                    <label for="selectedSkpdId" class="form-label">Filter SKPD</label>
                    <select class="form-select form-select-sm" wire:model.live="selectedSkpdId">
                        <option value="">Semua SKPD</option>
                        @foreach($skpdList as $skpd)
                            <option value="{{ $skpd->id }}">{{ $skpd->nama_opd }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                
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
                        @foreach($kodeRekeningLevel6 as $kr)
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
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="search" class="form-label">Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari kode/nama..." wire:model.live.debounce.300ms="search">
                        <button class="btn btn-outline-secondary" type="button" wire:click="resetFilter" title="Reset Filter">
                            <i class="bx bx-reset"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Level Filter & Actions -->
            <div class="row mb-3">
                <div class="col-md-6">
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
                <div class="col-md-6 text-end">
                    <!-- TAMBAHAN: Toggle Hide Empty Button -->
                    <button type="button" 
                            class="btn {{ $hideEmpty ? 'btn-success' : 'btn-outline-success' }} btn-sm" 
                            wire:click="toggleHideEmpty"
                            title="{{ $hideEmpty ? 'Menampilkan yang ada transaksi' : 'Menampilkan semua' }}">
                        <i class="bx {{ $hideEmpty ? 'bx-hide' : 'bx-show' }}"></i> 
                        {{ $hideEmpty ? 'Hanya Ada Transaksi' : 'Tampilkan Semua' }}
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetFilter">
                        <i class="bx bx-reset"></i> Reset Filter
                    </button>
                    @if($tahun)
                        <button class="btn btn-outline-primary btn-sm" wire:click="resetTanggalToFullYear">
                            <i class="bx bx-calendar"></i> Full Year {{ $tahun }}
                        </button>
                    @endif
                </div>
            </div>
            
            <!-- Info & Warning Messages -->
            @if($tahun)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-info">Filter Aktif: Tahun {{ $tahun }}</span>
                    @if($tanggalMulai && $tanggalSelesai)
                        <span class="badge bg-secondary">{{ Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }} - {{ Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y') }}</span>
                    @endif
                    @if($selectedSkpdId && (auth()->user()->isSuperAdmin() || auth()->user()->isKepalaBadan()))
                        @php
                            $selectedSkpd = $skpdList->firstWhere('id', $selectedSkpdId);
                        @endphp
                        @if($selectedSkpd)
                            <span class="badge bg-warning">SKPD: {{ $selectedSkpd->nama_opd }}</span>
                        @endif
                    @endif
                    @if($hideEmpty)
                        <span class="badge bg-success">Hanya Menampilkan yang Ada Transaksi</span>
                    @endif
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
            
            <!-- Loading indicator -->
            <div wire:loading wire:target="search, tahun, kodeRekeningId, tanggalMulai, tanggalSelesai, toggleLevel, perPage, gotoPage, nextPage, previousPage, selectedSkpdId, toggleHideEmpty" class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Memuat data...</div>
                </div>
            </div>
            
            <!-- Grand Total Summary & Per Page Selector -->
            <div class="row mb-3">
                <div class="col-md-8">
                    @if($grandTotal != 0)
                        <div class="alert {{ $grandTotal < 0 ? 'alert-danger' : 'alert-success' }} mb-0">
                            <h6 class="mb-0"><i class="bx bx-money"></i> Total Penerimaan: 
                                <strong class="{{ $grandTotal < 0 ? 'text-white' : '' }}">
                                    Rp {{ number_format($grandTotal, 0, ',', '.') }}
                                </strong>
                            @if($tahun) | Tahun {{ $tahun }} @endif
                            @if($tanggalMulai && $tanggalSelesai) | {{ Carbon\Carbon::parse($tanggalMulai)->format('d M') }} - {{ Carbon\Carbon::parse($tanggalSelesai)->format('d M Y') }} @endif
                            </h6>
                        </div>
                    @endif
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-inline-flex align-items-center">
                        <label class="me-2">Tampilkan:</label>
                        <select class="form-select form-select-sm" style="width: auto;" wire:model.live="perPage">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        <span class="ms-2">data per halaman</span>
                    </div>
                </div>
            </div>
            
            <!-- Hierarchical Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Kode</th>
                            <th width="40%">Uraian</th>
                            <th width="20%">Jumlah</th>
                            <th width="12%">Tanggal</th>
                            {{-- Kolom Aksi --}}
                            @canany(['edit-penerimaan', 'delete-penerimaan'])
                            <th width="8%">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = ($penerimaan->currentPage() - 1) * $penerimaan->perPage() + 1;
                        @endphp
                        @forelse($penerimaan as $p)
                        <tr class="level-{{ $p->level }} {{ $p->level == 1 ? 'table-primary' : ($p->level == 2 ? 'table-info' : ($p->level == 3 ? 'table-light' : ($p->level == 4 ? 'table-warning' : ($p->level == 5 ? 'table-success' : 'table-secondary')))) }}">
                            <td>{{ $no++ }}</td>
                            <td>
                                <code>{{ $p->kode }}</code>
                            </td>
                            <td>
                                <div style="padding-left: {{ ($p->level - 1) * 15 }}px;">
                                    {{ $p->nama }}
                                </div>
                            </td>
                            <td class="text-end">
                                @if($p->total_penerimaan != 0)
                                    <strong class="{{ $p->total_penerimaan < 0 ? 'text-danger' : '' }}">
                                        Rp {{ number_format($p->total_penerimaan, 0, ',', '.') }}
                                    </strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($p->tanggal_terakhir)
                                    {{ Carbon\Carbon::parse($p->tanggal_terakhir)->format('d-m-Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            {{-- Tombol Aksi --}}
                            @canany(['edit-penerimaan', 'delete-penerimaan'])
                            <td>
                                @if($p->level == 6 && $p->jumlah_transaksi > 0)
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" 
                                                class="btn btn-primary" 
                                                wire:click="showDetail({{ $p->id }})"
                                                title="Lihat Detail">
                                            <i class="bx bx-search-alt"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="badge bg-secondary">-</span>
                                @endif
                            </td>
                            @endcanany
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canany(['edit-penerimaan', 'delete-penerimaan']) ? '6' : '5' }}" class="text-center py-4">
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
                                            @if($hideEmpty) | Hanya Menampilkan yang Ada Transaksi @endif
                                        </small>
                                        <br>
                                        <button class="btn btn-outline-primary btn-sm mt-2" wire:click="resetFilter">
                                            <i class="bx bx-reset"></i> Reset Filter
                                        </button>
                                    </div>
                                @else
                                    <div class="text-muted">
                                        <i class="bx bx-data fs-1 mb-2"></i>
                                        <p>Pilih tahun untuk melihat data penerimaan.</p>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Info & Links -->
            <div class="row mt-3">
                <div class="col-md-6">
                    @if($penerimaan->total() > 0)
                        <p class="text-muted mb-0">
                            Menampilkan {{ $penerimaan->firstItem() }} - {{ $penerimaan->lastItem() }} dari {{ $penerimaan->total() }} data
                        </p>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        {{ $penerimaan->onEachSide(1)->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="card mt-3">
        <div class="card-body">
            <h6>Keterangan Level:</h6>
            <div class="row">
                <div class="col-md-2"><span class="badge bg-primary">Level 1</span> Utama</div>
                <div class="col-md-2"><span class="badge bg-info">Level 2</span> Kelompok</div>
                <div class="col-md-2"><span class="badge bg-light text-dark">Level 3</span> Jenis</div>
                <div class="col-md-2"><span class="badge bg-warning">Level 4</span> Objek</div>
                <div class="col-md-2"><span class="badge bg-success">Level 5</span> Rincian</div>
                <div class="col-md-2"><span class="badge bg-secondary">Level 6</span> Detail</div>
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
                    <h5 class="modal-title">Import Data Penerimaan</h5>
                    <button type="button" class="btn-close" wire:click="closeImportModal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="import">
                    <div class="modal-body">
                        <!-- TAMBAHAN: Info SKPD -->
                        @if(auth()->user()->skpd)
                        <div class="alert alert-warning py-2">
                            <i class="bx bx-info-circle"></i> 
                            Data akan diimport untuk: <strong>{{ auth()->user()->skpd->nama_opd }}</strong>
                        </div>
                        @endif
                        
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
                                <li>Kode rekening harus level 6 dan sudah terdaftar di sistem</li>
                                <li>Jumlah bisa menggunakan format angka biasa atau format rupiah</li>
                                <li>Jumlah dapat berupa nilai positif atau negatif (untuk koreksi/adjustment)</li>
                                <li>Format nilai negatif: gunakan tanda minus (-) di depan angka. Contoh: -1000000</li>
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
   
   <!-- Detail Penerimaan Modal -->
   <div class="modal fade @if($showDetailModal) show @endif" 
        tabindex="-1" 
        style="display: @if($showDetailModal) block @else none @endif;" 
        aria-modal="true" 
        role="dialog">
       <div class="modal-dialog modal-xl">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title">
                       Detail Penerimaan - 
                       @if($selectedKodeRekening)
                           <code>{{ $selectedKodeRekening->kode }}</code> {{ $selectedKodeRekening->nama }}
                       @endif
                   </h5>
                   <button type="button" class="btn-close" wire:click="closeDetailModal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                   @if($detailPenerimaan && count($detailPenerimaan) > 0)
                       <div class="table-responsive">
                           <table class="table table-sm table-striped">
                               <thead>
                                   <tr>
                                       <th width="5%">No</th>
                                       <th width="12%">Tanggal</th>
                                       <th width="20%">Jumlah</th>
                                       <th width="35%">Keterangan</th>
                                       <th width="15%">SKPD</th>
                                       <th width="13%">Aksi</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   @foreach($detailPenerimaan as $index => $detail)
                                   <tr>
                                       <td>{{ $index + 1 }}</td>
                                       <td>{{ $detail->tanggal->format('d-m-Y') }}</td>
                                       <td class="text-end">
                                           <strong class="{{ $detail->jumlah < 0 ? 'text-danger' : '' }}">
                                               Rp {{ number_format($detail->jumlah, 0, ',', '.') }}
                                           </strong>
                                       </td>
                                       <td>{{ $detail->keterangan ?? '-' }}</td>
                                       <td>
                                           @if($detail->skpd)
                                               <small>{{ Str::limit($detail->skpd->nama_opd, 30) }}</small>
                                           @else
                                               <small class="text-muted">-</small>
                                           @endif
                                       </td>
                                       <td>
                                           <div class="btn-group btn-group-sm" role="group">
                                               @can('edit-penerimaan')
                                               <a href="{{ route('penerimaan.edit', $detail->id) }}" 
                                                   class="btn btn-primary"
                                                   title="Edit">
                                                   <i class="bx bx-edit"></i>
                                               </a>
                                               @endcan
                                               @can('delete-penerimaan')
                                               <button type="button" class="btn btn-danger" 
                                                   onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" 
                                                   wire:click="delete({{ $detail->id }})" 
                                                   wire:loading.attr="disabled"
                                                   title="Hapus">
                                                   <i class="bx bx-trash"></i>
                                               </button>
                                               @endcan
                                           </div>
                                       </td>
                                   </tr>
                                   @endforeach
                               </tbody>
                               <tfoot>
                                   <tr class="table-info">
                                       <th colspan="2">Total</th>
                                       <th class="text-end">
                                           <span class="{{ $detailPenerimaan->sum('jumlah') < 0 ? 'text-danger' : '' }}">
                                               Rp {{ number_format($detailPenerimaan->sum('jumlah'), 0, ',', '.') }}
                                           </span>
                                       </th>
                                       <th colspan="3"></th>
                                   </tr>
                               </tfoot>
                           </table>
                       </div>
                   @else
                       <div class="text-center py-4">
                           <i class="bx bx-inbox fs-1 text-muted"></i>
                           <p class="text-muted mt-2">Tidak ada detail penerimaan</p>
                       </div>
                   @endif
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">
                       Tutup
                   </button>
               </div>
           </div>
       </div>
   </div>

   @if($showDetailModal)
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
      
      /* Make level rows stand out */
      tr.level-1 td {
          font-weight: bold;
          font-size: 1.1em;
      }
      
      tr.level-2 td {
          font-weight: 600;
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
</div> {{-- End of single root div --}}