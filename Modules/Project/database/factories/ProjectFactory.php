<?php

namespace Modules\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Project\Models\Project;
use Modules\Workspace\Models\Workspace;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
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
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => \App\Enums\ProjectStatus::Planned->value,
            'starts_at' => $this->faker->optional()->dateTimeBetween('+1 week', '+1 month'),
            'ends_at' => $this->faker->optional()->dateTimeBetween('+2 weeks', '+3 months'),
        ];
    }
}
