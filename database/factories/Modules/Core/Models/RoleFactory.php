<?php

namespace Database\Factories\Modules\Core\Models;

use App\Modules\Core\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => 'role_'.fake()->unique()->bothify('??###'),
            'guard_name' => 'web',
            'organization_id' => null,
        ];
    }
}
