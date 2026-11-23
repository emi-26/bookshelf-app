<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            // レビュー投稿者をいいね対象から除外する
            $eligibleUserIds = $users
                ->where('id', '!=', $review->user_id)
                ->pluck('id')
                ->shuffle();

            // 各レビューに0〜3人のいいねを設定する
            $likeCount = random_int(
                0,
                min(3, $eligibleUserIds->count())
            );

            $selectedUserIds = $eligibleUserIds
                ->take($likeCount)
                ->all();

            // 既存の関連を維持しながらいいねを登録する
            $review->likedByUsers()
                ->syncWithoutDetaching($selectedUserIds);
        }
    }
}
