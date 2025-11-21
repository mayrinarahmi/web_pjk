<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Http\Livewire\Dashboard;

// Controllers untuk Laporan Realisasi
use App\Http\Controllers\LaporanRealisasiController;
use App\Http\Controllers\PublicDashboardController;

// Livewire Components
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

// TEST ROUTE - DELETE AFTER TESTING
Route::get('/test-route', function() {
    return view('welcome'); // Laravel default view
});

Route::get('/test-public-view', function() {
    return view('public-dashboard.index', ['tahun' => 2025]);
});


// Route untuk halaman utama - cek apakah user sudah login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Public Dashboard - Main Page
Route::get('/dashboard-publik', [PublicDashboardController::class, 'index'])
    ->name('public.dashboard');

// Public Trend Analysis (Optional - jika mau diimplementasikan)
Route::get('/trend-publik', function() {
    return view('public-dashboard.trend');
})->name('public.trend');

// ====================================
// GUEST ROUTES (BELUM LOGIN)
// ====================================
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    // Authenticate
    Route::post('/authenticate', function (Request $request) {
        $credentials = $request->validate([
            'login' => 'required|string|size:18',
            'password' => 'required'
        ], [
            'login.required' => 'NIP wajib diisi',
            'login.size' => 'NIP harus 18 digit',
            'password.required' => 'Password wajib diisi'
        ]);

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

        Log::warning('Failed login attempt', [
            'nip' => $credentials['login'],
            'ip' => $request->ip()
        ]);

        return back()->withErrors([
            'login' => 'NIP atau password yang Anda masukkan salah.',
        ])->onlyInput('login');
    })->name('authenticate');
});

