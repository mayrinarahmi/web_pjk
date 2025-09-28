<?php

namespace App\Http\Livewire\User;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination;
    
    public $search = '';
    public $filterRole = '';
    public $filterSkpd = '';
    
    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = ['userDeleted' => '$refresh'];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'filterRole' => ['except' => ''],
        'filterSkpd' => ['except' => ''],
    ];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingFilterRole()
    {
        $this->resetPage();
    }
    
    public function updatingFilterSkpd()
    {
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->filterRole = '';
        $this->filterSkpd = '';
        $this->resetPage();
    }
    
    public function delete($id)
    {
        // Hindari menghapus diri sendiri
        if ($id == auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
            return;
        }
        
        // Cek apakah user adalah Super Admin terakhir
        $user = User::find($id);
        if ($user && $user->hasRole('Super Admin')) {
            $superAdminCount = User::role('Super Admin')->count();
            if ($superAdminCount <= 1) {
                session()->flash('error', 'Tidak dapat menghapus Super Admin terakhir.');
                return;
            }
        }
        
        // Delete user
        $user->delete();
        
        Log::info('User deleted', [
            'deleted_user_id' => $id,
            'deleted_by' => auth()->id()
        ]);
        
        session()->flash('message', 'Pengguna berhasil dihapus.');
        
        $this->dispatch('userDeleted');
    }
    
    public function render()
    {
        $query = User::query();
        
        // Search by NIP, name, or email
        if ($this->search) {
            $query->where(function($q) {
                $q->where('nip', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        
        // Filter by Role
        if ($this->filterRole) {
            $query->whereHas('roles', function($q) {
                $q->where('name', $this->filterRole);
            });
        }
        
        // Filter by SKPD
        if ($this->filterSkpd) {
            if ($this->filterSkpd == 'no_skpd') {
                $query->whereNull('skpd_id');
            } else {
                $query->where('skpd_id', $this->filterSkpd);
            }
        }
        
        // Load relationships and order
        $users = $query->with(['roles', 'skpd'])
                      ->orderBy('name')
                      ->paginate(10);
        
        // Get available roles for filter
        $roles = Role::orderBy('name')->get();
        
        // Get available SKPD for filter
        $skpdList = \App\Models\Skpd::where('status', 'aktif')
                                    ->orderBy('nama_opd')
                                    ->get();
        
        return view('livewire.user.index', [
            'users' => $users,
            'roles' => $roles,
            'skpdList' => $skpdList
        ]);
    }
}