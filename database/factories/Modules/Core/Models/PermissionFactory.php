<?php

namespace Database\Factories\Modules\Core\Models;

use App\Modules\Core\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Models\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $resource = fake()->randomElement(['users', 'roles', 'organizations', 'posts', 'documents']);
        $action = fake()->randomElement(['index', 'show', 'store', 'update', 'destroy', 'stats']);

        return [
            'name' => $resource.'.'.$action.'.'.fake()->unique()->numberBetween(1, 9999),
            'guard_name' => 'web',
            'description' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 100),
            'parent_id' => null,
        ];
    }
}
