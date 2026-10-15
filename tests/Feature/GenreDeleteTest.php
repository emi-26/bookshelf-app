<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->genre = Genre::create([
            'name' => '漫画',
        ]);
    }

    public function test_guest_cannot_delete_genre(): void
    {
        $response = $this->delete(route('genres.destroy', $this->genre));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $this->genre->id,
        ]);
    }

    public function test_authenticated_user_can_delete_unused_genre(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('genres.destroy', $this->genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $this->genre->id,
        ]);
    }

    public function test_genre_linked_to_book_cannot_be_deleted(): void
    {
        $book = Book::create([
            'user_id' => $this->user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-15',
            'description' => 'テスト用の説明です。',
            'image_url' => null,
        ]);

        $book->genres()->attach($this->genre->id);

        $response = $this->actingAs($this->user)
            ->delete(route('genres.destroy', $this->genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $this->genre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);
    }
}
