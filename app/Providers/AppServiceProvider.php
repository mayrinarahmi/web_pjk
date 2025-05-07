<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Livewire\TahunAnggaran\Create; // Import class Create
use App\Http\Livewire\TahunAnggaran\Edit; // Import class Create


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       
Livewire::component('tahun-anggaran.create', Create::class);
Livewire::component('tahun-anggaran.edit', Create::class);
    }
}
