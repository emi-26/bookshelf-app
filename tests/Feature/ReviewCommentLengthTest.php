<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCommentLengthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->book = Book::create([
            'user_id' => $this->user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-20',
            'description' => null,
            'image_url' => null,
        ]);

        $this->review = Review::create([
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'rating' => 4,
            'comment' => '編集前レビュー',
        ]);
    }

    public function test_comment_with_1000_characters_can_be_registered(): void
    {
        $otherBook = Book::create([
            'user_id' => $this->user->id,
            'title' => '別のテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567898',
            'published_date' => '2026-10-20',
            'description' => null,
            'image_url' => null,
        ]);

        $comment = str_repeat('あ', 1000);

        $response = $this
            ->actingAs($this->user)
            ->post(route('reviews.store', $otherBook), [
                'rating' => 5,
                'comment' => $comment,
            ]);

        $response->assertRedirect(route('books.show', $otherBook));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $otherBook->id,
            'comment' => $comment,
        ]);
    }

    public function test_comment_over_1000_characters_cannot_be_registered(): void
    {
        $otherBook = Book::create([
            'user_id' => $this->user->id,
            'title' => '別のテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567898',
            'published_date' => '2026-10-20',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->post(route('reviews.store', $otherBook), [
                'rating' => 5,
                'comment' => str_repeat('あ', 1001),
            ]);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $otherBook->id,
        ]);
    }

    public function test_comment_with_1000_characters_can_be_updated(): void
    {
        $comment = str_repeat('あ', 1000);

        $response = $this
            ->actingAs($this->user)
            ->put(route('reviews.update', $this->review), [
                'rating' => 5,
                'comment' => $comment,
            ]);

        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'comment' => $comment,
        ]);
    }

    public function test_comment_over_1000_characters_cannot_be_updated(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(route('reviews.update', $this->review), [
                'rating' => 5,
                'comment' => str_repeat('あ', 1001),
            ]);

        $response->assertSessionHasErrors('comment');

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'rating' => 4,
            'comment' => '編集前レビュー',
        ]);
    }
}
