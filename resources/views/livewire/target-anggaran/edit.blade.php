<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Target Anggaran</h5>
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
            
            {{-- Info current data --}}
            <div class="alert alert-info">
                <h6><i class="bx bx-info-circle"></i> Data yang sedang diedit:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Kode Rekening:</strong> {{ $kodeRekeningInfo->kode ?? '-' }}<br>
                        <strong>Nama:</strong> {{ $kodeRekeningInfo->nama ?? '-' }}<br>
                        <strong>Level:</strong> {{ $kodeRekeningInfo->level ?? '-' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Tahun Anggaran:</strong> {{ $tahunAnggaranInfo->display_name ?? '-' }}<br>
                        <strong>Target Lama:</strong> Rp {{ number_format($oldJumlah, 0, ',', '.') }}<br>
                        <strong>Target Baru:</strong> <span class="fw-bold text-primary">Rp {{ number_format($jumlah, 0, ',', '.') }}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>SKPD:</strong> {{ $targetAnggaran->skpd ? $targetAnggaran->skpd->nama_opd : 'Tidak ada SKPD' }}
                    </div>
                </div>
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
            
            <form wire:submit.prevent="update">
                <div class="row">
                    {{-- ================================================ --}}
                    {{-- SKPD SELECTOR - TAMBAHAN BARU (HANYA SUPER ADMIN) ✅ --}}
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
                            <i class="bx bx-building"></i> Super Admin dapat mengubah kepemilikan SKPD
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
                        </div>
                    </div>
                    
                    <div class="col-md-6">
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
                                <i class="bx bx-list-ol"></i> Kode Rekening <span class="text-danger">*</span>
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
                                <i class="bx bx-info-circle"></i> Hanya kode rekening <strong>Level 6</strong> yang dapat dipilih
                            </div>
                            <style>.hover-bg:hover { background-color: #f0f4ff; }</style>
                        </div>
                    </div>
                </div>
                
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
                    
                    {{-- Live preview perubahan --}}
                    @if($jumlah != $oldJumlah)
                        @php
                            $change = $jumlah - $oldJumlah;
                            $changeText = $change >= 0 ? 'bertambah' : 'berkurang';
                            $changeClass = $change >= 0 ? 'text-success' : 'text-danger';
                        @endphp
                        <div class="form-text {{ $changeClass }}">
                            <i class="bx {{ $change >= 0 ? 'bx-trending-up' : 'bx-trending-down' }}"></i>
                            Target {{ $changeText }} Rp {{ number_format(abs($change), 0, ',', '.') }}
                        </div>
                    @endif
                </div>
                
                {{-- Dampak Hierarki Preview --}}
                @if(!empty($impactPreview))
                    <div class="alert alert-warning">
                        <h6><i class="bx bx-git-branch"></i> Dampak Perubahan pada Hierarki Parent:</h6>
                        <ul class="mb-0">
                            @foreach($impactPreview as $impact)
                                <li>
                                    <strong>{{ $impact['kode'] }}:</strong> {{ $impact['change'] }}
                                    <small class="text-muted">({{ Str::limit($impact['nama'], 40) }})</small>
                                </li>
                            @endforeach
                        </ul>
                        <div class="form-text mt-2">
                            <i class="bx bx-info-circle"></i> Target parent akan otomatis diperbarui setelah menyimpan
                        </div>
                    </div>
                @endif
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="bx bx-save"></i> Simpan Perubahan
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
    
    {{-- Info tambahan tentang hierarki --}}
    <div class="card mt-3">
        <div class="card-body">
            <h6><i class="bx bx-help-circle"></i> Informasi Hierarki Target Anggaran:</h6>
            <ul class="mb-0">
                <li><strong>Otomatis Update:</strong> Perubahan target level 6 akan otomatis memperbarui semua parent (level 1-5)</li>
                <li><strong>Konsistensi:</strong> Target parent selalu = SUM dari children</li>
                <li><strong>Real-time Preview:</strong> Dampak perubahan ditampilkan sebelum menyimpan</li>
                <li><strong>Per SKPD:</strong> Target tersimpan untuk SKPD tertentu</li>
                <li><strong>Audit Trail:</strong> Informasi perubahan dan dampak tersimpan di log</li>
            </ul>
        </div>
    </div>
</div>