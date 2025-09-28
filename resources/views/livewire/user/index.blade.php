<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Pengguna</h5>
            @can('create-users')
            <a href="{{ route('user.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Pengguna
            </a>
            @endcan
        </div>
        <div class="card-body">
            <!-- Alert Messages -->
            @if (session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Cari (NIP/Nama/Email)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" 
                               class="form-control" 
                               placeholder="Cari user..." 
                               wire:model.debounce.300ms="search">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Filter Role</label>
                    <select class="form-select" wire:model="filterRole">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Filter SKPD</label>
                    <select class="form-select" wire:model="filterSkpd">
                        <option value="">Semua SKPD</option>
                        <option value="no_skpd">Tanpa SKPD</option>
                        @foreach($skpdList as $skpd)
                            <option value="{{ $skpd->id }}">{{ $skpd->nama_opd }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" 
                            class="btn btn-secondary w-100" 
                            wire:click="resetFilters">
                        <i class="bx bx-reset"></i> Reset
                    </button>
                </div>
            </div>
            
            <!-- Loading Indicator -->
            <div wire:loading class="mb-3">
                <div class="d-flex align-items-center text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Memuat data...</div>
                </div>
            </div>
            
            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">NIP</th>
                            <th width="20%">Nama</th>
                            <th width="20%">Email</th>
                            <th width="15%">Role</th>
                            <th width="15%">SKPD</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $key => $user)
                        <tr>
                            <td>{{ $users->firstItem() + $key }}</td>
                            <td>
                                <code>{{ $user->nip ?? '-' }}</code>
                            </td>
                            <td>
                                {{ $user->name }}
                                @if($user->id == auth()->id())
                                    <span class="badge bg-info ms-1">Anda</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->roles->count() > 0)
                                    @foreach($user->roles as $role)
                                        <span class="badge 
                                            @if(in_array($role->name, ['Super Admin', 'Administrator']))
                                                bg-danger
                                            @elseif($role->name == 'Kepala Badan')
                                                bg-primary
                                            @elseif($role->name == 'Operator SKPD')
                                                bg-success
                                            @elseif($role->name == 'Operator')
                                                bg-warning
                                            @else
                                                bg-secondary
                                            @endif
                                        ">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($user->skpd)
                                    <small>{{ $user->skpd->nama_opd }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('edit-users')
                                    <a href="{{ route('user.edit', $user->id) }}" 
                                       class="btn btn-primary" 
                                       title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    @endcan
                                    
                                    @can('delete-users')
                                        @if($user->id != auth()->id())
                                        <button type="button" 
                                                class="btn btn-danger" 
                                                onclick="confirm('Apakah Anda yakin ingin menghapus pengguna ini?') || event.stopImmediatePropagation()" 
                                                wire:click="delete({{ $user->id }})" 
                                                wire:loading.attr="disabled"
                                                title="Hapus">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                        @else
                                        <button type="button" 
                                                class="btn btn-secondary" 
                                                disabled
                                                title="Tidak dapat menghapus akun sendiri">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bx bx-user-x fs-1"></i>
                                    <p class="mt-2">Tidak ada data pengguna</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="mt-3">
                {{ $users->links() }}
            </div>
            
            <!-- Info -->
            <div class="text-muted">
                Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} 
                dari {{ $users->total() }} pengguna
            </div>
        </div>
    </div>
</div>