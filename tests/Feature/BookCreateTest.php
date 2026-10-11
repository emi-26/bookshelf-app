<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->genre = Genre::create([
            'name' => '小説',
        ]);
    }

    public function test_guest_cannot_access_book_create_page(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('books.store'), [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-10-11',
                'description' => 'テスト用の説明です。',
                'image_url' => 'https://example.com/book.jpg',
                'genres' => [$this->genre->id],
            ]);

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'title' => 'テスト書籍',
            'isbn' => '9781234567897',
        ]);

        $book = Book::where('isbn', '9781234567897')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_book_creation_requires_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('books.store'), []);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ]);
    }

    public function test_book_creation_requires_thirteen_digit_isbn(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('books.store'), [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '123456789',
                'published_date' => '2026-10-11',
                'genres' => [$this->genre->id],
            ]);

        $response->assertSessionHasErrors('isbn');
    }

    public function test_book_creation_requires_unique_isbn(): void
    {
        Book::create([
            'user_id' => $this->user->id,
            'title' => '既存書籍',
            'author' => '既存著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-11',
            'description' => null,
            'image_url' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('books.store'), [
                'title' => '別の書籍',
                'author' => '別の著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-10-11',
                'genres' => [$this->genre->id],
            ]);

        $response->assertSessionHasErrors('isbn');
    }

    public function test_book_creation_requires_at_least_one_genre(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('books.store'), [
                'title' => 'テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-10-11',
                'genres' => [],
            ]);

        $response->assertSessionHasErrors('genres');
    }
}
