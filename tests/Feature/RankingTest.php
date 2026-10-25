<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_ranking(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
    }

    public function test_books_are_ranked_by_average_rating_descending(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();

        $highRatedBook = Book::create([
            'user_id' => $user->id,
            'title' => '高評価の本',
            'author' => '著者A',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-25',
            'description' => null,
            'image_url' => null,
        ]);

        $lowRatedBook = Book::create([
            'user_id' => $user->id,
            'title' => '低評価の本',
            'author' => '著者B',
            'isbn' => '9781234567898',
            'published_date' => '2026-10-25',
            'description' => null,
            'image_url' => null,
        ]);

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
            'comment' => '高評価レビュー',
        ]);

        Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 3,
            'comment' => '低評価レビュー',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertSeeInOrder([
            '高評価の本',
            '低評価の本',
        ]);
    }

    public function test_book_without_reviews_is_not_displayed(): void
    {
        $user = User::factory()->create();

        Book::create([
            'user_id' => $user->id,
            'title' => 'レビューなしの本',
            'author' => '著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-25',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertDontSee('レビューなしの本');
    }

    public function test_ranking_displays_only_top_ten_books(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            $book = Book::create([
                'user_id' => $user->id,
                'title' => "ランキング本{$i}",
                'author' => '著者',
                'isbn' => '97812345678'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'published_date' => '2026-10-25',
                'description' => null,
                'image_url' => null,
            ]);

            Review::create([
                'user_id' => $reviewer->id,
                'book_id' => $book->id,
                'rating' => 5,
                'comment' => 'レビュー',
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertViewHas('rankedBooks', function ($rankedBooks) {
            return $rankedBooks->count() === 10;
        });
    }
}
