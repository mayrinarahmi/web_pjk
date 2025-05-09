<div>
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">Dashboard Penerimaan Pajak Daerah</h5>
                        <div class="d-flex align-items-center">
                            <label for="tahunAnggaranId" class="me-2">Tahun Anggaran:</label>
                            <select wire:model="tahunAnggaranId" wire:change="updatedTahunAnggaranId" id="tahunAnggaranId" class="form-select form-select-sm" style="width: 120px;">
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
                    <!-- Tambahan: Pagu Anggaran -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Pagu Anggaran:</small>
                        <small class="text-muted">Rp {{ number_format($paguAnggaran, 0, ',', '.') }}</small>
                    </div>
                    <!-- Tambahan: Kurang dari Target -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Kurang dari Target:</small>
                        <small class="text-muted">Rp {{ number_format(max(0, $targetPendapatan - $totalPendapatan), 0, ',', '.') }}</small>
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
                    <!-- Tambahan: Pagu Anggaran -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Pagu Anggaran:</small>
                        <small class="text-muted">Rp {{ number_format($paguPAD, 0, ',', '.') }}</small>
                    </div>
                    <!-- Tambahan: Kurang dari Target -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Kurang dari Target:</small>
                        <small class="text-muted">Rp {{ number_format(max(0, $targetPAD - $totalPAD), 0, ',', '.') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambahan Ringkasan - Pendapatan Transfer dan Lain-lain -->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Pendapatan Transfer</h5>
                            <small class="text-muted">Realisasi vs Target</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded-pill p-2">
                                <i class="bx bx-transfer bx-sm"></i>
                            </span>
                        </div>
                    </div>
                    <h3 class="card-title mb-2 mt-4">Rp {{ number_format($totalPendapatanTransfer, 0, ',', '.') }}</h3>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ min($persentasePendapatanTransfer, 100) }}%" aria-valuenow="{{ $persentasePendapatanTransfer }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">{{ $persentasePendapatanTransfer }}% dari target</small>
                        <small class="text-muted">Target: Rp {{ number_format($targetPendapatanTransfer, 0, ',', '.') }}</small>
                    </div>
                    <!-- Tambahan: Pagu Anggaran -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Pagu Anggaran:</small>
                        <small class="text-muted">Rp {{ number_format($paguTransfer, 0, ',', '.') }}</small>
                    </div>
                    <!-- Tambahan: Kurang dari Target -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Kurang dari Target:</small>
                        <small class="text-muted">Rp {{ number_format(max(0, $targetPendapatanTransfer - $totalPendapatanTransfer), 0, ',', '.') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Lain-lain Pendapatan Daerah Yang Sah</h5>
                            <small class="text-muted">Realisasi vs Target</small>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded-pill p-2">
                                <i class="bx bx-dollar-circle bx-sm"></i>
                            </span>
                        </div>
                    </div>
                    <h3 class="card-title mb-2 mt-4">Rp {{ number_format($totalLainLain, 0, ',', '.') }}</h3>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min($persentaseLainLain, 100) }}%" aria-valuenow="{{ $persentaseLainLain }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">{{ $persentaseLainLain }}% dari target</small>
                        <small class="text-muted">Target: Rp {{ number_format($targetLainLain, 0, ',', '.') }}</small>
                    </div>
                    <!-- Tambahan: Pagu Anggaran -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Pagu Anggaran:</small>
                        <small class="text-muted">Rp {{ number_format($paguLainLain, 0, ',', '.') }}</small>
                    </div>
                    <!-- Tambahan: Kurang dari Target -->
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Kurang dari Target:</small>
                        <small class="text-muted">Rp {{ number_format(max(0, $targetLainLain - $totalLainLain), 0, ',', '.') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tren Penerimaan Bulanan</h5>
                </div>
                <div class="card-body">
                    <!-- Menggunakan Alpine.js untuk chart bulanan dengan multiple series -->
                    <div id="monthlyRevenueChart" 
                         style="height: 300px;"
                         x-data="{}"
                         x-init="
                            $nextTick(() => {
                                if (typeof ApexCharts === 'undefined') {
                                    console.error('ApexCharts tidak tersedia');
                                    return;
                                }
                                
                                const monthlyData = {{ json_encode($dataBulanan) }};
                                const monthLabels = monthlyData.map(item => item.bulan);
                                
                                const chart = new ApexCharts($el, {
                                    series: [
                                        {
                                            name: 'PENDAPATAN ASLI DAERAH (PAD)',
                                            data: monthlyData.map(item => item.pad || 0)
                                        },
                                        {
                                            name: 'PENDAPATAN TRANSFER',
                                            data: monthlyData.map(item => item.transfer || 0)
                                        },
                                        {
                                            name: 'LAIN-LAIN PENDAPATAN DAERAH YANG SAH',
                                            data: monthlyData.map(item => item.lainlain || 0)
                                        }
                                    ],
                                    chart: {
                                        height: 300,
                                        type: 'bar',
                                        stacked: false,
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
                                        categories: monthLabels,
                                    },
                                    yaxis: {
                                        labels: {
                                            formatter: function(value) {
                                                return 'Rp ' + value.toLocaleString('id-ID');
                                            }
                                        }
                                    },
                                    tooltip: {
                                        y: {
                                            formatter: function (val) {
                                                return 'Rp ' + val.toLocaleString('id-ID')
                                            }
                                        }
                                    },
                                    // Gunakan warna yang sama dengan chart distribusi
                                    colors: ['#696cff', '#03c3ec', '#71dd37'],
                                    legend: {
                                        position: 'top'
                                    }
                                });
                                chart.render();
                               
                               // Update chart saat event updateCharts dipicu
                               window.addEventListener('updateCharts', function(event) {
                                   if (event.detail && event.detail.dataBulanan) {
                                       const newData = event.detail.dataBulanan;
                                       chart.updateSeries([
                                           {
                                               name: 'PENDAPATAN ASLI DAERAH (PAD)',
                                               data: newData.map(item => item.pad || 0)
                                           },
                                           {
                                               name: 'PENDAPATAN TRANSFER',
                                               data: newData.map(item => item.transfer || 0)
                                           },
                                           {
                                               name: 'LAIN-LAIN PENDAPATAN DAERAH YANG SAH',
                                               data: newData.map(item => item.lainlain || 0)
                                           }
                                       ]);
                                   }
                               });
                               
                               document.addEventListener('livewire:navigated', function() {
                                   chart.destroy();
                               });
                           })
                        "></div>
               </div>
           </div>
       </div>
   </div>

   <!-- Tabel Kategori - PERBAIKAN -->
   <div class="row">
       <div class="col-12 mb-4">
           <div class="card">
               <div class="card-header d-flex justify-content-between align-items-center">
                   <h5 class="card-title mb-0">Realisasi per Kategori</h5>
                   <small class="text-muted">Persentase Target: {{ $persentaseTarget }}%</small>
               </div>
               <div class="card-body">
                   <div class="table-responsive">
                       <table class="table table-hover">
                           <thead>
                               <tr>
                                   <th>Kategori</th>
                                   <th class="text-end">Pagu Anggaran</th>
                                   <th class="text-end">Target</th>
                                   <th class="text-end">Realisasi</th>
                                   <th class="text-end">Kurang dr Target</th>
                                   <th class="text-end">Persentase</th>
                                   <th>Progress</th>
                               </tr>
                           </thead>
                           <tbody>
                               @foreach($dataKategori as $kategori)
                               <tr>
                                   <td>{{ $kategori['nama'] }}</td>
                                   <td class="text-end">Rp {{ number_format($kategori['pagu'], 0, ',', '.') }}</td>
                                   <td class="text-end">Rp {{ number_format($kategori['target'], 0, ',', '.') }}</td>
                                   <td class="text-end">Rp {{ number_format($kategori['total'], 0, ',', '.') }}</td>
                                   <td class="text-end">Rp {{ number_format(max(0, $kategori['kurangDariTarget']), 0, ',', '.') }}</td>
                                   <td class="text-end">{{ $kategori['persentase'] }}%</td>
                                   <td>
                                       <div class="progress" style="height: 6px;">
                                           <div class="progress-bar 
                                               @if(strpos($kategori['nama'], 'ASLI') !== false) bg-primary 
                                               @elseif(strpos($kategori['nama'], 'TRANSFER') !== false) bg-info 
                                               @else bg-warning @endif" 
                                               role="progressbar" 
                                               style="width: {{ min($kategori['persentase'], 100) }}%" 
                                               aria-valuenow="{{ $kategori['persentase'] }}" 
                                               aria-valuemin="0" 
                                               aria-valuemax="100">
                                           </div>
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

<!-- Pastikan hanya satu instance ApexCharts yang dimuat -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.40.0/dist/apexcharts.min.js"></script>

<!-- Tambahan script untuk Alpine.js jika belum dimuat dari CDN atau Vite -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js" defer></script>

<!-- Script untuk event handler Dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
   // Debug library ApexCharts
   if (typeof ApexCharts === 'undefined') {
       console.error('ApexCharts library tidak tersedia!');
   } else {
       console.log('ApexCharts library tersedia, versi:', ApexCharts.version);
   }
   
   // Debug Alpine.js
   if (typeof Alpine === 'undefined') {
       console.error('Alpine.js library tidak tersedia!');
   } else {
       document.addEventListener('alpine:init', () => {
           console.log('Alpine.js siap digunakan');
       });
   }
   
   // Tambahkan event listener untuk Livewire
   if (typeof Livewire !== 'undefined') {
       Livewire.on('dashboardUpdated', (data) => {
           console.log('Event dashboardUpdated diterima:', data);
           
           // Trigger event untuk update chart Alpine
           window.dispatchEvent(new CustomEvent('updateCharts', {
               detail: {
                   dataBulanan: data.dataBulanan,
                   dataKategori: data.dataKategori
               }
           }));
       });
   }
});
</script>