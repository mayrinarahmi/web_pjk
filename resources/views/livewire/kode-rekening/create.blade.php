<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Kode Rekening</h5>
            <a href="{{ route('kode-rekening.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="mb-3">
                    <label for="kode" class="form-label">Kode</label>
                    <input type="text" class="form-control @error('kode') is-invalid @enderror" id="kode" wire:model="kode" placeholder="Contoh: 4.1.01.09.01.0001">
                    @error('kode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama</label>
                    <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" wire:model="nama" placeholder="Masukkan nama kode rekening">
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="level" class="form-label">Level</label>
                    <select class="form-select @error('level') is-invalid @enderror" id="level" wire:model="level">
                        <option value="1">Level 1</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5</option>
                        <option value="6">Level 6</option>
                    </select>
                    @error('level')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                @if($level > 1)
                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent</label>
                    <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" wire:model="parent_id">
                        <option value="">Pilih Parent</option>
                        @foreach($availableParents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->kode }} - {{ $parent->nama }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <i class="bx bx-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
