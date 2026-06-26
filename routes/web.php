<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Members\AudioController;
use App\Http\Controllers\Members\DashboardController;
use App\Http\Controllers\Members\LibraryController;
use App\Http\Controllers\Members\MaterialController;
use App\Http\Controllers\Members\MaterialPdfController;
use App\Http\Controllers\Members\ProgressController;
use App\Http\Controllers\Members\VideoController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/como-acceder', [PageController::class, 'howToAccess'])->name('pages.how-to-access');
Route::get('/preguntas-frecuentes', [PageController::class, 'faq'])->name('pages.faq');

Route::get('/dashboard', fn () => redirect()->route('members.dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->prefix('mi-biblioteca')->name('members.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/libros', [LibraryController::class, 'index'])->name('library');
    Route::get('/libros/api/libros', [LibraryController::class, 'books'])->name('library.books');
    Route::get('/libros/api/{book}/{chapter}', [LibraryController::class, 'chapter'])
        ->where(['book' => '[A-Za-z0-9]+', 'chapter' => '[0-9]+'])
        ->name('library.chapter');
    Route::post('/libros/progreso', [LibraryController::class, 'saveProgress'])->name('library.progress');
    Route::get('/progreso', [ProgressController::class, 'index'])->name('progress');
    Route::get('/escuchar', [AudioController::class, 'index'])->name('audio.index');
    Route::get('/escuchar/{audioTrack}', [AudioController::class, 'show'])->name('audio.show');
    Route::get('/escuchar/{audioTrack}/stream', [AudioController::class, 'stream'])->name('audio.stream');
    Route::post('/escuchar/{audioTrack}/progress', [AudioController::class, 'saveProgress'])->name('audio.progress');
    Route::post('/escuchar/{audioTrack}/complete', [AudioController::class, 'markComplete'])->name('audio.complete');
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::get('/videos/{video}/stream', [VideoController::class, 'stream'])->name('videos.stream');
    Route::post('/videos/{video}/progress', [VideoController::class, 'saveProgress'])->name('videos.progress');
    Route::post('/videos/{video}/complete', [VideoController::class, 'markComplete'])->name('videos.complete');
    Route::get('/materiales', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/materiales/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::get('/materiales/{material}/leer', [MaterialPdfController::class, 'reader'])->name('materials.pdf.reader');
    Route::post('/materiales/{material}/progreso-lectura', [MaterialPdfController::class, 'saveReadingProgress'])->name('materials.pdf.progress');
    Route::get('/materiales/{material}/pdf', [MaterialPdfController::class, 'stream'])->name('materials.pdf.stream');
    Route::get('/materiales/{material}/descargar', [MaterialPdfController::class, 'download'])->name('materials.pdf.download');
    Route::post('/materiales/{material}/estudiado', [MaterialController::class, 'toggleStudied'])->name('materials.toggle-studied');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
