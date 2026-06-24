<?php

namespace App\Services;

use App\DataTransferObjects\MemberActivityItem;
use App\Enums\MaterialType;
use App\Models\Material;
use App\Models\User;
use App\Models\UserAudioProgress;
use App\Models\UserBibleProgress;
use App\Models\UserMaterialProgress;
use App\Models\UserVideoProgress;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MemberProgressService
{
    public const MONTHLY_VERSE_GOAL = 30;

    /**
     * @return list<array{title: string, subtitle: string, href: string, icon: string, accent: string, material: null}>
     */
    public function continueCards(User $user): array
    {
        $cards = [];

        $reading = $this->readingContinueCard($user);

        if ($reading) {
            $cards[] = $reading;
        }

        $watching = $this->videoContinueCard($user);

        if ($watching) {
            $cards[] = $watching;
        }

        $listening = $this->audioContinueCard($user);

        if ($listening) {
            $cards[] = $listening;
        }

        return $cards;
    }

    public function lastActivityAt(User $user): ?CarbonInterface
    {
        $dates = collect([
            UserBibleProgress::query()->where('user_id', $user->id)->value('updated_at'),
            UserVideoProgress::query()->where('user_id', $user->id)->max('last_played_at'),
            UserAudioProgress::query()->where('user_id', $user->id)->max('last_played_at'),
            UserMaterialProgress::query()->where('user_id', $user->id)->max('updated_at'),
        ])->filter();

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates->map(fn ($date) => \Illuminate\Support\Carbon::parse($date))->max();
    }

    /**
     * @return Collection<int, MemberActivityItem>
     */
    public function activityTimeline(User $user): Collection
    {
        $items = collect();

        $bible = UserBibleProgress::query()->where('user_id', $user->id)->first();

        if ($bible) {
            $items->push(new MemberActivityItem(
                type: 'bible',
                title: $bible->bookName(),
                subtitle: 'Capítulo '.$bible->chapter.($bible->verse ? ' · Versículo '.$bible->verse : ''),
                percent: $bible->completionPercent(),
                completed: false,
                activityAt: $bible->updated_at,
                url: $this->bibleUrl($bible),
            ));
        }

        UserMaterialProgress::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('is_studied', true)
                    ->orWhere('last_page_read', '>', 0);
            })
            ->whereHas('material', fn ($query) => $query
                ->published()
                ->where('type', MaterialType::Libro))
            ->with('material')
            ->get()
            ->each(function (UserMaterialProgress $progress) use ($items) {
                $material = $progress->material;

                if (! $material) {
                    return;
                }

                $items->push(new MemberActivityItem(
                    type: 'material',
                    title: $material->title,
                    subtitle: $progress->statusLabel($material),
                    percent: $progress->completionPercent($material),
                    completed: $progress->is_studied,
                    activityAt: $progress->updated_at,
                    url: $material->hasPdf()
                        ? route('members.materials.pdf.reader', $material)
                        : route('members.materials.show', $material),
                ));
            });

        UserVideoProgress::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('completed', true)
                    ->orWhere('progress_seconds', '>', 0);
            })
            ->with('video')
            ->get()
            ->each(function (UserVideoProgress $progress) use ($items) {
                $video = $progress->video;

                if (! $video?->isPublished()) {
                    return;
                }

                $items->push(new MemberActivityItem(
                    type: 'video',
                    title: $video->title,
                    subtitle: $progress->statusLabel($video),
                    percent: $progress->completionPercent($video),
                    completed: $progress->completed,
                    activityAt: $progress->last_played_at ?? $progress->updated_at,
                    url: route('members.videos.show', $video),
                ));
            });

        UserAudioProgress::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('completed', true)
                    ->orWhere('progress_seconds', '>', 0);
            })
            ->with('audioTrack')
            ->get()
            ->each(function (UserAudioProgress $progress) use ($items) {
                $track = $progress->audioTrack;

                if (! $track) {
                    return;
                }

                $items->push(new MemberActivityItem(
                    type: 'audio',
                    title: $track->title,
                    subtitle: $progress->statusLabel($track),
                    percent: $progress->completionPercent($track),
                    completed: $progress->completed,
                    activityAt: $progress->last_played_at ?? $progress->updated_at,
                    url: route('members.audio.show', $track),
                ));
            });

        return $items
            ->sortByDesc(fn (MemberActivityItem $item) => $item->activityAt->timestamp)
            ->values();
    }

    public function suggestedStartUrl(User $user): string
    {
        return route('members.library');
    }

    public function suggestedStartLabel(): string
    {
        return 'La Biblia Explicada';
    }

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

    private function readingContinueCard(User $user): ?array
    {
        $bible = UserBibleProgress::query()->where('user_id', $user->id)->first();

        if ($bible && $bible->isInProgress()) {
            return [
                'title' => 'Continuar leyendo',
                'subtitle' => $bible->chapterLabel(),
                'href' => $this->bibleUrl($bible),
                'icon' => '📖',
                'accent' => 'gold',
                'material' => null,
            ];
        }

        $materialProgress = UserMaterialProgress::query()
            ->where('user_id', $user->id)
            ->where('is_studied', false)
            ->where('last_page_read', '>', 0)
            ->whereHas('material', fn ($query) => $query
                ->published()
                ->where('type', MaterialType::Libro))
            ->with('material')
            ->latest('updated_at')
            ->first();

        if (! $materialProgress?->material) {
            return null;
        }

        $material = $materialProgress->material;
        $percent = $materialProgress->completionPercent($material);

        if ($percent <= 0 || $percent >= 100) {
            return null;
        }

        return [
            'title' => 'Continuar leyendo',
            'subtitle' => $material->title,
            'href' => $material->hasPdf()
                ? route('members.materials.pdf.reader', $material)
                : route('members.materials.show', $material),
            'icon' => '📖',
            'accent' => 'gold',
            'material' => $material,
        ];
    }

    private function videoContinueCard(User $user): ?array
    {
        $progress = UserVideoProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', false)
            ->where('progress_seconds', '>', 0)
            ->with('video')
            ->orderByDesc('last_played_at')
            ->first();

        $video = $progress?->video;

        if (! $video?->isPublished()) {
            return null;
        }

        $percent = $progress->completionPercent($video);

        if ($percent <= 0 || $percent >= 100) {
            return null;
        }

        return [
            'title' => 'Continuar viendo',
            'subtitle' => $video->title.' · '.$percent.'%',
            'href' => route('members.videos.show', $video),
            'icon' => '🎬',
            'accent' => 'green',
            'material' => null,
        ];
    }

    private function audioContinueCard(User $user): ?array
    {
        $progress = UserAudioProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', false)
            ->where('progress_seconds', '>', 0)
            ->with('audioTrack')
            ->orderByDesc('last_played_at')
            ->first();

        $track = $progress?->audioTrack;

        if (! $track) {
            return null;
        }

        $percent = $progress->completionPercent($track);

        if ($percent <= 0 || $percent >= 100) {
            return null;
        }

        return [
            'title' => 'Continuar escuchando',
            'subtitle' => $track->title.' · '.$percent.'%',
            'href' => route('members.audio.show', $track),
            'icon' => '🎧',
            'accent' => 'gold',
            'material' => null,
        ];
    }

    private function bibleUrl(UserBibleProgress $progress): string
    {
        return route('members.library', array_filter([
            'libro' => $progress->book_abbr,
            'capitulo' => $progress->chapter,
            'versiculo' => $progress->verse,
        ]));
    }
}
