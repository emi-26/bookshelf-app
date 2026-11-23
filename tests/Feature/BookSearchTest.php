<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $novelGenre;

    private Genre $businessGenre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->novelGenre = Genre::create([
            'name' => '小説',
        ]);

        $this->businessGenre = Genre::create([
            'name' => 'ビジネス',
        ]);
    }

    public function test_books_can_be_searched_by_title_or_author(): void
    {
        $this->createBook(
            'Laravel入門',
            '山田太郎',
            '9781234567890',
            $this->novelGenre
        );

        $this->createBook(
            'PHP入門',
            '鈴木花子',
            '9781234567891',
            $this->businessGenre
        );

        $books = $this->getBooks([
            'keyword' => 'Laravel',
        ]);

        $this->assertCount(1, $books);
        $this->assertSame('Laravel入門', $books->first()->title);

        $books = $this->getBooks([
            'keyword' => '鈴木',
        ]);

        $this->assertCount(1, $books);
        $this->assertSame('PHP入門', $books->first()->title);
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $this->createBook(
            '小説の本',
            '著者A',
            '9781234567890',
            $this->novelGenre
        );

        $this->createBook(
            'ビジネスの本',
            '著者B',
            '9781234567891',
            $this->businessGenre
        );

        $books = $this->getBooks([
            'genre' => $this->novelGenre->id,
        ]);

        $this->assertCount(1, $books);
        $this->assertSame('小説の本', $books->first()->title);
    }

    private function getBooks(array $parameters): Collection
    {
        $response = $this->get('/books?'.http_build_query($parameters));

        $response->assertOk();
        $response->assertViewIs('books.index');

        return $response->viewData('books')->getCollection();
    }

    private function createBook(
        string $title,
        string $author,
        string $isbn,
        Genre $genre
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

        $book->genres()->attach($genre->id);

        return $book;
    }
}
