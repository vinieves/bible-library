# Dashboard Hero Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the members "Inicio" dashboard with a Jesus hero banner, a welcome card, a daily-rotating verse of the day, and a monthly Bible-reading-goal progress indicator, while keeping the existing "continue where you left off" section.

**Architecture:** Three new small, focused units — a `VerseOfTheDayService` (deterministic daily verse selection from a curated config list), a monthly verse counter persisted on the existing `user_bible_progress` row, and three new Blade components composed into the existing dashboard view. No new routes, no new tables.

**Tech Stack:** Laravel 13, PHPUnit (class-based tests extending `Tests\TestCase` for app-aware tests, plain `PHPUnit\Framework\TestCase` for pure-logic tests), Blade, Tailwind (existing `member-*` design tokens), SQLite.

## Global Constraints

- Monthly verse goal is fixed at **30 verses/month** (not configurable).
- Verse of the day rotates once per calendar day, same verse for all users, no DB writes (`(int) date('z') % count($list)`).
- Re-reading the same chapter must NOT double-count toward the monthly goal; only counted when `(book_abbr, chapter)` changes from the previously stored value.
- The existing top header (`x-members.header`) and bottom nav are untouched — no hamburger menu, no notifications icon (those features don't exist).
- Hero image lives at `public/images/jesus.png`, referenced via `asset('images/jesus.png')`.
- Spec reference: `docs/superpowers/specs/2026-06-24-dashboard-hero-redesign-design.md`.

---

## Task 1: Monthly counter columns on `user_bible_progress`

**Files:**
- Create: `database/migrations/2026_06_24_200001_add_monthly_verses_read_to_user_bible_progress.php`
- Modify: `app/Models/UserBibleProgress.php`
- Test: `tests/Unit/UserBibleProgressMonthlyTest.php`

**Interfaces:**
- Produces: `UserBibleProgress` gets two new attributes, `monthly_verses_read` (int, default 0) and `monthly_period` (string|null), both fillable and cast (`monthly_verses_read` => `integer`).

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_bible_progress', function (Blueprint $table) {
            $table->unsignedInteger('monthly_verses_read')->default(0)->after('verse');
            $table->string('monthly_period', 7)->nullable()->after('monthly_verses_read');
        });
    }

    public function down(): void
    {
        Schema::table('user_bible_progress', function (Blueprint $table) {
            $table->dropColumn(['monthly_verses_read', 'monthly_period']);
        });
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate`
Expected: `2026_06_24_200001_add_monthly_verses_read_to_user_bible_progress ... DONE`

- [ ] **Step 3: Update the model**

Edit `app/Models/UserBibleProgress.php` — extend `$fillable` and add to `casts()`:

```php
    protected $fillable = [
        'user_id',
        'book_abbr',
        'chapter',
        'verse',
        'monthly_verses_read',
        'monthly_period',
    ];

    protected function casts(): array
    {
        return [
            'chapter' => 'integer',
            'verse' => 'integer',
            'monthly_verses_read' => 'integer',
        ];
    }
```

- [ ] **Step 4: Write the failing test**

```php
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
```

This test is placed in `tests/Unit` but extends `Tests\TestCase` (not plain `PHPUnit\Framework\TestCase`) because it needs the database — this matches the existing `tests/Feature/ProfileTest.php` pattern of using `RefreshDatabase`, just located in `Unit` since it tests a single model in isolation.

- [ ] **Step 5: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/UserBibleProgressMonthlyTest.php`
Expected: FAIL — "monthly_verses_read" column or attribute not found (before Step 1-3) or PASS already if you did steps in order. Run this *before* Step 1-3 to confirm the red state, or trust the order above and skip straight to verifying green in Step 6.

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/UserBibleProgressMonthlyTest.php`
Expected: `OK (2 tests, ...)`

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_06_24_200001_add_monthly_verses_read_to_user_bible_progress.php app/Models/UserBibleProgress.php tests/Unit/UserBibleProgressMonthlyTest.php
git commit -m "feat: add monthly verse counter columns to user_bible_progress"
```

---

## Task 2: Verse of the day

**Files:**
- Create: `config/verse_of_the_day.php`
- Create: `app/Services/VerseOfTheDayService.php`
- Test: `tests/Feature/Services/VerseOfTheDayServiceTest.php`

**Interfaces:**
- Consumes: `App\Services\BibleReaderService::isAvailable(): bool` and `BibleReaderService::chapter(string $book, int $chapter): ?array` (returns `['book', 'bookName', 'chapter', 'verses' => list<['number' => int, 'text' => string, 'explanation' => string]>]`) — both already exist in `app/Services/BibleReaderService.php`.
- Produces: `VerseOfTheDayService::today(): ?array` returning `['reference' => string, 'text' => string]` or `null` when unavailable.

- [ ] **Step 1: Write the config file**

```php
<?php

return [

    /*
    | Curated list of well-known verse references rotated daily.
    | `verse_start`/`verse_end` are inclusive; use the same value for a single verse.
    | Book abbreviations must match the keys in config/bible.php.
    */
    'verses' => [
        ['book' => 'Prov', 'chapter' => 3, 'verse_start' => 5, 'verse_end' => 6],
        ['book' => 'Sal', 'chapter' => 23, 'verse_start' => 1, 'verse_end' => 3],
        ['book' => 'Jn', 'chapter' => 3, 'verse_start' => 16, 'verse_end' => 16],
        ['book' => 'Flp', 'chapter' => 4, 'verse_start' => 13, 'verse_end' => 13],
        ['book' => 'Jos', 'chapter' => 1, 'verse_start' => 9, 'verse_end' => 9],
        ['book' => 'Is', 'chapter' => 41, 'verse_start' => 10, 'verse_end' => 10],
        ['book' => 'Sal', 'chapter' => 46, 'verse_start' => 1, 'verse_end' => 1],
        ['book' => 'Rom', 'chapter' => 8, 'verse_start' => 28, 'verse_end' => 28],
        ['book' => 'Mt', 'chapter' => 11, 'verse_start' => 28, 'verse_end' => 28],
        ['book' => 'Jer', 'chapter' => 29, 'verse_start' => 11, 'verse_end' => 11],
        ['book' => '2Cor', 'chapter' => 5, 'verse_start' => 17, 'verse_end' => 17],
        ['book' => 'Gal', 'chapter' => 5, 'verse_start' => 22, 'verse_end' => 23],
        ['book' => 'Heb', 'chapter' => 11, 'verse_start' => 1, 'verse_end' => 1],
        ['book' => 'Stg', 'chapter' => 1, 'verse_start' => 5, 'verse_end' => 5],
        ['book' => '1Jn', 'chapter' => 4, 'verse_start' => 19, 'verse_end' => 19],
        ['book' => 'Sal', 'chapter' => 119, 'verse_start' => 105, 'verse_end' => 105],
        ['book' => 'Mt', 'chapter' => 6, 'verse_start' => 33, 'verse_end' => 33],
        ['book' => 'Col', 'chapter' => 3, 'verse_start' => 23, 'verse_end' => 23],
    ],

];
```

- [ ] **Step 2: Write the failing test**

```php
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
```

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Services/VerseOfTheDayServiceTest.php`
Expected: FAIL — class `App\Services\VerseOfTheDayService` not found.

- [ ] **Step 4: Write the service**

```php
<?php

namespace App\Services;

class VerseOfTheDayService
{
    public function __construct(private BibleReaderService $bible) {}

    /**
     * @return array{reference: string, text: string}|null
     */
    public function today(): ?array
    {
        if (! $this->bible->isAvailable()) {
            return null;
        }

        $list = config('verse_of_the_day.verses', []);

        if ($list === []) {
            return null;
        }

        $index = (int) date('z') % count($list);
        $entry = $list[$index];

        $chapterData = $this->bible->chapter($entry['book'], $entry['chapter']);

        if ($chapterData === null) {
            return null;
        }

        $text = [];

        foreach ($chapterData['verses'] as $verse) {
            if ($verse['number'] >= $entry['verse_start'] && $verse['number'] <= $entry['verse_end']) {
                $text[] = $verse['text'];
            }
        }

        if ($text === []) {
            return null;
        }

        $bookName = $chapterData['bookName'];
        $reference = $entry['verse_start'] === $entry['verse_end']
            ? "{$bookName} {$entry['chapter']}:{$entry['verse_start']}"
            : "{$bookName} {$entry['chapter']}:{$entry['verse_start']}-{$entry['verse_end']}";

        return [
            'reference' => $reference,
            'text' => implode(' ', $text),
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Services/VerseOfTheDayServiceTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add config/verse_of_the_day.php app/Services/VerseOfTheDayService.php tests/Feature/Services/VerseOfTheDayServiceTest.php
git commit -m "feat: add VerseOfTheDayService with curated daily verse rotation"
```

---

## Task 3: Monthly counter logic in `LibraryController::saveProgress`

**Files:**
- Modify: `app/Http/Controllers/Members/LibraryController.php:53-71`
- Test: `tests/Feature/Members/LibraryControllerProgressTest.php`

**Interfaces:**
- Consumes: `BibleReaderService::chapter(string $book, int $chapter): ?array` (already injected pattern elsewhere in this controller).
- Produces: after `saveProgress`, the user's `UserBibleProgress` row has `monthly_verses_read` and `monthly_period` correctly updated, per the rules in the Global Constraints section.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature\Members;

use App\Models\User;
use App\Models\UserBibleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LibraryControllerProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_progress_on_a_new_chapter_adds_its_verse_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/mi-biblioteca/libros/progreso', [
                'book_abbr' => 'Sal',
                'chapter' => 23,
                'verse' => 1,
            ])
            ->assertOk();

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(6, $progress->monthly_verses_read);
        $this->assertSame(now()->format('Y-m'), $progress->monthly_period);
    }

    public function test_saving_progress_again_on_the_same_chapter_does_not_double_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 3,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(6, $progress->monthly_verses_read);
    }

    public function test_advancing_to_a_new_chapter_adds_to_the_existing_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Sal', 'chapter' => 23, 'verse' => 1,
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Jn', 'chapter' => 3, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(6 + 34, $progress->monthly_verses_read);
    }

    public function test_counter_resets_when_the_stored_period_is_a_previous_month(): void
    {
        $user = User::factory()->create();

        UserBibleProgress::create([
            'user_id' => $user->id,
            'book_abbr' => 'Sal',
            'chapter' => 23,
            'verse' => 1,
            'monthly_verses_read' => 27,
            'monthly_period' => Carbon::now()->subMonth()->format('Y-m'),
        ]);

        $this->actingAs($user)->post('/mi-biblioteca/libros/progreso', [
            'book_abbr' => 'Jn', 'chapter' => 3, 'verse' => 1,
        ]);

        $progress = UserBibleProgress::where('user_id', $user->id)->first();

        $this->assertSame(34, $progress->monthly_verses_read);
        $this->assertSame(now()->format('Y-m'), $progress->monthly_period);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Members/LibraryControllerProgressTest.php`
Expected: FAIL — assertions on `monthly_verses_read` fail (currently always 0, since the column is never written by `saveProgress`).

- [ ] **Step 3: Implement the counter logic**

Replace `saveProgress` in `app/Http/Controllers/Members/LibraryController.php`:

```php
    public function saveProgress(Request $request, BibleReaderService $bible): JsonResponse
    {
        $validated = $request->validate([
            'book_abbr' => ['required', 'string', 'max:12'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse' => ['nullable', 'integer', 'min:1'],
        ]);

        $existing = UserBibleProgress::query()->where('user_id', Auth::id())->first();

        $chapterChanged = ! $existing
            || $existing->book_abbr !== $validated['book_abbr']
            || $existing->chapter !== $validated['chapter'];

        $versesToAdd = 0;

        if ($chapterChanged) {
            $chapterData = $bible->chapter($validated['book_abbr'], $validated['chapter']);
            $versesToAdd = $chapterData ? count($chapterData['verses']) : 0;
        }

        $currentPeriod = now()->format('Y-m');
        $samePeriod = $existing?->monthly_period === $currentPeriod;

        $monthlyVersesRead = $samePeriod
            ? $existing->monthly_verses_read + $versesToAdd
            : $versesToAdd;

        UserBibleProgress::query()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'book_abbr' => $validated['book_abbr'],
                'chapter' => $validated['chapter'],
                'verse' => $validated['verse'] ?? null,
                'monthly_verses_read' => $monthlyVersesRead,
                'monthly_period' => $currentPeriod,
            ],
        );

        return response()->json(['ok' => true]);
    }
```

`BibleReaderService` is already imported at the top of this file (`use App\Services\BibleReaderService;`) since it's used in `chapter()` above — no new import needed.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Members/LibraryControllerProgressTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Members/LibraryController.php tests/Feature/Members/LibraryControllerProgressTest.php
git commit -m "feat: track monthly verses-read counter on chapter progress"
```

---

## Task 4: Dashboard wiring — monthly goal + verse of the day in `MemberProgressService` / `DashboardController`

**Files:**
- Modify: `app/Services/MemberProgressService.php`
- Modify: `app/Http/Controllers/Members/DashboardController.php`
- Test: `tests/Feature/Members/DashboardControllerTest.php`

**Interfaces:**
- Consumes: `VerseOfTheDayService::today(): ?array` from Task 2; `UserBibleProgress` columns from Task 1.
- Produces: `MemberProgressService::monthlyGoalProgress(User $user): array` returning `['read' => int, 'goal' => int, 'percent' => int, 'label' => string]`. View receives `monthlyGoal` (that array) and `verseOfTheDay` (`['reference','text']|null`).

- [ ] **Step 1: Write the failing test**

```php
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
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Members/DashboardControllerTest.php`
Expected: FAIL — `monthlyGoal`/`verseOfTheDay` view variables not set.

- [ ] **Step 3: Add `monthlyGoalProgress` to `MemberProgressService`**

Add this public method and constant to `app/Services/MemberProgressService.php` (alongside the existing `suggestedStartUrl`/`suggestedStartLabel` methods):

```php
    public const MONTHLY_VERSE_GOAL = 30;

    /**
     * @return array{read: int, goal: int, percent: int, label: string}
     */
    public function monthlyGoalProgress(User $user): array
    {
        $progress = UserBibleProgress::query()->where('user_id', $user->id)->first();

        $currentPeriod = now()->format('Y-m');
        $read = ($progress && $progress->monthly_period === $currentPeriod)
            ? $progress->monthly_verses_read
            : 0;

        $percent = (int) min(100, round($read / self::MONTHLY_VERSE_GOAL * 100));

        return [
            'read' => $read,
            'goal' => self::MONTHLY_VERSE_GOAL,
            'percent' => $percent,
            'label' => "{$read} de ".self::MONTHLY_VERSE_GOAL.' versículos este mes',
        ];
    }
```

`UserBibleProgress` and `User` are already imported at the top of this file.

- [ ] **Step 4: Wire it into `DashboardController`**

Replace the contents of `app/Http/Controllers/Members/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Services\MemberProgressService;
use App\Services\VerseOfTheDayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(MemberProgressService $progressService, VerseOfTheDayService $verseService): View
    {
        $user = Auth::user();

        return view('members.dashboard', [
            'continueCards' => $progressService->continueCards($user),
            'suggestedStartUrl' => $progressService->suggestedStartUrl($user),
            'suggestedStartLabel' => $progressService->suggestedStartLabel(),
            'monthlyGoal' => $progressService->monthlyGoalProgress($user),
            'verseOfTheDay' => $verseService->today(),
        ]);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Members/DashboardControllerTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add app/Services/MemberProgressService.php app/Http/Controllers/Members/DashboardController.php tests/Feature/Members/DashboardControllerTest.php
git commit -m "feat: expose monthly goal progress and verse of the day to dashboard"
```

---

## Task 5: Dashboard view — hero, welcome card, verse card, progress card

**Files:**
- Create: `resources/views/components/members/dashboard-hero.blade.php`
- Create: `resources/views/components/members/verse-of-the-day.blade.php`
- Create: `resources/views/components/members/monthly-goal-progress.blade.php`
- Modify: `resources/views/members/dashboard.blade.php`
- Test: `tests/Feature/Members/DashboardControllerTest.php` (extend, same file as Task 4)

**Interfaces:**
- Consumes: `$monthlyGoal` (`['read','goal','percent','label']`) and `$verseOfTheDay` (`['reference','text']|null`) from Task 4; `$continueCards`, `$suggestedStartUrl`, `$suggestedStartLabel` (unchanged, from existing controller).

- [ ] **Step 1: Write the failing assertions (extend the Task 4 test file)**

Add to `tests/Feature/Members/DashboardControllerTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Members/DashboardControllerTest.php`
Expected: FAIL — hero image/welcome copy/goal label not found in response body.

- [ ] **Step 3: Create the hero component**

```blade
{{-- resources/views/components/members/dashboard-hero.blade.php --}}
<div class="overflow-hidden rounded-b-3xl">
    <img src="{{ asset('images/jesus.png') }}"
         alt=""
         class="h-56 w-full object-cover sm:h-72"
         loading="eager">
</div>
```

- [ ] **Step 4: Create the verse-of-the-day component**

```blade
{{-- resources/views/components/members/verse-of-the-day.blade.php --}}
@props(['verse'])

@if($verse)
    <section class="dashboard-card rounded-2xl border border-member-gold/15 bg-member-card px-4 py-4 sm:px-6 sm:py-5">
        <p class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-member-body/65">
            <span aria-hidden="true">📖</span> Versículo del día
        </p>
        <p class="text-base italic leading-relaxed text-member-title sm:text-lg">
            “{{ $verse['text'] }}”
        </p>
        <p class="mt-2 text-sm font-semibold text-member-gold-dark">
            {{ $verse['reference'] }}
        </p>
    </section>
@endif
```

- [ ] **Step 5: Create the monthly-goal-progress component**

```blade
{{-- resources/views/components/members/monthly-goal-progress.blade.php --}}
@props(['goal'])

<section class="dashboard-card flex items-center justify-between gap-4 rounded-2xl border border-member-gold/15 bg-member-card px-4 py-4 sm:px-6 sm:py-5">
    <div class="flex items-center gap-4">
        <div class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-full"
             style="background: conic-gradient(var(--member-gold, #b8860b) {{ $goal['percent'] }}%, rgba(184,134,11,0.15) 0);">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-member-card text-sm font-semibold text-member-title">
                {{ $goal['percent'] }}%
            </div>
        </div>
        <div>
            <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-member-body/65">
                <span aria-hidden="true">📈</span> Tu progreso
            </p>
            <p class="mt-1 text-sm text-member-body">
                {{ $goal['label'] }}
            </p>
        </div>
    </div>
</section>
```

- [ ] **Step 6: Update the dashboard view**

Replace `resources/views/members/dashboard.blade.php`:

```blade
@extends('layouts.members')

@section('title', 'Inicio')

@section('content')
    <x-members.dashboard-hero />

    <div class="mt-5 space-y-4">
        <header>
            <h1 class="page-title mb-1.5">Bienvenido</h1>
            <p class="text-base text-member-body/80 sm:text-lg">Tu camino para comprender toda la Biblia comienza aquí.</p>
        </header>

        <x-members.verse-of-the-day :verse="$verseOfTheDay" />

        <x-members.monthly-goal-progress :goal="$monthlyGoal" />

        <section>
            <p class="mb-3 text-xs font-medium uppercase tracking-wider text-member-body/65">Comience donde lo dejó</p>

            @if(count($continueCards) > 0)
                <div class="space-y-3">
                    @foreach($continueCards as $card)
                        <x-members.card
                            :href="$card['href']"
                            :title="$card['title']"
                            :subtitle="$card['subtitle']"
                            :icon="$card['icon']"
                            :accent="$card['accent']"
                            :material="$card['material'] ?? null"
                        />
                    @endforeach
                </div>
            @else
                <div class="dashboard-continue-empty rounded-2xl px-4 py-5 text-center sm:px-6">
                    <p class="text-sm text-member-body">
                        Aún no tiene progreso guardado. Comience explorando
                        <span class="font-medium text-member-gold">{{ $suggestedStartLabel }}</span>.
                    </p>
                    <a href="{{ $suggestedStartUrl }}"
                       class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-member-gold transition hover:text-member-gold-dark">
                        Ir a Libros
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </section>
    </div>
@endsection
```

Note: this view's content sits below the existing `<x-members.header>` rendered by `layouts/members.blade.php` — per the approved design, the hero is an additional banner under that header, not a replacement for it.

- [ ] **Step 7: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Members/DashboardControllerTest.php`
Expected: `OK (6 tests, ...)`

- [ ] **Step 8: Run the full test suite**

Run: `vendor/bin/phpunit`
Expected: `OK (...)` — no regressions in other Feature/Unit tests.

- [ ] **Step 9: Commit**

```bash
git add resources/views/components/members/dashboard-hero.blade.php resources/views/components/members/verse-of-the-day.blade.php resources/views/components/members/monthly-goal-progress.blade.php resources/views/members/dashboard.blade.php tests/Feature/Members/DashboardControllerTest.php
git commit -m "feat: redesign members dashboard with hero, verse of the day and monthly goal"
```

---

## Manual verification (after all tasks)

- [ ] Visit `http://127.0.0.1:8000/mi-biblioteca` logged in as `cliente@biblioteca.test`, confirm: hero image shows, "Bienvenido" card, a verse of the day appears, the progress ring shows `0 de 30 versículos este mes`.
- [ ] Open `/mi-biblioteca/libros`, read a chapter (triggers `saveProgress`), return to `/mi-biblioteca`, confirm the monthly goal label increased by that chapter's verse count.
- [ ] Re-open the same chapter again, confirm the count does NOT increase a second time.
