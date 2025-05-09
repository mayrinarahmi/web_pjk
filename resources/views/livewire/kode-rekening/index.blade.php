<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Kode Rekening</h5>
            <a href="{{ route('kode-rekening.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Kode Rekening
            </a>
        </div>
        <div class="card-body">
            <!-- Form pencarian super sederhana -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="simpleSearch" class="form-control" placeholder="Ketik untuk mencari...">
                    </div>
                </div>
            </div>
            
            <!-- Feedback message -->
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
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Kode</th>
                            <th>Nama</th>
                            <th width="10%">Level</th>
                            <th width="10%">Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kodeRekening as $key => $kode)
                        <tr>
                            <td>{{ $kodeRekening->firstItem() + $key }}</td>
                            <td>{{ $kode->kode }}</td>
                            <td>{{ $kode->nama }}</td>
                            <td>{{ $kode->level }}</td>
                            <td>
                                @if($kode->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('kode-rekening.edit', $kode->id) }}" class="btn btn-primary btn-sm">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click="delete({{ $kode->id }})" wire:loading.attr="disabled">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="no-data-row">
                            <td colspan="6" class="text-center">Tidak ada data</td>
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

    <!-- Script Pencarian Super-Sederhana -->
    <script>
        // Tunggu sampai halaman selesai dimuat
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil elemen input pencarian
            const searchInput = document.getElementById('simpleSearch');
            
            // Tambahkan event listener untuk input
            searchInput.addEventListener('input', function() {
                // Ambil nilai pencarian (lowercase)
                const searchText = this.value.toLowerCase();
                
                // Ambil semua baris dalam tabel kecuali header
                const rows = document.querySelectorAll('#dataTable tbody tr');
                
                // Jumlah baris yang terlihat
                let visibleCount = 0;
                
                // Loop melalui semua baris
                rows.forEach(function(row) {
                    // Skip baris "tidak ada data"
                    if (row.classList.contains('no-data-row')) {
                        return;
                    }
                    
                    // Ambil teks dari kolom kode dan nama
                    const kode = row.cells[1].textContent.toLowerCase();
                    const nama = row.cells[2].textContent.toLowerCase();
                    
                    // Cek apakah teks pencarian ada dalam kode atau nama
                    if (kode.includes(searchText) || nama.includes(searchText)) {
                        // Tampilkan baris
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        // Sembunyikan baris
                        row.style.display = 'none';
                    }
                });
                
                // Tangani kasus "tidak ada hasil"
                let noDataRow = document.querySelector('.no-data-row');
                
                if (visibleCount === 0) {
                    // Jika tidak ada hasil
                    if (noDataRow) {
                        // Update pesan dan tampilkan
                        noDataRow.style.display = '';
                        noDataRow.querySelector('td').textContent = 'Tidak ditemukan hasil untuk "' + searchText + '"';
                    } else {
                        // Buat baris pesan jika belum ada
                        const tbody = document.querySelector('#dataTable tbody');
                        const tr = document.createElement('tr');
                        tr.className = 'no-data-row';
                        const td = document.createElement('td');
                        td.colSpan = 6;
                        td.className = 'text-center';
                        td.textContent = 'Tidak ditemukan hasil untuk "' + searchText + '"';
                        tr.appendChild(td);
                        tbody.appendChild(tr);
                        noDataRow = tr;
                    }
                } else if (noDataRow) {
                    // Jika ada hasil, sembunyikan pesan "tidak ada data"
                    noDataRow.style.display = 'none';
                }
            });
        });
    </script>
</div>