// ====================================
// AUTHENTICATED ROUTES
// ====================================
Route::middleware('auth')->group(function () {
    
    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    // Profile
    Route::get('/profile/edit', function() {
        return view('profile.edit');
    })->name('profile.edit');

    // Dashboard - Semua user bisa akses
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    
    // Trend Analysis
    Route::middleware('permission:view-trend-analysis')->group(function () {
        Route::get('/trend-analysis', function() {
            return view('trend-analysis');
        })->name('trend-analysis');
    });

    // ====================================
    // LAPORAN ROUTES
    // ====================================
    Route::middleware('permission:view-laporan')->group(function () {
        Route::get('/laporan', LaporanIndex::class)->name('laporan.index');
        Route::get('/laporan/realisasi', [LaporanRealisasiController::class, 'index'])->name('laporan.realisasi');
        Route::get('/laporan/realisasi/data', [LaporanRealisasiController::class, 'getData'])->name('laporan.realisasi.data');
        Route::get('/laporan/realisasi/export-excel', [LaporanRealisasiController::class, 'exportExcel'])->name('laporan.realisasi.export.excel');
        Route::get('/laporan/realisasi/export-pdf', [LaporanRealisasiController::class, 'exportPDF'])->name('laporan.realisasi.export.pdf');
    });

    // ====================================
    // MASTER DATA ROUTES
    // ====================================
    
    // Tahun Anggaran
    Route::prefix('tahun-anggaran')->name('tahun-anggaran.')->group(function () {
        Route::middleware('permission:view-tahun-anggaran')->group(function () {
            Route::get('/', TahunAnggaranIndex::class)->name('index');
        });
        Route::middleware('permission:create-tahun-anggaran')->group(function () {
            Route::get('/create', TahunAnggaranCreate::class)->name('create');
        });
        Route::middleware('permission:edit-tahun-anggaran')->group(function () {
            Route::get('/{id}/edit', TahunAnggaranEdit::class)->name('edit');
        });
    });
    
    // Kode Rekening
    Route::prefix('kode-rekening')->name('kode-rekening.')->group(function () {
        Route::middleware('permission:view-kode-rekening')->group(function () {
            Route::get('/', KodeRekeningIndex::class)->name('index');
            Route::get('/template', function() {
                return response()->download(public_path('templates/template_kode_rekening.xlsx'));
            })->name('template');
        });
        Route::middleware('permission:create-kode-rekening')->group(function () {
            Route::get('/create', KodeRekeningCreate::class)->name('create');
        });
        Route::middleware('permission:edit-kode-rekening')->group(function () {
            Route::get('/{id}/edit', KodeRekeningEdit::class)->name('edit');
        });
    });
    
    // Target Periode
    Route::prefix('target-periode')->name('target-periode.')->group(function () {
        Route::middleware('permission:view-target')->group(function () {
            Route::get('/', TargetPeriodeIndex::class)->name('index');
        });
        Route::middleware('permission:create-target')->group(function () {
            Route::get('/create', TargetPeriodeCreate::class)->name('create');
        });
        Route::middleware('permission:edit-target')->group(function () {
            Route::get('/{id}/edit', TargetPeriodeEdit::class)->name('edit');
        });
    });
    
    // ====================================
    // TARGET ANGGARAN (PAGU) - UPDATED ✅
    // ====================================
    Route::prefix('target-anggaran')->name('target-anggaran.')->group(function () {
        // Index - View permission (SEMUA ROLE dengan permission view-target)
        Route::get('/', TargetAnggaranIndex::class)
            ->name('index')
            ->middleware('permission:view-target');
        
        // Create - Create permission
        Route::get('/create', TargetAnggaranCreate::class)
            ->name('create')
            ->middleware('permission:create-target');
        
        // Edit - Edit permission
        Route::get('/{id}/edit', TargetAnggaranEdit::class)
            ->name('edit')
            ->middleware('permission:edit-target');
    });
    
    // Target Bulan (legacy)
    Route::prefix('target-bulan')->name('target-bulan.')->group(function () {
        Route::middleware('permission:view-target')->group(function () {
            Route::get('/', TargetBulanIndex::class)->name('index');
        });
        Route::middleware('permission:create-target')->group(function () {
            Route::get('/create', TargetBulanCreate::class)->name('create');
        });
        Route::middleware('permission:edit-target')->group(function () {
            Route::get('/{id}/edit', TargetBulanEdit::class)->name('edit');
        });
    });

    // ====================================
    // TRANSAKSI ROUTES
    // ====================================
    
    // Penerimaan
    Route::prefix('penerimaan')->name('penerimaan.')->group(function () {
        Route::middleware('permission:view-penerimaan')->group(function () {
            Route::get('/', PenerimaanIndex::class)->name('index');
        });
        Route::middleware('permission:create-penerimaan')->group(function () {
            Route::get('/create', PenerimaanCreate::class)->name('create');
        });
        Route::middleware('permission:edit-penerimaan')->group(function () {
            Route::get('/{id}/edit', PenerimaanEdit::class)->name('edit');
        });
    });

    // ====================================
    // PENGATURAN ROUTES
    // ====================================
    
    // User Management
    Route::prefix('user')->name('user.')->group(function () {
        Route::middleware('permission:view-users')->group(function () {
            Route::get('/', UserIndex::class)->name('index');
        });
        Route::middleware('permission:create-users')->group(function () {
            Route::get('/create', UserCreate::class)->name('create');
        });
        Route::middleware('permission:edit-users')->group(function () {
            Route::get('/{id}/edit', UserEdit::class)->name('edit');
        });
    });

    // Backup Management
    Route::prefix('backup')->name('backup.')->middleware('permission:manage-backup')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'create'])->name('create');
        Route::get('/download/{fileName}', [BackupController::class, 'download'])->name('download');
        Route::post('/restore/{fileName}', [BackupController::class, 'restore'])->name('restore');
        Route::delete('/delete/{fileName}', [BackupController::class, 'delete'])->name('delete');
    });
    
    // SKPD Management
    Route::prefix('skpd')->name('skpd.')->middleware('permission:manage-skpd')->group(function () {
        Route::get('/', SkpdIndex::class)->name('index');
    });
});

// ====================================
// UTILITY ROUTES (DEVELOPMENT ONLY)
// ====================================
Route::get('/clear-all-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('permission:cache-reset');
    
    return "✅ All cache cleared successfully!<br><br>
    - Cache cleared<br>
    - Config cleared<br>
    - Route cleared<br>
    - View cleared<br>
    - Permission cache cleared<br><br>
    <a href='/'>Go to Home</a>";
})->name('clear-cache');