<?php

namespace App\Http\Controllers\Members;

use App\Enums\MaterialType;
use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\View\View;

class BonusController extends Controller
{
    public function index(): View
    {
        $bonuses = Material::query()
            ->published()
            ->where('type', MaterialType::Bonus)
            ->with(['category', 'plan'])
            ->orderBy('sort_order')
            ->get();

        $user = auth()->user();
        $progressByMaterial = $user->materialProgress()
            ->whereIn('material_id', $bonuses->pluck('id'))
            ->get()
            ->keyBy('material_id');

        return view('members.bonuses.index', compact(
            'bonuses',
            'progressByMaterial',
        ));
    }
}
