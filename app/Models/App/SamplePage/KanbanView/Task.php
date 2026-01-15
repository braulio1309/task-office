<?php

namespace App\Models\App\SamplePage\KanbanView;

use App\Models\App\AppModel;
use App\Models\App\Traits\TaskValidationRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Core\Auth\User;

class Task extends AppModel
{
    use HasFactory, TaskValidationRules;

    protected $fillable = ['title', 'owner_name', 'stage_id', 'end_date', 'supervisor', 'assigned_to', 'status'];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

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
        return $this->belongsTo(User::class, 'supervisor');
    }
}
