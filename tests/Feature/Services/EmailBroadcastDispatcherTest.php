<?php

namespace Tests\Feature\Services;

use App\Enums\EmailBroadcastStatus;
use App\Jobs\SendBroadcastEmailJob;
use App\Models\EmailBroadcast;
use App\Models\User;
use App\Models\Setting;
use App\Services\EmailBroadcastAudienceService;
use App\Services\EmailBroadcastDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class EmailBroadcastDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_enqueues_a_job_per_non_admin_recipient(): void
    {
        Bus::fake();

        User::factory()->count(3)->create(['is_admin' => false]);
        User::factory()->create(['is_admin' => true]);

        $broadcast = EmailBroadcast::create([
            'subject' => 'Olá {nome}',
            'body' => '<p>Corpo</p>',
            'audience_type' => 'all',
            'exclude_admins' => true,
            'status' => EmailBroadcastStatus::Draft,
        ]);

        $total = app(EmailBroadcastDispatcher::class)->dispatch($broadcast);

        $this->assertSame(3, $total);

        Bus::assertBatched(fn ($batch): bool => $batch->jobs->count() === 3
            && $batch->jobs->every(fn ($job): bool => $job instanceof SendBroadcastEmailJob));

        $broadcast->refresh();
        $this->assertSame(EmailBroadcastStatus::Queued, $broadcast->status);
        $this->assertSame(3, $broadcast->total_recipients);
    }

    public function test_email_list_audience_targets_only_registered_users(): void
    {
        $registered = User::factory()->create(['is_admin' => false, 'email' => 'cliente@example.com']);
        User::factory()->create(['is_admin' => false, 'email' => 'outro@example.com']);

        $broadcast = EmailBroadcast::create([
            'subject' => 'Assunto',
            'body' => '<p>x</p>',
            'audience_type' => 'email_list',
            'email_list' => ['cliente@example.com', 'naoexiste@example.com'],
            'exclude_admins' => true,
            'status' => EmailBroadcastStatus::Draft,
        ]);

        $count = app(EmailBroadcastAudienceService::class)->count($broadcast);

        $this->assertSame(1, $count);
    }

    /**
     * @return list<int> delays (segundos a partir de agora) na ordem dos jobs do lote
     */
    private function batchedDelaySeconds(): array
    {
        $delays = [];

        Bus::assertBatched(function ($batch) use (&$delays): bool {
            $delays = $batch->jobs
                ->map(fn ($job): int => $job->delay instanceof \DateTimeInterface
                    ? (int) round(Carbon::parse($job->delay)->diffInSeconds(now(), absolute: true))
                    : (int) $job->delay)
                ->all();

            return true;
        });

        return $delays;
    }

    public function test_emails_are_spaced_one_by_one_at_the_configured_rate(): void
    {
        Bus::fake();

        // Ritmo padrão = 12/min → ~5s de intervalo.
        User::factory()->count(4)->create(['is_admin' => false]);

        $broadcast = EmailBroadcast::create([
            'subject' => 'Assunto',
            'body' => '<p>x</p>',
            'audience_type' => 'all',
            'exclude_admins' => true,
            'status' => EmailBroadcastStatus::Draft,
        ]);

        app(EmailBroadcastDispatcher::class)->dispatch($broadcast);

        $delays = $this->batchedDelaySeconds();
        $this->assertCount(4, $delays);

        // Sem rajada: cada e-mail sai depois do anterior (não-decrescente).
        for ($i = 1; $i < count($delays); $i++) {
            $this->assertGreaterThan($delays[$i - 1], $delays[$i]);
            $gap = $delays[$i] - $delays[$i - 1];
            $this->assertGreaterThanOrEqual(3, $gap); // ~5s ± jitter
            $this->assertLessThanOrEqual(8, $gap);
        }
    }

    public function test_slower_preset_increases_the_spacing(): void
    {
        Bus::fake();

        Setting::set('broadcast_rate_per_minute', '6'); // ~10s de intervalo

        User::factory()->count(3)->create(['is_admin' => false]);

        $broadcast = EmailBroadcast::create([
            'subject' => 'Assunto',
            'body' => '<p>x</p>',
            'audience_type' => 'all',
            'exclude_admins' => true,
            'status' => EmailBroadcastStatus::Draft,
        ]);

        app(EmailBroadcastDispatcher::class)->dispatch($broadcast);

        $delays = $this->batchedDelaySeconds();
        $gap = $delays[1] - $delays[0];

        $this->assertGreaterThanOrEqual(8, $gap); // ~10s ± jitter
        $this->assertLessThanOrEqual(14, $gap);
    }

    public function test_dispatch_ignores_broadcast_that_is_not_draft(): void
    {
        Bus::fake();

        $broadcast = EmailBroadcast::create([
            'subject' => 'Assunto',
            'body' => '<p>x</p>',
            'audience_type' => 'all',
            'status' => EmailBroadcastStatus::Sent,
        ]);

        $total = app(EmailBroadcastDispatcher::class)->dispatch($broadcast);

        $this->assertSame(0, $total);
        Bus::assertNothingBatched();
    }
}
