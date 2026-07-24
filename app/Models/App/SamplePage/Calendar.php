<?php

namespace App\Models\App\SamplePage;

use App\Models\App\Traits\CalendarValidationRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Core\Auth\User;
use App\Models\App\SamplePage\KanbanView\Stage;

class Calendar extends Model
{
    use HasFactory,CalendarValidationRules;
    protected $fillable = ['title','description','start','end','completed', 'end_date', 'supervisor', 'assigned_to', 'status'];

     public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

}
