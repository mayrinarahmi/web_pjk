<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Target Anggaran</h5>
            <a href="{{ route('target-anggaran.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
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
                    <label for="kodeRekeningId" class="form-label">Kode Rekening (Level 5)</label>
                    <select class="form-select @error('kodeRekeningId') is-invalid @enderror" id="kodeRekeningId" wire:model="kodeRekeningId">
                        <option value="">Pilih Kode Rekening</option>
                        @foreach($kodeRekening as $kr)
                        <option value="{{ $kr->id }}">{{ $kr->kode }} - {{ $kr->nama }}</option>
                        @endforeach
                    </select>
                    @error('kodeRekeningId')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="jumlah" class="form-label">Jumlah Target</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control @error('jumlah') is-invalid @enderror" id="jumlah" wire:model="jumlah" placeholder="Masukkan jumlah target">
                    </div>
                    @error('jumlah')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <i class="bx bx-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
