<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->genre = Genre::create([
            'name' => '小説',
        ]);

        $this->book = Book::create([
            'user_id' => $this->user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-20',
            'description' => 'テスト用の説明です。',
            'image_url' => null,
        ]);

        $this->book->genres()->attach($this->genre->id);
    }

    public function test_guest_cannot_create_review(): void
    {
        $response = $this->post(
            route('reviews.store', $this->book),
            [
                'rating' => 5,
                'comment' => 'テストレビュー',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $this->book->id,
            'comment' => 'テストレビュー',
        ]);
    }

    public function test_authenticated_user_can_create_review(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(
                route('reviews.store', $this->book),
                [
                    'rating' => 5,
                    'comment' => 'テストレビュー',
                ]
            );

        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);
    }

    public function test_rating_is_required(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(
                route('reviews.store', $this->book),
                [
                    'rating' => null,
                    'comment' => 'テストレビュー',
                ]
            );

        $response->assertSessionHasErrors('rating');
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(
                route('reviews.store', $this->book),
                [
                    'rating' => 6,
                    'comment' => 'テストレビュー',
                ]
            );

        $response->assertSessionHasErrors('rating');
    }

    public function test_comment_is_required(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(
                route('reviews.store', $this->book),
                [
                    'rating' => 5,
                    'comment' => '',
                ]
            );

        $response->assertSessionHasErrors('comment');
    }

    public function test_same_user_cannot_review_same_book_twice(): void
    {
        Review::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'rating' => 4,
            'comment' => '1回目のレビュー',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->post(
                route('reviews.store', $this->book),
                [
                    'rating' => 5,
                    'comment' => '2回目のレビュー',
                ]
            );

        $response->assertRedirect(route('books.show', $this->book));

        $response->assertSessionHas(
            'error',
            'この書籍にはすでにレビューを投稿しています。'
        );

        $this->assertDatabaseCount('reviews', 1);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => '2回目のレビュー',
        ]);
    }
}
