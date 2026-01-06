<?php

namespace App\Models\App\SamplePage;

use App\Models\App\Traits\CalendarValidationRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Core\Auth\User;

class Calendar extends Model
{
    use HasFactory,CalendarValidationRules;
    protected $fillable = ['title','description','start','end','completed', 'end_date', 'supervisor', 'assigned_to', 'status'];

     public function assignee()
    {
        // Primer argumento: El modelo relacionado (User)
        // Segundo argumento: La columna en esta tabla (events) que tiene el ID
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relación: El evento "pertenece" a un supervisor.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
