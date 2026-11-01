<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
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

    private function ratingDistribution(Collection $reviews): Collection
    {
        return collect(range(1, 5))
            ->map(fn (int $rating) => $reviews
                ->where('rating', $rating)
                ->count());
    }

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
