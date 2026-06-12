<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\UserMaterialProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $studied = UserMaterialProgress::query()
            ->where('user_id', $user->id)
            ->where('is_studied', true)
            ->with('material.category')
            ->latest('studied_at')
            ->get();

        $totalPublished = Material::query()->published()->count();
        $studiedCount = $studied->count();
        $progressPercent = $totalPublished > 0
            ? (int) round(($studiedCount / $totalPublished) * 100)
            : 0;

        return view('members.progress.index', compact('studied', 'studiedCount', 'totalPublished', 'progressPercent'));
    }
}
