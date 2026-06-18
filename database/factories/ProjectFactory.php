<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Modules\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement([
                'School Construction',
                'Dam Construction',
                'Residential Building Construction',
            ]),
            'description' => fake()->optional()->sentence(),
            'status' => ProjectStatus::Planned,
            'starts_at' => fake()->optional()->dateTimeBetween('-1 month', '+1 month'),
            'ends_at' => fake()->optional()->dateTimeBetween('+2 months', '+18 months'),
        ];
    }
}
