<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MaterialCheckoutController extends Controller
{
    public function redirect(Material $material): RedirectResponse
    {
        session(['pending_upsell_material_id' => $material->id]);

        $checkoutUrl = $material->plan?->products->where('is_active', true)->first()?->checkout_url
            ?? Setting::get('checkout_completo_url');

        if (! $checkoutUrl) {
            return back()->with('error', 'No hay un enlace de pago configurado para este material.');
        }

        return redirect()->away($checkoutUrl);
    }

    public function pending(): View
    {
        $materialId = session('pending_upsell_material_id');
        $material = $materialId ? Material::find($materialId) : null;

        return view('members.materials.pending-payment', compact('material'));
    }

    public function checkAccess(Material $material): JsonResponse
    {
        $unlocked = Auth::user()->fresh()->hasAccessToMaterial($material);

        if ($unlocked) {
            session()->forget('pending_upsell_material_id');
        }

        return response()->json(['unlocked' => $unlocked]);
    }
}
