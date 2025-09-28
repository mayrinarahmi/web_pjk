@extends('layouts.auth')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="auth-logo text-center mb-2">
            <img src="{{ asset('images/logo.png') }}" alt="Logo BKPAD Banjarmasin" class="pkpad-logo" style="width: 300px; height: auto; max-width: 100%; margin-bottom: 10px;">
        </div>
        
        <h1 class="login-title text-center mb-4" style="font-size: 1.2rem; color: #6c757d; font-weight: 500;">(Sistem Informasi Laporan Pendapatan Automatis Terpadu)</h1>
        
        <div class="card auth-card">
            <div class="card-body p-4">
                <h4 class="mb-4 text-center">Login</h4>
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('authenticate') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="login" class="form-label">NIP</label>
                        <input type="text" 
                               class="form-control @error('login') is-invalid @enderror" 
                               id="login" 
                               name="login" 
                               placeholder="Masukkan NIP 18 digit" 
                               maxlength="18"
                               pattern="[0-9]{18}"
                               title="NIP harus 18 digit angka"
                               required 
                               autofocus>
                        @error('login')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Gunakan NIP untuk login (18 digit angka)</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">PASSWORD</label>
                        <div class="input-group input-group-merge">
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="••••••••" 
                                   required>
                            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn-login w-100">Login</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="mb-0">© AMR - {{ date('Y') }} BPKPAD Kota Banjarmasin</p>
        </div>
    </div>
</div>

<style>
/* Additional CSS untuk memperbaiki tampilan logo */
.auth-logo {
    margin-bottom: 0.5rem;
}

.pkpad-logo {
    width: 300px !important;
    height: auto !important;
    max-width: 100% !important;
    margin-bottom: 10px !important;
    display: block !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

.login-title {
    font-size: 1.2rem !important;
    color: #6c757d !important;
    font-weight: 500 !important;
    margin-bottom: 2rem !important;
    text-align: center !important;
    margin-top: 0 !important;
}

.auth-card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: none;
    border-radius: 10px;
}

.btn-login {
    width: 100%;
    padding: 12px;
    background-color: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    transition: background-color 0.3s;
}

.btn-login:hover {
    background-color: #5855eb;
    color: white;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 10px 12px;
}

.form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
}

.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .pkpad-logo {
        width: 200px !important;
    }
    
    .login-title {
        font-size: 1rem !important;
    }
}
</style>

<script>
    // Toggle password visibility
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('.input-group-text');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bx-hide');
                    icon.classList.toggle('bx-show');
                }
            });
        }
        
        // Auto format NIP input to only accept numbers
        const nipInput = document.getElementById('login');
        if (nipInput) {
            nipInput.addEventListener('input', function(e) {
                // Remove non-numeric characters
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
    });
</script>
@endsection