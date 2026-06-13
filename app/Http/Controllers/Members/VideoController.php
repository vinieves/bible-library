<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\UserVideoProgress;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $videos = Video::query()
            ->published()
            ->with(['category', 'requiredPlan'])
            ->orderBy('order')
            ->get();

        $progressByVideo = $user->videoProgress()
            ->whereIn('video_id', $videos->pluck('id'))
            ->get()
            ->keyBy('video_id');

        return view('members.videos.index', [
            'videos' => $videos,
            'progressByVideo' => $progressByVideo,
            'subscriptionTitle' => Setting::get('audio_subscription_title', 'Biblioteca Bíblica Digital'),
            'checkoutUrl' => Setting::get('audio_subscription_checkout_url', '#'),
        ]);
    }

    public function show(Video $video): View|RedirectResponse
    {
        if (! $video->isPublished()) {
            abort(404);
        }

        $user = Auth::user();

        if (! $user->hasAccessToVideo($video)) {
            return view('members.videos.locked', [
                'video' => $video,
                'checkoutUrl' => $video->checkoutUrl(),
                'subscriptionTitle' => Setting::get('audio_subscription_title', 'Biblioteca Bíblica Digital'),
            ]);
        }

        $progress = UserVideoProgress::query()->firstOrCreate([
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        return view('members.videos.show', compact('video', 'progress'));
    }

    public function stream(Video $video): BinaryFileResponse
    {
        $this->authorizeVideo($video);

        $absolutePath = Storage::disk('private')->path($video->video_file);

        return response()->file($absolutePath, [
            'Content-Type' => $video->streamMimeType(),
            'Content-Disposition' => 'inline; filename="'.$video->streamFilename().'"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-transform',
        ]);
    }

    public function saveProgress(Request $request, Video $video): JsonResponse
    {
        $this->authorizeVideo($video);

        $validated = $request->validate([
            'progress_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $progress = UserVideoProgress::query()->firstOrCreate([
            'user_id' => Auth::id(),
            'video_id' => $video->id,
        ]);

        $seconds = $validated['progress_seconds'];
        $total = $video->durationSeconds();
        $completed = $total && $seconds >= max(1, $total - 5);

        $progress->update([
            'progress_seconds' => max($progress->progress_seconds, $seconds),
            'completed' => $progress->completed || $completed,
            'last_played_at' => now(),
        ]);

        return response()->json([
            'progress_seconds' => $progress->progress_seconds,
            'completed' => $progress->completed,
            'completion_percent' => $progress->completionPercent($video),
        ]);
    }

    public function markComplete(Video $video): RedirectResponse
    {
        $this->authorizeVideo($video);

        $progress = UserVideoProgress::query()->firstOrCreate([
            'user_id' => Auth::id(),
            'video_id' => $video->id,
        ]);

        $total = $video->durationSeconds();

        $progress->update([
            'completed' => true,
            'progress_seconds' => $total ?: max($progress->progress_seconds, 1),
            'last_played_at' => now(),
        ]);

        return back()->with('success', 'Video marcado como visto.');
    }

    private function authorizeVideo(Video $video): void
    {
        if (! $video->isPublished() || ! $video->hasVideoFile()) {
            abort(404);
        }

        if (! Auth::user()->hasAccessToVideo($video)) {
            abort(403);
        }
    }
}
