<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah APBD Murni</h5>
            <a href="{{ route('tahun-anggaran.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="alert alert-info">
                    <i class="bx bx-info-circle"></i> 
                    Form ini untuk menambahkan APBD Murni. APBD Perubahan dapat dibuat melalui menu aksi di halaman daftar.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tahun" class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('tahun') is-invalid @enderror" 
                                id="tahun" wire:model="tahun" placeholder="Masukkan tahun">
                            @error('tahun')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tanggal_penetapan" class="form-label">Tanggal Penetapan</label>
                            <input type="date" class="form-control @error('tanggal_penetapan') is-invalid @enderror" 
                                id="tanggal_penetapan" wire:model="tanggal_penetapan">
                            @error('tanggal_penetapan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control @error('keterangan') is-invalid @enderror" 
                        id="keterangan" wire:model="keterangan" rows="3" 
                        placeholder="Masukkan keterangan (opsional)"></textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
               
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" wire:model="is_active">
                        <label class="form-check-label" for="is_active">
                            Aktifkan sebagai tahun anggaran saat ini
                        </label>
                    </div>
                </div>
               
                <div class="text-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="bx bx-save"></i> Simpan
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>