<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Models\ForumPostReaction;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ForumController extends Controller
{
    public function index(): View
    {
        $posts = ForumPost::query()
            ->published()
            ->with('persona')
            ->withCount('reactions')
            ->orderByDesc('created_at')
            ->get();

        $reactedPostIds = ForumPostReaction::query()
            ->where('user_id', Auth::id())
            ->whereIn('forum_post_id', $posts->pluck('id'))
            ->pluck('forum_post_id')
            ->flip();

        return view('members.forum.index', compact('posts', 'reactedPostIds'));
    }

    public function react(ForumPost $forumPost): JsonResponse
    {
        if (! $forumPost->isPublished()) {
            abort(404);
        }

        $userId = Auth::id();

        $existing = ForumPostReaction::query()
            ->where('forum_post_id', $forumPost->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $reacted = false;
        } else {
            try {
                ForumPostReaction::query()->create([
                    'forum_post_id' => $forumPost->id,
                    'user_id' => $userId,
                ]);
                $reacted = true;
            } catch (QueryException) {
                $reacted = true;
            }
        }

        return response()->json([
            'reacted' => $reacted,
            'count' => $forumPost->reactions()->count(),
        ]);
    }

    public function streamAudio(ForumPost $forumPost): BinaryFileResponse
    {
        if (! $forumPost->isPublished() || ! $forumPost->hasAudioFile()) {
            abort(404);
        }

        $absolutePath = Storage::disk('private')->path($forumPost->audio_file);

        return response()->file($absolutePath, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="post-'.$forumPost->id.'.mp3"',
        ]);
    }
}
