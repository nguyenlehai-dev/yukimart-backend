<?php

namespace Database\Factories;

use App\Modules\Core\Models\User;
use App\Modules\Post\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Post\Models\PostCategory>
 */
class PostCategoryFactory extends Factory
{
    protected $model = PostCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $name = Str::title($name);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional(0.7)->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'sort_order' => fake()->numberBetween(0, 100),
            'parent_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
