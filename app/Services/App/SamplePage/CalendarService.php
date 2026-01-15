<?php

namespace App\Services\App\SamplePage;

use App\Models\App\SamplePage\KanbanView\Task;
use App\Services\App\AppService;

class CalendarService extends AppService
{
    public function __construct(Task $task)
    {
        $this->model = $task;
    }

    /**
     * Get tasks formatted for calendar display
     *
     * @return \Illuminate\Support\Collection
     */
    public function get()
    {
        return $this->model
            ->with(['stage', 'assignee', 'supervisor'])
            ->get()
            ->map(function ($task) {
                return $this->formatTaskForCalendar($task);
            });
    }

    /**
     * Format a task for FullCalendar display
     *
     * @param Task $task
     * @return array
     */
    protected function formatTaskForCalendar($task)
    {
        // Use end_date as the display date, or created_at if no end_date
        $date = $task->end_date ?? $task->created_at;
        
        // Format stage information if available
        $stageName = $task->stage ? $task->stage->name : __('No Stage');
        
        // Build title with stage information
        $title = $task->title . ' (' . $stageName . ')';
        
        return [
            'id' => $task->id,
            'title' => $title,
            'start' => $date,
            'end' => $date,
            'backgroundColor' => $this->getColorByStage($task->stage),
            'borderColor' => $this->getColorByStage($task->stage),
            'extendedProps' => [
                'description' => $task->title,
                'stage_id' => $task->stage_id,
                'stage_name' => $stageName,
                'assigned_to' => $task->assigned_to,
                'supervisor_id' => $task->supervisor,
                'status' => $task->status ?? 'pending',
                'end_date' => $task->end_date,
                'owner_name' => $task->owner_name,
            ]
        ];
    }

    /**
     * Get color based on stage
     *
     * @param mixed $stage
     * @return string
     */
    protected function getColorByStage($stage)
    {
        if (!$stage) {
            return '#6c757d'; // Gray for no stage
        }

        // Generate a consistent color based on stage ID
        $colors = [
            '#007bff', // Blue
            '#28a745', // Green
            '#ffc107', // Yellow
            '#dc3545', // Red
            '#17a2b8', // Cyan
            '#6610f2', // Indigo
            '#e83e8c', // Pink
            '#fd7e14', // Orange
        ];

        return $colors[$stage->id % count($colors)];
    }
}
