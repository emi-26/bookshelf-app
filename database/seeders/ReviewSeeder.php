<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $comments = [
            5 => [
                '素晴らしい本でした！',
                '人生が変わりました。',
                '何度も読み返しています。',
            ],
            4 => [
                'とても参考になりました。',
                '読みやすくておすすめです。',
                '期待通りの内容でした。',
            ],
            3 => [
                '普通でした。',
                '可もなく不可もなく。',
                '期待したほどではなかった。',
            ],
            2 => [
                '少し期待外れでした。',
                '内容が薄い印象。',
                'もう少し深掘りしてほしかった。',
            ],
            1 => [
                '残念ながら合いませんでした。',
                '期待と違いました。',
            ],
        ];

        // 各書籍2〜4件、合計32件になるまで件数を決める
        do {
            $reviewCounts = [];

            foreach ($books as $book) {
                $reviewCounts[] = rand(2, 4);
            }
        } while (array_sum($reviewCounts) !== 32);

        foreach ($books as $index => $book) {
            $reviewCount = $reviewCounts[$index];
            $selectedUsers = $users->random($reviewCount);

            foreach ($selectedUsers as $user) {
                $rating = rand(1, 5);

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => collect($comments[$rating])->random(),
                ]);
            }
        }
    }
}
