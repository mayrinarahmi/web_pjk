<?php

namespace App\Http\Livewire\User;

use Livewire\Component;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class Create extends Component
{
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role_id;
    
    public $roles = [];
    
    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'role_id' => 'required|exists:roles,id',
    ];
    
    public function mount()
    {
        $this->roles = Role::all();
    }
    
    public function save()
    {
        $this->validate();
        
        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role_id' => $this->role_id,
        ]);
        
        session()->flash('message', 'Pengguna berhasil ditambahkan.');
        return redirect()->route('user.index');
    }
    
    public function render()
    {
        return view('livewire.user.create');
    }
}
