<?php

namespace App\Http\Livewire\Penerimaan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\Skpd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    public $tanggal;
    public $kode_rekening_id;
    public $jumlah;
    public $keterangan;
    public $tahun;
    
    // SKPD Management
    public $selectedSkpdId;
    public $skpdList = [];
    public $userSkpdInfo = '';
    public $showSkpdDropdown = false;
    
    public $kodeRekeningLevel6 = [];
    public $availableYears = [];
    
    protected $rules = [
        'tanggal' => 'required|date',
        'kode_rekening_id' => 'required|exists:kode_rekening,id',
        'jumlah' => 'required|numeric',
        'keterangan' => 'nullable|string|max:255',
        'tahun' => 'required|integer|min:2000|max:2100',
        'selectedSkpdId' => 'nullable|exists:skpd,id',
    ];
    
    public function mount()
    {
        $user = auth()->user();
        
        // Initialize SKPD context
        $this->initializeSkpdContext($user);
        
        // Set tanggal hari ini
        $this->tanggal = date('Y-m-d');
        
        // Ambil tahun dari tahun anggaran aktif
        $activeTahun = TahunAnggaran::getActive();
        $this->tahun = $activeTahun ? $activeTahun->tahun : Carbon::now()->year;
        
        // Ambil semua tahun yang tersedia
        $this->availableYears = TahunAnggaran::distinct()
                                           ->orderBy('tahun', 'desc')
                                           ->pluck('tahun')
                                           ->toArray();
        
        if (empty($this->availableYears)) {
            session()->flash('error', 'Tidak ada tahun anggaran yang tersedia. Silakan buat tahun anggaran terlebih dahulu.');
            return redirect()->route('tahun-anggaran.index');
        }
        
        // Load kode rekening
        $this->loadKodeRekening();
        
        Log::info('Penerimaan Create mounted', [
            'user' => $user->name,
            'skpd' => $user->skpd ? $user->skpd->nama_opd : 'No SKPD',
            'available_kode_rekening' => is_countable($this->kodeRekeningLevel6) ? count($this->kodeRekeningLevel6) : 0,
            'show_skpd_dropdown' => $this->showSkpdDropdown
        ]);
    }
    
    /**
     * Initialize SKPD context berdasarkan role user
     */
    private function initializeSkpdContext($user)
    {
        if ($user->canViewAllSkpd()) {
            // Super Admin / Kepala Badan - bisa pilih SKPD
            $this->showSkpdDropdown = true;
            $this->skpdList = Skpd::where('status', 'aktif')
                                  ->orderBy('nama_opd')
                                  ->get();
            
            $roleName = $user->isSuperAdmin() ? 'Super Admin' : 'Kepala Badan';
            $this->userSkpdInfo = $roleName . ' - Pilih SKPD untuk input data';
            
            // Default select BPKPAD untuk Super Admin
            if ($user->isSuperAdmin()) {
                $bpkpad = Skpd::where('kode_opd', '5.02.0.00.0.00.05.0000')->first();
                $this->selectedSkpdId = $bpkpad ? $bpkpad->id : null;
            }
            
        } elseif ($user->skpd_id && $user->skpd) {
            // Operator SKPD - locked ke SKPD sendiri
            $this->showSkpdDropdown = false;
            $this->selectedSkpdId = $user->skpd_id;
            $this->userSkpdInfo = 'Input untuk SKPD: ' . $user->skpd->nama_opd;
            
        } else {
            // User tanpa SKPD
            $this->showSkpdDropdown = false;
            $this->userSkpdInfo = 'Anda tidak terdaftar di SKPD manapun';
            session()->flash('error', 'Anda tidak dapat menginput data karena tidak terdaftar di SKPD manapun.');
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

        // Filter kode rekening berdasarkan tahun yang dipilih
        if ($this->tahun) {
            $query->forTahun($this->tahun);
        }

        if ($user->canViewAllSkpd()) {
            // Super Admin/Kepala Badan - filter by selected SKPD
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
                    // SKPD tidak ditemukan
                    $this->kodeRekeningLevel6 = collect([]);
                    return;
                }
            }
            // Jika tidak ada SKPD dipilih - show all (untuk Super Admin)
            
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
            // User tanpa SKPD - no access
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
        
        // Hanya Super Admin/Kepala Badan yang bisa ganti SKPD
        if (!$user->canViewAllSkpd()) {
            $this->selectedSkpdId = $user->skpd_id;
            session()->flash('error', 'Anda tidak dapat mengubah SKPD.');
            return;
        }
        
        // Reload kode rekening sesuai SKPD yang dipilih
        $this->loadKodeRekening();
        
        // Reset kode rekening selection
        $this->kode_rekening_id = null;
        
        // Update info
        if ($this->selectedSkpdId) {
            $skpd = Skpd::find($this->selectedSkpdId);
            if ($skpd) {
                $this->userSkpdInfo = 'Input untuk SKPD: ' . $skpd->nama_opd;
            }
        } else {
            $roleName = $user->isSuperAdmin() ? 'Super Admin' : 'Kepala Badan';
            $this->userSkpdInfo = $roleName . ' - Pilih SKPD untuk input data';
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
            session()->flash('error', 'Kode rekening yang dipilih tidak tersedia untuk SKPD Anda.');
            return false;
        }
        
        return true;
    }
    
    public function save()
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
        // VALIDATION 5: SKPD ID Check
        // ==========================================
        $skpdId = null;
        
        if ($user->canViewAllSkpd()) {
            // Super Admin/Kepala Badan - gunakan selectedSkpdId
            if (!$this->selectedSkpdId) {
                session()->flash('error', 'Silakan pilih SKPD terlebih dahulu.');
                return;
            }
            $skpdId = $this->selectedSkpdId;
            
        } elseif ($user->skpd_id) {
            // Operator SKPD - gunakan skpd_id user
            $skpdId = $user->skpd_id;
            
        } else {
            session()->flash('error', 'Tidak dapat menentukan SKPD untuk data ini.');
            return;
        }
        
        // ==========================================
        // VALIDATION 6: SKPD Exists
        // ==========================================
        $skpd = Skpd::find($skpdId);
        if (!$skpd) {
            session()->flash('error', 'SKPD tidak ditemukan.');
            return;
        }
        
        // ==========================================
        // CREATE PENERIMAAN
        // ==========================================
        Penerimaan::create([
            'tanggal' => $this->tanggal,
            'kode_rekening_id' => $this->kode_rekening_id,
            'tahun' => $this->tahun,
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
            'skpd_id' => $skpdId,
            'created_by' => $user->id,
        ]);
        
        Log::info('Penerimaan created', [
            'user' => $user->name,
            'skpd_id' => $skpdId,
            'skpd_nama' => $skpd->nama_opd,
            'kode_rekening' => $kodeRekening->kode,
            'jumlah' => $this->jumlah
        ]);
        
        session()->flash('message', 'Data penerimaan berhasil disimpan untuk ' . $skpd->nama_opd . '.');
        return redirect()->route('penerimaan.index');
    }
    
    /**
     * Auto-update tahun berdasarkan tanggal
     */
    public function updatedTanggal()
    {
        if ($this->tanggal) {
            $tahunDariTanggal = Carbon::parse($this->tanggal)->year;
            $oldTahun = $this->tahun;

            if (in_array($tahunDariTanggal, $this->availableYears)) {
                $this->tahun = $tahunDariTanggal;
            }

            // Reload kode rekening jika tahun berubah (generasi kode bisa berbeda)
            if ($oldTahun != $this->tahun) {
                $this->loadKodeRekening();
                $this->kode_rekening_id = null;
            }
        }
    }

    public function render()
    {
        return view('livewire.penerimaan.create');
    }
}