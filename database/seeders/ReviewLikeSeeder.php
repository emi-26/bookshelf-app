<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\ReviewLike;
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

        $likes = [
            [0, 0],
            [1, 0],
            [2, 1],
            [3, 1],
            [4, 2],
            [0, 3],
            [1, 4],
            [2, 5],
            [3, 6],
            [4, 7],
            [0, 8],
            [1, 9],
            [2, 10],
            [3, 11],
            [4, 12],
        ];

        foreach ($likes as [$userIndex, $reviewIndex]) {
            ReviewLike::firstOrCreate([
                'user_id' => $users[$userIndex]->id,
                'review_id' => $reviews[$reviewIndex]->id,
            ]);
        }
    }
}
