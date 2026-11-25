<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * 書籍の平均評価に基づくランキングを表示する。
     *
     * レビューが投稿されている書籍を対象に、
     * 平均評価の降順で上位10件を取得する。
     */
    public function index(): View
    {
        $rankedBooks = Book::query()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->has('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
