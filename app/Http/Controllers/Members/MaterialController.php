<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Setting;
use App\Models\UserMaterialProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function show(Material $material): View
    {
        if (! $material->isPublished()) {
            abort(404);
        }

        $user = Auth::user();

        if (! $user->hasAccessToMaterial($material)) {
            return view('members.materials.locked', [
                'material' => $material,
                'checkoutUrl' => $this->checkoutUrlForPlan($material->plan?->slug),
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

    private function checkoutUrlForPlan(?string $slug): ?string
    {
        return match ($slug) {
            'basico' => Setting::get('checkout_basico_url'),
            'completo' => Setting::get('checkout_completo_url'),
            'premium', 'vitalicio' => Setting::get('checkout_premium_url'),
            default => Setting::get('checkout_premium_url'),
        };
    }
}
