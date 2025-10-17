<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Penerimaan</h5>
            <a href="{{ route('penerimaan.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <!-- USER SKPD INFO BADGE -->
            @if($userSkpdInfo)
            <div class="alert alert-info mb-3 d-flex align-items-center" role="alert">
                <i class="bx bx-info-circle-fill me-2 fs-5"></i>
                <div class="flex-grow-1">
                    <strong>{{ $userSkpdInfo }}</strong>
                    @if(!$showSkpdDropdown && $selectedSkpdId)
                        <br>
                        <small class="text-muted">
                            <i class="bx bx-lock-fill"></i> Data akan tersimpan untuk SKPD Anda dan tidak dapat diubah
                        </small>
                    @endif
                </div>
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
                <!-- ============================================ -->
                <!-- SKPD SELECTION (Super Admin Only) -->
                <!-- ============================================ -->
                @if($showSkpdDropdown)
                <div class="mb-3">
                    <label for="selectedSkpdId" class="form-label fw-bold">
                        <i class="bx bx-building"></i> SKPD <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('selectedSkpdId') is-invalid @enderror" 
                        id="selectedSkpdId" wire:model.live="selectedSkpdId">
                        <option value="">Pilih SKPD</option>
                        @foreach($skpdList as $skpd)
                            <option value="{{ $skpd->id }}">{{ $skpd->nama_opd }}</option>
                        @endforeach
                    </select>
                    @error('selectedSkpdId')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        <i class="bx bx-info-circle"></i> Pilih SKPD yang akan diinputkan data penerimaannya
                    </div>
                </div>
                @else
                <!-- SKPD READONLY (Operator SKPD) -->
                <div class="mb-3">
                    <label for="skpd_readonly" class="form-label fw-bold">
                        <i class="bx bx-building"></i> SKPD
                    </label>
                    <input type="text" 
                           class="form-control bg-light" 
                           id="skpd_readonly"
                           value="{{ auth()->user()->skpd ? auth()->user()->skpd->nama_opd : 'Tidak ada SKPD' }}" 
                           readonly 
                           disabled>
                    <div class="form-text text-muted">
                        <i class="bx bx-lock-fill"></i> SKPD Anda (tidak dapat diubah)
                    </div>
                </div>
                @endif
                
                <!-- ============================================ -->
                <!-- TANGGAL & TAHUN -->
                <!-- ============================================ -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label fw-bold">
                                <i class="bx bx-calendar"></i> Tanggal <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" 
                                id="tanggal" wire:model.live="tanggal">
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tahun" class="form-label fw-bold">
                                <i class="bx bx-time"></i> Tahun <span class="text-danger">*</span>
                            </label>
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
                
                <!-- ============================================ -->
                <!-- KODE REKENING -->
                <!-- ============================================ -->
                <div class="mb-3">
                    <label for="kode_rekening_id" class="form-label fw-bold">
                        <i class="bx bx-list-ol"></i> Kode Rekening <span class="text-danger">*</span>
                    </label>
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
                    
                    @if(count($kodeRekeningLevel6) > 0)
                        <div class="form-text">
                            <i class="bx bx-check-circle text-success"></i> 
                            {{ count($kodeRekeningLevel6) }} kode rekening tersedia untuk SKPD ini
                        </div>
                    @else
                        <div class="form-text text-warning">
                            <i class="bx bx-error-circle"></i> 
                            Tidak ada kode rekening yang tersedia. 
                            @if($showSkpdDropdown)
                                Pilih SKPD yang memiliki assignment kode rekening.
                            @else
                                Hubungi administrator untuk assign kode rekening ke SKPD Anda.
                            @endif
                        </div>
                    @endif
                </div>
                
                <!-- ============================================ -->
                <!-- JUMLAH -->
                <!-- ============================================ -->
                <div class="mb-3">
                    <label for="jumlah" class="form-label fw-bold">
                        <i class="bx bx-money"></i> Jumlah <span class="text-danger">*</span>
                    </label>
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
                
                <!-- ============================================ -->
                <!-- KETERANGAN -->
                <!-- ============================================ -->
                <div class="mb-3">
                    <label for="keterangan" class="form-label fw-bold">
                        <i class="bx bx-note"></i> Keterangan
                    </label>
                    <textarea class="form-control @error('keterangan') is-invalid @enderror" 
                        id="keterangan" wire:model="keterangan" rows="3" 
                        placeholder="Masukkan keterangan (opsional)"></textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- ============================================ -->
                <!-- BUTTONS -->
                <!-- ============================================ -->
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('penerimaan.index') }}" class="btn btn-secondary">
                        <i class="bx bx-x"></i> Batal
                    </a>
                    
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="bx bx-save"></i> Simpan Data
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