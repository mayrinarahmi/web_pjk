<?php

namespace App\Http\Livewire\KodeRekening;

use Livewire\Component;
use App\Models\KodeRekening;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Imports\KodeRekeningImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    public $showHierarchy = true;
    public $importFile;
    public $showImportModal = false;
    public $importErrors = [];
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['kodeRekeningDeleted' => '$refresh'];
    
    protected $rules = [
        'importFile' => 'required|file|mimes:xlsx,xls|max:10240',
    ];
    
    public function toggleImportModal()
    {
        $this->showImportModal = !$this->showImportModal;
        $this->importFile = null;
        $this->importErrors = [];
        $this->resetValidation();
    }
    
    public function import()
    {
        $this->validate();
        $this->importErrors = [];
        
        try {
            // Import data
            $import = new KodeRekeningImport;
            Excel::import($import, $this->importFile);
            
            // Cek errors dari import (jika ada)
            if (method_exists($import, 'errors') && count($import->errors()) > 0) {
                foreach ($import->errors() as $error) {
                    $this->importErrors[] = $error->getMessage();
                }
            }
            
            // Tampilkan pesan sukses atau warning
            if (count($this->importErrors) > 0) {
                session()->flash('warning', 'Import selesai dengan beberapa warning.');
            } else {
                session()->flash('success', 'Data kode rekening berhasil diimport.');
                $this->showImportModal = false;
                $this->importFile = null;
            }
            
            $this->dispatch('kodeRekeningDeleted'); // refresh data
            
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Handle validation errors dari Laravel Excel
            $failures = $e->failures();
            
            foreach ($failures as $failure) {
                $errors = $failure->errors();
                $row = $failure->row();
                
                foreach ($errors as $error) {
                    $this->importErrors[] = "Baris {$row}: {$error}";
                }
            }
            
            session()->flash('error', 'Import gagal karena validasi error.');
            
        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Import Error: ' . $e->getMessage());
            
            // Parse error message
            $errorMessage = 'Terjadi kesalahan: ';
            
            if (strpos($e->getMessage(), 'Undefined array key') !== false) {
                $errorMessage .= 'Format kolom Excel tidak sesuai. Pastikan headers: kode, nama, level (huruf kecil)';
            } elseif (strpos($e->getMessage(), 'Parent kode tidak ditemukan') !== false) {
                $errorMessage .= $e->getMessage();
            } else {
                $errorMessage .= 'Silakan cek format file Excel Anda.';
            }
            
            session()->flash('error', $errorMessage);
            $this->importErrors[] = $errorMessage;
        }
    }
    
    public function downloadTemplate()
    {
        $templatePath = public_path('templates/template_kode_rekening.xlsx');
        
        if (!file_exists($templatePath)) {
            session()->flash('error', 'Template file tidak ditemukan.');
            return;
        }
        
        return response()->download($templatePath);
    }
    
    public function resetFilters()
    {
        $this->showHierarchy = true;
        $this->resetPage();
    }
    
    public function delete($id)
    {
        $kodeRekening = KodeRekening::find($id);
        
        if ($kodeRekening->children()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki sub-kode rekening.');
            return;
        }
        
        if ($kodeRekening->level == 4 && $kodeRekening->penerimaan()->count() > 0) {
            session()->flash('error', 'Kode rekening tidak dapat dihapus karena memiliki data penerimaan terkait.');
            return;
        }
        
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
            $kodeRekening = KodeRekening::orderBy('kode')->paginate(15);
            
            return view('livewire.kode-rekening.index', [
                'kodeRekening' => $kodeRekening
            ]);
        }
    }
}