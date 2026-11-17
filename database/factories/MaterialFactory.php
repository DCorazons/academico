<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'meeting' => fake()->numberBetween(1, 14),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
        ];
    }
}
