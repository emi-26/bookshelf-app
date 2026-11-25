<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * ログインユーザーのレビューを登録する。
     *
     * 同一ユーザーによる同一書籍への重複投稿を防止し、
     * 投稿済みの場合はエラーメッセージを返す。
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $alreadyReviewed = $book->reviews()
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyReviewed) {
            return redirect()
                ->route('books.show', $book)
                ->with('error', 'この書籍にはすでにレビューを投稿しています。');
        }

        $book->reviews()->create([
            'user_id' => Auth::id(),
            'rating' => $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }
}
