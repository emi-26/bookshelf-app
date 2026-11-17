<?php

namespace Tests\Feature;

use App\Http\Controllers\BookController;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BookSortTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $novelGenre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->novelGenre = Genre::create([
            'name' => '小説',
        ]);
    }

    public function test_books_can_be_sorted_by_created_at(): void
    {
        $oldBook = $this->createBook(
            '古い本',
            '著者A',
            '9781234567890'
        );

        $newBook = $this->createBook(
            '新しい本',
            '著者B',
            '9781234567891'
        );

        $oldBook->forceFill([
            'created_at' => '2026-01-01 00:00:00',
        ])->save();

        $newBook->forceFill([
            'created_at' => '2026-10-01 00:00:00',
        ])->save();

        $newest = $this->getBooks([
            'sort' => 'latest',
        ]);

        $this->assertSame($newBook->id, $newest->first()->id);

        $oldest = $this->getBooks([
            'sort' => 'oldest',
        ]);

        $this->assertSame($oldBook->id, $oldest->first()->id);
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        $this->createBook(
            'Zの本',
            '著者A',
            '9781234567890'
        );

        $this->createBook(
            'Aの本',
            '著者B',
            '9781234567891'
        );

        $books = $this->getBooks([
            'sort' => 'title',
        ]);

        $this->assertSame('Aの本', $books->first()->title);
    }

    public function test_books_can_be_sorted_by_rating(): void
    {
        $highBook = $this->createBook(
            '高評価の本',
            '著者A',
            '9781234567890'
        );

        $lowBook = $this->createBook(
            '低評価の本',
            '著者B',
            '9781234567891'
        );

        $noReviewBook = $this->createBook(
            'レビューなし',
            '著者C',
            '9781234567892'
        );

        Review::create([
            'user_id' => $this->user->id,
            'book_id' => $highBook->id,
            'rating' => 5,
            'comment' => '高評価です。',
        ]);

        Review::create([
            'user_id' => $this->user->id,
            'book_id' => $lowBook->id,
            'rating' => 2,
            'comment' => '低評価です。',
        ]);

        $books = $this->getBooks([
            'sort' => 'rating',
        ]);

        $this->assertSame($highBook->id, $books->get(0)->id);
        $this->assertSame($lowBook->id, $books->get(1)->id);
        $this->assertSame($noReviewBook->id, $books->get(2)->id);
    }

    private function getBooks(array $parameters)
    {
        $request = Request::create('/', 'GET', $parameters);

        $view = app(BookController::class)->index($request);

        return $view->getData()['books']->getCollection();
    }

    private function createBook(
        string $title,
        string $author,
        string $isbn
    ): Book {
        $book = Book::create([
            'user_id' => $this->user->id,
            'title' => $title,
            'author' => $author,
            'isbn' => $isbn,
            'published_date' => '2026-10-11',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($this->novelGenre->id);

        return $book;
    }
}
