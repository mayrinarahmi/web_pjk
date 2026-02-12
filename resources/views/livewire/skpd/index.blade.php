<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Kelola SKPD</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-info">
                    <i class="bx bx-info-circle"></i> Assign kode rekening untuk setiap SKPD
                </span>
            </div>
        </div>
        <div class="card-body">
            <!-- Search -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari kode/nama SKPD..." 
                               wire:model.debounce.300ms="search">
                    </div>
                </div>
            </div>
            
            <!-- Feedback messages -->
            @if(session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Kode OPD</th>
                            <th width="30%">Nama OPD</th>
                            <th width="8%">Status</th>
                            <th width="22%">Kode Rekening</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = ($skpdList->currentPage() - 1) * $skpdList->perPage() + 1;
                        @endphp
                        @forelse($skpdList as $skpd)
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td><code>{{ $skpd->kode_opd }}</code></td>
                            <td>{{ $skpd->nama_opd }}</td>
                            <td>
                                @if($skpd->status == 'aktif')
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @if($skpd->assignment_count > 0)
                                    <div>
                                        <span class="badge bg-primary">
                                            <i class="bx bx-check"></i> {{ $skpd->assignment_count }} kode
                                        </span>
                                        <!-- Button to show details -->
                                        <button type="button" 
                                                class="btn btn-link btn-sm p-0 ms-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailModal{{ $skpd->id }}">
                                            <small>Lihat Detail</small>
                                        </button>
                                    </div>
                                @else
                                    <span class="badge bg-warning">
                                        <i class="bx bx-x"></i> Belum di-assign
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-primary" 
                                            wire:click="openAssignModal({{ $skpd->id }})"
                                            title="Assign Kode Rekening">
                                        <i class="bx bx-list-check"></i> Assign
                                    </button>
                                    @if($skpd->assignment_count > 0)
                                    <button type="button" class="btn btn-danger"
                                            onclick="confirm('Hapus semua assignment untuk {{ $skpd->nama_opd }}?') || event.stopImmediatePropagation()"
                                            wire:click="clearAssignment({{ $skpd->id }})"
                                            title="Clear Assignment">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3">
                                <i class="bx bx-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Tidak ada data SKPD</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="row mt-3">
                <div class="col-md-6">
                    @if($skpdList->total() > 0)
                        <p class="text-muted mb-0">
                            Menampilkan {{ $skpdList->firstItem() }} - {{ $skpdList->lastItem() }} 
                            dari {{ $skpdList->total() }} SKPD
                        </p>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        {{ $skpdList->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail Kode Rekening untuk setiap SKPD -->
    @foreach($skpdList as $skpd)
        @if($skpd->assignment_count > 0)
        <div class="modal fade" id="detailModal{{ $skpd->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl"> <!-- Changed to modal-xl for extra width -->
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Kode Rekening - {{ $skpd->nama_opd }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $access = $skpd->kode_rekening_access ?? [];
                            // Get kode rekening details
                            $kodeDetails = \App\Models\KodeRekening::whereIn('id', $access)
                                                                   ->orderBy('kode')
                                                                   ->get();
                        @endphp
                        
                        @php
                            $countByGen = $kodeDetails->groupBy('berlaku_mulai');
                        @endphp
                        <div class="alert alert-info py-2">
                            <i class="bx bx-info-circle"></i>
                            Total: <strong>{{ count($access) }} kode rekening</strong> yang dapat diakses
                            @if($countByGen->count() > 1)
                                <br><small>
                                    @foreach($countByGen as $gen => $items)
                                        <span class="badge bg-{{ $gen >= 2026 ? 'info' : 'secondary' }} me-1">{{ $gen }}: {{ $items->count() }} kode</span>
                                    @endforeach
                                </small>
                            @endif
                        </div>
                        
                        @if($kodeDetails->count() > 0)
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="18%">Kode</th>
                                            <th width="62%">Uraian</th>
                                            <th width="20%" class="text-center">Berlaku Mulai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kodeDetails as $kode)
                                        <tr>
                                            <td class="text-nowrap"><code>{{ $kode->kode }}</code></td>
                                            <td style="white-space: normal; word-wrap: break-word;">{{ $kode->nama }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $kode->berlaku_mulai >= 2026 ? 'info' : 'secondary' }}">
                                                    {{ $kode->berlaku_mulai ?? '-' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
    
    <!-- Modal Assign Kode Rekening -->
    <div class="modal fade @if($showAssignModal) show @endif" 
         tabindex="-1" 
         style="display: @if($showAssignModal) block @else none @endif;" 
         aria-modal="true" 
         role="dialog">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Assign Kode Rekening - 
                        @if($selectedSkpd)
                            {{ $selectedSkpd->nama_opd }}
                        @endif
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeAssignModal"></button>
                </div>
                <div class="modal-body">
                    @if($selectedSkpd)
                    <div class="alert alert-info py-2">
                        <i class="bx bx-info-circle"></i>
                        Pilih kode rekening yang dapat diakses oleh <strong>{{ $selectedSkpd->nama_opd }}</strong>.
                        Check parent akan otomatis check semua children.
                    </div>

                    <!-- Filter Berlaku Mulai -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Filter Kode Rekening</label>
                            <select class="form-select" wire:model.live="assignTahunFilter">
                                <option value="">Semua Generasi</option>
                                @php
                                    $berlakuYears = \App\Models\KodeRekening::distinct()->whereNotNull('berlaku_mulai')->orderBy('berlaku_mulai')->pluck('berlaku_mulai');
                                @endphp
                                @foreach($berlakuYears as $year)
                                    <option value="{{ $year }}">Berlaku mulai {{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Loading -->
                    <div wire:loading wire:target="openAssignModal" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat kode rekening...</p>
                    </div>
                    
                    <!-- Kode Rekening Tree -->
                    <div wire:loading.remove wire:target="openAssignModal" class="kode-tree">
                        @foreach($kodeRekeningTree as $level3)
                        <div class="tree-level-3 mb-2">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="kode_{{ $level3['id'] }}"
                                       wire:click="toggleKodeRekening({{ $level3['id'] }}, 3)"
                                       @if(in_array($level3['id'], $selectedKodeRekening)) checked @endif>
                                <label class="form-check-label fw-bold text-primary" for="kode_{{ $level3['id'] }}">
                                    <code>{{ $level3['kode'] }}</code> - {{ $level3['nama'] }}
                                </label>
                            </div>
                            
                            @foreach($level3['children'] as $level4)
                            <div class="tree-level-4 ms-4 mt-1">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="kode_{{ $level4['id'] }}"
                                           wire:click="toggleKodeRekening({{ $level4['id'] }}, 4)"
                                           @if(in_array($level4['id'], $selectedKodeRekening)) checked @endif>
                                    <label class="form-check-label text-info" for="kode_{{ $level4['id'] }}">
                                        <code>{{ $level4['kode'] }}</code> - {{ $level4['nama'] }}
                                    </label>
                                </div>
                                
                                @foreach($level4['children'] as $level5)
                                <div class="tree-level-5 ms-4 mt-1">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="kode_{{ $level5['id'] }}"
                                               wire:click="toggleKodeRekening({{ $level5['id'] }}, 5)"
                                               @if(in_array($level5['id'], $selectedKodeRekening)) checked @endif>
                                        <label class="form-check-label text-success" for="kode_{{ $level5['id'] }}">
                                            <code>{{ $level5['kode'] }}</code> - {{ $level5['nama'] }}
                                            @if(isset($level5['level6_count']) && $level5['level6_count'] > 0)
                                                <span class="badge bg-secondary ms-1">{{ $level5['level6_count'] }} item</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-3">
                        <span class="badge bg-primary">{{ count($selectedKodeRekening) }}</span> kode rekening dipilih
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeAssignModal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" 
                            wire:click="saveAssignment"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveAssignment">
                            <i class="bx bx-save"></i> Simpan
                        </span>
                        <span wire:loading wire:target="saveAssignment">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    @if($showAssignModal)
        <div class="modal-backdrop fade show"></div>
    @endif
    
    <style>
        .kode-tree {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
        }
        
        .tree-level-3 {
            border-left: 3px solid #007bff;
            padding-left: 10px;
        }
        
        .tree-level-4 {
            border-left: 2px solid #17a2b8;
            padding-left: 10px;
        }
        
        .tree-level-5 {
            border-left: 1px solid #28a745;
            padding-left: 10px;
        }
        
        .form-check-label code {
            font-size: 0.9em;
        }
    </style>
</div>