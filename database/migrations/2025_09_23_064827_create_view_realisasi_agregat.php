<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateViewRealisasiAgregat extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_realisasi_agregat AS
            SELECT 
                kr.id,
                kr.kode,
                kr.nama,
                kr.level,
                kr.parent_id,
                COALESCE(
                    CASE 
                        WHEN kr.level = 6 THEN p.total
                        ELSE NULL
                    END, 0
                ) as realisasi_direct,
                ta.tahun_anggaran_id,
                ta.jumlah as target_anggaran
            FROM kode_rekening kr
            LEFT JOIN (
                SELECT 
                    kode_rekening_id,
                    tahun,
                    SUM(jumlah) as total
                FROM penerimaan
                GROUP BY kode_rekening_id, tahun
            ) p ON kr.id = p.kode_rekening_id
            LEFT JOIN target_anggaran ta ON kr.id = ta.kode_rekening_id
            WHERE kr.is_active = 1
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS v_realisasi_agregat");
    }
}