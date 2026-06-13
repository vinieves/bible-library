<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\AudioCategory;
use App\Models\AudioTrack;
use App\Models\Setting;
use App\Models\UserAudioProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AudioController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $search = $request->string('q')->trim()->toString();
        $categoryId = $request->integer('categoria') ?: null;

        $tracks = AudioTrack::query()
            ->published()
            ->with('category')
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($categoryId, fn ($query) => $query->where('audio_category_id', $categoryId))
            ->orderBy('order')
            ->get();

        $categories = AudioCategory::query()
            ->where('is_active', true)
            ->whereHas('tracks', fn ($query) => $query->published())
            ->orderBy('order')
            ->get();

        $progressByTrack = $user->audioProgress()
            ->whereIn('audio_track_id', $tracks->pluck('id'))
            ->get()
            ->keyBy('audio_track_id');

        return view('members.audio.index', [
            'tracks' => $tracks,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
            'progressByTrack' => $progressByTrack,
        ]);
    }

    public function show(AudioTrack $audioTrack): View|RedirectResponse
    {
        if (! $audioTrack->isPublished()) {
            abort(404);
        }

        $user = Auth::user();

        if (! $user->hasAccessToAudioTrack($audioTrack)) {
            return view('members.audio.locked', [
                'audioTrack' => $audioTrack,
                'checkoutUrl' => $audioTrack->checkoutUrl(),
                'subscriptionTitle' => Setting::get('audio_subscription_title', 'Biblioteca Bíblica en Audio'),
            ]);
        }

        $progress = UserAudioProgress::query()->firstOrCreate([
            'user_id' => $user->id,
            'audio_track_id' => $audioTrack->id,
        ]);

        return view('members.audio.show', compact('audioTrack', 'progress'));
    }

    public function stream(AudioTrack $audioTrack): BinaryFileResponse
    {
        $this->authorizeAudio($audioTrack);

        $absolutePath = Storage::disk('private')->path($audioTrack->audio_file);

        return response()->file($absolutePath, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="'.$audioTrack->slug.'.mp3"',
        ]);
    }

    public function saveProgress(Request $request, AudioTrack $audioTrack): JsonResponse
    {
        $this->authorizeAudio($audioTrack);

        $validated = $request->validate([
            'progress_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $progress = UserAudioProgress::query()->firstOrCreate([
            'user_id' => Auth::id(),
            'audio_track_id' => $audioTrack->id,
        ]);

        $seconds = $validated['progress_seconds'];
        $total = $audioTrack->durationSeconds();
        $completed = $total && $seconds >= max(1, $total - 5);

        $progress->update([
            'progress_seconds' => max($progress->progress_seconds, $seconds),
            'completed' => $progress->completed || $completed,
            'last_played_at' => now(),
        ]);

        return response()->json([
            'progress_seconds' => $progress->progress_seconds,
            'completed' => $progress->completed,
            'completion_percent' => $progress->completionPercent($audioTrack),
        ]);
    }

    public function markComplete(AudioTrack $audioTrack): RedirectResponse
    {
        $this->authorizeAudio($audioTrack);

        $progress = UserAudioProgress::query()->firstOrCreate([
            'user_id' => Auth::id(),
            'audio_track_id' => $audioTrack->id,
        ]);

        $total = $audioTrack->durationSeconds();

        $progress->update([
            'completed' => true,
            'progress_seconds' => $total ?: max($progress->progress_seconds, 1),
            'last_played_at' => now(),
        ]);

        return back()->with('success', 'Audio marcado como escuchado.');
    }

    private function authorizeAudio(AudioTrack $audioTrack): void
    {
        if (! $audioTrack->isPublished() || ! $audioTrack->hasAudioFile()) {
            abort(404);
        }

        if (! Auth::user()->hasAccessToAudioTrack($audioTrack)) {
            abort(403);
        }
    }
}
