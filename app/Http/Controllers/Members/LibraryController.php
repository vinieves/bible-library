<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Services\BibleReaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(BibleReaderService $bible): View
    {
        return view('members.library.index', [
            'bibleAvailable' => $bible->isAvailable(),
        ]);
    }

    public function books(BibleReaderService $bible): JsonResponse
    {
        return response()->json($bible->booksIndex());
    }

    public function chapter(string $book, int $chapter, BibleReaderService $bible): JsonResponse
    {
        $data = $bible->chapter($book, $chapter);

        if ($data === null) {
            return response()->json(['message' => 'Capítulo no encontrado.'], 404);
        }

        return response()->json($data);
    }
}
