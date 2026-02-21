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
                
                <div class="mb-3" x-data="{
                    open: false,
                    search: '',
                    selectedId: @entangle('kodeRekeningId'),
                    items: @js($kodeRekening->map(fn($kr) => ['id' => $kr->id, 'kode' => $kr->kode, 'nama' => $kr->nama])->values()->toArray()),
                    get filtered() {
                        if (!this.search) return this.items;
                        const s = this.search.toLowerCase();
                        return this.items.filter(i => i.kode.toLowerCase().includes(s) || i.nama.toLowerCase().includes(s));
                    },
                    get selectedLabel() {
                        const item = this.items.find(i => i.id == this.selectedId);
                        return item ? item.kode + ' - ' + item.nama : '';
                    },
                    select(item) {
                        this.selectedId = item.id;
                        this.search = '';
                        this.open = false;
                    },
                    clear() {
                        this.selectedId = '';
                        this.search = '';
                        this.$refs.searchInput.focus();
                    }
                }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <label class="form-label fw-bold">
                        <i class="bx bx-list-ol"></i> Kode Rekening Level 6 <span class="text-danger">*</span>
                    </label>

                    <div class="position-relative">
                        <div class="form-control d-flex align-items-center @error('kodeRekeningId') is-invalid @enderror"
                             style="min-height: 38px; cursor: pointer;"
                             @click="open = !open; $nextTick(() => { if(open) $refs.searchInput.focus() })">
                            <template x-if="!open && selectedId">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <span class="text-truncate" x-text="selectedLabel"></span>
                                    <button type="button" class="btn-close btn-close-sm ms-2"
                                            style="font-size: 0.6rem;"
                                            @click.stop="clear()" title="Hapus pilihan"></button>
                                </div>
                            </template>
                            <template x-if="!open && !selectedId">
                                <span class="text-muted">Ketik kode atau nama rekening...</span>
                            </template>
                            <template x-if="open">
                                <input type="text"
                                       x-ref="searchInput"
                                       x-model="search"
                                       class="border-0 w-100 p-0"
                                       style="outline: none; box-shadow: none;"
                                       placeholder="Ketik kode atau nama rekening..."
                                       @keydown.tab="open = false"
                                       @click.stop>
                            </template>
                        </div>

                        @error('kodeRekeningId')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div x-show="open" x-transition.opacity
                             class="position-absolute w-100 bg-white border rounded-bottom shadow-lg"
                             style="z-index: 1050; max-height: 280px; overflow-y: auto; top: 100%; left: 0;">
                            <template x-if="filtered.length === 0">
                                <div class="px-3 py-2 text-muted small">
                                    <i class="bx bx-search-alt"></i> Tidak ditemukan
                                </div>
                            </template>
                            <template x-for="item in filtered" :key="item.id">
                                <div class="px-3 py-2 border-bottom"
                                     style="cursor: pointer; font-size: 0.875rem;"
                                     :class="{ 'bg-primary text-white': item.id == selectedId, 'hover-bg': item.id != selectedId }"
                                     @click="select(item)">
                                    <span class="fw-semibold" x-text="item.kode"></span>
                                    <span class="ms-1" :class="item.id == selectedId ? 'text-white' : 'text-muted'">-</span>
                                    <span class="ms-1" x-text="item.nama"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="form-text">
                        @if($kodeRekening->isNotEmpty())
                            <i class="bx bx-check-circle text-success"></i>
                            {{ $kodeRekening->count() }} kode rekening tersedia — hanya <strong>Level 6</strong> (sub rincian objek)
                        @else
                            <i class="bx bx-error-circle text-warning"></i>
                            @if($showSkpdDropdown && !$selectedSkpdId)
                                Pilih SKPD terlebih dahulu untuk melihat kode rekening yang tersedia.
                            @else
                                Tidak ada kode rekening Level 6 untuk SKPD ini. Hubungi administrator.
                            @endif
                        @endif
                    </div>

                    <style>.hover-bg:hover { background-color: #f0f4ff; }</style>
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