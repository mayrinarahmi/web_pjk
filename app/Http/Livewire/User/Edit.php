<?php

namespace App\Http\Livewire\User;

use Livewire\Component;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class Edit extends Component
{
    public $userId;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role_id;
    
    public $roles = [];
    
    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ];
    }
    
    public function mount($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        
        $this->roles = Role::all();
    }
    
    public function update()
    {
        $this->validate();
        
        $user = User::find($this->userId);
        
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
        ];
        
        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }
        
        $user->update($data);
        
        session()->flash('message', 'Pengguna berhasil diperbarui.');
        return redirect()->route('user.index');
    }
    
    public function render()
    {
        return view('livewire.user.edit');
    }
}
