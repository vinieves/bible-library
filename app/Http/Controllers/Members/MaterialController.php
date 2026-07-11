<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\UserMaterialProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(): View
    {
        $materials = Material::query()
            ->published()
            ->with(['category', 'plan'])
            ->orderBy('sort_order')
            ->get();

        $user = Auth::user();
        $progressByMaterial = $user->materialProgress()
            ->whereIn('material_id', $materials->pluck('id'))
            ->get()
            ->keyBy('material_id');

        return view('members.materials.index', compact('materials', 'progressByMaterial'));
    }

    public function show(Material $material): View
    {
        if (! $material->isPublished()) {
            abort(404);
        }

        $user = Auth::user();

        if (! $user->hasAccessToMaterial($material)) {
            return view('members.materials.locked', [
                'material' => $material,
            ]);
        }

        $progress = UserMaterialProgress::query()->firstOrCreate([
            'user_id' => $user->id,
            'material_id' => $material->id,
        ]);

        return view('members.materials.show', compact('material', 'progress'));
    }

    public function toggleStudied(Material $material): RedirectResponse
    {
        $this->authorizeMaterial($material);

        $progress = UserMaterialProgress::query()->firstOrCreate([
            'user_id' => Auth::id(),
            'material_id' => $material->id,
        ]);

        $progress->update([
            'is_studied' => ! $progress->is_studied,
            'studied_at' => ! $progress->is_studied ? now() : null,
        ]);

        return back()->with('success', $progress->is_studied
            ? 'Material marcado como estudiado.'
            : 'Material desmarcado.');
    }

    private function authorizeMaterial(Material $material): void
    {
        if (! $material->isPublished()) {
            abort(404);
        }

        if (! Auth::user()->hasAccessToMaterial($material)) {
            abort(403);
        }
    }

}
