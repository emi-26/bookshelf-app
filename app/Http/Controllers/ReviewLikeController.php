<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewLikeController extends Controller
{
    /**
     * ログインユーザーのレビューへのいいね・解除を切り替える。
     */
    public function toggle(Review $review): RedirectResponse
    {
        Auth::user()
            ->likedReviews()
            ->toggle($review->id);

        return back();
    }
}
