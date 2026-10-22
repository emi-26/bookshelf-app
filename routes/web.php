<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// ゲストでも閲覧できる
Route::get('/', [BookController::class, 'index'])->name('books.index');

Route::get('/books/{book}', [BookController::class, 'show'])
    ->whereNumber('book')
    ->name('books.show');

Route::view('/ranking', 'ranking.index')->name('ranking.index');

// ログインが必要
Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)
        ->except(['index', 'show']);

    Route::view('/favorites', 'favorites.index')->name('favorites.index');

    Route::resource('genres', GenreController::class);

    // 以下は各機能を実装するまでの仮ルート
    Route::post('/books/{book}/favorite', function () {
        return back();
    })->name('favorites.toggle');

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
