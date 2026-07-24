<?php

namespace App\Models;

use App\Models\Core\Auth\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'permissible_type',
        'permissible_id',
        'access_level',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permissible()
    {
        return $this->morphTo();
    }
}
