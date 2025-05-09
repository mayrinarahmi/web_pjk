<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Target Anggaran</h5>
            <a href="{{ route('target-anggaran.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Target Anggaran
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="tahunAnggaranId" class="form-label">Tahun Anggaran</label>
                    <select class="form-select" id="tahunAnggaranId" wire:model.live="tahunAnggaranId">
                        <option value="">Pilih Tahun Anggaran</option>
                        @foreach($tahunAnggaran as $ta)
                        <option value="{{ $ta->id }}">{{ $ta->tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <!-- Perubahan utama disini - menggunakan wire:model.live.debounce -->
                        <input type="text" class="form-control" placeholder="Cari kode atau nama rekening..." 
                               wire:model.live.debounce.500ms="search">
                        <!-- Tombol search tambahan jika diperlukan -->
                        <button class="btn btn-outline-primary" type="button" wire:click="performSearch">
                            Cari
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter Level</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn {{ $showLevel1 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(1)">1</button>
                        <button type="button" class="btn {{ $showLevel2 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(2)">2</button>
                        <button type="button" class="btn {{ $showLevel3 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(3)">3</button>
                        <button type="button" class="btn {{ $showLevel4 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(4)">4</button>
                        <button type="button" class="btn {{ $showLevel5 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(5)">5</button>
                        <button type="button" class="btn {{ $showLevel6 ? 'btn-primary' : 'btn-outline-primary' }} btn-sm" wire:click="toggleLevel(6)">6</button>
                    </div>
                </div>
            </div>
            
            <!-- Debug info (uncomment jika perlu debug) -->
            <!-- <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        Search: "{{ $search }}" | 
                        Tahun: {{ $tahunAnggaranId ?? 'kosong' }} | 
                        Levels: {{ $showLevel1 ? '1 ' : '' }}{{ $showLevel2 ? '2 ' : '' }}{{ $showLevel3 ? '3 ' : '' }}{{ $showLevel4 ? '4 ' : '' }}{{ $showLevel5 ? '5 ' : '' }}{{ $showLevel6 ? '6' : '' }}
                    </div>
                </div>
            </div> -->
            
            <!-- Tambahan tombol reset -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetFilters">
                        <i class="bx bx-reset"></i> Reset Filter
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">Kode</th>
                            <th width="40%">Nama Rekening</th>
                            <th width="10%">Level</th>
                            <th width="20%">Pagu Anggaran</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kodeRekening as $kr)
                        <tr class="{{ $kr->level == 1 ? 'table-primary' : ($kr->level == 2 ? 'table-info' : ($kr->level == 3 ? 'table-light' : ($kr->level == 4 ? 'table-warning' : ($kr->level == 5 ? 'table-success' : '')))) }}">
                            <td>{{ $kr->kode }}</td>
                            <td>{{ $kr->nama }}</td>
                            <td>{{ $kr->level }}</td>
                            <td class="text-end">
                                @if($tahunAnggaranId)
                                    @php
                                        $targetAnggaran = App\Models\KodeRekening::getTargetAnggaran($kr->id, $tahunAnggaranId);
                                    @endphp
                                    Rp {{ number_format($targetAnggaran, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($kr->level == 5 && $tahunAnggaranId)
                                    @php
                                        $targetAnggaranObj = App\Models\TargetAnggaran::where('kode_rekening_id', $kr->id)
                                            ->where('tahun_anggaran_id', $tahunAnggaranId)
                                            ->first();
                                    @endphp
                                    
                                    @if($targetAnggaranObj)
                                        <a href="{{ route('target-anggaran.edit', $targetAnggaranObj->id) }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-edit"></i> Edit
                                        </a>
                                    @else
                                        <a href="{{ route('target-anggaran.create') }}" class="btn btn-success btn-sm">
                                            <i class="bx bx-plus"></i> Tambah
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $kodeRekening->links() }}
            </div>
        </div>
    </div>
    
    <style>
        /* Style untuk tabel */
        .table-success {
            background-color: rgba(0, 255, 0, 0.1) !important;
        }
    </style>

    <!-- Script Alternatif jika wire:model.live tidak bekerja -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Alternatif manual untuk pencarian jika wire:model tidak berfungsi
            const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.500ms="search"]');
            if (searchInput) {
                // Tambahkan event listener untuk tombol Enter
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        // Panggil livewire method secara manual
                        window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('performSearch');
                    }
                });
            }
        });
    </script>
</div>