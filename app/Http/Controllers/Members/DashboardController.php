<?php

namespace App\Http\Controllers\Members;

use App\Enums\MaterialType;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\UserMaterialProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $recentProgress = UserMaterialProgress::query()
            ->where('user_id', $user->id)
            ->whereHas('material', fn ($query) => $query->published())
            ->with(['material.category', 'material.plan'])
            ->where('is_studied', false)
            ->where('last_page_read', '>', 0)
            ->latest('updated_at')
            ->first();

        if (! $recentProgress) {
            $recentProgress = UserMaterialProgress::query()
                ->where('user_id', $user->id)
                ->whereHas('material', fn ($query) => $query->published())
                ->with(['material.category', 'material.plan'])
                ->latest('updated_at')
                ->first();
        }

        $recentMaterial = $recentProgress?->material ?? Material::query()
            ->published()
            ->where('type', MaterialType::Libro)
            ->orderBy('sort_order')
            ->first();

        $totalPublished = Material::query()
            ->published()
            ->whereIn('type', [MaterialType::Libro, MaterialType::Bonus])
            ->count();

        $studiedCount = $user->materialProgress()
            ->where('is_studied', true)
            ->whereHas('material', fn ($query) => $query
                ->published()
                ->whereIn('type', [MaterialType::Libro, MaterialType::Bonus]))
            ->count();

        $progressPercent = $totalPublished > 0
            ? (int) round(($studiedCount / $totalPublished) * 100)
            : 0;

        return view('members.dashboard', compact(
            'recentMaterial',
            'recentProgress',
            'studiedCount',
            'totalPublished',
            'progressPercent',
        ));
    }
}
