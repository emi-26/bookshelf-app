<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\IsbnController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

// ゲストでも閲覧できる
Route::get('/', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/books/{book}', [BookController::class, 'show'])
    ->whereNumber('book')
    ->name('books.show');

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

// ログインが必要
Route::middleware('auth')->group(function () {
    Route::get('/books/isbn/{isbn}', [IsbnController::class, 'show'])
        ->name('books.isbn.show');

    Route::resource('books', BookController::class)
        ->except(['index', 'show']);

    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    Route::resource('genres', GenreController::class);

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');

    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
        ->name('reading-plans.create');

    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
        ->name('reading-plans.store');

    Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])
        ->name('reading-plans.edit');

    Route::put('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'update'])
        ->name('reading-plans.update');

    Route::post('/reading-plans/{readingPlan}/complete', [ReadingPlanController::class, 'complete'])
        ->name('reading-plans.complete');

    Route::delete('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'destroy'])
        ->name('reading-plans.destroy');

    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::post('/{review}/like', [ReviewLikeController::class, 'toggle'])
            ->name('like');

        Route::get('/{review}/edit', [ReviewController::class, 'edit'])
            ->name('edit');

        Route::put('/{review}', [ReviewController::class, 'update'])
            ->name('update');

        Route::delete('/{review}', [ReviewController::class, 'destroy'])
            ->name('destroy');
    });
});
