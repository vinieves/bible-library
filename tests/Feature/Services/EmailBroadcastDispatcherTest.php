<?php

namespace Tests\Feature\Services;

use App\Enums\EmailBroadcastStatus;
use App\Jobs\SendBroadcastEmailJob;
use App\Models\EmailBroadcast;
use App\Models\User;
use App\Services\EmailBroadcastAudienceService;
use App\Services\EmailBroadcastDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
