<?php

namespace Tests\Feature\Members;

use App\Models\User;
use App\Models\UserBibleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/mi-biblioteca');

        $response->assertOk();
        $response->assertViewHas('monthlyGoal');
        $response->assertViewHas('verseOfTheDay');
    }

    public function test_monthly_goal_defaults_to_zero_read_with_no_progress_row(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/mi-biblioteca');

        $response->assertViewHas('monthlyGoal', function (array $monthlyGoal) {
            return $monthlyGoal['read'] === 0
                && $monthlyGoal['goal'] === 30
                && $monthlyGoal['percent'] === 0
                && $monthlyGoal['label'] === '0 de 30 versículos este mes';
        });
    }

    public function test_monthly_goal_reflects_current_period_count(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'monthly_verses_read' => 12,
            'monthly_period' => now()->format('Y-m'),
        ]);

        $response = $this->actingAs($user)->get('/mi-biblioteca');

        $response->assertViewHas('monthlyGoal', function (array $monthlyGoal) {
            return $monthlyGoal['read'] === 12
                && $monthlyGoal['percent'] === 40
                && $monthlyGoal['label'] === '12 de 30 versículos este mes';
        });
    }

    public function test_monthly_goal_ignores_a_stale_period_count(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'monthly_verses_read' => 27,
            'monthly_period' => now()->subMonth()->format('Y-m'),
        ]);

        $response = $this->actingAs($user)->get('/mi-biblioteca');

        $response->assertViewHas('monthlyGoal', function (array $monthlyGoal) {
            return $monthlyGoal['read'] === 0 && $monthlyGoal['percent'] === 0;
        });
    }

    public function test_dashboard_shows_the_hero_image_and_welcome_copy(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/mi-biblioteca');

        $response->assertSee('images/jesus.png', false);
        $response->assertSee('Bienvenido');
        $response->assertSee('Tu camino para comprender toda la Biblia comienza aquí');
    }

    public function test_dashboard_shows_the_monthly_goal_label(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/mi-biblioteca');

        $response->assertSee('0 de 30 versículos este mes');
    }
}
