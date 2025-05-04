<div>
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">Dashboard Penerimaan Pajak Daerah</h5>
                        <div class="d-flex align-items-center">
                            <label for="tahunAnggaranId" class="me-2">Tahun Anggaran:</label>
                            <select wire:model="tahunAnggaranId" id="tahunAnggaranId" class="form-select form-select-sm" style="width: 120px;">
                                @foreach($tahunAnggaran as $ta)
                                    <option value="{{ $ta->id }}">{{ $ta->tahun }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Penerimaan -->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total Pendapatan Daerah</h5>
                            <small class="text-muted">Realisasi vs Target</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded-pill p-2">
                                <i class="bx bx-money bx-sm"></i>
                            </span>
                        </div>
                    </div>
                    <h3 class="card-title mb-2 mt-4">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</h3>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min($persentasePendapatan, 100) }}%" aria-valuenow="{{ $persentasePendapatan }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">{{ $persentasePendapatan }}% dari target</small>
                        <small class="text-muted">Target: Rp {{ number_format($targetPendapatan, 0, ',', '.') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Pendapatan Asli Daerah (PAD)</h5>
                            <small class="text-muted">Realisasi vs Target</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded-pill p-2">
                                <i class="bx bx-line-chart bx-sm"></i>
                            </span>
                        </div>
                    </div>
                    <h3 class="card-title mb-2 mt-4">Rp {{ number_format($totalPAD, 0, ',', '.') }}</h3>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ min($persentasePAD, 100) }}%" aria-valuenow="{{ $persentasePAD }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">{{ $persentasePAD }}% dari target</small>
                        <small class="text-muted">Target: Rp {{ number_format($targetPAD, 0, ',', '.') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="row">
        <div class="col-lg-8 col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tren Penerimaan Bulanan</h5>
                </div>
                <div class="card-body">
                    <div id="monthlyRevenueChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Distribusi Penerimaan</h5>
                </div>
                <div class="card-body">
                    <div id="revenueDistributionChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Kategori -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Realisasi per Kategori</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-end">Target</th>
                                    <th class="text-end">Realisasi</th>
                                    <th class="text-end">Persentase</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dataKategori as $kategori)
                                <tr>
                                    <td>{{ $kategori['nama'] }}</td>
                                    <td class="text-end">Rp {{ number_format($kategori['target'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($kategori['total'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ $kategori['persentase'] }}%</td>
                                    <td>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min($kategori['persentase'], 100) }}%" aria-valuenow="{{ $kategori['persentase'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('livewire:load', function () {
        // Data untuk grafik bulanan
        var monthlyData = @json($dataBulanan);
        var monthlyLabels = monthlyData.map(item => item.bulan);
        var monthlySeries = monthlyData.map(item => item.total);
        
        // Grafik bulanan
        var monthlyChart = new ApexCharts(document.querySelector("#monthlyRevenueChart"), {
            series: [{
                name: 'Penerimaan',
                data: monthlySeries
            }],
            chart: {
                height: 300,
                type: 'bar',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '60%',
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: monthlyLabels,
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            },
            colors: ['#696cff']
        });
        monthlyChart.render();
        
        // Data untuk grafik distribusi
        var categoryData = @json($dataKategori);
        var categoryLabels = categoryData.map(item => item.nama);
        var categorySeries = categoryData.map(item => item.total);
        
        // Grafik distribusi
        var distributionChart = new ApexCharts(document.querySelector("#revenueDistributionChart"), {
            series: categorySeries,
            chart: {
                height: 300,
                type: 'donut',
            },
            labels: categoryLabels,
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            colors: ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00', '#8592a3']
        });
        distributionChart.render();
        
        // Memperbarui grafik saat data berubah
        Livewire.on('dashboardUpdated', () => {
            monthlyChart.updateSeries([{
                data: @json($dataBulanan).map(item => item.total)
            }]);
            
            distributionChart.updateSeries(
                @json($dataKategori).map(item => item.total)
            );
        });
    });
</script>
@endpush
