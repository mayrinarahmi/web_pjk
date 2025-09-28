<?php

namespace App\Http\Livewire\User;

use Livewire\Component;
use App\Models\User;
use App\Models\Skpd;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    public $nip;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $spatie_role; // Untuk Spatie Role
    public $skpd_id;
    
    public $roles = [];
    public $skpdList = [];
    public $showSkpdField = false;
    public $skpdRequired = false;
    
    protected function rules()
    {
        $rules = [
            'nip' => 'required|digits:18|unique:users,nip',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'spatie_role' => 'required|string|exists:roles,name',
        ];
        
        // Validasi SKPD berdasarkan role
        if ($this->skpdRequired) {
            $rules['skpd_id'] = 'required|exists:skpd,id';
        } else {
            $rules['skpd_id'] = 'nullable';
        }
        
        return $rules;
    }
    
    protected $messages = [
        'nip.required' => 'NIP wajib diisi',
        'nip.digits' => 'NIP harus 18 digit angka',
        'nip.unique' => 'NIP sudah terdaftar',
        'name.required' => 'Nama wajib diisi',
        'email.required' => 'Email wajib diisi',
        'email.email' => 'Format email tidak valid',
        'email.unique' => 'Email sudah terdaftar',
        'password.required' => 'Password wajib diisi',
        'password.min' => 'Password minimal 8 karakter',
        'password.confirmed' => 'Konfirmasi password tidak cocok',
        'spatie_role.required' => 'Role wajib dipilih',
        'skpd_id.required' => 'SKPD wajib dipilih untuk role ini',
    ];
    
    public function mount()
    {
        // Load Spatie Roles
        $this->roles = Role::orderBy('name')->get();
        
        // Load SKPD list
        $this->skpdList = Skpd::where('status', 'aktif')
                              ->orderBy('nama_opd')
                              ->get();
    }
    
    public function updatedSpatieRole()
    {
        $this->handleRoleChange();
    }
    
    private function handleRoleChange()
    {
        // Reset SKPD
        $this->skpd_id = null;
        
        // Determine SKPD requirement based on role
        switch($this->spatie_role) {
            case 'Super Admin':
            case 'Administrator':
            case 'Operator':
                // Tidak boleh punya SKPD
                $this->showSkpdField = false;
                $this->skpdRequired = false;
                $this->skpd_id = null;
                break;
                
            case 'Kepala Badan':
                // Harus BPKPAD
                $this->showSkpdField = true;
                $this->skpdRequired = true;
                // Auto select BPKPAD
                $bpkpad = Skpd::where('kode_opd', '5.02.0.00.0.00.05.0000')->first();
                if ($bpkpad) {
                    $this->skpd_id = $bpkpad->id;
                }
                break;
                
            case 'Operator SKPD':
                // Harus pilih SKPD
                $this->showSkpdField = true;
                $this->skpdRequired = true;
                break;
                
            case 'Viewer':
                // SKPD optional
                $this->showSkpdField = true;
                $this->skpdRequired = false;
                break;
                
            default:
                $this->showSkpdField = false;
                $this->skpdRequired = false;
        }
    }
    
    public function save()
    {
        $this->validate();
        
        // Validasi tambahan untuk kombinasi role + SKPD
        if (!$this->validateRoleSkpdCombination()) {
            return;
        }
        
        DB::beginTransaction();
        try {
            // Determine role_id for backward compatibility
            $roleId = $this->getRoleIdFromSpatieName($this->spatie_role);
            
            // Create user
            $user = User::create([
                'nip' => $this->nip,
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role_id' => $roleId, // For backward compatibility
                'skpd_id' => $this->skpd_id,
            ]);
            
            // Assign Spatie Role
            $user->assignRole($this->spatie_role);
            
            DB::commit();
            
            Log::info('User created successfully', [
                'user_id' => $user->id,
                'nip' => $user->nip,
                'role' => $this->spatie_role,
                'skpd' => $this->skpd_id
            ]);
            
            session()->flash('message', 'Pengguna berhasil ditambahkan.');
            return redirect()->route('user.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menambahkan pengguna.');
        }
    }
    
    private function validateRoleSkpdCombination()
    {
        // Super Admin, Administrator, Operator tidak boleh punya SKPD
        if (in_array($this->spatie_role, ['Super Admin', 'Administrator', 'Operator'])) {
            if ($this->skpd_id) {
                $this->addError('skpd_id', 'Role ' . $this->spatie_role . ' tidak boleh memiliki SKPD');
                return false;
            }
        }
        
        // Kepala Badan harus BPKPAD
        if ($this->spatie_role == 'Kepala Badan') {
            $bpkpad = Skpd::where('kode_opd', '5.02.0.00.0.00.05.0000')->first();
            if (!$bpkpad || $this->skpd_id != $bpkpad->id) {
                $this->addError('skpd_id', 'Kepala Badan harus menggunakan SKPD BPKPAD');
                return false;
            }
        }
        
        // Operator SKPD harus punya SKPD
        if ($this->spatie_role == 'Operator SKPD' && !$this->skpd_id) {
            $this->addError('skpd_id', 'Operator SKPD harus memilih SKPD');
            return false;
        }
        
        return true;
    }
    
    private function getRoleIdFromSpatieName($spatieName)
    {
        // Mapping untuk backward compatibility
        $mapping = [
            'Super Admin' => 1,
            'Administrator' => 1,
            'Kepala Badan' => 1,
            'Operator' => 2,
            'Operator SKPD' => 2,
            'Viewer' => 3,
        ];
        
        return $mapping[$spatieName] ?? 3;
    }
    
    public function render()
    {
        return view('livewire.user.create');
    }
}