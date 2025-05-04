<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use ZipArchive;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function index()
    {
        $backups = collect(Storage::disk('local')->files('backups'))
            ->filter(function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) == 'zip';
            })
            ->map(function ($file) {
                return [
                    'file_name' => basename($file),
                    'file_size' => Storage::disk('local')->size($file),
                    'created_at' => Storage::disk('local')->lastModified($file),
                    'path' => $file,
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
            
        return view('backup.index', compact('backups'));
    }
    
    public function create()
    {
        try {
            // Pastikan direktori backup ada
            if (!Storage::disk('local')->exists('backups')) {
                Storage::disk('local')->makeDirectory('backups');
            }
            
            // Nama file backup
            $fileName = 'backup-' . Carbon::now()->format('Y-m-d-H-i-s') . '.zip';
            $filePath = storage_path('app/backups/' . $fileName);
            
            // Dump database
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPassword = config('database.connections.mysql.password');
            
            $dumpPath = storage_path('app/backups/dump.sql');
            
            // Gunakan mysqldump untuk membuat backup database
            $command = "mysqldump --user={$dbUser} --password={$dbPassword} {$dbName} > {$dumpPath}";
            
            $process = Process::fromShellCommandline($command);
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            
            // Buat file zip
            $zip = new ZipArchive();
            
            if ($zip->open($filePath, ZipArchive::CREATE) === TRUE) {
                // Tambahkan file dump database ke zip
                $zip->addFile($dumpPath, 'database.sql');
                
                // Tambahkan file-file aplikasi (opsional)
                // $this->addDirectoryToZip($zip, base_path(), '');
                
                $zip->close();
            }
            
            // Hapus file dump sementara
            unlink($dumpPath);
            
            return redirect()->route('backup.index')->with('message', 'Backup berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->route('backup.index')->with('error', 'Backup gagal: ' . $e->getMessage());
        }
    }
    
    public function download($fileName)
    {
        $path = storage_path('app/backups/' . $fileName);
        
        if (file_exists($path)) {
            return response()->download($path);
        }
        
        return redirect()->route('backup.index')->with('error', 'File tidak ditemukan.');
    }
    
    public function delete($fileName)
    {
        $path = 'backups/' . $fileName;
        
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
            return redirect()->route('backup.index')->with('message', 'Backup berhasil dihapus.');
        }
        
        return redirect()->route('backup.index')->with('error', 'File tidak ditemukan.');
    }
    
    public function restore($fileName)
    {
        try {
            $path = storage_path('app/backups/' . $fileName);
            
            if (!file_exists($path)) {
                return redirect()->route('backup.index')->with('error', 'File backup tidak ditemukan.');
            }
            
            $zip = new ZipArchive();
            
            if ($zip->open($path) === TRUE) {
                // Ekstrak file SQL
                $zip->extractTo(storage_path('app/temp'));
                $zip->close();
                
                $dumpPath = storage_path('app/temp/database.sql');
                
                if (!file_exists($dumpPath)) {
                    return redirect()->route('backup.index')->with('error', 'File database tidak ditemukan dalam backup.');
                }
                
                // Restore database
                $dbName = config('database.connections.mysql.database');
                $dbUser = config('database.connections.mysql.username');
                $dbPassword = config('database.connections.mysql.password');
                
                $command = "mysql --user={$dbUser} --password={$dbPassword} {$dbName} < {$dumpPath}";
                
                $process = Process::fromShellCommandline($command);
                $process->run();
                
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
                
                // Hapus file temporary
                unlink($dumpPath);
                rmdir(storage_path('app/temp'));
                
                return redirect()->route('backup.index')->with('message', 'Restore berhasil dilakukan.');
            }
            
            return redirect()->route('backup.index')->with('error', 'Gagal membuka file backup.');
        } catch (\Exception $e) {
            return redirect()->route('backup.index')->with('error', 'Restore gagal: ' . $e->getMessage());
        }
    }
    
    private function addDirectoryToZip($zip, $path, $relativePath)
    {
        $files = scandir($path);
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && $file != 'vendor' && $file != 'node_modules' && $file != 'storage') {
                $filePath = $path . '/' . $file;
                $zipPath = $relativePath . '/' . $file;
                
                if (is_dir($filePath)) {
                    $zip->addEmptyDir($zipPath);
                    $this->addDirectoryToZip($zip, $filePath, $zipPath);
                } else {
                    $zip->addFile($filePath, $zipPath);
                }
            }
        }
    }
}

