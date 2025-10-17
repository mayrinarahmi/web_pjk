@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Backup & Restore Database</h4>
            <p class="text-muted mb-0">Kelola backup database sistem SILAPAT</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="submitCreateBackup()">
            <i class="bx bx-plus-circle"></i> Buat Backup Baru
        </button>
    </div>
    
    {{-- Flash Messages --}}
    @if(session('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            <strong>Berhasil!</strong> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    {{-- Hidden Form for Create Backup (POST METHOD) --}}
    <form id="createBackupForm" method="POST" action="{{ route('backup.create') }}" style="display: none;">
        @csrf
    </form>
    
    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary bg-soft text-primary">
                                <i class="bx bx-data font-size-24"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Total Backup</p>
                            <h5 class="mb-0">{{ $stats['total_backups'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success bg-soft text-success">
                                <i class="bx bx-refresh font-size-24"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Total Restore</p>
                            <h5 class="mb-0">{{ $stats['total_restores'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info bg-soft text-info">
                                <i class="bx bx-hdd font-size-24"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Total Size</p>
                            <h5 class="mb-0">{{ formatBytes($stats['total_size']) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-warning bg-soft text-warning">
                                <i class="bx bx-calendar font-size-24"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Backup Terakhir</p>
                            <h6 class="mb-0 font-size-12">
                                @if($stats['last_backup'])
                                    {{ $stats['last_backup']->created_at->diffForHumans() }}
                                @else
                                    Belum ada
                                @endif
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Info Alert --}}
    <div class="alert alert-info border-0 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bx bx-info-circle font-size-24 me-3"></i>
            <div>
                <strong>Informasi Backup:</strong>
                <ul class="mb-0 mt-2">
                    <li>Backup mencakup seluruh database kecuali: cache, sessions, jobs</li>
                    <li>Sebelum restore, sistem akan otomatis membuat backup current data</li>
                    <li>Maximum ukuran backup: 100 MB</li>
                    <li>File backup disimpan di: <code>storage/app/backups/</code></li>
                </ul>
            </div>
        </div>
    </div>
    
    {{-- Main Content --}}
    <div class="row">
        {{-- Backup List --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-list-ul"></i> Daftar Backup
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th><i class="bx bx-file"></i> Nama File</th>
                                    <th width="12%"><i class="bx bx-hdd"></i> Ukuran</th>
                                    <th width="15%"><i class="bx bx-calendar"></i> Tanggal</th>
                                    <th width="15%"><i class="bx bx-user"></i> Dibuat Oleh</th>
                                    <th width="18%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($backupFiles as $key => $backup)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bx bx-archive font-size-20 text-primary me-2"></i>
                                            <span class="font-size-13">{{ $backup['file_name'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ formatBytes($backup['file_size']) }}</span>
                                    </td>
                                    <td>
                                        <small>{{ date('d M Y', $backup['created_at']) }}<br>{{ date('H:i:s', $backup['created_at']) }}</small>
                                    </td>
                                    <td>
                                        @if($backup['created_by'])
                                            <small>{{ $backup['created_by']->name }}</small>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('backup.download', $backup['file_name']) }}" 
                                               class="btn btn-outline-info"
                                               title="Download">
                                                <i class="bx bx-download"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-warning" 
                                                    onclick="confirmRestore('{{ $backup['file_name'] }}')"
                                                    title="Restore">
                                                <i class="bx bx-refresh"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    onclick="confirmDelete('{{ $backup['file_name'] }}')"
                                                    title="Hapus">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bx bx-folder-open font-size-48 text-muted mb-3 d-block"></i>
                                        <p class="text-muted mb-0">Belum ada backup</p>
                                        <small class="text-muted">Klik tombol "Buat Backup Baru" untuk membuat backup pertama</small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Recent Activity --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-time-five"></i> Aktivitas Terbaru
                    </h5>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @forelse($recentLogs as $log)
                    <div class="d-flex mb-3 pb-3 border-bottom">
                        <div class="flex-shrink-0">
                            @if($log->action == 'create')
                                <div class="avatar-xs rounded-circle bg-primary bg-soft text-primary">
                                    <i class="bx bx-plus font-size-16"></i>
                                </div>
                            @elseif($log->action == 'restore')
                                <div class="avatar-xs rounded-circle bg-warning bg-soft text-warning">
                                    <i class="bx bx-refresh font-size-16"></i>
                                </div>
                            @elseif($log->action == 'download')
                                <div class="avatar-xs rounded-circle bg-info bg-soft text-info">
                                    <i class="bx bx-download font-size-16"></i>
                                </div>
                            @elseif($log->action == 'delete')
                                <div class="avatar-xs rounded-circle bg-danger bg-soft text-danger">
                                    <i class="bx bx-trash font-size-16"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 font-size-13">
                                @if($log->action == 'create')
                                    Backup Dibuat
                                @elseif($log->action == 'restore')
                                    Database Di-restore
                                @elseif($log->action == 'download')
                                    Backup Didownload
                                @elseif($log->action == 'delete')
                                    Backup Dihapus
                                @endif
                                
                                @if($log->status == 'failed')
                                    <span class="badge bg-danger">Gagal</span>
                                @endif
                            </h6>
                            <p class="text-muted font-size-12 mb-1">{{ $log->file_name }}</p>
                            <p class="text-muted font-size-11 mb-0">
                                <i class="bx bx-user font-size-12"></i> {{ $log->creator ? $log->creator->name : 'System' }}
                                <span class="mx-1">•</span>
                                <i class="bx bx-time font-size-12"></i> {{ $log->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="bx bx-history font-size-36 text-muted mb-2 d-block"></i>
                        <p class="text-muted mb-0">Belum ada aktivitas</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirm Delete --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bx bx-error-circle"></i> Konfirmasi Hapus Backup
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bx bx-trash font-size-48 text-danger"></i>
                </div>
                <p class="text-center mb-2">Anda yakin ingin menghapus backup ini?</p>
                <div class="alert alert-warning">
                    <strong>File:</strong> <span id="deleteFileName"></span>
                </div>
                <p class="text-muted font-size-13 mb-0">
                    <i class="bx bx-info-circle"></i> File yang sudah dihapus tidak dapat dikembalikan.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x"></i> Batal
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-trash"></i> Ya, Hapus Backup
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirm Restore --}}
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="bx bx-error-circle"></i> Konfirmasi Restore Database
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bx bx-refresh font-size-48 text-warning"></i>
                </div>
                <p class="text-center mb-2"><strong>Anda akan merestore database dari backup:</strong></p>
                <div class="alert alert-info">
                    <strong>File:</strong> <span id="restoreFileName"></span>
                </div>
                <div class="alert alert-danger">
                    <strong><i class="bx bx-error-circle"></i> PERHATIAN:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Semua data saat ini akan diganti dengan data backup</li>
                        <li>Database akan di-backup otomatis sebelum restore</li>
                        <li>Proses tidak bisa dibatalkan setelah dimulai</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x"></i> Batal
                </button>
                <form id="restoreForm" method="POST" style="display: inline;">
                    @csrf
                    @method('POST')
                    <button type="submit" class="btn btn-warning">
                        <i class="bx bx-refresh"></i> Ya, Restore Database
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
        <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-white mt-3" id="loadingText">Sedang memproses...</p>
    </div>
</div>

@endsection

@push('styles')
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-xs {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-soft {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

.bg-primary.bg-soft {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.bg-success.bg-soft {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

.bg-info.bg-soft {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

.bg-warning.bg-soft {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.bg-danger.bg-soft {
    background-color: rgba(220, 53, 69, 0.1) !important;
}
</style>
@endpush

@push('scripts')
<script>
// Submit create backup form (POST method)
function submitCreateBackup() {
    if (confirm('Buat backup database sekarang?')) {
        showLoading('Membuat backup...');
        document.getElementById('createBackupForm').submit();
    }
}

// Confirm delete backup
function confirmDelete(fileName) {
    document.getElementById('deleteFileName').textContent = fileName;
    document.getElementById('deleteForm').action = "{{ route('backup.delete', '') }}/" + fileName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Confirm restore backup
function confirmRestore(fileName) {
    document.getElementById('restoreFileName').textContent = fileName;
    document.getElementById('restoreForm').action = "{{ route('backup.restore', '') }}/" + fileName;
    
    const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}

// Show loading overlay
function showLoading(text) {
    document.getElementById('loadingText').textContent = text;
    document.getElementById('loadingOverlay').style.display = 'block';
}

// Show loading on form submit
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function() {
            showLoading('Menghapus backup...');
        });
    }
    
    const restoreForm = document.getElementById('restoreForm');
    if (restoreForm) {
        restoreForm.addEventListener('submit', function() {
            showLoading('Merestore database... Mohon tunggu, proses ini membutuhkan waktu beberapa menit.');
        });
    }
});
</script>
@endpush