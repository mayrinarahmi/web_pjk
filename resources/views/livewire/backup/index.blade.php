<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Backup & Restore Database</h5>
            <button type="button" class="btn btn-primary btn-sm" wire:click="createBackup" wire:loading.attr="disabled">
                <i class="bx bx-plus"></i> Buat Backup Baru
            </button>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <p class="mb-0">
                    <i class="bx bx-info-circle"></i> Backup secara berkala membantu melindungi data Anda dari kehilangan yang tidak diinginkan.
                </p>
            </div>
            
            <div class="mb-4">
                <h6>Unggah File Backup</h6>
                <form wire:submit.prevent="uploadBackup">
                    <div class="input-group">
                        <input type="file" class="form-control @error('backupFile') is-invalid @enderror" wire:model="backupFile">
                        <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                            <i class="bx bx-upload"></i> Unggah
                        </button>
                    </div>
                    @error('backupFile')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </form>
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
                                    <button type="button" class="btn btn-info btn-sm" wire:click="downloadBackup('{{ $backup['path'] }}')" wire:loading.attr="disabled">
                                        <i class="bx bx-download"></i> Download
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" wire:click="restoreBackup('{{ $backup['path'] }}')" onclick="confirm('Anda yakin ingin melakukan restore dari backup ini? Semua data saat ini akan diganti.') || event.stopImmediatePropagation()" wire:loading.attr="disabled">
                                        <i class="bx bx-refresh"></i> Restore
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" wire:click="deleteBackup('{{ $backup['path'] }}')" onclick="confirm('Anda yakin ingin menghapus backup ini?') || event.stopImmediatePropagation()" wire:loading.attr="disabled">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
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
</div>
