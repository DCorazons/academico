<?php

namespace Tests\Feature\Research;

use App\Models\ExamAnswer;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResearchFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_and_exam_factories_create_valid_rows(): void
    {
        $material = Material::factory()->create();
        $answer = ExamAnswer::factory()->create();

        $this->assertModelExists($material);
        $this->assertModelExists($answer);
        $this->assertModelExists($answer->submission->exam);
        $this->assertSame($answer->submission->exam_id, $answer->submission->exam->id);
    }
}
