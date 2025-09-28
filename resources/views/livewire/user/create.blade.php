<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Pengguna</h5>
            <a href="{{ route('user.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <!-- Alert Messages -->
            @if (session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <form wire:submit.prevent="save">
                <div class="row">
                    <!-- NIP -->
                    <div class="col-md-6 mb-3">
                        <label for="nip" class="form-label">NIP <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('nip') is-invalid @enderror" 
                               id="nip" 
                               wire:model="nip" 
                               placeholder="Masukkan NIP 18 digit"
                               maxlength="18"
                               pattern="[0-9]{18}"
                               title="NIP harus 18 digit angka">
                        @error('nip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Format: 18 digit angka (contoh: 198001011990031001)</div>
                    </div>
                    
                    <!-- Nama -->
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               wire:model="name" 
                               placeholder="Masukkan nama lengkap">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               wire:model="email" 
                               placeholder="Masukkan email"
                               autocomplete="new-email"
                               readonly onfocus="this.removeAttribute('readonly');">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Role -->
                    <div class="col-md-6 mb-3">
                        <label for="spatie_role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('spatie_role') is-invalid @enderror" 
                                id="spatie_role" 
                                wire:model.live="spatie_role">
                            <option value="">-- Pilih Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('spatie_role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            @if($spatie_role == 'Super Admin' || $spatie_role == 'Administrator')
                                <span class="text-info"><i class="bx bx-info-circle"></i> Full akses sistem</span>
                            @elseif($spatie_role == 'Kepala Badan')
                                <span class="text-info"><i class="bx bx-info-circle"></i> View only + Export laporan</span>
                            @elseif($spatie_role == 'Operator SKPD')
                                <span class="text-info"><i class="bx bx-info-circle"></i> Input penerimaan untuk SKPD tertentu</span>
                            @elseif($spatie_role == 'Operator')
                                <span class="text-info"><i class="bx bx-info-circle"></i> Kelola master data terbatas</span>
                            @elseif($spatie_role == 'Viewer')
                                <span class="text-info"><i class="bx bx-info-circle"></i> Hanya lihat dashboard & laporan</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- SKPD (Conditional) -->
                @if($showSkpdField)
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="skpd_id" class="form-label">
                            SKPD 
                            @if($skpdRequired)
                                <span class="text-danger">*</span>
                            @else
                                <span class="text-muted">(Optional)</span>
                            @endif
                        </label>
                        <select class="form-select @error('skpd_id') is-invalid @enderror" 
                                id="skpd_id" 
                                wire:model="skpd_id"
                                @if($spatie_role == 'Kepala Badan') disabled @endif>
                            <option value="">-- Pilih SKPD --</option>
                            @foreach($skpdList as $skpd)
                                <option value="{{ $skpd->id }}">{{ $skpd->nama_opd }}</option>
                            @endforeach
                        </select>
                        @error('skpd_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($spatie_role == 'Kepala Badan')
                            <div class="form-text text-info">
                                <i class="bx bx-info-circle"></i> Kepala Badan otomatis menggunakan BPKPAD
                            </div>
                        @endif
                    </div>
                </div>
                @endif
                
                <div class="row">
                    <!-- Password -->
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               wire:model="password" 
                               placeholder="Minimal 8 karakter"
                               autocomplete="new-password"
                               readonly onfocus="this.removeAttribute('readonly');">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Konfirmasi Password -->
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control @error('password_confirmation') is-invalid @enderror" 
                               id="password_confirmation" 
                               wire:model="password_confirmation" 
                               placeholder="Ulangi password"
                               autocomplete="new-password"
                               readonly onfocus="this.removeAttribute('readonly');">
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Information Box -->
                <div class="alert alert-info mb-3">
                    <h6 class="alert-heading"><i class="bx bx-info-circle"></i> Informasi Login</h6>
                    <hr>
                    <p class="mb-0">User akan login menggunakan:</p>
                    <ul class="mb-0">
                        <li><strong>Username:</strong> NIP yang diinput</li>
                        <li><strong>Password:</strong> Password yang dibuat</li>
                    </ul>
                </div>
                
                <div class="text-end">
                    <a href="{{ route('user.index') }}" class="btn btn-secondary">
                        <i class="bx bx-x"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="bx bx-save"></i> Simpan
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto format NIP to only accept numbers
    document.addEventListener('DOMContentLoaded', function() {
        const nipInput = document.getElementById('nip');
        if (nipInput) {
            nipInput.addEventListener('input', function(e) {
                // Remove non-numeric characters
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
    });
</script>