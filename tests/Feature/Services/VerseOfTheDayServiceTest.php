<?php

namespace Tests\Feature\Services;

use App\Services\BibleReaderService;
use App\Services\VerseOfTheDayService;
use Tests\TestCase;

class VerseOfTheDayServiceTest extends TestCase
{
    public function test_today_returns_a_reference_and_text_for_a_known_book(): void
    {
        config(['verse_of_the_day.verses' => [
            ['book' => 'Prov', 'chapter' => 3, 'verse_start' => 5, 'verse_end' => 6],
        ]]);

        $service = app(VerseOfTheDayService::class);

        $result = $service->today();

        $this->assertNotNull($result);
        $this->assertSame('Proverbios 3:5-6', $result['reference']);
        $this->assertStringContainsString('confianza', $result['text']);
    }

    public function test_today_uses_single_verse_reference_format_when_start_equals_end(): void
    {
        config(['verse_of_the_day.verses' => [
            ['book' => 'Jn', 'chapter' => 3, 'verse_start' => 16, 'verse_end' => 16],
        ]]);

        $service = app(VerseOfTheDayService::class);

        $result = $service->today();

        $this->assertSame('Juan 3:16', $result['reference']);
    }

    public function test_today_returns_null_when_bible_data_is_unavailable(): void
    {
        $this->mock(BibleReaderService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(false);
        });

        config(['verse_of_the_day.verses' => [
            ['book' => 'Prov', 'chapter' => 3, 'verse_start' => 5, 'verse_end' => 6],
        ]]);

        $service = app(VerseOfTheDayService::class);

        $this->assertNull($service->today());
    }

    public function test_today_returns_null_when_verse_list_is_empty(): void
    {
        config(['verse_of_the_day.verses' => []]);

        $service = app(VerseOfTheDayService::class);

        $this->assertNull($service->today());
    }
}
