<?php

namespace Database\Factories;

use App\Modules\Core\Models\User;
use App\Modules\Post\Models\Post;
use App\Modules\Post\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Post\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'view_count' => 0,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Trạng thái published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'published']);
    }

    /**
     * Trạng thái draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'draft']);
    }

    /**
     * Thuộc một hoặc nhiều danh mục (gán sau khi create qua $post->categories()->attach(...)).
     */
    public function forCategories(PostCategory|array $categories): static
    {
        return $this->afterCreating(function (Post $post) use ($categories) {
            $ids = is_array($categories)
                ? array_map(fn ($c) => $c instanceof PostCategory ? $c->id : $c, $categories)
                : [$categories->id];
            $post->categories()->sync($ids);
        });
    }

    /**
     * Gán người tạo/sửa (dùng trong seeder).
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
