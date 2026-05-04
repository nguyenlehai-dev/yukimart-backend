<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed dữ liệu nền tảng: User → Permission/Role/Team → Setting.
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->call(PermissionSeeder::class);
        $this->call(SettingSeeder::class);
    }

    /**
     * Tạo user mẫu. User đầu tiên dùng làm người tạo/sửa cho dữ liệu mẫu.
     */
    protected function seedUsers(): void
    {
        User::factory(10)->create();

        User::where('id', 1)->update(['created_by' => 1, 'updated_by' => 1]);
        User::where('id', '>', 1)->update(['created_by' => 1, 'updated_by' => 1]);
    }
}
