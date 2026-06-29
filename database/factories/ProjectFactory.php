<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Workspace\Models\Workspace;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the model's factory.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'slug' => $this->faker->slug,
            'status' => ProjectStatus::Planned,
            'workspace_id' => Workspace::factory(),
        ];
    }
}
