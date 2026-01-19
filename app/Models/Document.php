<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\Core\Auth\Traits\Attribute\UserAttribute;
use App\Models\Core\Auth\Traits\Boot\UserBootTrait;
use App\Models\Core\Auth\Traits\Method\HasRoles;
use App\Models\Core\Auth\Traits\Method\UserMethod;
use App\Models\Core\Auth\Traits\Method\UserStatus;
use App\Models\Core\Auth\Traits\Relationship\UserRelationship;
use App\Models\Core\Auth\Traits\Rules\UserRules;
use App\Models\Core\Auth\Traits\Scope\UserScope;
use Spatie\Activitylog\Traits\CausesActivity;
use Altek\Eventually\Eventually;
use App\Models\Core\Auth\User;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
     use UserAttribute,
        UserMethod,
        UserRelationship,
        UserScope,
        HasRoles,
        UserRules,
        UserBootTrait,
        Eventually,
        Notifiable,
        CausesActivity,
        UserStatus,
        HasFactory;

    protected $fillable = [
        'folder_id',
        'name',
        'file_path',
        'mime_type',
        'size',
        'created_by',
    ];

    protected $appends = ['readable_size', 'download_url', 'created_at_formatted', 'preview_url'];

    // ===========================
    // Relaciones
    // ===========================

    /**
     * Carpeta contenedora.
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Usuario que subió el archivo.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    

    
    public function getReadableSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function permissions()
    {
        return $this->morphMany(EntityPermission::class, 'permissible');
    }
    
    /**
     * Devuelve la URL pública o temporal para descargar.
     */
    public function getDownloadUrlAttribute()
    {
        // Ajusta 'public' o 's3' según tu disco
        return Storage::disk('public')->url($this->file_path); 
    }

    /**
     * Devuelve la URL para previsualizar el documento
     */
    public function getPreviewUrlAttribute()
    {
        return url('documents/view/' . $this->id);
    }

    /**
     * Devuelve la fecha de creación formateada
     */
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i') : '';
    }
}
