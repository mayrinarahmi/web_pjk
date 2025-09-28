<?php
namespace App\Http\Controllers;

use App\Services\LaporanRealisasiService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class LaporanRealisasiController extends Controller
{
    public function index(Request $request)
    {
        // Get available years from database
        $availableYears = DB::table('penerimaan')
            ->select(DB::raw('DISTINCT tahun'))
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');
        
        // Add next year if not exists
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        if (!$availableYears->contains($nextYear)) {
            $availableYears->prepend($nextYear);
        }
        
        // Default values
        $tahun = $request->tahun ?? $currentYear;
        $tanggalAwal = $request->tanggal_awal ?? null;
        $tanggalAkhir = $request->tanggal_akhir ?? null;
        
        $service = new LaporanRealisasiService();
        $data = $service->generateLaporan($tahun, $tanggalAwal, $tanggalAkhir);
        
        return view('laporan.realisasi', compact('data', 'tahun', 'tanggalAwal', 'tanggalAkhir', 'availableYears'));
    }
}