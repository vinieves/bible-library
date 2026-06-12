<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\UserMaterialProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialPdfController extends Controller
{
    public function reader(Material $material): View
    {
        $this->authorizePdf($material);

        $progress = UserMaterialProgress::query()->firstOrCreate([
            'user_id' => Auth::id(),
            'material_id' => $material->id,
        ]);

        return view('members.materials.reader', compact('material', 'progress'));
    }

    public function saveReadingProgress(Request $request, Material $material): JsonResponse
    {
        $this->authorizePdf($material);

        $validated = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
            'total_pages' => ['required', 'integer', 'min:1'],
        ]);

        $page = min($validated['page'], $validated['total_pages']);
        $totalPages = $validated['total_pages'];

        if (! $material->pdf_page_count) {
            $material->update(['pdf_page_count' => $totalPages]);
            $material->refresh();
        }

        $progress = UserMaterialProgress::query()->firstOrCreate([
            'user_id' => Auth::id(),
            'material_id' => $material->id,
        ]);

        $lastPageRead = max($progress->last_page_read, $page);
        $updates = [
            'current_page' => $page,
            'last_page_read' => $lastPageRead,
        ];

        $knownTotal = (int) ($material->pdf_page_count ?: $totalPages);
        if ($lastPageRead >= $knownTotal && ! $progress->is_studied) {
            $updates['is_studied'] = true;
            $updates['studied_at'] = now();
        }

        $progress->update($updates);
        $progress->refresh();

        return response()->json([
            'current_page' => $progress->current_page,
            'last_page_read' => $progress->last_page_read,
            'total_pages' => $knownTotal,
            'completion_percent' => $progress->completionPercent($material),
            'is_studied' => $progress->is_studied,
        ]);
    }

    public function stream(Material $material): BinaryFileResponse
    {
        $this->authorizePdf($material);

        $absolutePath = Storage::disk('private')->path($material->pdf_path);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->downloadFilename($material).'"',
        ]);
    }

    public function download(Material $material): StreamedResponse
    {
        $this->authorizePdf($material);

        return Storage::disk('private')->download(
            $material->pdf_path,
            $this->downloadFilename($material)
        );
    }

    private function authorizePdf(Material $material): void
    {
        if (! $material->isPublished() || ! $material->hasPdf()) {
            abort(404);
        }

        if (! Auth::user()->hasAccessToMaterial($material)) {
            abort(403);
        }
    }

    private function downloadFilename(Material $material): string
    {
        return Str::slug($material->title).'.pdf';
    }
}
