<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\ReviewLikeSeeder;
use Database\Seeders\ReviewSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(GenreSeeder::class);
        $this->seed(BookSeeder::class);
        $this->seed(ReviewSeeder::class);
        $this->seed(ReviewLikeSeeder::class);
    }

    public function test_reviews_are_seeded_according_to_requirements(): void
    {
        $this->assertSame(5, User::count());
        $this->assertSame(11, Book::count());
        $this->assertSame(32, Review::count());

        foreach (Book::all() as $book) {
            $reviewCount = $book->reviews()->count();

            $this->assertGreaterThanOrEqual(2, $reviewCount);
            $this->assertLessThanOrEqual(4, $reviewCount);
        }

        foreach (Review::all() as $review) {
            $this->assertGreaterThanOrEqual(1, $review->rating);
            $this->assertLessThanOrEqual(5, $review->rating);
        }
    }

    public function test_review_likes_are_seeded_according_to_requirements(): void
    {
        $reviews = Review::with('likedByUsers')->get();

        foreach ($reviews as $review) {
            $likedUserIds = $review->likedByUsers
                ->pluck('id');

            $this->assertLessThanOrEqual(
                3,
                $likedUserIds->count()
            );

            $this->assertFalse(
                $likedUserIds->contains($review->user_id)
            );

            $this->assertSame(
                $likedUserIds->count(),
                $likedUserIds->unique()->count()
            );
        }

        $likes = DB::table('review_likes')->get();

        $uniqueLikes = $likes->unique(
            fn ($like) => $like->user_id.'-'.$like->review_id
        );

        $this->assertCount(
            $likes->count(),
            $uniqueLikes
        );
    }
}
