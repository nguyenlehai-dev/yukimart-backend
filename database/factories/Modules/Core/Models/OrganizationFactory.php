<?php

namespace Database\Factories\Modules\Core\Models;

use App\Modules\Core\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
