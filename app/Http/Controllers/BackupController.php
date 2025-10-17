<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use ZipArchive;
use Carbon\Carbon;
use App\Models\BackupLog;

class BackupController extends Controller
{
    /**
     * Tables yang di-exclude dari backup
     */
    private $excludeTables = [
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'failed_jobs',
        'password_reset_tokens',
    ];
    
    /**
     * Maximum backup file size (100MB)
     */
    private $maxFileSize = 104857600; // 100MB in bytes
    
    /**
     * Constructor - Add middleware
     */
    public function __construct()
    {
        // IMPORTANT: Hanya Super Admin yang bisa akses
        $this->middleware(['auth', 'role:Super Admin']);
    }
    
    /**
     * Display list of backups
     */
    public function index()
    {
        try {
            // Get backups dari storage
            $backupFiles = collect(Storage::disk('local')->files('backups'))
                ->filter(function ($file) {
                    return pathinfo($file, PATHINFO_EXTENSION) == 'zip';
                })
                ->map(function ($file) {
                    $fileName = basename($file);
                    
                    // Get log info jika ada
                    $log = BackupLog::where('file_name', $fileName)
                                    ->where('action', 'create')
                                    ->latest()
                                    ->first();
                    
                    return [
                        'file_name' => $fileName,
                        'file_size' => Storage::disk('local')->size($file),
                        'created_at' => Storage::disk('local')->lastModified($file),
                        'path' => $file,
                        'created_by' => $log ? $log->creator : null,
                    ];
                })
                ->sortByDesc('created_at')
                ->values();
            
            // Get recent logs untuk statistics
            $recentLogs = BackupLog::with('creator')
                                   ->orderBy('created_at', 'desc')
                                   ->limit(5)
                                   ->get();
            
            // Get statistics
            $stats = [
                'total_backups' => BackupLog::where('action', 'create')->where('status', 'success')->count(),
                'total_restores' => BackupLog::where('action', 'restore')->where('status', 'success')->count(),
                'total_size' => $backupFiles->sum('file_size'),
                'last_backup' => BackupLog::where('action', 'create')->where('status', 'success')->latest()->first(),
            ];
                
            return view('backup.index', compact('backupFiles', 'recentLogs', 'stats'));
            
        } catch (\Exception $e) {
            Log::error('Backup index error: ' . $e->getMessage());
            return view('backup.index', [
                'backupFiles' => collect([]),
                'recentLogs' => collect([]),
                'stats' => [
                    'total_backups' => 0,
                    'total_restores' => 0,
                    'total_size' => 0,
                    'last_backup' => null,
                ],
            ])->with('error', 'Terjadi kesalahan saat memuat data backup.');
        }
    }
    
