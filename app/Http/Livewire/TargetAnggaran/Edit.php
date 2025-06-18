<?php

namespace App\Http\Livewire\TargetAnggaran;

use Livewire\Component;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;

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
    
    protected $rules = [
        'tahunAnggaranId' => 'required',
        'kodeRekeningId' => 'required',
        'jumlah' => 'required|numeric|min:0',
    ];
    
    protected $listeners = ['targetUpdated' => '$refresh'];
    
    public function mount($id)
    {
        $this->targetAnggaran = TargetAnggaran::with(['kodeRekening', 'tahunAnggaran'])->findOrFail($id);
        $this->targetAnggaranId = $this->targetAnggaran->id;
        $this->tahunAnggaranId = $this->targetAnggaran->tahun_anggaran_id;
        $this->kodeRekeningId = $this->targetAnggaran->kode_rekening_id;
        $this->jumlah = $this->targetAnggaran->jumlah;
        $this->oldJumlah = $this->targetAnggaran->jumlah;
        
        // Set info untuk display
        $this->kodeRekeningInfo = $this->targetAnggaran->kodeRekening;
        $this->tahunAnggaranInfo = $this->targetAnggaran->tahunAnggaran;
    }
    
    public function update()
    {
        $this->validate();
        
        // Pastikan kode rekening adalah level 5
        $kodeRekening = KodeRekening::find($this->kodeRekeningId);
        if (!$kodeRekening || $kodeRekening->level != 5) {
            session()->flash('error', 'Target anggaran hanya bisa diinput untuk kode rekening level 5.');
            return;
        }
        
        // Update target anggaran
        $targetAnggaran = TargetAnggaran::find($this->targetAnggaranId);
        $oldValue = $targetAnggaran->jumlah;
        
        $targetAnggaran->update([
            'kode_rekening_id' => $this->kodeRekeningId,
            'tahun_anggaran_id' => $this->tahunAnggaranId,
            'jumlah' => $this->jumlah,
        ]);
        
        $changeAmount = $this->jumlah - $oldValue;
        $changeText = $changeAmount >= 0 ? 
            'ditambah Rp ' . number_format(abs($changeAmount), 0, ',', '.') :
            'dikurangi Rp ' . number_format(abs($changeAmount), 0, ',', '.');
        
        session()->flash('message', "Target anggaran berhasil diperbarui ({$changeText}).");
        
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
        
        // Hanya tampilkan kode rekening level 5
        $kodeRekening = KodeRekening::where('level', 5)
            ->where('is_active', true)
            ->orderBy('kode', 'asc')
            ->get();
        
        // Calculate preview impacts
        $impactPreview = $this->previewHierarchiImpact();
            
        return view('livewire.target-anggaran.edit', [
            'tahunAnggaran' => $tahunAnggaran,
            'kodeRekening' => $kodeRekening,
            'impactPreview' => $impactPreview
        ]);
    }
}