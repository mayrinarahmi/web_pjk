<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\Skpd;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Edit extends Component
{
    public $targetAnggaranId;
    public $tahunAnggaranId;
    public $kodeRekeningId;
    public $jumlah = 0;
    public $targetAnggaran;
    
    // Info untuk display
    public $kodeRekeningInfo;
    public $tahunAnggaranInfo;
    public $oldJumlah;
    
    // ==========================================
    // SKPD MANAGEMENT - TAMBAHAN BARU
    // ==========================================
    public $selectedSkpdId;
    public $skpdList = [];
    public $userSkpdInfo = '';
    public $showSkpdDropdown = false;
    public $originalSkpdId; // Track original SKPD
    
    public $kodeRekeningLevel6 = [];
    
    protected $rules = [
        'tahunAnggaranId' => 'required',
        'kodeRekeningId' => 'required',
        'jumlah' => 'required|numeric|min:0',
        'selectedSkpdId' => 'nullable|exists:skpd,id',
    ];
    
    protected $listeners = ['targetUpdated' => '$refresh'];
    
    public function mount($id)
    {
        $this->targetAnggaran = TargetAnggaran::with(['kodeRekening', 'tahunAnggaran', 'skpd'])->findOrFail($id);
        $user = auth()->user();
        
        // ==========================================
        // AUTHORIZATION CHECK
        // ==========================================
        if (!$this->authorizeEdit($user, $this->targetAnggaran)) {
            session()->flash('error', 'Anda tidak memiliki akses untuk mengedit data ini.');
            return redirect()->route('target-anggaran.index');
        }
        
        // Store original data
        $this->targetAnggaranId = $this->targetAnggaran->id;
        $this->tahunAnggaranId = $this->targetAnggaran->tahun_anggaran_id;
        $this->kodeRekeningId = $this->targetAnggaran->kode_rekening_id;
        $this->jumlah = $this->targetAnggaran->jumlah;
        $this->oldJumlah = $this->targetAnggaran->jumlah;
        $this->originalSkpdId = $this->targetAnggaran->skpd_id;
        $this->selectedSkpdId = $this->targetAnggaran->skpd_id;
        
        // Set info untuk display
        $this->kodeRekeningInfo = $this->targetAnggaran->kodeRekening;
        $this->tahunAnggaranInfo = $this->targetAnggaran->tahunAnggaran;
        
        // Initialize SKPD context
        $this->initializeSkpdContext($user, $this->targetAnggaran);
        
        // Load kode rekening
        $this->loadKodeRekening();
        
        Log::info('TargetAnggaran Edit mounted', [
            'user' => $user->name,
            'target_id' => $id,
            'skpd' => $this->targetAnggaran->skpd ? $this->targetAnggaran->skpd->nama_opd : 'No SKPD',
            'show_skpd_dropdown' => $this->showSkpdDropdown
        ]);
    }
    
    /**
     * Authorization check - siapa yang boleh edit
     */
    private function authorizeEdit($user, $targetAnggaran)
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
            return $targetAnggaran->skpd_id == $user->skpd_id;
        }
        
        // Default: tidak ada akses
        return false;
    }
    
    /**
     * Initialize SKPD context berdasarkan role user
     */
    private function initializeSkpdContext($user, $targetAnggaran)
    {
        if ($user->isSuperAdmin()) {
            // Super Admin - bisa ganti SKPD
            $this->showSkpdDropdown = true;
            $this->skpdList = Skpd::where('status', 'aktif')
                                  ->orderBy('nama_opd')
                                  ->get();
            
            $skpdName = $targetAnggaran->skpd ? $targetAnggaran->skpd->nama_opd : 'Tanpa SKPD';
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
     * FIXED: Level 6 (bukan Level 5)
     */
    private function loadKodeRekening()
    {
        $user = auth()->user();
        $query = KodeRekening::where('level', 6) // ✅ LEVEL 6
                             ->where('is_active', true);

        // Filter berdasarkan tahun berlaku
        if ($this->tahunAnggaranId) {
            $ta = TahunAnggaran::find($this->tahunAnggaranId);
            if ($ta) {
                $query->forTahun($ta->tahun);
            }
        }

        if ($user->isSuperAdmin()) {
            // Super Admin - filter by selected SKPD
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
                        $this->kodeRekeningLevel6 = collect([]);
                        return;
                    }
                } else {
                    $this->kodeRekeningLevel6 = collect([]);
                    return;
                }
            }
            
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
                $this->kodeRekeningLevel6 = collect([]);
                return;
            }
            
        } else {
            $this->kodeRekeningLevel6 = collect([]);
            return;
        }
        
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
        if (!in_array($this->kodeRekeningId, $allowedIds)) {
            $this->kodeRekeningId = null;
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
        if (!$this->kodeRekeningLevel6 instanceof \Illuminate\Support\Collection) {
            $this->kodeRekeningLevel6 = collect([]);
        }
        
        $allowedIds = $this->kodeRekeningLevel6->pluck('id')->toArray();
        
        if (!in_array($this->kodeRekeningId, $allowedIds)) {
            session()->flash('error', 'Kode rekening yang dipilih tidak tersedia untuk SKPD ini.');
            return false;
        }
        
        return true;
    }
    
    public function update()
    {
        // Validasi awal
        if (!$this->kodeRekeningLevel6 instanceof \Illuminate\Support\Collection) {
            $this->kodeRekeningLevel6 = collect([]);
        }
        
        if ($this->kodeRekeningLevel6->isEmpty()) {
            session()->flash('error', 'Tidak ada kode rekening yang tersedia untuk SKPD ini.');
            return;
        }
        
        $this->validate();
        
        $user = auth()->user();
        $targetAnggaran = TargetAnggaran::find($this->targetAnggaranId);
        
        if (!$targetAnggaran) {
            session()->flash('error', 'Data target anggaran tidak ditemukan.');
            return redirect()->route('target-anggaran.index');
        }
        
        // Re-check authorization
        if (!$this->authorizeEdit($user, $targetAnggaran)) {
            session()->flash('error', 'Anda tidak memiliki akses untuk mengedit data ini.');
            return redirect()->route('target-anggaran.index');
        }
        
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
        // VALIDATION 3: SKPD Exists
        // ==========================================
        $skpd = Skpd::find($skpdId);
        if (!$skpd) {
            session()->flash('error', 'SKPD tidak ditemukan.');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            $oldValue = $targetAnggaran->jumlah;
            
            // Update target anggaran
            $targetAnggaran->update([
                'kode_rekening_id' => $this->kodeRekeningId,
                'tahun_anggaran_id' => $this->tahunAnggaranId,
                'skpd_id' => $skpdId,
                'jumlah' => $this->jumlah,
            ]);
            
            DB::commit();
            
            $changeAmount = $this->jumlah - $oldValue;
            $changeText = $changeAmount >= 0 ? 
                'ditambah Rp ' . number_format(abs($changeAmount), 0, ',', '.') :
                'dikurangi Rp ' . number_format(abs($changeAmount), 0, ',', '.');
            
            session()->flash('message', "Target anggaran untuk {$skpd->nama_opd} berhasil diperbarui ({$changeText}).");
            
            Log::info('Target anggaran updated', [
                'user' => $user->name,
                'target_id' => $this->targetAnggaranId,
                'skpd_id' => $skpdId,
                'skpd_nama' => $skpd->nama_opd,
                'kode_rekening' => $kodeRekening->kode,
                'jumlah' => $this->jumlah,
                'skpd_changed' => $this->originalSkpdId != $skpdId
            ]);
            
            // Update hierarki setelah menyimpan
            try {
                KodeRekening::updateHierarchiTargets($this->tahunAnggaranId);
                
                // Hitung dampak perubahan ke parent
                $parentImpacts = $this->calculateParentImpacts($kodeRekening, $changeAmount);
                
                if (!empty($parentImpacts)) {
                    $impactMessage = "Dampak hierarki: ";
                    foreach ($parentImpacts as $impact) {
                        $impactMessage .= "{$impact['kode']} {$impact['change']}, ";
                    }
                    $impactMessage = rtrim($impactMessage, ', ');
                    
                    session()->flash('message', session('message') . ' ' . $impactMessage);
                }
                
            } catch (\Exception $e) {
                session()->flash('warning', 'Target tersimpan, tetapi gagal memperbarui hierarki: ' . $e->getMessage());
            }
            
            // Emit event untuk refresh parent component
            $this->dispatch('targetUpdated');
            
            return redirect()->route('target-anggaran.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update target anggaran', [
                'error' => $e->getMessage(),
                'target_id' => $this->targetAnggaranId,
                'skpd_id' => $skpdId
            ]);
            
            session()->flash('error', 'Gagal memperbarui target anggaran: ' . $e->getMessage());
        }
    }
    
    private function calculateParentImpacts($kodeRekening, $changeAmount)
    {
        $impacts = [];
        $current = $kodeRekening->parent;
        
        while ($current && $changeAmount != 0) {
            $changeText = $changeAmount >= 0 ? 
                '+Rp ' . number_format(abs($changeAmount), 0, ',', '.') :
                '-Rp ' . number_format(abs($changeAmount), 0, ',', '.');
                
            $impacts[] = [
                'kode' => $current->kode,
                'nama' => $current->nama,
                'change' => $changeText
            ];
            
            $current = $current->parent;
        }
        
        return $impacts;
    }
    
    public function previewHierarchiImpact()
    {
        if (!$this->kodeRekeningInfo) return [];
        
        $changeAmount = $this->jumlah - $this->oldJumlah;
        if ($changeAmount == 0) return [];
        
        return $this->calculateParentImpacts($this->kodeRekeningInfo, $changeAmount);
    }
    
    // Method untuk live preview saat user mengetik
    public function updatedJumlah()
    {
        $this->dispatch('impactPreviewUpdated', $this->previewHierarchiImpact());
    }
    
    public function render()
    {
        $tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')
            ->orderBy('jenis_anggaran', 'asc')
            ->get();
        
        // Calculate preview impacts
        $impactPreview = $this->previewHierarchiImpact();
            
        return view('livewire.target-anggaran.edit', [
            'tahunAnggaran' => $tahunAnggaran,
            'kodeRekening' => $this->kodeRekeningLevel6,
            'impactPreview' => $impactPreview,
            'skpdList' => $this->skpdList,
            'userSkpdInfo' => $this->userSkpdInfo,
            'showSkpdDropdown' => $this->showSkpdDropdown
        ]);
    }
}