    /**
     * Create new backup
     */
 public function create()
{
    try {
        // Check disk space
        if (!$this->checkDiskSpace()) {
            return redirect()->route('backup.index')
                ->with('error', 'Ruang disk tidak cukup untuk membuat backup.');
        }
        
        // Pastikan direktori backup ada
        if (!Storage::disk('local')->exists('backups')) {
            Storage::disk('local')->makeDirectory('backups');
        }
        
        // Nama file backup dengan timestamp
        $timestamp = Carbon::now()->format('Y-m-d-His');
        $fileName = 'backup-' . $timestamp . '.sql';
        $filePath = storage_path('app/backups/' . $fileName);
        
        // Database credentials
        $dbName = config('database.connections.mysql.database');
        
        // BUILD SQL DUMP USING LARAVEL
        $dumpContent = "-- SILAPAT Database Backup\n";
        $dumpContent .= "-- Generated: " . Carbon::now()->toDateTimeString() . "\n";
        $dumpContent .= "-- Database: {$dbName}\n\n";
        $dumpContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // FIXED: Get only BASE TABLES (skip VIEWS)
        $tablesResult = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        
        foreach ($tablesResult as $table) {
            $tableData = (array)$table;
            $tableName = array_values($tableData)[0];
            
            // Skip excluded tables
            if (in_array($tableName, $this->excludeTables)) {
                continue;
            }
            
            $dumpContent .= "\n-- --------------------------------------------------------\n";
            $dumpContent .= "-- Table: {$tableName}\n";
            $dumpContent .= "-- --------------------------------------------------------\n\n";
            
            // DROP TABLE
            $dumpContent .= "DROP TABLE IF EXISTS `{$tableName}`;\n\n";
            
            // CREATE TABLE - FIXED ACCESS
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createRow = (array)$createTable[0];
            $createStatement = isset($createRow['Create Table']) ? $createRow['Create Table'] : array_values($createRow)[1];
            $dumpContent .= $createStatement . ";\n\n";
            
            // INSERT DATA
            $rows = DB::table($tableName)->get();
            
            if ($rows->count() > 0) {
                foreach ($rows as $row) {
                    $values = array_map(function($val) {
                        if (is_null($val)) {
                            return 'NULL';
                        }
                        return "'" . addslashes($val) . "'";
                    }, (array)$row);
                    
                    $dumpContent .= "INSERT INTO `{$tableName}` VALUES (" . implode(',', $values) . ");\n";
                }
                $dumpContent .= "\n";
            }
        }
        
        $dumpContent .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        
        // Save SQL file
        file_put_contents($filePath, $dumpContent);
        
        // Verify file exists and not empty
        if (!file_exists($filePath) || filesize($filePath) == 0) {
            throw new \Exception('Backup file is empty or not created.');
        }
        
        $fileSize = filesize($filePath);
        
        // Check file size limit
        if ($fileSize > $this->maxFileSize) {
            unlink($filePath);
            throw new \Exception('Backup file too large (max 100MB).');
        }
        
        // Create ZIP
        $zipFileName = 'backup-' . $timestamp . '.zip';
        $zipFilePath = storage_path('app/backups/' . $zipFileName);
        
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($filePath, 'database.sql');
            
            // Add metadata
            $metadata = [
                'backup_date' => Carbon::now()->toDateTimeString(),
                'database_name' => $dbName,
                'created_by' => auth()->user()->name,
                'excluded_tables' => $this->excludeTables,
                'method' => 'Laravel DB Query (Tables Only)',
            ];
            $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            
            $zip->close();
        }
        
        // Delete SQL file, keep only ZIP
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $finalFileSize = filesize($zipFilePath);
        
        // Log to database
        BackupLog::create([
            'file_name' => $zipFileName,
            'file_path' => 'backups/' . $zipFileName,
            'file_size' => $finalFileSize,
            'action' => 'create',
            'status' => 'success',
            'notes' => 'Backup created successfully (Tables only, Views excluded). Excluded tables: ' . implode(', ', $this->excludeTables),
            'created_by' => auth()->id(),
        ]);
        
        Log::info('Backup created successfully', [
            'file_name' => $zipFileName,
            'file_size' => $finalFileSize,
            'user' => auth()->user()->name,
            'method' => 'Laravel Native',
        ]);
        
        return redirect()->route('backup.index')
            ->with('message', 'Backup berhasil dibuat! File: ' . $zipFileName . ' (' . $this->formatBytes($finalFileSize) . ')');
            
    } catch (\Exception $e) {
        // Cleanup on error
        if (isset($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        if (isset($zipFilePath) && file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
        
        // Log error
        BackupLog::create([
            'file_name' => $fileName ?? 'unknown',
            'file_path' => '',
            'file_size' => 0,
            'action' => 'create',
            'status' => 'failed',
            'notes' => 'Backup failed: ' . $e->getMessage(),
            'created_by' => auth()->id(),
        ]);
        
        Log::error('Backup creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user' => auth()->user()->name,
        ]);
        
        return redirect()->route('backup.index')
            ->with('error', 'Backup gagal dibuat: ' . $e->getMessage());
    }
}

    
    /**
     * Download backup file
     */
    public function download($fileName)
    {
        try {
            $path = storage_path('app/backups/' . $fileName);
            
            if (!file_exists($path)) {
                return redirect()->route('backup.index')
                    ->with('error', 'File backup tidak ditemukan.');
            }
            
            // Log download
            BackupLog::create([
                'file_name' => $fileName,
                'file_path' => 'backups/' . $fileName,
                'file_size' => filesize($path),
                'action' => 'download',
                'status' => 'success',
                'notes' => 'Backup downloaded by ' . auth()->user()->name,
                'created_by' => auth()->id(),
            ]);
            
            return response()->download($path);
            
        } catch (\Exception $e) {
            Log::error('Backup download failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Download gagal: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete backup file
     */
    public function delete(Request $request, $fileName)
    {
        try {
            $path = 'backups/' . $fileName;
            
            if (!Storage::disk('local')->exists($path)) {
                return redirect()->route('backup.index')
                    ->with('error', 'File backup tidak ditemukan.');
            }
            
            // Get file size before delete
            $fileSize = Storage::disk('local')->size($path);
            
            // Delete file
            Storage::disk('local')->delete($path);
            
            // Log deletion
            BackupLog::create([
                'file_name' => $fileName,
                'file_path' => $path,
                'file_size' => $fileSize,
                'action' => 'delete',
                'status' => 'success',
                'notes' => 'Backup deleted by ' . auth()->user()->name,
                'created_by' => auth()->id(),
            ]);
            
            Log::info('Backup deleted', [
                'file' => $fileName,
                'user' => auth()->user()->name,
            ]);
            
            return redirect()->route('backup.index')
                ->with('message', 'Backup berhasil dihapus.');
                
        } catch (\Exception $e) {
            Log::error('Backup deletion failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Penghapusan backup gagal: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore database from backup
     */
    public function restore(Request $request, $fileName)
    {
        try {
            $path = storage_path('app/backups/' . $fileName);
            
            if (!file_exists($path)) {
                return redirect()->route('backup.index')
                    ->with('error', 'File backup tidak ditemukan.');
            }
            
            // IMPORTANT: Create backup before restore
            $this->createPreRestoreBackup();
            
            $tempDir = storage_path('app/temp-restore-' . time());
            
            // Create temp directory
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $zip = new ZipArchive();
            
            if ($zip->open($path) === TRUE) {
                // Extract SQL file
                $zip->extractTo($tempDir);
                $zip->close();
                
                $dumpPath = $tempDir . '/database.sql';
                
                if (!file_exists($dumpPath)) {
                    $this->cleanupTempDir($tempDir);
                    throw new \Exception('File database.sql tidak ditemukan dalam backup.');
                }
                
                // Database credentials
                $dbName = config('database.connections.mysql.database');
                $dbUser = config('database.connections.mysql.username');
                $dbPassword = config('database.connections.mysql.password');
                $dbHost = config('database.connections.mysql.host', 'localhost');
                $dbPort = config('database.connections.mysql.port', '3306');
                
                // Restore database
                $command = sprintf(
                    'MYSQL_PWD=%s mysql --user=%s --host=%s --port=%s %s < %s',
                    escapeshellarg($dbPassword),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbName),
                    escapeshellarg($dumpPath)
                );
                
                exec($command . ' 2>&1', $output, $returnVar);
                
                if ($returnVar !== 0) {
                    $this->cleanupTempDir($tempDir);
                    throw new \Exception('MySQL restore failed: ' . implode("\n", $output));
                }
                
                // Cleanup temp directory
                $this->cleanupTempDir($tempDir);
                
                // Clear application cache
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('view:clear');
                
                // Log restore
                BackupLog::create([
                    'file_name' => $fileName,
                    'file_path' => 'backups/' . $fileName,
                    'file_size' => filesize($path),
                    'action' => 'restore',
                    'status' => 'success',
                    'notes' => 'Database restored successfully by ' . auth()->user()->name,
                    'created_by' => auth()->id(),
                ]);
                
                Log::info('Database restored', [
                    'file' => $fileName,
                    'user' => auth()->user()->name,
                ]);
                
                return redirect()->route('backup.index')
                    ->with('message', 'Restore berhasil dilakukan! Database telah dikembalikan ke backup: ' . $fileName);
                
            } else {
                throw new \Exception('Gagal membuka file backup.');
            }
            
        } catch (\Exception $e) {
            // Cleanup on error
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->cleanupTempDir($tempDir);
            }
            
            // Log failed restore
            BackupLog::create([
                'file_name' => $fileName,
                'file_path' => 'backups/' . $fileName,
                'file_size' => file_exists($path) ? filesize($path) : 0,
                'action' => 'restore',
                'status' => 'failed',
                'notes' => 'Restore failed: ' . $e->getMessage(),
                'created_by' => auth()->id(),
            ]);
            
            Log::error('Database restore failed', [
                'file' => $fileName,
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);
            
            return redirect()->route('backup.index')
                ->with('error', 'Restore gagal: ' . $e->getMessage());
        }
    }
    
    /**
     * Create backup before restore (safety measure)
     */
    private function createPreRestoreBackup()
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d-His');
            $fileName = 'pre-restore-backup-' . $timestamp . '.zip';
            
            // Simplified backup creation
            $this->create();
            
            Log::info('Pre-restore backup created', [
                'file' => $fileName,
                'user' => auth()->user()->name,
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Pre-restore backup failed: ' . $e->getMessage());
            // Don't throw error, just log warning
        }
    }
    
    /**
     * Cleanup temporary directory
     */
    private function cleanupTempDir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->cleanupTempDir($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Check available disk space
     */
    private function checkDiskSpace()
    {
        $freeSpace = disk_free_space(storage_path('app/backups'));
        $requiredSpace = 200 * 1024 * 1024; // 200MB minimum
        
        return $freeSpace > $requiredSpace;
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}