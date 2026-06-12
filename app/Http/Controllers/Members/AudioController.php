<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
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
    public function index(): View
    {
        $user = Auth::user();

        $freeTracks = AudioTrack::query()
            ->published()
            ->where('is_free', true)
            ->with('category')
            ->orderBy('order')
            ->get();

        $premiumTracks = AudioTrack::query()
            ->published()
            ->where('is_premium', true)
            ->with(['category', 'requiredPlan'])
            ->orderBy('order')
            ->get();

        $progressByTrack = $user->audioProgress()
            ->whereIn('audio_track_id', $freeTracks->pluck('id')->merge($premiumTracks->pluck('id')))
            ->get()
            ->keyBy('audio_track_id');

        return view('members.audio.index', [
            'freeTracks' => $freeTracks,
            'premiumTracks' => $premiumTracks,
            'progressByTrack' => $progressByTrack,
            'subscriptionTitle' => Setting::get('audio_subscription_title', 'Biblioteca Bíblica en Audio'),
            'subscriptionPrice' => Setting::get('audio_subscription_price', 'USD $4.90/mes'),
            'checkoutUrl' => Setting::get('audio_subscription_checkout_url', '#'),
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
