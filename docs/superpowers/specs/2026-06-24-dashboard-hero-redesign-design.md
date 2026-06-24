# Dashboard "Inicio" Hero Redesign

## Context

The members dashboard (`/mi-biblioteca`, rendered by `App\Http\Controllers\Members\DashboardController` + `resources/views/members/dashboard.blade.php`) currently shows a plain text header and a "continue where you left off" list. The user wants it restyled to match a reference mockup: a hero photo of Jesus at the top, a welcome card, a "verse of the day" card, and a monthly reading-goal progress ring — while keeping the existing "continue cards" section below.

## Goals

- Redesign the dashboard page to match the reference mockup's visual structure.
- Add a daily-rotating "verse of the day" card, sourced from the existing Bible data.
- Add a monthly reading-goal progress indicator ("X of 30 verses this month").
- Do this without disrupting the existing members layout, header, or navigation.

## Non-goals

- No hamburger menu or notifications system (don't exist today; mockup icons are decorative only and are dropped).
- No historical reading log / analytics beyond the monthly counter.
- No admin-configurable monthly goal (fixed at 30 verses for now).
- No changes to the existing top header (logo, user name, logout) — it stays as-is.

## Design

### 1. Page structure (`resources/views/members/dashboard.blade.php`)

Top to bottom, in order:

1. **Hero banner** — new component `<x-members.dashboard-hero>`. Full-width image (`public/images/<filename>`, exact filename TBD once user uploads it) referenced via `asset()`. Renders above the existing `<x-members.header>` is NOT touched — the hero is an additional section at the top of the page `@section('content')`, the global header from `layouts.members` still renders above it as today.
2. **Welcome card** — title "Bienvenido" + subtitle "Tu camino para comprender toda la Biblia comienza aquí" (static copy, replacing today's "Bienvenido a su Biblioteca Bíblica / Elija qué desea estudiar hoy").
3. **Verse of the day card** — new component `<x-members.verse-of-the-day>`, fed by `VerseOfTheDayService`.
4. **Monthly progress card** — new component `<x-members.monthly-goal-progress>`, showing a percentage ring and "X de 30 versículos este mes" label.
5. **Existing "Comience donde lo dejó" section** — unchanged, kept below the new cards.

Each new card is its own Blade component (consistent with the existing `x-members.card` pattern), so the main dashboard view stays a thin composition of components rather than growing a large monolithic template.

### 2. Verse of the day

New `App\Services\VerseOfTheDayService`:

- A curated list of ~30 well-known verse references lives in `config/verse_of_the_day.php` as an array of `['book' => 'pro', 'chapter' => 3, 'verse_start' => 5, 'verse_end' => 6]`-shaped entries (single verse or short range).
- `VerseOfTheDayService::today(): array` picks `index = (int) date('z') % count($list)` — deterministic per calendar day, no DB writes, resets naturally at midnight server time.
- Looks up the actual verse text via the existing `BibleReaderService::chapter($book, $chapter)` and extracts the verse(s) in the configured range.
- Returns `['reference' => 'Proverbios 3:5-6', 'text' => '...']` for the view to render.
- If `BibleReaderService::isAvailable()` is false (Bible data file missing) or the configured reference can't be resolved, the service returns `null` and the verse-of-the-day card is omitted from the page (no broken card, no exception).

### 3. Monthly reading-goal counter

**Migration**: add to `user_bible_progress`:
- `monthly_verses_read` (`unsignedInteger`, default `0`)
- `monthly_period` (`string`, nullable) — stores `'Y-m'` of the period the counter applies to.

**Logic**, inside `LibraryController::saveProgress()`:

1. Load the existing `UserBibleProgress` row for the user (if any), before applying the update.
2. Compute `$currentPeriod = now()->format('Y-m')`.
3. Determine `$versesToAdd`:
   - If there's no existing row, or the existing row's `(book_abbr, chapter)` differs from the new `(book_abbr, chapter)` being saved (i.e. the user advanced to a different chapter), look up that chapter's verse count via `BibleReaderService::chapter()` and use it as `$versesToAdd`. Otherwise `$versesToAdd = 0` (same chapter, no double count).
4. Determine the counter to persist:
   - If the existing row's `monthly_period` is null or different from `$currentPeriod`, reset: `monthly_verses_read = $versesToAdd`, `monthly_period = $currentPeriod`.
   - Else: `monthly_verses_read = existing.monthly_verses_read + $versesToAdd`, `monthly_period` unchanged.
5. Persist all fields (book_abbr, chapter, verse, monthly_verses_read, monthly_period) in the same `updateOrCreate` call.

**Constant**: `MemberProgressService::MONTHLY_VERSE_GOAL = 30`.

**Exposing to the view**: `DashboardController` asks `MemberProgressService` for the user's current `monthly_verses_read` (0 if no row / stale period) and computes:
- `monthlyGoalPercent = min(100, (int) round($read / 30 * 100))`
- `monthlyGoalLabel = "{$read} de 30 versículos este mes"`

Passed to the view alongside the existing `continueCards` etc.

### Known limitation (accepted)

The monthly counter only tracks "did the user move to a different chapter than last saved." Re-reading an earlier chapter and then advancing again to a chapter already counted this month will double-count it. This is an accepted simplification (no reading-history table) per explicit choice — not a bug to fix later unless requirements change.

### Error handling

- Missing hero image file → standard broken-image browser behavior; not handled specially (image is a static asset the user controls).
- Bible data unavailable → verse-of-the-day card silently omitted (see above); rest of page renders normally.
- No `UserBibleProgress` row yet → monthly progress shows 0%, "0 de 30 versículos este mes".

### Testing

- Feature test: dashboard page renders 200 with all new sections when authenticated.
- Unit test: `VerseOfTheDayService` returns a verse for a known day index; returns `null` when Bible data unavailable.
- Unit/feature test: `saveProgress` increments `monthly_verses_read` on chapter change, does not increment on verse-only update within the same chapter, and resets when `monthly_period` rolls over to a new month.
