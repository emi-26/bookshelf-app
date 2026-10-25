<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $novelGenre;

    private Genre $businessGenre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $this->novelGenre = Genre::create([
            'name' => '小説',
        ]);

        $this->businessGenre = Genre::create([
            'name' => 'ビジネス',
        ]);
    }

    public function test_guest_can_get_book_list(): void
    {
        $book = $this->createBook(
            'テスト書籍',
            'テスト著者',
            '9781234567890'
        );

        $book->genres()->attach($this->novelGenre->id);

        Review::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '面白かったです。',
        ]);

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'テスト書籍')
            ->assertJsonPath('data.0.genres.0.name', '小説')
            ->assertJsonPath('data.0.average_rating', 4)
            ->assertJsonPath('data.0.reviews_count', 1);
    }

    public function test_book_list_can_be_searched_by_keyword(): void
    {
        $firstBook = $this->createBook(
            'Laravel入門',
            '山田太郎',
            '9781234567890'
        );

        $secondBook = $this->createBook(
            'PHP入門',
            '鈴木花子',
            '9781234567891'
        );

        $firstBook->genres()->attach($this->novelGenre->id);
        $secondBook->genres()->attach($this->novelGenre->id);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel入門');
    }

    public function test_book_list_can_be_filtered_by_genre(): void
    {
        $novelBook = $this->createBook(
            '小説の本',
            '著者A',
            '9781234567890'
        );

        $businessBook = $this->createBook(
            'ビジネスの本',
            '著者B',
            '9781234567891'
        );

        $novelBook->genres()->attach($this->novelGenre->id);
        $businessBook->genres()->attach($this->businessGenre->id);

        $response = $this->getJson(
            '/api/v1/books?genre_id='.$this->novelGenre->id
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '小説の本');
    }

    public function test_guest_can_get_book_detail(): void
    {
        $book = $this->createBook(
            '詳細テスト書籍',
            '詳細テスト著者',
            '9781234567890'
        );

        $book->genres()->attach($this->novelGenre->id);

        Review::create([
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '最高でした。',
        ]);

        $response = $this->getJson('/api/v1/books/'.$book->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', '詳細テスト書籍')
            ->assertJsonPath('data.genres.0.name', '小説')
            ->assertJsonPath('data.average_rating', 5)
            ->assertJsonPath('data.reviews_count', 1)
            ->assertJsonPath('data.reviews.0.user.name', '山田太郎')
            ->assertJsonPath('data.reviews.0.rating', 5)
            ->assertJsonPath('data.reviews.0.comment', '最高でした。');
    }

    private function createBook(
        string $title,
        string $author,
        string $isbn
    ): Book {
        return Book::create([
            'user_id' => $this->user->id,
            'title' => $title,
            'author' => $author,
            'isbn' => $isbn,
            'published_date' => '2026-10-25',
            'description' => null,
            'image_url' => null,
        ]);
    }
}
