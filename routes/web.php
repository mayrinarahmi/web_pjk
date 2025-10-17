<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;  // ← ADD THIS FOR TURNSTILE
use Illuminate\Support\Facades\Log;   // ← ADD THIS FOR LOGGING
use App\Http\Livewire\Dashboard;

// Controllers untuk Laporan Realisasi
use App\Http\Controllers\LaporanRealisasiController;

// Livewire Components (existing)
use App\Http\Livewire\KodeRekening\Index as KodeRekeningIndex;
use App\Http\Livewire\KodeRekening\Create as KodeRekeningCreate;
use App\Http\Livewire\KodeRekening\Edit as KodeRekeningEdit;
use App\Http\Livewire\TargetBulan\Index as TargetBulanIndex;
use App\Http\Livewire\TargetBulan\Create as TargetBulanCreate;
use App\Http\Livewire\TargetBulan\Edit as TargetBulanEdit;
use App\Http\Livewire\TargetAnggaran\Index as TargetAnggaranIndex;
use App\Http\Livewire\TargetAnggaran\Create as TargetAnggaranCreate;
use App\Http\Livewire\TargetAnggaran\Edit as TargetAnggaranEdit;
use App\Http\Livewire\TargetPeriode\Index as TargetPeriodeIndex;
use App\Http\Livewire\TargetPeriode\Create as TargetPeriodeCreate;
use App\Http\Livewire\TargetPeriode\Edit as TargetPeriodeEdit;
use App\Http\Livewire\Penerimaan\Index as PenerimaanIndex;
use App\Http\Livewire\Penerimaan\Create as PenerimaanCreate;
use App\Http\Livewire\Penerimaan\Edit as PenerimaanEdit;
use App\Http\Livewire\Laporan\Index as LaporanIndex;
use App\Http\Livewire\User\Index as UserIndex;
use App\Http\Livewire\User\Create as UserCreate;
use App\Http\Livewire\User\Edit as UserEdit;
use App\Http\Controllers\BackupController;
use App\Http\Livewire\TahunAnggaran\Index as TahunAnggaranIndex;
use App\Http\Livewire\TahunAnggaran\Create as TahunAnggaranCreate;
use App\Http\Livewire\TahunAnggaran\Edit as TahunAnggaranEdit;
use App\Http\Livewire\Skpd\Index as SkpdIndex;

// Route untuk halaman utama - cek apakah user sudah login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// === ROUTES UNTUK GUEST (BELUM LOGIN) ===
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    // ========================================
    // AUTHENTICATE ROUTE (WITH TURNSTILE)
    // ========================================
    Route::post('/authenticate', function (Request $request) {
        // Validate form inputs
        $credentials = $request->validate([
            'login' => 'required|string|size:18',
            'password' => 'required'
        ], [
            'login.required' => 'NIP wajib diisi',
            'login.size' => 'NIP harus 18 digit',
            'password.required' => 'Password wajib diisi'
        ]);

        // ========================================
        // CLOUDFLARE TURNSTILE VALIDATION
        // ========================================
        $turnstileToken = $request->input('cf-turnstile-response');
        
        // Check if token exists
        if (empty($turnstileToken)) {
            Log::warning('Turnstile validation failed: Empty token', [
                'ip' => $request->ip(),
                'nip' => $credentials['login']
            ]);
            
            return back()
                ->withErrors(['captcha' => 'Verifikasi keamanan gagal. Silakan refresh halaman dan coba lagi.'])
                ->withInput($request->except('password'));
        }
        
        try {
            // Call Cloudflare Turnstile API
            $response = Http::timeout(10)->asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => env('TURNSTILE_SECRET_KEY'),
                'response' => $turnstileToken,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();

            // Check validation result
            if (!($result['success'] ?? false)) {
                Log::warning('Turnstile validation failed', [
                    'ip' => $request->ip(),
                    'nip' => $credentials['login'],
                    'errors' => $result['error-codes'] ?? [],
                ]);
                
                return back()
                    ->withErrors(['captcha' => 'Verifikasi keamanan gagal. Silakan coba lagi.'])
                    ->withInput($request->except('password'));
            }
            
            Log::info('Turnstile validation success', [
                'ip' => $request->ip(),
                'nip' => $credentials['login']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Turnstile validation error', [
                'ip' => $request->ip(),
                'nip' => $credentials['login'],
                'error' => $e->getMessage()
            ]);
            
            return back()
                ->withErrors(['captcha' => 'Terjadi kesalahan verifikasi keamanan. Silakan coba lagi.'])
                ->withInput($request->except('password'));
        }

        // ========================================
        // EXISTING LOGIN LOGIC
        // ========================================
        $attemptCredentials = [
            'nip' => $credentials['login'],
            'password' => $credentials['password']
        ];

        if (Auth::attempt($attemptCredentials)) {
            $request->session()->regenerate();
            
            Log::info('User logged in successfully', [
                'user_id' => Auth::id(),
                'nip' => Auth::user()->nip,
                'name' => Auth::user()->name,
                'role' => Auth::user()->roles->pluck('name')->first(),
                'skpd' => Auth::user()->skpd ? Auth::user()->skpd->nama_opd : 'No SKPD',
                'ip' => $request->ip()
            ]);
            
            return redirect()->intended(route('dashboard'));
        }

        // Login failed
        Log::warning('Failed login attempt', [
            'nip' => $credentials['login'],
            'ip' => $request->ip()
        ]);

        return back()->withErrors([
            'login' => 'NIP atau password yang Anda masukkan salah.',
        ])->onlyInput('login');
    })->name('authenticate');
});

