<?php
namespace App\Http\Livewire\KodeRekening;

use Livewire\Component;
use App\Models\KodeRekening;

class Create extends Component
{
    public $kode;
    public $nama;
    public $level = 1;
    public $parent_id = null;
    
    public $availableParents = [];
    
    protected $rules = [
        'kode' => 'required|string|max:50|unique:kode_rekening,kode',
        'nama' => 'required|string|max:255',
        'level' => 'required|integer|min:1|max:6',
        'parent_id' => 'nullable|exists:kode_rekening,id',
    ];
    
    public function mount()
    {
        $this->updateAvailableParents();
    }
    
    public function updatedLevel()
    {
        $this->parent_id = null;
        $this->updateAvailableParents();
    }
    
    public function updateAvailableParents()
{
    if ($this->level == 1) {
        $this->availableParents = [];
    } else if ($this->level == 5) {
        // Untuk level 5, bisa memilih parent dari level 3 dan 4
        $this->availableParents = KodeRekening::whereIn('level', [$this->level - 1, $this->level - 2])
            ->orderBy('level', 'desc') // Urutkan berdasarkan level (level 4 dulu, kemudian level 3)
            ->orderBy('kode')
            ->get();
    } else {
        // Untuk level lainnya tetap hanya bisa memilih parent 1 tingkat di atasnya
        $this->availableParents = KodeRekening::where('level', $this->level - 1)
            ->orderBy('kode')
            ->get();
    }
}
    
    public function save()
    {
        $this->validate();
        
        // Validasi tambahan untuk level dan parent
        if ($this->level > 1 && !$this->parent_id) {
            session()->flash('error', 'Kode rekening level ' . $this->level . ' harus memiliki parent.');
            return;
        }
        
        if ($this->level == 1 && $this->parent_id) {
            session()->flash('error', 'Kode rekening level 1 tidak boleh memiliki parent.');
            return;
        }
        
        KodeRekening::create([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'level' => $this->level,
            'parent_id' => $this->parent_id,
            'is_active' => true,
        ]);
        
        session()->flash('message', 'Kode rekening berhasil ditambahkan.');
        return redirect()->route('kode-rekening.index');
    }
    
    public function render()
    {
        return view('livewire.kode-rekening.create');
    }
}
