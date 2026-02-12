<?php

namespace App\Http\Livewire\Skpd;

use Livewire\Component;
use App\Models\Skpd;
use App\Models\KodeRekening;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    use WithPagination;
    
    public $search = '';
    public $perPage = 10;
    
    // Modal Assign Kode Rekening
    public $showAssignModal = false;
    public $selectedSkpd = null;
    public $selectedSkpdId = null;
    public $kodeRekeningTree = [];
    public $selectedKodeRekening = [];
    
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['refreshSkpdList' => '$refresh'];
    
    public function mount()
    {
        // Initialize
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function openAssignModal($skpdId)
    {
        $this->selectedSkpdId = $skpdId;
        $this->selectedSkpd = Skpd::find($skpdId);
        
        if ($this->selectedSkpd) {
            // Load kode rekening tree
            $this->loadKodeRekeningTree();
            
            // Load existing assignments - HANDLE BOTH STRING AND ARRAY
            $existingAccess = $this->selectedSkpd->kode_rekening_access ?? [];
            
            // Handle jika masih string (dari database TEXT/VARCHAR)
            if (is_string($existingAccess)) {
                // Decode JSON string
                $existingAccess = json_decode($existingAccess, true);
                
                // Jika decode gagal atau null, set ke array kosong
                if (!is_array($existingAccess)) {
                    $existingAccess = [];
                }
            }
            
            // Pastikan array
            if (!is_array($existingAccess)) {
                $existingAccess = [];
            }
            
            // Pastikan array berisi integer
            $this->selectedKodeRekening = array_map('intval', $existingAccess);
            
            $this->showAssignModal = true;
            
            Log::info('Opening assign modal for SKPD', [
                'skpd_id' => $skpdId,
                'skpd_name' => $this->selectedSkpd->nama_opd,
                'existing_access' => $this->selectedKodeRekening,
                'raw_data' => $this->selectedSkpd->kode_rekening_access
            ]);
        }
    }
    
    public function closeAssignModal()
    {
        $this->showAssignModal = false;
        $this->selectedSkpd = null;
        $this->selectedSkpdId = null;
        $this->selectedKodeRekening = [];
        $this->kodeRekeningTree = [];
    }
    
    private function loadKodeRekeningTree()
    {
        // Load kode rekening hierarchically
        $level3 = KodeRekening::where('level', 3)
                              ->where('is_active', true)
                              ->orderBy('kode')
                              ->get();
        
        $tree = [];
        
        foreach ($level3 as $l3) {
            $l3Node = [
                'id' => $l3->id,
                'kode' => $l3->kode,
                'nama' => $l3->nama,
                'level' => 3,
                'children' => []
            ];
            
            // Get Level 4 children
            $level4 = KodeRekening::where('parent_id', $l3->id)
                                  ->where('level', 4)
                                  ->where('is_active', true)
                                  ->orderBy('kode')
                                  ->get();
            
            foreach ($level4 as $l4) {
                $l4Node = [
                    'id' => $l4->id,
                    'kode' => $l4->kode,
                    'nama' => $l4->nama,
                    'level' => 4,
                    'children' => []
                ];
                
                // Get Level 5 children
                $level5 = KodeRekening::where('parent_id', $l4->id)
                                      ->where('level', 5)
                                      ->where('is_active', true)
                                      ->orderBy('kode')
                                      ->get();
                
                foreach ($level5 as $l5) {
                    $l5Node = [
                        'id' => $l5->id,
                        'kode' => $l5->kode,
                        'nama' => $l5->nama,
                        'level' => 5,
                        'children' => []
                    ];
                    
                    // Get Level 6 count for display
                    $level6Count = KodeRekening::where('parent_id', $l5->id)
                                               ->where('level', 6)
                                               ->where('is_active', true)
                                               ->count();
                    
                    $l5Node['level6_count'] = $level6Count;
                    $l4Node['children'][] = $l5Node;
                }
                
                $l3Node['children'][] = $l4Node;
            }
            
            $tree[] = $l3Node;
        }
        
        $this->kodeRekeningTree = $tree;
    }
    
    public function toggleKodeRekening($kodeRekeningId, $level)
    {
        $kodeRekeningId = (int) $kodeRekeningId;
        
        $kodeRekening = KodeRekening::find($kodeRekeningId);
        if (!$kodeRekening) return;
        
        $isChecked = in_array($kodeRekeningId, $this->selectedKodeRekening);
        
        if ($isChecked) {
            // Uncheck this and all children
            $this->uncheckWithChildren($kodeRekeningId);
        } else {
            // Check this and all children
            $this->checkWithChildren($kodeRekeningId);
        }
        
        Log::info('Toggled kode rekening', [
            'id' => $kodeRekeningId,
            'level' => $level,
            'checked' => !$isChecked,
            'selected_items' => $this->selectedKodeRekening
        ]);
    }
    
    private function checkWithChildren($kodeRekeningId)
    {
        $kodeRekeningId = (int) $kodeRekeningId;
        
        // Add to selected if not already there
        if (!in_array($kodeRekeningId, $this->selectedKodeRekening)) {
            $this->selectedKodeRekening[] = $kodeRekeningId;
        }
        
        // Get all children (recursive)
        $children = $this->getAllChildrenIds($kodeRekeningId);
        foreach ($children as $childId) {
            $childId = (int) $childId;
            if (!in_array($childId, $this->selectedKodeRekening)) {
                $this->selectedKodeRekening[] = $childId;
            }
        }
        
        // Make sure array is unique
        $this->selectedKodeRekening = array_unique($this->selectedKodeRekening);
    }
    
    private function uncheckWithChildren($kodeRekeningId)
    {
        $kodeRekeningId = (int) $kodeRekeningId;
        
        // Remove from selected
        $this->selectedKodeRekening = array_diff($this->selectedKodeRekening, [$kodeRekeningId]);
        
        // Remove all children
        $children = $this->getAllChildrenIds($kodeRekeningId);
        $this->selectedKodeRekening = array_diff($this->selectedKodeRekening, $children);
        
        // Re-index array
        $this->selectedKodeRekening = array_values($this->selectedKodeRekening);
    }
    
    private function getAllChildrenIds($parentId)
    {
        $ids = [];
        $children = KodeRekening::where('parent_id', $parentId)
                                ->where('is_active', true)
                                ->get();
        
        foreach ($children as $child) {
            $ids[] = (int) $child->id;
            // Recursive call for grandchildren
            $ids = array_merge($ids, $this->getAllChildrenIds($child->id));
        }
        
        return $ids;
    }
    
    public function saveAssignment()
    {
        if (!$this->selectedSkpd) {
            session()->flash('error', 'SKPD tidak ditemukan');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            Log::info('Starting save assignment', [
                'skpd_id' => $this->selectedSkpd->id,
                'selected_items' => $this->selectedKodeRekening
            ]);
            
            if (empty($this->selectedKodeRekening)) {
                // Jika tidak ada yang dipilih, simpan array kosong
                $this->selectedSkpd->kode_rekening_access = [];
                $this->selectedSkpd->save();
                
                DB::commit();
                
                session()->flash('message', 'Assignment kode rekening untuk ' . $this->selectedSkpd->nama_opd . ' telah dikosongkan.');
                
            } else {
                // Get all Level 6 IDs that should be accessible
                $level6Ids = [];
                
                foreach ($this->selectedKodeRekening as $selectedId) {
                    $selectedId = (int) $selectedId;
                    $kodeRekening = KodeRekening::find($selectedId);
                    
                    if (!$kodeRekening) {
                        Log::warning('Kode rekening not found', ['id' => $selectedId]);
                        continue;
                    }
                    
                    if ($kodeRekening->level == 6) {
                        // If it's already level 6, add directly
                        $level6Ids[] = $selectedId;
                    } else {
                        // Get all level 6 descendants
                        $descendants = KodeRekening::where('kode', 'like', $kodeRekening->kode . '%')
                                                   ->where('level', 6)
                                                   ->where('is_active', true)
                                                   ->pluck('id')
                                                   ->toArray();
                        
                        // Convert to integers
                        $descendants = array_map('intval', $descendants);
                        $level6Ids = array_merge($level6Ids, $descendants);
                    }
                }
                
                // Remove duplicates and ensure integers
                $level6Ids = array_values(array_unique(array_map('intval', $level6Ids)));
                
                Log::info('Level 6 IDs to save', [
                    'count' => count($level6Ids),
                    'ids' => $level6Ids
                ]);
                
                // Save with Eloquent cast (akan otomatis convert ke JSON)
                $this->selectedSkpd->kode_rekening_access = $level6Ids;
                $this->selectedSkpd->save();
                
                // Verify save
                $this->selectedSkpd->refresh();
                $savedData = $this->selectedSkpd->kode_rekening_access;
                
                Log::info('Assignment saved successfully', [
                    'skpd_id' => $this->selectedSkpd->id,
                    'saved_count' => count($savedData),
                    'saved_data' => $savedData
                ]);
                
                DB::commit();
                
                session()->flash('message', 'Berhasil assign ' . count($level6Ids) . ' kode rekening level 6 ke ' . $this->selectedSkpd->nama_opd);
            }
            
            $this->closeAssignModal();
            $this->dispatch('refreshSkpdList'); // Livewire 3 uses dispatch
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to save assignment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Gagal menyimpan assignment: ' . $e->getMessage());
        }
    }
    
    public function clearAssignment($skpdId)
    {
        try {
            $skpd = Skpd::find($skpdId);
            if ($skpd) {
                $skpd->kode_rekening_access = [];
                $skpd->save();
                
                session()->flash('message', 'Assignment kode rekening untuk ' . $skpd->nama_opd . ' telah dihapus.');
                $this->dispatch('refreshSkpdList'); // Livewire 3 uses dispatch
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear assignment', ['error' => $e->getMessage()]);
            session()->flash('error', 'Gagal menghapus assignment: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        $query = Skpd::query();
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('kode_opd', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_opd', 'like', '%' . $this->search . '%');
            });
        }
        
        $skpdList = $query->orderBy('kode_opd')
                          ->paginate($this->perPage);
        
        // Add assignment count for each SKPD - WITH ERROR HANDLING
        foreach ($skpdList as $skpd) {
            $access = $skpd->kode_rekening_access ?? [];
            
            // Handle jika masih string (sebelum migration)
            if (is_string($access)) {
                $access = json_decode($access, true) ?? [];
            }
            
            // Pastikan array
            if (!is_array($access)) {
                $access = [];
            }
            
            $skpd->assignment_count = count($access);
        }
        
        return view('livewire.skpd.index', [
            'skpdList' => $skpdList
        ]);
    }
}