<?php

namespace App\Models\App\SamplePage\KanbanView;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\App\SamplePage\Calendar;
class Stage extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function calendars()
    {
        return $this->hasMany(Calendar::class);
    }
}
