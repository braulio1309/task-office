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
class Folder extends Model
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
        'name',
        'parent_id',
        'created_by',
    ];

    
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Subcarpetas directas.
     */
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Todos los descendientes (subcarpetas de subcarpetas).
     * Útil para cargar toda la estructura de golpe (Eager Loading).
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    // ===========================
    // Relaciones de Contenido
    // ===========================

    /**
     * Documentos dentro de esta carpeta.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Usuario que creó la carpeta.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===========================
    // Relaciones de Permisos
    // ===========================

    /**
     * Permisos asignados específicamente a esta carpeta.
     */
    public function permissions()
    {
        return $this->morphMany(EntityPermission::class, 'permissible');
    }

    // ===========================
    // Helpers / Accessors
    // ===========================

    /**
     * Obtener la ruta completa (breadcrumbs) como array.
     * Ej: ['Inicio', 'Finanzas', '2024']
     */
    public function getPathAttribute()
    {
        $path = collect([$this]);
        $folder = $this->parent;

        while($folder) {
            $path->prepend($folder);
            $folder = $folder->parent;
        }

        return $path;
    }
}
