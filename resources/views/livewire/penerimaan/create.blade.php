<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Penerimaan</h5>
            <a href="{{ route('penerimaan.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <!-- TAMBAHAN: Display SKPD Info -->
            @if($userSkpdInfo)
            <div class="alert alert-info py-2">
                <i class="bx bx-info-circle"></i> {{ $userSkpdInfo }}
            </div>
            @endif
            
            <!-- Feedback messages -->
            @if(session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <form wire:submit.prevent="save">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" 
                                id="tanggal" wire:model="tanggal">
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tahun" class="form-label">Tahun <span class="text-danger">*</span></label>
                            <select class="form-select @error('tahun') is-invalid @enderror" 
                                id="tahun" wire:model="tahun">
                                <option value="">Pilih Tahun</option>
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                            @error('tahun')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bx bx-info-circle"></i> Tahun akan otomatis terisi berdasarkan tanggal yang dipilih
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="kode_rekening_id" class="form-label">Kode Rekening <span class="text-danger">*</span></label>
                    <select class="form-select @error('kode_rekening_id') is-invalid @enderror" 
                        id="kode_rekening_id" wire:model="kode_rekening_id">
                        <option value="">Pilih Kode Rekening</option>
                        @foreach($kodeRekeningLevel6 as $kr)
                            <option value="{{ $kr->id }}">{{ $kr->kode }} - {{ $kr->nama }}</option>
                        @endforeach
                    </select>
                    @error('kode_rekening_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        <i class="bx bx-info-circle"></i> Hanya kode rekening level 6 yang dapat dipilih
                        @if(count($kodeRekeningLevel6) == 0)
                            <br><span class="text-warning">
                                <i class="bx bx-exclamation-triangle"></i> 
                                Tidak ada kode rekening yang tersedia. Hubungi administrator.
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control @error('jumlah') is-invalid @enderror" 
                            id="jumlah" wire:model="jumlah" placeholder="0" step="0.01">
                        @error('jumlah')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">
                        <i class="bx bx-info-circle"></i> Gunakan tanda minus (-) untuk koreksi/pengembalian. Contoh: -1000000
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