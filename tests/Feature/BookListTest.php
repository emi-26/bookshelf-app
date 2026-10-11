<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookListTest extends TestCase
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

    public function test_guest_can_view_book_list(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_guest_can_view_book_detail(): void
    {
        $book = $this->createBook();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee($book->title);
    }

    private function createBook(): Book
    {
        $book = Book::create([
            'user_id' => $this->user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-11',
            'description' => 'テスト用の説明です。',
            'image_url' => null,
        ]);

        $book->genres()->attach($this->genre->id);

        return $book;
    }
}
