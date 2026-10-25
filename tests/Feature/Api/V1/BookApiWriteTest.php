<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiWriteTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->genre = Genre::create([
            'name' => '小説',
        ]);
    }

    public function test_guest_cannot_create_book(): void
    {
        $response = $this->postJson('/api/v1/books', $this->bookData());

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_book(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/books', $this->bookData());

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'APIテスト書籍')
            ->assertJsonPath('data.genres.0.name', '小説');

        $this->assertDatabaseHas('books', [
            'user_id' => $this->owner->id,
            'isbn' => '9781234567890',
        ]);
    }

    public function test_book_creation_requires_required_fields(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/books', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);
    }

    public function test_owner_can_update_book(): void
    {
        $book = $this->createBook($this->owner);

        Sanctum::actingAs($this->owner);

        $data = $this->bookData();
        $data['title'] = '更新後の書籍';

        $response = $this->putJson(
            '/api/v1/books/'.$book->id,
            $data
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.title', '更新後の書籍');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後の書籍',
        ]);
    }

    public function test_other_user_cannot_update_book(): void
    {
        $book = $this->createBook($this->owner);

        Sanctum::actingAs($this->otherUser);

        $response = $this->putJson(
            '/api/v1/books/'.$book->id,
            $this->bookData()
        );

        $response->assertForbidden();
    }

    public function test_owner_can_delete_book(): void
    {
        $book = $this->createBook($this->owner);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson('/api/v1/books/'.$book->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $book = $this->createBook($this->owner);

        Sanctum::actingAs($this->otherUser);

        $response = $this->deleteJson('/api/v1/books/'.$book->id);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    private function createBook(User $user): Book
    {
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '既存書籍',
            'author' => '既存著者',
            'isbn' => '9781234567891',
            'published_date' => '2026-10-25',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($this->genre->id);

        return $book;
    }

    private function bookData(): array
    {
        return [
            'title' => 'APIテスト書籍',
            'author' => 'API著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-10-25',
            'description' => 'APIテストです。',
            'image_url' => null,
            'genres' => [$this->genre->id],
        ];
    }
}
