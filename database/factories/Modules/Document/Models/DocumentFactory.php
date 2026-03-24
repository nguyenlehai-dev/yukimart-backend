<?php

namespace Database\Factories\Modules\Document\Models;

use App\Modules\Document\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Document\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'so_ky_hieu' => strtoupper(fake()->bothify('VB-####/??')),
            'ten_van_ban' => fake()->sentence(4),
            'noi_dung' => fake()->optional()->paragraphs(2, true),
            'issuing_agency_id' => null,
            'issuing_level_id' => null,
            'signer_id' => null,
            'ngay_ban_hanh' => fake()->optional()->date(),
            'ngay_xuat_ban' => fake()->optional()->date(),
            'ngay_hieu_luc' => fake()->optional()->date(),
            'ngay_het_hieu_luc' => fake()->optional()->date(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
