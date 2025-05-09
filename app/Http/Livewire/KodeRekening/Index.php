<?php

namespace App\Http\Livewire\KodeRekening;

use Livewire\Component;
use App\Models\KodeRekening;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    
    // Hapus semua property filter
    public $showHierarchy = true;
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['kodeRekeningDeleted' => '$refresh'];
    
    public function resetFilters()
    {
        $this->showHierarchy = true;
        $this->resetPage();
    }
    
    public function delete($id)
    {
        // Kode delete tetap sama seperti sebelumnya
        $kodeRekening = KodeRekening::find($id);
        
        // Cek apakah memiliki child
        if ($kodeRekening->children()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki sub-kode rekening.');
            return;
        }
        
        // Cek apakah memiliki data penerimaan (untuk level 4)
        if ($kodeRekening->level == 4 && $kodeRekening->penerimaan()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki data penerimaan terkait.');
            return;
        }
        
        // Cek apakah memiliki data target anggaran
        if ($kodeRekening->targetAnggaran()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki data target anggaran terkait.');
            return;
        }
        
        $kodeRekening->delete();
        session()->flash('message', 'Kode rekening berhasil dihapus.');
        
        $this->dispatch('kodeRekeningDeleted');
    }
    
    public function render()
    {
        if ($this->showHierarchy) {
            // Tampilkan dalam struktur hierarki
            $kodeRekening = KodeRekening::whereNull('parent_id')
                ->with(['children' => function($query) {
                    $query->orderBy('kode');
                    $query->with(['children' => function($query) {
                        $query->orderBy('kode');
                        $query->with(['children' => function($query) {
                            $query->orderBy('kode');
                            $query->with(['children' => function($query) {
                                $query->orderBy('kode');
                                $query->with(['children' => function($query) {
                                    $query->orderBy('kode');
                                }]);
                            }]);
                        }]);
                    }]);
                }])
                ->orderBy('kode')
                ->get();
                
            return view('livewire.kode-rekening.index-hierarchy', [
                'kodeRekening' => $kodeRekening
            ]);
        } else {
            // Tampilkan data tanpa filter
            $kodeRekening = KodeRekening::orderBy('kode')->paginate(15);
            
            return view('livewire.kode-rekening.index', [
                'kodeRekening' => $kodeRekening
            ]);
        }
    }
}