<?php

namespace Database\Factories\Modules\Document\Models;

use App\Modules\Document\Models\IssuingAgency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Document\Models\IssuingAgency>
 */
class IssuingAgencyFactory extends Factory
{
    protected $model = IssuingAgency::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
