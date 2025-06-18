<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\KodeRekening;
use App\Models\Penerimaan;

class DebugController extends Controller
{
    /**
     * Check database connection and views
     */
    public function checkDatabase()
    {
        try {
            // Check basic connection
            $connected = DB::connection()->getPdo() ? true : false;
            
            // Check if views exist
            $views = [
                'v_penerimaan_monthly' => $this->checkViewExists('v_penerimaan_monthly'),
                'v_monthly_growth_rate' => $this->checkViewExists('v_monthly_growth_rate'),
                'v_penerimaan_yearly' => $this->checkViewExists('v_penerimaan_yearly'),
                'v_kode_rekening_tree' => $this->checkViewExists('v_kode_rekening_tree'),
                'v_trend_summary' => $this->checkViewExists('v_trend_summary')
            ];
            
            // Get sample data counts
            $counts = [
                'kode_rekening' => KodeRekening::count(),
                'kode_rekening_active' => KodeRekening::where('is_active', true)->count(),
                'penerimaan' => Penerimaan::count(),
                'penerimaan_2025' => Penerimaan::where('tahun', 2025)->count(),
                'penerimaan_2024' => Penerimaan::where('tahun', 2024)->count(),
                'penerimaan_2023' => Penerimaan::where('tahun', 2023)->count()
            ];
            
            // Get sample kode rekening
            $sampleKode = KodeRekening::where('is_active', true)
                ->where('level', 2)
                ->limit(5)
                ->get(['id', 'kode', 'nama', 'level']);
            
            return response()->json([
                'success' => true,
                'database_connected' => $connected,
                'views' => $views,
                'data_counts' => $counts,
                'sample_kode_rekening' => $sampleKode
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Test raw SQL query
     */
    public function testSQL()
    {
        try {
            // Test basic aggregation query
            $query = "
                SELECT 
                    kr.id,
                    kr.kode,
                    kr.nama,
                    kr.level,
                    COUNT(p.id) as transaction_count,
                    SUM(p.jumlah) as total_amount,
                    MIN(p.tahun) as min_year,
                    MAX(p.tahun) as max_year
                FROM kode_rekening kr
                LEFT JOIN penerimaan p ON kr.id = p.kode_rekening_id
                WHERE kr.is_active = 1 
                AND kr.level = 2
                GROUP BY kr.id, kr.kode, kr.nama, kr.level
                LIMIT 10
            ";
            
            $results = DB::select($query);
            
            // Test yearly aggregation
            $yearlyQuery = "
                SELECT 
                    tahun,
                    COUNT(*) as transaction_count,
                    SUM(jumlah) as total_amount,
                    COUNT(DISTINCT kode_rekening_id) as unique_categories
                FROM penerimaan
                WHERE tahun >= 2023
                GROUP BY tahun
                ORDER BY tahun DESC
            ";
            
            $yearlyResults = DB::select($yearlyQuery);
            
            return response()->json([
                'success' => true,
                'basic_aggregation' => $results,
                'yearly_summary' => $yearlyResults,
                'query_executed' => [
                    'basic' => $query,
                    'yearly' => $yearlyQuery
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'sql_error' => $e instanceof \PDOException ? $e->errorInfo : null
            ], 500);
        }
    }
    
    /**
     * Check if a database view exists
     */
    private function checkViewExists($viewName)
    {
        try {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.views 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$viewName]);
            
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Test specific trend data
     */
    public function testTrendData()
    {
        try {
            $level = 2;
            $startYear = 2023;
            $endYear = 2025;
            
            // Direct query similar to what TrendAnalysisService does
            $query = DB::table('kode_rekening as kr')
                ->select(
                    'kr.id',
                    'kr.kode',
                    'kr.nama',
                    'p.tahun',
                    DB::raw('COALESCE(SUM(p.jumlah), 0) as total')
                )
                ->leftJoin('penerimaan as p', function($join) use ($startYear, $endYear) {
                    $join->on('kr.id', '=', 'p.kode_rekening_id')
                         ->whereBetween('p.tahun', [$startYear, $endYear]);
                })
                ->where('kr.is_active', true)
                ->where('kr.level', $level)
                ->groupBy('kr.id', 'kr.kode', 'kr.nama', 'p.tahun')
                ->orderBy('kr.kode')
                ->orderBy('p.tahun')
                ->limit(20)
                ->get();
            
            // Group by kode rekening
            $grouped = $query->groupBy('id');
            
            return response()->json([
                'success' => true,
                'raw_data' => $query,
                'grouped_count' => $grouped->count(),
                'sample_grouped' => $grouped->take(3),
                'parameters' => [
                    'level' => $level,
                    'start_year' => $startYear,
                    'end_year' => $endYear
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}