<?php

namespace App\Http\Controllers\Members;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $categoryId = $request->integer('categoria') ?: null;

        $materials = Material::query()
            ->published()
            ->where('type', MaterialType::Libro)
            ->with(['category', 'plan'])
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('sort_order')
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('materials', fn ($query) => $query->where('status', MaterialStatus::Published)->where('type', MaterialType::Libro))
            ->orderBy('sort_order')
            ->get();

        $user = Auth::user();
        $progressByMaterial = $user->materialProgress()
            ->whereIn('material_id', $materials->pluck('id'))
            ->get()
            ->keyBy('material_id');

        return view('members.library.index', compact(
            'materials',
            'categories',
            'search',
            'categoryId',
            'progressByMaterial',
        ));
    }
}
