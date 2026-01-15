<?php

namespace Database\Factories\App\SamplePage\KanbanView;

use App\Models\App\SamplePage\KanbanView\Task;
use App\Models\App\SamplePage\KanbanView\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'owner_name' => $this->faker->name,
            'stage_id' => Stage::factory(),
            'end_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'overdue']),
        ];
    }
}
