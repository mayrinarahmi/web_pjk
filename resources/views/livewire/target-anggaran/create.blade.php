<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tambah Target Anggaran</h5>
            <a href="{{ route('target-anggaran.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            {{-- ================================================ --}}
            {{-- USER INFO & SKPD INFO - TAMBAHAN BARU ✅ --}}
            {{-- ================================================ --}}
            @if($userSkpdInfo)
            <div class="alert {{ $showSkpdDropdown ? 'alert-info' : 'alert-warning' }} py-2">
                <i class="bx bx-info-circle"></i> {{ $userSkpdInfo }}
            </div>
            @endif
            
            {{-- Info penting --}}
            <div class="alert alert-info">
                <h6><i class="bx bx-info-circle"></i> Informasi Target Anggaran:</h6>
                <ul class="mb-0">
                    <li><strong>Level 6:</strong> Input manual target anggaran per SKPD</li>
                    <li><strong>Level 1-5:</strong> Otomatis dihitung dari SUM children setelah menyimpan</li>
                    <li><strong>Konsolidasi:</strong> Total dari semua SKPD dihitung otomatis</li>
                    <li><strong>Hierarki:</strong> Target parent akan diperbarui otomatis</li>
                </ul>
            </div>
            
            {{-- Feedback messages --}}
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
            
            @if(session()->has('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <form wire:submit.prevent="save">
                <div class="row">
                    {{-- ================================================ --}}
                    {{-- SKPD SELECTOR - TAMBAHAN BARU ✅ --}}
                    {{-- ================================================ --}}
                    @if($showSkpdDropdown)
                    <div class="col-md-12 mb-3">
                        <label for="selectedSkpdId" class="form-label">SKPD <span class="text-danger">*</span></label>
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
                            <i class="bx bx-building"></i> Pilih SKPD yang akan diinput target anggarannya
                        </div>
                    </div>
                    @endif
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tahunAnggaranId" class="form-label">Tahun Anggaran <span class="text-danger">*</span></label>
                            <select class="form-select @error('tahunAnggaranId') is-invalid @enderror" 
                                id="tahunAnggaranId" wire:model="tahunAnggaranId">
                                <option value="">Pilih Tahun Anggaran</option>
                                @foreach($tahunAnggaran as $ta)
                                    <option value="{{ $ta->id }}">
                                        {{ $ta->tahun }} - {{ $ta->jenis_anggaran == 'murni' ? 'MURNI' : 'PERUBAHAN' }}
                                        {{ $ta->is_active ? '(AKTIF)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tahunAnggaranId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bx bx-calendar"></i> Pilih tahun anggaran untuk target yang akan diinput
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        @if($tahunAnggaranId)
                            @php
                                $selectedTahun = $tahunAnggaran->where('id', $tahunAnggaranId)->first();
                            @endphp
                            <div class="mb-3">
                                <label class="form-label">Info Tahun Anggaran</label>
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small>
                                            <strong>Tahun:</strong> {{ $selectedTahun->tahun ?? '-' }}<br>
                                            <strong>Jenis:</strong> 
                                            <span class="badge {{ $selectedTahun->jenis_anggaran == 'murni' ? 'bg-primary' : 'bg-warning' }}">
                                                {{ $selectedTahun->jenis_anggaran == 'murni' ? 'MURNI' : 'PERUBAHAN' }}
                                            </span><br>
                                            <strong>Status:</strong> 
                                            <span class="badge {{ $selectedTahun->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $selectedTahun->is_active ? 'AKTIF' : 'TIDAK AKTIF' }}
                                            </span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="kodeRekeningId" class="form-label">Kode Rekening Level 6 <span class="text-danger">*</span></label>
                    <select class="form-select @error('kodeRekeningId') is-invalid @enderror" 
                        id="kodeRekeningId" wire:model.live="kodeRekeningId">
                        <option value="">Pilih Kode Rekening Level 6</option>
                        @foreach($kodeRekening as $kr)
                            <option value="{{ $kr->id }}">{{ $kr->kode }} - {{ $kr->nama }}</option>
                        @endforeach
                    </select>
                    @error('kodeRekeningId')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        <i class="bx bx-list-ol"></i> Hanya kode rekening <strong>Level 6</strong> (sub rincian objek) yang dapat diinput manual
                    </div>
                    
                    @if($kodeRekening->isEmpty())
                        <div class="alert alert-warning mt-2 py-2">
                            <i class="bx bx-error"></i> 
                            @if($showSkpdDropdown && !$selectedSkpdId)
                                Silakan pilih SKPD terlebih dahulu untuk melihat kode rekening yang tersedia.
                            @else
                                Tidak ada kode rekening Level 6 yang tersedia untuk SKPD ini. Hubungi administrator untuk assignment.
                            @endif
                        </div>
                    @endif
                </div>
                
                {{-- Tampilkan hierarki path jika kode rekening dipilih --}}
                @if($kodeRekeningId)
                    @php
                        $selectedKode = $kodeRekening->where('id', $kodeRekeningId)->first();
                        if ($selectedKode) {
                            $hierarchyPath = $selectedKode->getHierarchiPath();
                        }
                    @endphp
                    @if(isset($hierarchyPath) && count($hierarchyPath) > 1)
                        <div class="alert alert-light border mb-3">
                            <h6><i class="bx bx-git-branch"></i> Hierarki Kode Rekening:</h6>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    @foreach($hierarchyPath as $index => $path)
                                        <li class="breadcrumb-item {{ $index == count($hierarchyPath) - 1 ? 'active' : '' }}">
                                            @if($index == count($hierarchyPath) - 1)
                                                <strong>{{ $path->kode }}</strong> - {{ $path->nama }}
                                            @else
                                                {{ $path->kode }} - {{ Str::limit($path->nama, 30) }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                        </div>
                    @endif
                @endif
                
                <div class="mb-3">
                    <label for="jumlah" class="form-label">Target Anggaran <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control @error('jumlah') is-invalid @enderror" 
                            id="jumlah" wire:model.live="jumlah" placeholder="0" min="0" step="0.01">
                        @error('jumlah')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    {{-- Format preview --}}
                    @if($jumlah > 0)
                        <div class="form-text text-success">
                            <i class="bx bx-money"></i> 
                            Format: <strong>Rp {{ number_format($jumlah, 0, ',', '.') }}</strong>
                        </div>
                    @endif
                </div>
                
                {{-- Preview dampak hierarki --}}
                @if($jumlah > 0 && $kodeRekeningId)
                    @php
                        $selectedKode = $kodeRekening->where('id', $kodeRekeningId)->first();
                        $parentImpacts = [];
                        if ($selectedKode) {
                            $current = $selectedKode->parent;
                            while ($current) {
                                $parentImpacts[] = [
                                    'kode' => $current->kode,
                                    'nama' => $current->nama,
                                    'level' => $current->level
                                ];
                                $current = $current->parent;
                            }
                        }
                    @endphp
                    
                    @if(!empty($parentImpacts))
                        <div class="alert alert-info">
                            <h6><i class="bx bx-trending-up"></i> Target Parent yang akan Terpengaruh:</h6>
                            <ul class="mb-0">
                                @foreach($parentImpacts as $impact)
                                    <li>
                                        <strong>{{ $impact['kode'] }}</strong> (Level {{ $impact['level'] }}): 
                                        <span class="text-success">+Rp {{ number_format($jumlah, 0, ',', '.') }}</span>
                                        <br><small class="text-muted">{{ Str::limit($impact['nama'], 50) }}</small>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="form-text mt-2">
                                <i class="bx bx-info-circle"></i> Target parent akan ditambahkan secara otomatis setelah menyimpan
                            </div>
                        </div>
                    @endif
                @endif
                
                <div class="text-end">
                    <button type="submit" 
                            class="btn btn-primary" 
                            wire:loading.attr="disabled"
                            @if($kodeRekening->isEmpty()) disabled @endif>
                        <span wire:loading.remove>
                            <i class="bx bx-save"></i> Simpan Target Anggaran
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Menyimpan & Update Hierarki...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    {{-- Bantuan penggunaan --}}
    <div class="card mt-3">
        <div class="card-body">
            <h6><i class="bx bx-help-circle"></i> Cara Penggunaan:</h6>
            <div class="row">
                <div class="col-md-6">
                    <ol>
                        @if($showSkpdDropdown)
                        <li>Pilih <strong>SKPD</strong> yang akan diinput</li>
                        @endif
                        <li>Pilih <strong>Tahun Anggaran</strong> yang akan diinput</li>
                        <li>Pilih <strong>Kode Rekening Level 6</strong> (sub rincian objek)</li>
                        <li>Masukkan <strong>Target Anggaran</strong> dalam rupiah</li>
                        <li>Klik <strong>Simpan</strong> untuk menyimpan dan update hierarki</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6>Catatan Penting:</h6>
                    <ul>
                        <li>Hanya <strong>Level 6</strong> yang bisa diinput manual</li>
                        <li>Level 1-5 otomatis dihitung dari children</li>
                        <li>Target yang sudah ada akan di-update</li>
                        <li>Hierarki parent diperbarui otomatis</li>
                        <li>Data tersimpan <strong>per SKPD</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>