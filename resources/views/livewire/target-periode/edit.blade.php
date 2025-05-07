<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Edit Target Periode</h5>
            <a href="{{ route('target-periode.index') }}" class="btn btn-secondary btn-sm">
                <i class="bx bx-arrow-back"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            @if (session()->has('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <form wire:submit.prevent="update">
                <div class="mb-3">
                    <label for="tahunAnggaranId" class="form-label">Tahun Anggaran</label>
                    <select wire:model="tahunAnggaranId" id="tahunAnggaranId" class="form-select" disabled>
                        <option value="">Pilih Tahun Anggaran</option>
                        @foreach($tahunAnggaran as $ta)
                            <option value="{{ $ta->id }}">{{ $ta->tahun }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="nama_periode" class="form-label">Nama Periode</label>
                    <input type="text" wire:model="nama_periode" id="nama_periode" class="form-control" placeholder="Contoh: Triwulan I">
                    @error('nama_periode') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="bulan_awal" class="form-label">Bulan Awal</label>
                        <select wire:model="bulan_awal" id="bulan_awal" class="form-select">
                            <option value="">Pilih Bulan Awal</option>
                            @foreach($daftarBulan as $key => $bulan)
                                <option value="{{ $key }}">{{ $bulan }}</option>
                            @endforeach
                        </select>
                        @error('bulan_awal') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="bulan_akhir" class="form-label">Bulan Akhir</label>
                        <select wire:model="bulan_akhir" id="bulan_akhir" class="form-select">
                            <option value="">Pilih Bulan Akhir</option>
                            @foreach($daftarBulan as $key => $bulan)
                                <option value="{{ $key }}">{{ $bulan }}</option>
                            @endforeach
                        </select>
                        @error('bulan_akhir') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="persentase" class="form-label">Persentase</label>
                    <div class="input-group">
                        <input type="number" wire:model="persentase" id="persentase" class="form-control" step="0.01" min="0.01" max="100">
                        <span class="input-group-text">%</span>
                    </div>
                    @error('persentase') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>