<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// ゲストでも閲覧できる
Route::get('/', [BookController::class, 'index'])->name('books.index');

Route::get('/books/{book}', [BookController::class, 'show'])
    ->whereNumber('book')
    ->name('books.show');

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

// ログインが必要
Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)
        ->except(['index', 'show']);

    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    Route::resource('genres', GenreController::class);

    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::post('/{review}/like', function () {
            return back();
        })->name('like');

        Route::get('/{review}/edit', [ReviewController::class, 'edit'])
            ->name('edit');

        Route::put('/{review}', [ReviewController::class, 'update'])
            ->name('update');

        Route::delete('/{review}', [ReviewController::class, 'destroy'])
            ->name('destroy');
    });
});
