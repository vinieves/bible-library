<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\UserBibleProgress;
use App\Services\BibleReaderService;
use App\Services\MemberProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(BibleReaderService $bible, MemberProgressService $progressService, Request $request): View
    {
        $user = Auth::user();
        $savedProgress = UserBibleProgress::query()
            ->where('user_id', $user->id)
            ->first();

        $initialBook = $request->query('libro', $savedProgress?->book_abbr);
        $initialChapter = (int) $request->query('capitulo', $savedProgress?->chapter ?? 0) ?: null;
        $initialVerse = (int) $request->query('versiculo', $savedProgress?->verse ?? 0) ?: null;

        return view('members.library.index', [
            'bibleAvailable' => $bible->isAvailable(),
            'booksUrl' => url('/mi-biblioteca/libros/api/libros'),
            'chapterUrl' => url('/mi-biblioteca/libros/api/__BOOK__/__CHAPTER__'),
            'progressUrl' => url('/mi-biblioteca/libros/progreso'),
            'initialBook' => $initialBook,
            'initialChapter' => $initialChapter,
            'initialVerse' => $initialVerse,
            'streak' => $progressService->learningStreak($user),
        ]);
    }

    public function books(BibleReaderService $bible): JsonResponse
    {
        return response()->json($bible->booksIndex());
    }

    public function chapter(string $book, int $chapter, BibleReaderService $bible): JsonResponse
    {
        $data = $bible->chapter($book, $chapter);

        if ($data === null) {
            return response()->json(['message' => 'Capítulo no encontrado.'], 404);
        }

        return response()->json($data);
    }

    public function saveProgress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_abbr' => ['required', 'string', 'max:12'],
            'chapter' => ['required', 'integer', 'min:1'],
            'verse' => ['nullable', 'integer', 'min:1'],
        ]);

        $existing = UserBibleProgress::query()->where('user_id', Auth::id())->first();

        $verseChanged = $validated['verse'] !== null && (
            ! $existing
            || $existing->book_abbr !== $validated['book_abbr']
            || $existing->chapter !== $validated['chapter']
            || $existing->verse !== $validated['verse']
        );

        $versesToAdd = $verseChanged ? 1 : 0;

        $currentPeriod = now()->format('Y-m');
        $samePeriod = $existing?->monthly_period === $currentPeriod;

        $monthlyVersesRead = $samePeriod
            ? $existing->monthly_verses_read + $versesToAdd
            : $versesToAdd;

        $today = now()->toDateString();
        $lastActivityDate = $existing?->last_activity_date?->toDateString();
        $verseClicked = $validated['verse'] !== null;

        if ($verseClicked) {
            $currentStreak = match (true) {
                $lastActivityDate === $today => $existing->current_streak,
                $lastActivityDate === now()->subDay()->toDateString() => $existing->current_streak + 1,
                default => 1,
            };
        } else {
            $currentStreak = $existing?->current_streak ?? 0;
        }

        UserBibleProgress::query()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'book_abbr' => $validated['book_abbr'],
                'chapter' => $validated['chapter'],
                'verse' => $validated['verse'] ?? null,
                'monthly_verses_read' => $monthlyVersesRead,
                'monthly_period' => $currentPeriod,
                'current_streak' => $currentStreak,
                'last_activity_date' => $verseClicked ? $today : $lastActivityDate,
            ],
        );

        return response()->json(['ok' => true]);
    }
}
