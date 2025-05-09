<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Struktur Hierarki Kode Rekening</h5>
            <a href="{{ route('kode-rekening.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Tambah Kode Rekening
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="simpleSearch" class="form-control" placeholder="Cari kode atau nama...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="levelFilter" class="form-select">
                        <option value="">Semua Level</option>
                        <option value="1">Level 1</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5</option>
                        <option value="6">Level 6</option>
                    </select>
                </div>
                <div class="col-md-5 text-end">
                    <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-reset"></i> Reset Filter
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="hierarchyTable">
                    <thead class="table-light">
                        <tr>
                            <th width="20%">Kode</th>
                            <th>Nama</th>
                            <th width="10%">Level</th>
                            <th width="10%">Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kodeRekening as $kode)
                            @include('livewire.kode-rekening.partials.rekening-item', ['kode' => $kode, 'level' => 0])
                        @endforeach
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="5" class="text-center">Tidak ditemukan hasil yang sesuai</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Script untuk pencarian di tampilan hierarki -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('simpleSearch');
            const levelSelect = document.getElementById('levelFilter');
            const resetBtn = document.getElementById('resetFilterBtn');
            const table = document.getElementById('hierarchyTable');
            const noResultsRow = document.getElementById('noResultsRow');
            
            // Fungsi pencarian utama
            function applyFilter() {
                const searchText = searchInput.value.toLowerCase();
                const levelValue = levelSelect.value;
                
                let hasVisibleRows = false;
                
                // Sembunyikan semua baris
                const rows = table.querySelectorAll('tbody tr:not(#noResultsRow)');
                rows.forEach(row => {
                    // Dapatkan nilai dari kolom
                    const kode = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                    const nama = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const level = row.querySelector('td:nth-child(3)').textContent.trim();
                    
                    // Periksa apakah baris cocok dengan kriteria filter
                    const matchesSearch = searchText === '' || 
                                         kode.includes(searchText) || 
                                         nama.includes(searchText);
                    
                    const matchesLevel = levelValue === '' || level === levelValue;
                    
                    // Tampilkan/sembunyikan baris berdasarkan hasil filter
                    if (matchesSearch && matchesLevel) {
                        row.style.display = '';
                        hasVisibleRows = true;
                        
                        // Untuk hierarki, kita perlu menampilkan parent jika child cocok
                        let parent = getParentRow(row);
                        while (parent) {
                            parent.style.display = '';
                            parent = getParentRow(parent);
                        }
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Tampilkan pesan "tidak ada hasil" jika diperlukan
                noResultsRow.style.display = hasVisibleRows ? 'none' : '';
            }
            
            // Fungsi helper untuk mendapatkan baris parent
            function getParentRow(row) {
                const kodeIndentasi = getIndentationLevel(row);
                if (kodeIndentasi <= 0) return null;
                
                let prevRow = row.previousElementSibling;
                while (prevRow) {
                    const prevIndentasi = getIndentationLevel(prevRow);
                    if (prevIndentasi < kodeIndentasi) {
                        return prevRow;
                    }
                    prevRow = prevRow.previousElementSibling;
                }
                
                return null;
            }
            
            // Fungsi helper untuk mendapatkan level indentasi
            function getIndentationLevel(row) {
                const firstCell = row.querySelector('td:first-child');
                const paddingLeft = window.getComputedStyle(firstCell).paddingLeft;
                return parseInt(paddingLeft) || 0;
            }
            
            // Event listener untuk input pencarian
            searchInput.addEventListener('input', applyFilter);
            
            // Event listener untuk filter level
            levelSelect.addEventListener('change', applyFilter);
            
            // Event listener untuk tombol reset
            resetBtn.addEventListener('click', function() {
                searchInput.value = '';
                levelSelect.selectedIndex = 0;
                applyFilter();
            });
        });
    </script>
</div>