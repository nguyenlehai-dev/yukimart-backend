<?php

namespace Database\Factories\Modules\Document\Models;

use App\Modules\Document\Models\DocumentSigner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Document\Models\DocumentSigner>
 */
class DocumentSignerFactory extends Factory
{
    protected $model = DocumentSigner::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
