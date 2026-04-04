<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Kode Rekening</h5>
            <a href="{{ route('kode-rekening.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="update">
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
                    <label class="form-label">Parent</label>
                    <input type="text"
                           class="form-control mb-1"
                           wire:model.live.debounce.250ms="parentSearch"
                           placeholder="Cari kode atau nama parent..."
                           autocomplete="off">
                    <select class="form-select @error('parent_id') is-invalid @enderror"
                            id="parent_id"
                            wire:model="parent_id">
                        <option value="">-- Pilih Parent --</option>
                        @foreach($filteredParents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->kode }} - {{ $parent->nama }}</option>
                        @endforeach
                    </select>
                    @if($parentSearch && count($filteredParents) === 0)
                        <div class="text-muted small mt-1"><i class="bx bx-info-circle"></i> Tidak ada parent yang cocok dengan pencarian.</div>
                    @endif
                    @error('parent_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                @endif
                
                <div class="mb-3">
                    <label for="berlaku_mulai" class="form-label">Berlaku Mulai (Tahun)</label>
                    <input type="number"
                           class="form-control @error('berlaku_mulai') is-invalid @enderror"
                           id="berlaku_mulai"
                           wire:model="berlaku_mulai"
                           placeholder="Contoh: 2026 (kosongkan jika selalu berlaku)"
                           min="2000" max="2099">
                    @error('berlaku_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">Kosongkan jika kode rekening berlaku di semua tahun.</div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" wire:model="is_active">
                        <label class="form-check-label" for="is_active">
                            Aktif
                        </label>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <i class="bx bx-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
