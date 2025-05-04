@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Backup & Restore Database</h5>
        <a href="{{ route('backup.create') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-plus"></i> Buat Backup Baru
        </a>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <p class="mb-0">
                <i class="bx bx-info-circle"></i> Backup secara berkala membantu melindungi data Anda dari kehilangan yang tidak diinginkan.
            </p>
        </div>
        
        <h6>Daftar Backup</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama File</th>
                        <th>Ukuran</th>
                        <th>Tanggal Dibuat</th>
                        <th width="25%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $key => $backup)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $backup['file_name'] }}</td>
                        <td>{{ round($backup['file_size'] / 1048576, 2) }} MB</td>
                        <td>{{ date('d M Y H:i:s', $backup['created_at']) }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('backup.download', $backup['file_name']) }}" class="btn btn-info btn-sm">
                                    <i class="bx bx-download"></i> Download
                                </a>
                                <a href="{{ route('backup.restore', $backup['file_name']) }}" class="btn btn-warning btn-sm" onclick="return confirm('Anda yakin ingin melakukan restore dari backup ini? Semua data saat ini akan diganti.')">
                                    <i class="bx bx-refresh"></i> Restore
                                </a>
                                <a href="{{ route('backup.delete', $backup['file_name']) }}" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus backup ini?')">
                                    <i class="bx bx-trash"></i> Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data backup</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
