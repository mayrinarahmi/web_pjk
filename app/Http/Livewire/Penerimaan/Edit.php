<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\Skpd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Edit extends Component
{
    public $penerimaanId;
    public $tanggal;
    public $kode_rekening_id;
    public $tahun;
    public $jumlah;
    public $keterangan;
    
    // SKPD Management
    public $selectedSkpdId;
    public $skpdList = [];
    public $userSkpdInfo = '';
    public $showSkpdDropdown = false;
    public $originalSkpdId; // Track original SKPD
    
    public $kodeRekeningLevel6 = [];
    public $availableYears = [];
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'tahun' => 'required|integer|min:2000|max:2100',
        'jumlah' => 'required|numeric',
        'keterangan' => 'nullable|string|max:255',
        'selectedSkpdId' => 'nullable|exists:skpd,id',
    ];
    
    public function mount($id)
    {
        $penerimaan = Penerimaan::findOrFail($id);
        $user = auth()->user();
        
        // ==========================================
        // AUTHORIZATION CHECK
        // ==========================================
        if (!$this->authorizeEdit($user, $penerimaan)) {
            session()->flash('error', 'Anda tidak memiliki akses untuk mengedit data ini.');
            return redirect()->route('penerimaan.index');
        }
        
        // Store original data
        $this->penerimaanId = $penerimaan->id;
        $this->tanggal = $penerimaan->tanggal->format('Y-m-d');
        $this->kode_rekening_id = $penerimaan->kode_rekening_id;
        $this->tahun = $penerimaan->tahun;
        $this->jumlah = $penerimaan->jumlah;
        $this->keterangan = $penerimaan->keterangan;
        $this->originalSkpdId = $penerimaan->skpd_id;
        $this->selectedSkpdId = $penerimaan->skpd_id;
        
        // ==========================================
        // INITIALIZE SKPD CONTEXT
        // ==========================================
        $this->initializeSkpdContext($user, $penerimaan);
        
        // Load available years
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
        
        // Load kode rekening
        $this->loadKodeRekening();
        
        Log::info('Penerimaan Edit mounted', [
            'user' => $user->name,
            'penerimaan_id' => $id,
            'skpd' => $penerimaan->skpd ? $penerimaan->skpd->nama_opd : 'No SKPD',
            'show_skpd_dropdown' => $this->showSkpdDropdown,
            'available_kode_rekening' => is_countable($this->kodeRekeningLevel6) ? count($this->kodeRekeningLevel6) : 0
        ]);
    }
    
    /**
     * Authorization check - siapa yang boleh edit
     */
    private function authorizeEdit($user, $penerimaan)
    {
        // Kepala Badan TIDAK BISA EDIT
        if ($user->isKepalaBadan()) {
            return false;
        }
        
        // Super Admin BISA EDIT SEMUA
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Operator SKPD HANYA BISA EDIT DATA SKPD SENDIRI
        if ($user->skpd_id) {
            return $penerimaan->skpd_id == $user->skpd_id;
        }
        
        // Default: tidak ada akses
        return false;
    }
    
    /**
     * Initialize SKPD context berdasarkan role user
     */
    private function initializeSkpdContext($user, $penerimaan)
    {
        if ($user->isSuperAdmin()) {
            // Super Admin - bisa ganti SKPD
            $this->showSkpdDropdown = true;
            $this->skpdList = Skpd::where('status', 'aktif')
                                  ->orderBy('nama_opd')
                                  ->get();
            
            $skpdName = $penerimaan->skpd ? $penerimaan->skpd->nama_opd : 'Tanpa SKPD';
            $this->userSkpdInfo = 'Super Admin - Edit data SKPD: ' . $skpdName;
            
        } elseif ($user->skpd_id && $user->skpd) {
            // Operator SKPD - locked, tidak bisa ganti SKPD
            $this->showSkpdDropdown = false;
            $this->userSkpdInfo = 'Edit data untuk: ' . $user->skpd->nama_opd;
            
        } else {
            // Seharusnya tidak sampai sini (sudah di-block di authorization)
            $this->showSkpdDropdown = false;
            $this->userSkpdInfo = 'Tidak dapat mengedit data ini';
        }
    }
    
    /**
     * Load kode rekening berdasarkan SKPD yang dipilih
     * FIXED: Proper handling untuk SKPD yang belum di-assign
     */
    private function loadKodeRekening()
    {
        $user = auth()->user();
        $query = KodeRekening::where('level', 6)
                             ->where('is_active', true);
        
        if ($user->isSuperAdmin()) {
            // Super Admin - filter by selected SKPD
            if ($this->selectedSkpdId) {
                $selectedSkpd = Skpd::find($this->selectedSkpdId);
                if ($selectedSkpd) {
                    $allowedKodes = $selectedSkpd->kode_rekening_access;
                    
                    // FIXED: Cek NULL dan empty array dengan ketat
                    if (is_array($allowedKodes) && !empty($allowedKodes)) {
                        $query->whereIn('id', $allowedKodes);
                    } else {
                        // SKPD belum ada assignment - return empty
                        $this->kodeRekeningLevel6 = collect([]);
                        return; // CRITICAL: Stop execution here!
                    }
                } else {
                    $this->kodeRekeningLevel6 = collect([]);
                    return;
                }
            }
            // Jika tidak ada SKPD dipilih - show all
            
        } elseif ($user->skpd_id && $user->skpd) {
            // Operator SKPD - hanya kode yang di-assign
            $allowedKodes = $user->skpd->kode_rekening_access;
            
            // FIXED: Cek NULL dan empty array dengan ketat
            if (is_array($allowedKodes) && !empty($allowedKodes)) {
                $query->whereIn('id', $allowedKodes);
            } else {
                // SKPD belum ada assignment - HARUS RETURN EMPTY!
                $this->kodeRekeningLevel6 = collect([]);
                return; // CRITICAL: Stop execution here!
            }
            
        } else {
            $this->kodeRekeningLevel6 = collect([]);
            return;
        }
        
        // HANYA EKSEKUSI INI jika sudah lolos semua validasi di atas
        $this->kodeRekeningLevel6 = $query->orderBy('kode')->get();
    }
    
    /**
     * Handler ketika SKPD dipilih (untuk Super Admin)
     */
    public function updatedSelectedSkpdId()
    {
        $user = auth()->user();
        
        // Hanya Super Admin yang bisa ganti SKPD
        if (!$user->isSuperAdmin()) {
            $this->selectedSkpdId = $this->originalSkpdId;
            session()->flash('error', 'Anda tidak dapat mengubah SKPD untuk data ini.');
            return;
        }
        
        // Reload kode rekening sesuai SKPD yang dipilih
        $this->loadKodeRekening();
        
        // Reset kode rekening selection jika kode lama tidak ada di list baru
        if (!$this->kodeRekeningLevel6 instanceof \Illuminate\Support\Collection) {
            $this->kodeRekeningLevel6 = collect([]);
        }
        
        $allowedIds = $this->kodeRekeningLevel6->pluck('id')->toArray();
        if (!in_array($this->kode_rekening_id, $allowedIds)) {
            $this->kode_rekening_id = null;
        }
        
        // Update info
        if ($this->selectedSkpdId) {
            $skpd = Skpd::find($this->selectedSkpdId);
            if ($skpd) {
                $this->userSkpdInfo = 'Edit data untuk SKPD: ' . $skpd->nama_opd;
            }
        }
    }
    
    /**
     * Validasi kode rekening sesuai SKPD access
     */
    private function validateKodeRekeningAccess()
    {
        // FIXED: Handle collection properly
        if (!$this->kodeRekeningLevel6 instanceof \Illuminate\Support\Collection) {
            $this->kodeRekeningLevel6 = collect([]);
        }
        
        $allowedIds = $this->kodeRekeningLevel6->pluck('id')->toArray();
        
        if (!in_array($this->kode_rekening_id, $allowedIds)) {
            session()->flash('error', 'Kode rekening yang dipilih tidak tersedia untuk SKPD ini.');
            return false;
        }
        
        return true;
    }
    
    public function update()
    {
        // FIXED: Validasi awal - cek apakah ada kode rekening tersedia
        if (!$this->kodeRekeningLevel6 instanceof \Illuminate\Support\Collection) {
            $this->kodeRekeningLevel6 = collect([]);
        }
        
        if ($this->kodeRekeningLevel6->isEmpty()) {
            session()->flash('error', 'Tidak ada kode rekening yang tersedia untuk SKPD ini. Hubungi administrator untuk assignment kode rekening.');
            return;
        }
        
        $this->validate();
        
        $user = auth()->user();
        $penerimaan = Penerimaan::find($this->penerimaanId);
        
        if (!$penerimaan) {
            session()->flash('error', 'Data penerimaan tidak ditemukan.');
            return redirect()->route('penerimaan.index');
        }
        
        // ==========================================
        // RE-CHECK AUTHORIZATION
        // ==========================================
        if (!$this->authorizeEdit($user, $penerimaan)) {
            session()->flash('error', 'Anda tidak memiliki akses untuk mengedit data ini.');
            return redirect()->route('penerimaan.index');
        }
        
        // ==========================================
        // VALIDATION 1: Level 6 Check
        // ==========================================
        $kodeRekening = KodeRekening::find($this->kode_rekening_id);
        if (!$kodeRekening || $kodeRekening->level != 6) {
            session()->flash('error', 'Penerimaan hanya dapat diinput untuk kode rekening level 6.');
            return;
        }
        
        // ==========================================
        // VALIDATION 2: Kode Rekening Access
        // ==========================================
        if (!$this->validateKodeRekeningAccess()) {
            return;
        }
        
        // ==========================================
        // VALIDATION 3: Tahun Check
        // ==========================================
        $tahunExists = TahunAnggaran::where('tahun', $this->tahun)->exists();
        if (!$tahunExists) {
            session()->flash('error', 'Tahun ' . $this->tahun . ' tidak tersedia di tahun anggaran.');
            return;
        }
        
        // ==========================================
        // VALIDATION 4: Tahun Consistency
        // ==========================================
        $tahunDariTanggal = Carbon::parse($this->tanggal)->year;
        if ($tahunDariTanggal != $this->tahun) {
            session()->flash('error', 'Tahun pada tanggal (' . $tahunDariTanggal . ') harus sama dengan tahun anggaran (' . $this->tahun . ').');
            return;
        }
        
        // ==========================================
        // DETERMINE SKPD ID
        // ==========================================
        $skpdId = null;
        
        if ($user->isSuperAdmin()) {
            // Super Admin bisa ganti SKPD
            if (!$this->selectedSkpdId) {
                session()->flash('error', 'Silakan pilih SKPD terlebih dahulu.');
                return;
            }
            $skpdId = $this->selectedSkpdId;
            
        } elseif ($user->skpd_id) {
            // Operator SKPD - locked ke SKPD sendiri
            $skpdId = $user->skpd_id;
            
        } else {
            session()->flash('error', 'Tidak dapat menentukan SKPD untuk data ini.');
            return;
        }
        
        // ==========================================
        // VALIDATION 5: SKPD Exists
        // ==========================================
        $skpd = Skpd::find($skpdId);
        if (!$skpd) {
            session()->flash('error', 'SKPD tidak ditemukan.');
            return;
        }
        
        // ==========================================
        // UPDATE PENERIMAAN
        // ==========================================
        $penerimaan->update([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun' => $this->tahun,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
            'skpd_id' => $skpdId,
            'updated_by' => $user->id,
        ]);
        
        Log::info('Penerimaan updated', [
            'user' => $user->name,
            'penerimaan_id' => $this->penerimaanId,
            'skpd_id' => $skpdId,
            'skpd_nama' => $skpd->nama_opd,
            'kode_rekening' => $kodeRekening->kode,
            'jumlah' => $this->jumlah,
            'skpd_changed' => $this->originalSkpdId != $skpdId
        ]);
        
        session()->flash('message', 'Data penerimaan berhasil diperbarui untuk ' . $skpd->nama_opd . '.');
        return redirect()->route('penerimaan.index');
    }
    
    /**
     * Auto-update tahun berdasarkan tanggal
     */
    public function updatedTanggal()
    {
        if ($this->tanggal) {
            $tahunDariTanggal = Carbon::parse($this->tanggal)->year;
            
            if (in_array($tahunDariTanggal, $this->availableYears)) {
                $this->tahun = $tahunDariTanggal;
            }
        }
    }
    
    public function render()
    {
        return view('livewire.penerimaan.edit');
    }
}