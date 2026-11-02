<?php

namespace Tests\Feature\Research;

use Database\Seeders\ResearchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResearchDatasetTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_produces_the_research_dataset(): void
    {
        $this->seed(ResearchSeeder::class);

        $this->artisan('research:verify-dataset')->assertSuccessful();
    }

    public function test_seeder_writes_no_activity_log(): void
    {
        $this->seed(ResearchSeeder::class);

        $this->assertSame(0, DB::table('activity_log')->count());
    }

    public function test_verification_fails_on_a_different_dataset(): void
    {
        $this->seed(ResearchSeeder::class);

        DB::table('enrollments')->where('id', 1)->update(['status_id' => 1]);

        $this->artisan('research:verify-dataset')->assertFailed();
    }
}