// === ROUTES UNTUK USER YANG SUDAH LOGIN ===
Route::middleware('auth')->group(function () {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    Route::get('/profile/edit', function() {
        return view('profile.edit');
    })->name('profile.edit');

    // Dashboard - Semua user bisa akses
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    
    // Trend Analysis PAGE (VIEW)
    Route::middleware('permission:view-trend-analysis')->group(function () {
        Route::get('/trend-analysis', function() {
            return view('trend-analysis');
        })->name('trend-analysis');
    });

    // Laporan - Semua user bisa akses
    Route::middleware('permission:view-laporan')->group(function () {
        Route::get('/laporan', LaporanIndex::class)->name('laporan.index');
        
        // ====================================
        // LAPORAN REALISASI ROUTES (NEW)
        // ====================================
        Route::get('/laporan/realisasi', [LaporanRealisasiController::class, 'index'])
            ->name('laporan.realisasi');
        
        Route::get('/laporan/realisasi/data', [LaporanRealisasiController::class, 'getData'])
            ->name('laporan.realisasi.data');
        
        Route::get('/laporan/realisasi/export-excel', [LaporanRealisasiController::class, 'exportExcel'])
            ->name('laporan.realisasi.export.excel');
        
        Route::get('/laporan/realisasi/export-pdf', [LaporanRealisasiController::class, 'exportPDF'])
            ->name('laporan.realisasi.export.pdf');
        // ====================================
    });

    // ====================================
    // MASTER DATA ROUTES
    // ====================================
    
    // Tahun Anggaran
    Route::middleware('permission:view-tahun-anggaran')->group(function () {
        Route::get('/tahun-anggaran', TahunAnggaranIndex::class)->name('tahun-anggaran.index');
    });
    
    Route::middleware('permission:create-tahun-anggaran')->group(function () {
        Route::get('/tahun-anggaran/create', TahunAnggaranCreate::class)->name('tahun-anggaran.create');
    });
    Route::middleware('permission:edit-tahun-anggaran')->group(function () {
        Route::get('/tahun-anggaran/{id}/edit', TahunAnggaranEdit::class)->name('tahun-anggaran.edit');
    });
    
    // Kode Rekening
    Route::middleware('permission:view-kode-rekening')->group(function () {
        Route::get('/kode-rekening', KodeRekeningIndex::class)->name('kode-rekening.index');
        Route::get('/kode-rekening/template', function() {
            return response()->download(public_path('templates/template_kode_rekening.xlsx'));
        })->name('kode-rekening.template');
    });
    
    Route::middleware('permission:create-kode-rekening')->group(function () {
        Route::get('/kode-rekening/create', KodeRekeningCreate::class)->name('kode-rekening.create');
    });
    Route::middleware('permission:edit-kode-rekening')->group(function () {
        Route::get('/kode-rekening/{id}/edit', KodeRekeningEdit::class)->name('kode-rekening.edit');
    });
    
    // Target Periode
    Route::middleware('permission:view-target')->group(function () {
        Route::get('/target-periode', TargetPeriodeIndex::class)->name('target-periode.index');
    });
    
    Route::middleware('permission:create-target')->group(function () {
        Route::get('/target-periode/create', TargetPeriodeCreate::class)->name('target-periode.create');
    });
    Route::middleware('permission:edit-target')->group(function () {
        Route::get('/target-periode/{id}/edit', TargetPeriodeEdit::class)->name('target-periode.edit');
    });
    
    // Target Anggaran (Pagu)
    Route::middleware('permission:view-target')->group(function () {
        Route::get('/target-anggaran', TargetAnggaranIndex::class)->name('target-anggaran.index');
    });
    
    Route::middleware('permission:create-target')->group(function () {
        Route::get('/target-anggaran/create', TargetAnggaranCreate::class)->name('target-anggaran.create');
    });
    Route::middleware('permission:edit-target')->group(function () {
        Route::get('/target-anggaran/{id}/edit', TargetAnggaranEdit::class)->name('target-anggaran.edit');
    });
    
    // Target Bulan (legacy)
    Route::middleware('permission:view-target')->group(function () {
        Route::get('/target-bulan', TargetBulanIndex::class)->name('target-bulan.index');
    });
    Route::middleware('permission:create-target')->group(function () {
        Route::get('/target-bulan/create', TargetBulanCreate::class)->name('target-bulan.create');
    });
    Route::middleware('permission:edit-target')->group(function () {
        Route::get('/target-bulan/{id}/edit', TargetBulanEdit::class)->name('target-bulan.edit');
    });

    // ====================================
    // TRANSAKSI ROUTES
    // ====================================
    
    // Penerimaan
    Route::middleware('permission:view-penerimaan')->group(function () {
        Route::get('/penerimaan', PenerimaanIndex::class)->name('penerimaan.index');
    });
    
    Route::middleware('permission:create-penerimaan')->group(function () {
        Route::get('/penerimaan/create', PenerimaanCreate::class)->name('penerimaan.create');
    });
    Route::middleware('permission:edit-penerimaan')->group(function () {
        Route::get('/penerimaan/{id}/edit', PenerimaanEdit::class)->name('penerimaan.edit');
    });

    // ====================================
    // PENGATURAN ROUTES
    // ====================================
    
    // User Management
    Route::middleware('permission:view-users')->group(function () {
        Route::get('/user', UserIndex::class)->name('user.index');
    });
    Route::middleware('permission:create-users')->group(function () {
        Route::get('/user/create', UserCreate::class)->name('user.create');
    });
    Route::middleware('permission:edit-users')->group(function () {
        Route::get('/user/{id}/edit', UserEdit::class)->name('user.edit');
    });

    // Backup routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup/create', [BackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/download/{fileName}', [BackupController::class, 'download'])->name('backup.download');
        Route::post('/backup/restore/{fileName}', [BackupController::class, 'restore'])->name('backup.restore');
        Route::delete('/backup/delete/{fileName}', [BackupController::class, 'delete'])->name('backup.delete');
    });
    
    // SKPD Management Routes
    Route::middleware(['auth', 'role:Super Admin|Administrator'])->group(function () {
        Route::get('/skpd', SkpdIndex::class)->name('skpd.index');
    });
});

// TEMPORARY - HAPUS SETELAH DIGUNAKAN
Route::get('/clear-all-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    
    return "Cache cleared successfully!";
});