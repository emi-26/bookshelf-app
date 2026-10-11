<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
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

    public function test_owner_can_access_book_edit_page(): void
    {
        $book = $this->createBook();

        $response = $this->actingAs($this->user)
            ->get(route('books.edit', $book));

        $response->assertOk();
        $response->assertSee($book->title);
    }

    public function test_other_user_cannot_access_book_edit_page(): void
    {
        $book = $this->createBook();

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    public function test_owner_can_update_book(): void
    {
        $book = $this->createBook();

        $response = $this->actingAs($this->user)
            ->put(route('books.update', $book), [
                'title' => '更新後の書籍',
                'author' => '更新後の著者',
                'isbn' => $book->isbn,
                'published_date' => '2026-10-12',
                'description' => '更新後の説明です。',
                'image_url' => null,
                'genres' => [$this->genre->id],
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
        ]);
    }

    public function test_other_user_cannot_update_book(): void
    {
        $book = $this->createBook();

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->put(route('books.update', $book), [
                'title' => '不正更新',
                'author' => '別ユーザー',
                'isbn' => $book->isbn,
                'published_date' => '2026-10-12',
                'genres' => [$this->genre->id],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正更新',
        ]);
    }

    public function test_book_update_allows_current_isbn(): void
    {
        $book = $this->createBook();

        $response = $this->actingAs($this->user)
            ->put(route('books.update', $book), [
                'title' => $book->title,
                'author' => $book->author,
                'isbn' => $book->isbn,
                'published_date' => $book->published_date,
                'description' => $book->description,
                'image_url' => $book->image_url,
                'genres' => [$this->genre->id],
            ]);

        $response->assertSessionDoesntHaveErrors('isbn');
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
