<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $reviewer;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->reviewer = User::factory()->create();

        $book = Book::create([
            'user_id' => $this->reviewer->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-25',
            'description' => null,
            'image_url' => null,
        ]);

        $this->review = Review::create([
            'user_id' => $this->reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);
    }

    public function test_guest_cannot_like_review(): void
    {
        $response = $this->post(
            route('reviews.like', $this->review)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $this->user->id,
            'review_id' => $this->review->id,
        ]);
    }

    public function test_authenticated_user_can_like_review(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(
                route('reviews.like', $this->review)
            );

        $response->assertRedirect();

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $this->user->id,
            'review_id' => $this->review->id,
        ]);
    }

    public function test_authenticated_user_can_remove_review_like(): void
    {
        $this->user->likedReviews()->attach($this->review->id);

        $response = $this
            ->actingAs($this->user)
            ->post(
                route('reviews.like', $this->review)
            );

        $response->assertRedirect();

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $this->user->id,
            'review_id' => $this->review->id,
        ]);
    }

    public function test_toggle_does_not_create_duplicate_review_likes(): void
    {
        $this->actingAs($this->user)
            ->post(route('reviews.like', $this->review));

        $this->actingAs($this->user)
            ->post(route('reviews.like', $this->review));

        $this->actingAs($this->user)
            ->post(route('reviews.like', $this->review));

        $this->assertDatabaseCount('review_likes', 1);
    }
}
