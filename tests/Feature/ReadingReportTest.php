<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_reading_report(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect('/login');
    }

    public function test_report_contains_only_authenticated_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBook = $this->createBook($user, '自分の本', '9781234567890');
        $otherBook = $this->createBook($otherUser, '他人の本', '9781234567891');

        $this->createReview($user, $ownBook, 5);
        $this->createReview($otherUser, $otherBook, 1);

        $stats = $this->getStats($user);

        $this->assertSame(1, $stats['summary']['total_reviews']);
        $this->assertSame(1, $stats['summary']['books_read']);
        $this->assertEquals(5.0, $stats['summary']['average_rating']);
    }

    public function test_report_contains_rating_distribution(): void
    {
        $user = User::factory()->create();

        foreach ([1, 2, 2, 5] as $index => $rating) {
            $book = $this->createBook(
                $user,
                "書籍{$index}",
                '97812345678'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)
            );

            $this->createReview($user, $book, $rating);
        }

        $distribution = $this->getStats($user)['rating_distribution'];

        $this->assertSame([1, 2, 0, 0, 1], $distribution->all());
    }

    public function test_report_contains_top_rated_books(): void
    {
        $user = User::factory()->create();

        foreach ([5, 4, 3] as $index => $rating) {
            $book = $this->createBook(
                $user,
                "書籍{$index}",
                '97812345679'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)
            );

            $this->createReview($user, $book, $rating);
        }

        $topRatedBooks = $this->getStats($user)['top_rated_books'];

        $this->assertCount(2, $topRatedBooks);
        $this->assertSame(5, $topRatedBooks->first()['rating']);
        $this->assertSame(4, $topRatedBooks->get(1)['rating']);
    }

    public function test_report_contains_genre_rating_statistics(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $book = $this->createBook(
            $user,
            'ジャンル付き書籍',
            '9781234567892'
        );

        $book->genres()->attach($genre->id);

        $this->createReview($user, $book, 4);

        $genreRatings = $this->getStats($user)['genre_ratings'];

        $this->assertCount(1, $genreRatings);
        $this->assertSame('小説', $genreRatings->first()['name']);
        $this->assertSame(1, $genreRatings->first()['count']);
        $this->assertEquals(4.0, $genreRatings->first()['average_rating']);
    }

    private function getStats(User $user): array
    {
        $this->actingAs($user);

        $view = app(ReportController::class)->index();

        return $view->getData()['stats'];
    }

    private function createReview(
        User $user,
        Book $book,
        int $rating
    ): Review {
        return Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $rating,
            'comment' => 'テストレビューです。',
        ]);
    }

    private function createBook(
        User $user,
        string $title,
        string $isbn
    ): Book {
        return Book::create([
            'user_id' => $user->id,
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => $isbn,
            'published_date' => '2026-10-11',
            'description' => null,
            'image_url' => null,
        ]);
    }
}
