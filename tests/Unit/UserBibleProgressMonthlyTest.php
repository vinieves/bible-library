<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserBibleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBibleProgressMonthlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_verses_read_defaults_to_zero(): void
    {
        $user = User::factory()->create();

        $progress = UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Gn',
            'chapter' => 1,
        ]);

        $this->assertSame(0, $progress->monthly_verses_read);
        $this->assertNull($progress->monthly_period);
    }

    public function test_monthly_verses_read_and_period_are_fillable(): void
    {
        $user = User::factory()->create();

        $progress = UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Gn',
            'chapter' => 1,
            'monthly_verses_read' => 12,
            'monthly_period' => '2026-06',
        ]);

        $this->assertSame(12, $progress->monthly_verses_read);
        $this->assertSame('2026-06', $progress->monthly_period);
    }
}
