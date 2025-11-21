<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\Skpd;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Create extends Component
{
    public $tahunAnggaranId;
    public $kodeRekeningId;
    public $jumlah = 0;
    
    // ==========================================
    // SKPD MANAGEMENT - TAMBAHAN BARU
    // ==========================================
    public $selectedSkpdId;
    public $skpdList = [];
    public $userSkpdInfo = '';
    public $showSkpdDropdown = false;
    
    public $kodeRekeningLevel6 = [];
    
    protected $rules = [
        'tahunAnggaranId' => 'required',
        'kodeRekeningId' => 'required',
        'jumlah' => 'required|numeric|min:0',
        'selectedSkpdId' => 'nullable|exists:skpd,id',
    ];
    
    protected $listeners = ['targetCreated' => '$refresh'];
    
    public function mount()
    {
        $user = auth()->user();
        
        // Initialize SKPD context
        $this->initializeSkpdContext($user);
        
        // Set default tahun anggaran aktif
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        // Load kode rekening
        $this->loadKodeRekening();
        
        Log::info('TargetAnggaran Create mounted', [
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
                $bpkpad = Skpd::where('nama_opd', 'like', '%BPKPAD%')->first();
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
     * FIXED: Level 6 (bukan Level 5)
     */
    private function loadKodeRekening()
    {
        $user = auth()->user();
        $query = KodeRekening::where('level', 6) // ✅ LEVEL 6
                             ->where('is_active', true);
        
        if ($user->canViewAllSkpd()) {
            // Super Admin/Kepala Badan - filter by selected SKPD
            if ($this->selectedSkpdId) {
                $selectedSkpd = Skpd::find($this->selectedSkpdId);
                if ($selectedSkpd) {
                    $allowedKodes = $selectedSkpd->kode_rekening_access;
                    
                    // Handle jika masih string
                    if (is_string($allowedKodes)) {
                        $allowedKodes = json_decode($allowedKodes, true) ?? [];
                    }
                    
                    if (is_array($allowedKodes) && !empty($allowedKodes)) {
                        $query->whereIn('id', $allowedKodes);
                    } else {
                        // SKPD belum ada assignment - return empty
                        $this->kodeRekeningLevel6 = collect([]);
                        return;
                    }
                } else {
                    $this->kodeRekeningLevel6 = collect([]);
                    return;
                }
            }
            // Jika tidak ada SKPD dipilih - show all (untuk Super Admin)
            
        } elseif ($user->skpd_id && $user->skpd) {
            // Operator SKPD - hanya kode yang di-assign
            $allowedKodes = $user->skpd->kode_rekening_access;
            
            // Handle jika masih string
            if (is_string($allowedKodes)) {
                $allowedKodes = json_decode($allowedKodes, true) ?? [];
            }
            
            if (is_array($allowedKodes) && !empty($allowedKodes)) {
                $query->whereIn('id', $allowedKodes);
            } else {
                // SKPD belum ada assignment
                $this->kodeRekeningLevel6 = collect([]);
                return;
            }
            
        } else {
            // User tanpa SKPD - no access
            $this->kodeRekeningLevel6 = collect([]);
            return;
        }
        
        // Execute query
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
        $this->kodeRekeningId = null;
        
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
        if (!$this->kodeRekeningLevel6 instanceof \Illuminate\Support\Collection) {
            $this->kodeRekeningLevel6 = collect([]);
        }
        
        $allowedIds = $this->kodeRekeningLevel6->pluck('id')->toArray();
        
        if (!in_array($this->kodeRekeningId, $allowedIds)) {
            session()->flash('error', 'Kode rekening yang dipilih tidak tersedia untuk SKPD Anda.');
            return false;
        }
        
        return true;
    }
    
    public function save()
    {
        // Validasi awal - cek apakah ada kode rekening tersedia
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
        // VALIDATION 1: Level 6 Check (UPDATED!)
        // ==========================================
        $kodeRekening = KodeRekening::find($this->kodeRekeningId);
        if (!$kodeRekening || $kodeRekening->level != 6) {
            session()->flash('error', 'Target anggaran hanya dapat diinput untuk kode rekening level 6.');
            return;
        }
        
        // ==========================================
        // VALIDATION 2: Kode Rekening Access
        // ==========================================
        if (!$this->validateKodeRekeningAccess()) {
            return;
        }
        
        // ==========================================
        // VALIDATION 3: SKPD ID Check
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
        // VALIDATION 4: SKPD Exists
        // ==========================================
        $skpd = Skpd::find($skpdId);
        if (!$skpd) {
            session()->flash('error', 'SKPD tidak ditemukan.');
            return;
        }
        
        // ==========================================
        // CEK EXISTING TARGET
        // ==========================================
        $existingTarget = TargetAnggaran::where('kode_rekening_id', $this->kodeRekeningId)
            ->where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->where('skpd_id', $skpdId)
            ->first();
        
        try {
            DB::beginTransaction();
            
            if ($existingTarget) {
                // Update target yang sudah ada
                $existingTarget->jumlah = $this->jumlah;
                $existingTarget->save();
                
                session()->flash('message', 'Target anggaran untuk ' . $skpd->nama_opd . ' berhasil diperbarui.');
            } else {
                // Buat target baru
                TargetAnggaran::create([
                    'kode_rekening_id' => $this->kodeRekeningId,
                    'tahun_anggaran_id' => $this->tahunAnggaranId,
                    'skpd_id' => $skpdId,
                    'jumlah' => $this->jumlah,
                ]);
                
                session()->flash('message', 'Target anggaran untuk ' . $skpd->nama_opd . ' berhasil ditambahkan.');
            }
            
            DB::commit();
            
            Log::info('Target anggaran created/updated', [
                'user' => $user->name,
                'skpd_id' => $skpdId,
                'skpd_nama' => $skpd->nama_opd,
                'kode_rekening' => $kodeRekening->kode,
                'jumlah' => $this->jumlah
            ]);
            
            // Update hierarki setelah menyimpan
            try {
                KodeRekening::updateHierarchiTargets($this->tahunAnggaranId);
                session()->flash('message', session('message') . ' Hierarki target otomatis diperbarui.');
            } catch (\Exception $e) {
                session()->flash('warning', 'Target tersimpan, tetapi gagal memperbarui hierarki: ' . $e->getMessage());
            }
            
            // Emit event untuk refresh parent component
            $this->dispatch('targetCreated');
            
            return redirect()->route('target-anggaran.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to save target anggaran', [
                'error' => $e->getMessage(),
                'skpd_id' => $skpdId,
                'kode_rekening_id' => $this->kodeRekeningId
            ]);
            
            session()->flash('error', 'Gagal menyimpan target anggaran: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        $tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')
            ->orderBy('jenis_anggaran', 'asc')
            ->get();
        
        return view('livewire.target-anggaran.create', [
            'tahunAnggaran' => $tahunAnggaran,
            'kodeRekening' => $this->kodeRekeningLevel6,
            'skpdList' => $this->skpdList,
            'userSkpdInfo' => $this->userSkpdInfo,
            'showSkpdDropdown' => $this->showSkpdDropdown
        ]);
    }
}