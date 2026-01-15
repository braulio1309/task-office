<?php

namespace Tests\Feature\Backend;

use App\Models\App\SamplePage\KanbanView\Stage;
use App\Models\App\SamplePage\KanbanView\Task;
use App\Models\Core\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class CalendarTaskTest.
 */
class CalendarTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
    }

    /** @test */
    public function it_can_list_tasks_for_calendar_view()
    {
        // Create a stage first
        $stage = Stage::factory()->create(['name' => 'Test Stage']);
        
        // Create a task with the stage
        $task = Task::factory()->create([
            'title' => 'Test Task',
            'stage_id' => $stage->id,
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'pending',
        ]);

        $response = $this->getJson('/calendars');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        
        // Verify the task is formatted for calendar
        $calendarData = $response->json()[0];
        $this->assertEquals($task->id, $calendarData['id']);
        $this->assertStringContainsString($task->title, $calendarData['title']);
        $this->assertStringContainsString($stage->name, $calendarData['title']);
        $this->assertArrayHasKey('start', $calendarData);
        $this->assertArrayHasKey('end', $calendarData);
        $this->assertArrayHasKey('extendedProps', $calendarData);
    }

    /** @test */
    public function it_can_create_a_task_via_calendar_endpoint()
    {
        $stage = Stage::factory()->create(['name' => 'New Stage']);
        
        $taskData = [
            'title' => 'New Calendar Task',
            'stage_id' => $stage->id,
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'pending',
            'owner_name' => 'Test Owner',
        ];

        $response = $this->postJson('/calendars', $taskData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Calendar Task',
            'stage_id' => $stage->id,
        ]);
    }

    /** @test */
    public function it_can_update_a_task_via_calendar_endpoint()
    {
        $stage = Stage::factory()->create();
        $task = Task::factory()->create([
            'title' => 'Original Title',
            'stage_id' => $stage->id,
        ]);

        $newStage = Stage::factory()->create(['name' => 'Updated Stage']);
        $updateData = [
            'title' => 'Updated Title',
            'stage_id' => $newStage->id,
            'status' => 'completed',
        ];

        $response = $this->putJson("/calendars/{$task->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'stage_id' => $newStage->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_can_delete_a_task_via_calendar_endpoint()
    {
        $stage = Stage::factory()->create();
        $task = Task::factory()->create([
            'stage_id' => $stage->id,
        ]);

        $response = $this->deleteJson("/calendars/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    /** @test */
    public function it_includes_stage_information_in_calendar_data()
    {
        $stage = Stage::factory()->create(['name' => 'Development']);
        $task = Task::factory()->create([
            'title' => 'Build Feature',
            'stage_id' => $stage->id,
        ]);

        $response = $this->getJson('/calendars');

        $response->assertStatus(200);
        
        $calendarData = $response->json()[0];
        $this->assertEquals($stage->id, $calendarData['extendedProps']['stage_id']);
        $this->assertEquals($stage->name, $calendarData['extendedProps']['stage_name']);
    }

    /** @test */
    public function it_includes_user_relationships_in_calendar_data()
    {
        $stage = Stage::factory()->create();
        $assignee = User::factory()->create();
        $supervisor = User::factory()->create();
        
        $task = Task::factory()->create([
            'stage_id' => $stage->id,
            'assigned_to' => $assignee->id,
            'supervisor' => $supervisor->id,
        ]);

        $response = $this->getJson('/calendars');

        $response->assertStatus(200);
        
        $calendarData = $response->json()[0];
        $this->assertEquals($assignee->id, $calendarData['extendedProps']['assigned_to']);
        $this->assertEquals($supervisor->id, $calendarData['extendedProps']['supervisor_id']);
    }

    /** @test */
    public function task_creation_requires_stage_id()
    {
        $taskData = [
            'title' => 'Task without stage',
            'end_date' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson('/calendars', $taskData);

        // Depending on validation, this should fail
        // The exact status code may vary based on your validation setup
        $response->assertStatus(422);
    }
}
