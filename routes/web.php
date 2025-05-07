<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\KodeRekening\Index as KodeRekeningIndex;
use App\Http\Livewire\KodeRekening\Create as KodeRekeningCreate;
use App\Http\Livewire\KodeRekening\Edit as KodeRekeningEdit;
use App\Http\Livewire\TargetBulan\Index as TargetBulanIndex;
use App\Http\Livewire\TargetBulan\Create as TargetBulanCreate;
use App\Http\Livewire\TargetBulan\Edit as TargetBulanEdit;
use App\Http\Livewire\TargetAnggaran\Index as TargetAnggaranIndex;
use App\Http\Livewire\TargetAnggaran\Create as TargetAnggaranCreate;
use App\Http\Livewire\TargetAnggaran\Edit as TargetAnggaranEdit;
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

// Route untuk halaman utama - cek apakah user sudah login
Route::get('/', function () {
    // Jika sudah login, arahkan ke dashboard
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    // Jika belum login, arahkan ke halaman login
    return redirect()->route('login');
});

// === ROUTES UNTUK GUEST (BELUM LOGIN) ===
Route::middleware('guest')->group(function () {
    // Route untuk halaman login
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    // Route untuk proses autentikasi
    Route::post('/authenticate', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak cocok dengan catatan kami.',
        ])->onlyInput('email');
    })->name('authenticate');
});

// === ROUTES UNTUK USER YANG SUDAH LOGIN ===
Route::middleware('auth')->group(function () {
    // Route untuk logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    // Profile route
    Route::get('/profile/edit', function() {
        return view('profile.edit');
    })->name('profile.edit');

    // Route yang bisa diakses oleh semua user yang sudah login
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/laporan', LaporanIndex::class)->name('laporan.index');

    // === ROUTES UNTUK ADMINISTRATOR ===
    Route::middleware('role:Administrator')->group(function () {
        Route::get('/user', UserIndex::class)->name('user.index');
        Route::get('/user/create', UserCreate::class)->name('user.create');
        Route::get('/user/{id}/edit', UserEdit::class)->name('user.edit');
        
        // Backup routes
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/create', [BackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/download/{fileName}', [BackupController::class, 'download'])->name('backup.download');
        Route::get('/backup/delete/{fileName}', [BackupController::class, 'delete'])->name('backup.delete');
        Route::get('/backup/restore/{fileName}', [BackupController::class, 'restore'])->name('backup.restore');
    });

    // === ROUTES UNTUK ADMINISTRATOR DAN OPERATOR ===
    Route::middleware('role:Administrator,Operator')->group(function () {
        // Tahun Anggaran routes
        Route::get('/tahun-anggaran', TahunAnggaranIndex::class)->name('tahun-anggaran.index');
        Route::get('/tahun-anggaran/create', TahunAnggaranCreate::class)->name('tahun-anggaran.create');
        Route::get('/tahun-anggaran/{id}/edit', TahunAnggaranEdit::class)->name('tahun-anggaran.edit');
        
        // Kode Rekening routes
        Route::get('/kode-rekening', KodeRekeningIndex::class)->name('kode-rekening.index');
        Route::get('/kode-rekening/create', KodeRekeningCreate::class)->name('kode-rekening.create');
        Route::get('/kode-rekening/{id}/edit', KodeRekeningEdit::class)->name('kode-rekening.edit');
        
        // Target Bulan routes
        Route::get('/target-bulan', TargetBulanIndex::class)->name('target-bulan.index');
        Route::get('/target-bulan/create', TargetBulanCreate::class)->name('target-bulan.create');
        Route::get('/target-bulan/{id}/edit', TargetBulanEdit::class)->name('target-bulan.edit');
        
        // Target Anggaran routes
        Route::get('/target-anggaran', TargetAnggaranIndex::class)->name('target-anggaran.index');
        Route::get('/target-anggaran/create', TargetAnggaranCreate::class)->name('target-anggaran.create');
        Route::get('/target-anggaran/{id}/edit', TargetAnggaranEdit::class)->name('target-anggaran.edit');
        
        // Target Periode Routes
Route::get('/target-periode', App\Http\Livewire\TargetPeriode\Index::class)->name('target-periode.index');
Route::get('/target-periode/create', App\Http\Livewire\TargetPeriode\Create::class)->name('target-periode.create');
Route::get('/target-periode/{id}/edit', App\Http\Livewire\TargetPeriode\Edit::class)->name('target-periode.edit');

        // Penerimaan routes
        Route::get('/penerimaan', PenerimaanIndex::class)->name('penerimaan.index');
        Route::get('/penerimaan/create', PenerimaanCreate::class)->name('penerimaan.create');
        Route::get('/penerimaan/{id}/edit', PenerimaanEdit::class)->name('penerimaan.edit');
    });
});