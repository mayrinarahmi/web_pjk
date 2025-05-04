<?php

namespace App\Http\Livewire\User;

use Livewire\Component;
use App\Models\User;
use App\Models\Role;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    
    public $search = '';
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['userDeleted' => '$refresh'];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function delete($id)
    {
        // Hindari menghapus diri sendiri
        if ($id == auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
            return;
        }
        
        User::find($id)->delete();
        session()->flash('message', 'Pengguna berhasil dihapus.');
        
        $this->emit('userDeleted');
    }
    
    public function render()
    {
        $query = User::query();
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        
        $users = $query->with('role')->orderBy('name')->paginate(10);
        
        return view('livewire.user.index', [
            'users' => $users
        ]);
    }
}
