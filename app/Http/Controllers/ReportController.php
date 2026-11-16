<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * ログインユーザーのレビューを集計し、読書レポートを表示する。
     *
     * レビュー数、読書冊数、平均評価、評価分布、
     * 高評価書籍、ジャンル別評価を取得する。
     */
    public function index(): View
    {
        $reviews = Review::query()
            ->with(['book.genres'])
            ->where('user_id', auth()->id())
            ->get();

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews->pluck('book_id')->unique()->count(),
                'average_rating' => $reviews->avg('rating') ?? 0,
            ],
            'rating_distribution' => $this->ratingDistribution($reviews),
            'top_rated_books' => $this->topRatedBooks($reviews),
            'genre_ratings' => $this->genreRatings($reviews),
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * 評価1〜5のそれぞれについて、レビュー件数を集計する。
     */
    private function ratingDistribution(Collection $reviews): Collection
    {
        return collect(range(1, 5))
            ->map(fn (int $rating) => $reviews
                ->where('rating', $rating)
                ->count());
    }

    /**
     * 評価4以上のレビューから、評価の高い上位5件の書籍情報を取得する。
     */
    private function topRatedBooks(Collection $reviews): Collection
    {
        return $reviews
            ->where('rating', '>=', 4)
            ->sortByDesc('rating')
            ->take(5)
            ->map(fn (Review $review) => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values();
    }

    /**
     * レビューをジャンルごとに集計し、平均評価の上位5件を取得する。
     *
     * 複数ジャンルに属する書籍のレビューは、
     * 紐づく各ジャンルの評価集計に含める。
     */
    private function genreRatings(Collection $reviews): Collection
    {
        return $reviews
            ->flatMap(function (Review $review) {
                return $review->book->genres->map(fn ($genre) => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'rating' => $review->rating,
                ]);
            })
            ->groupBy('id')
            ->map(function (Collection $items) {
                return [
                    'id' => $items->first()['id'],
                    'name' => $items->first()['name'],
                    'count' => $items->count(),
                    'average_rating' => $items->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();
    }
}
