<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'file_name',
        'file_path',
        'file_size',
        'action',
        'status',
        'notes',
        'created_by',
    ];
    
    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Relationship dengan User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Scope untuk filter by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
    
    /**
     * Scope untuk successful actions only
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }
    
    /**
     * Get latest backups
     */
    public static function getLatestBackups($limit = 10)
    {
        return self::where('action', 'create')
                   ->where('status', 'success')
                   ->orderBy('created_at', 'desc')
                   ->limit($limit)
                   ->get();
    }
}