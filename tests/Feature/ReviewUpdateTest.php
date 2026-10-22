<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateTest extends TestCase
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
            'comment' => '編集前レビュー',
        ]);
    }

    public function test_guest_cannot_access_review_edit_page(): void
    {
        $response = $this->get(
            route('reviews.edit', $this->review)
        );

        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_access_review_edit_page(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(
                route('reviews.edit', $this->review)
            );

        $response->assertOk();
        $response->assertSee('編集前レビュー');
    }

    public function test_other_user_cannot_access_review_edit_page(): void
    {
        $response = $this
            ->actingAs($this->otherUser)
            ->get(
                route('reviews.edit', $this->review)
            );

        $response->assertForbidden();
    }

    public function test_owner_can_update_review(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(
                route('reviews.update', $this->review),
                [
                    'rating' => 5,
                    'comment' => '編集後レビュー',
                ]
            );

        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'rating' => 5,
            'comment' => '編集後レビュー',
        ]);
    }

    public function test_other_user_cannot_update_review(): void
    {
        $response = $this
            ->actingAs($this->otherUser)
            ->put(
                route('reviews.update', $this->review),
                [
                    'rating' => 5,
                    'comment' => '他人が変更',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'rating' => 4,
            'comment' => '編集前レビュー',
        ]);
    }

    public function test_rating_is_required_when_updating_review(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(
                route('reviews.update', $this->review),
                [
                    'rating' => null,
                    'comment' => '編集後レビュー',
                ]
            );

        $response->assertSessionHasErrors('rating');
    }

    public function test_comment_is_required_when_updating_review(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->put(
                route('reviews.update', $this->review),
                [
                    'rating' => 5,
                    'comment' => '',
                ]
            );

        $response->assertSessionHasErrors('comment');
    }
}
