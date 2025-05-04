<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Target Kelompok Bulan</h5>
            <a href="{{ route('target-bulan.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="update">
                <div class="mb-3">
                    <label for="tahunAnggaranId" class="form-label">Tahun Anggaran</label>
                    <select class="form-select @error('tahunAnggaranId') is-invalid @enderror" id="tahunAnggaranId" wire:model="tahunAnggaranId">
                        <option value="">Pilih Tahun Anggaran</option>
                        @foreach($tahunAnggaran as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->tahun }}</option>
                        @endforeach
                    </select>
                    @error('tahunAnggaranId')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="nama_kelompok" class="form-label">Nama Kelompok</label>
                    <input type="text" class="form-control @error('nama_kelompok') is-invalid @enderror" id="nama_kelompok" wire:model="nama_kelompok" placeholder="Contoh: Triwulan I">
                    @error('nama_kelompok')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Bulan</label>
                    <div class="row">
                        @foreach($daftarBulan as $key => $namaBulan)
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="bulan_{{ $key }}" wire:model="bulan" value="{{ $key }}">
                                    <label class="form-check-label" for="bulan_{{ $key }}">
                                        {{ $namaBulan }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('bulan')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="persentase" class="form-label">Persentase</label>
                    <div class="input-group">
                        <input type="number" class="form-control @error('persentase') is-invalid @enderror" id="persentase" wire:model="persentase" placeholder="Masukkan persentase" step="0.01" min="0.01" max="100">
                        <span class="input-group-text">%</span>
                    </div>
                    @error('persentase')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
