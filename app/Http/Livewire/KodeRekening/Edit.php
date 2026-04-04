<?php
namespace App\Http\Livewire\KodeRekening;

use Livewire\Component;
use App\Models\KodeRekening;

class Edit extends Component
{
    public $kodeRekeningId;
    public $kode;
    public $nama;
    public $level;
    public $parent_id;
    public $is_active;
    public $berlaku_mulai = null;

    public $availableParents = [];
    public $parentSearch = '';

    protected function rules()
    {
        return [
            'kode' => 'required|string|max:50|unique:kode_rekening,kode,' . $this->kodeRekeningId,
            'nama' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:6',
            'parent_id' => 'nullable|exists:kode_rekening,id',
            'is_active' => 'boolean',
            'berlaku_mulai' => 'nullable|integer|min:2000|max:2099',
        ];
    }
    
    public function mount($id)
    {
        $kodeRekening = KodeRekening::findOrFail($id);
        $this->kodeRekeningId = $kodeRekening->id;
        $this->kode = $kodeRekening->kode;
        $this->nama = $kodeRekening->nama;
        $this->level = $kodeRekening->level;
        $this->parent_id = $kodeRekening->parent_id;
        $this->is_active = $kodeRekening->is_active;
        $this->berlaku_mulai = $kodeRekening->berlaku_mulai;
        
        $this->updateAvailableParents();
    }
    
    public function updatedLevel()
    {
        $this->parent_id = null;
        $this->parentSearch = '';
        $this->updateAvailableParents();
    }
    
    public function updateAvailableParents()
    {
        if ($this->level == 1) {
            $this->availableParents = collect([]);
        } else {
            $this->availableParents = KodeRekening::where('level', $this->level - 1)
                ->where('is_active', true)
                ->where('id', '!=', $this->kodeRekeningId)
                ->orderBy('kode')
                ->orderByDesc('berlaku_mulai')
                ->get()
                ->unique('kode')
                ->values();
        }
    }
    
    public function update()
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
        
        // Cek apakah kode rekening memiliki children
        $kodeRekening = KodeRekening::find($this->kodeRekeningId);
        if ($kodeRekening->children()->count() > 0 && $kodeRekening->level != $this->level) {
            session()->flash('error', 'Tidak dapat mengubah level kode rekening yang memiliki sub-kode rekening.');
            return;
        }
        
        $kodeRekening->update([
            'kode' => $this->kode,
            'nama' => $this->nama,
            'level' => $this->level,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'berlaku_mulai' => $this->berlaku_mulai ?: null,
        ]);
        
        session()->flash('message', 'Kode rekening berhasil diperbarui.');
        return redirect()->route('kode-rekening.index');
    }
    
    public function render()
    {
        $search = strtolower(trim($this->parentSearch));
        $filteredParents = $search
            ? $this->availableParents->filter(fn($p) =>
                str_contains(strtolower($p->kode), $search) ||
                str_contains(strtolower($p->nama), $search)
              )->values()
            : $this->availableParents;

        return view('livewire.kode-rekening.edit', compact('filteredParents'));
    }
}
