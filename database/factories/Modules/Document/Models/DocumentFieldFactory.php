<?php

namespace Database\Factories\Modules\Document\Models;

use App\Modules\Document\Models\DocumentField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Document\Models\DocumentField>
 */
class DocumentFieldFactory extends Factory
{
    protected $model = DocumentField::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
