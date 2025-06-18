@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="card bg-primary text-white mb-4">
        <div class="card-body">
            <h4 class="card-title text-white mb-0">Analisis Trend Penerimaan Daerah</h4>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                {{-- Category Search --}}
                <div class="col-md-8">
                    <label class="form-label">KATEGORI PENERIMAAN</label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               placeholder="Ketik untuk mencari: reklame, hotel, restoran..."
                               wire:model.live="searchTerm">
                        @if($selectedCategoryId)
                            <button class="btn btn-outline-secondary" type="button" wire:click="resetToOverview">
                                <i class="bx bx-x"></i> Reset
                            </button>
                        @endif
                    </div>
                    
                    <small class="text-muted d-block mt-1">
                        Menampilkan: <strong>{{ $selectedCategoryName }}</strong>
                    </small>
                    
                    @if(count($searchResults) > 0)
                    <div class="list-group mt-2 position-absolute w-50" style="z-index: 1000;">
                        @foreach($searchResults as $result)
                            <button type="button" 
                                    class="list-group-item list-group-item-action"
                                    wire:click="selectCategory({{ $result->id }}, '{{ $result->nama }}')">
                                <small class="text-muted">{{ $result->kode }}</small>
                                <br>
                                <strong>{{ $result->nama }}</strong>
                            </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Year Range --}}
                <div class="col-md-4">
                    <label class="form-label">RENTANG TAHUN</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" 
                                class="btn {{ $yearRange == 2 ? 'btn-primary' : 'btn-outline-primary' }}"
                                wire:click="updateYearRange(2)">
                            2 Tahun
                        </button>
                        <button type="button" 
                                class="btn {{ $yearRange == 3 ? 'btn-primary' : 'btn-outline-primary' }}"
                                wire:click="updateYearRange(3)">
                            3 Tahun
                        </button>
                        <button type="button" 
                                class="btn {{ $yearRange == 5 ? 'btn-primary' : 'btn-outline-primary' }}"
                                wire:click="updateYearRange(5)">
                            5 Tahun
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                Trend Penerimaan {{ $selectedCategoryName }} 
                ({{ $yearRange }} Tahun Terakhir)
            </h5>
        </div>
        <div class="card-body">
            <div id="trendChart" style="height: 450px;"></div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>
<script>
let chart = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initial chart data
    const initialData = @json($chartData);
    console.log('Initial data:', initialData);
    
    // Render chart function
    function renderChart(data) {
        console.log('Rendering chart with data:', data);
        
        if (chart) {
            chart.destroy();
        }
        
        const options = {
            series: data.series || [],
            chart: {
                type: 'line',
                height: 450,
                toolbar: {
                    show: true
                }
            },
            colors: ['#696cff', '#71dd37', '#ff3e1d'],
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 5
            },
            xaxis: {
                categories: data.categories || [],
                title: {
                    text: 'Tahun'
                }
            },
            yaxis: {
                title: {
                    text: 'Jumlah Penerimaan'
                },
                labels: {
                    formatter: function(val) {
                        if (val >= 1000000000) {
                            return 'Rp ' + (val / 1000000000).toFixed(1) + ' M';
                        } else if (val >= 1000000) {
                            return 'Rp ' + (val / 1000000).toFixed(1) + ' Jt';
                        }
                        return 'Rp ' + val;
                    }
                }
            },
            legend: {
                position: 'top'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            }
        };
        
        chart = new ApexCharts(document.querySelector("#trendChart"), options);
        chart.render();
    }
    
    // Initial render
    if (initialData && initialData.series) {
        renderChart(initialData);
    }
    
    // Listen for Livewire events
    window.addEventListener('chartDataUpdated', event => {
        console.log('Chart update event received:', event.detail);
        if (event.detail && event.detail.chartData) {
            renderChart(event.detail.chartData);
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    .card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(161, 172, 184, 0.4);
    }
    
    .list-group {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        max-height: 400px;
        overflow-y: auto;
    }
    
    .list-group-item {
        cursor: pointer;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush