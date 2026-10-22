<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $genre = Genre::create([
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

        $this->book->genres()->attach($genre->id);

        $this->review = Review::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'rating' => 4,
            'comment' => '削除テストレビュー',
        ]);
    }

    public function test_guest_cannot_delete_review(): void
    {
        $response = $this->delete(
            route('reviews.destroy', $this->review)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
        ]);
    }

    public function test_owner_can_delete_review(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->delete(
                route('reviews.destroy', $this->review)
            );

        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $this->review->id,
        ]);
    }

    public function test_other_user_cannot_delete_review(): void
    {
        $response = $this
            ->actingAs($this->otherUser)
            ->delete(
                route('reviews.destroy', $this->review)
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
        ]);
    }
}
