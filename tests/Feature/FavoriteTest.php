<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        $this->book = Book::create([
            'user_id' => $this->otherUser->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567897',
            'published_date' => '2026-10-25',
            'description' => 'テスト用の説明です。',
            'image_url' => null,
        ]);

        $this->book->genres()->attach($genre->id);
    }

    public function test_guest_cannot_view_favorites(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_own_favorites(): void
    {
        $this->user->favoriteBooks()->attach($this->book->id);

        $response = $this
            ->actingAs($this->user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('テスト書籍');
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $response = $this->post(
            route('favorites.toggle', $this->book)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
        ]);
    }

    public function test_authenticated_user_can_add_favorite(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(
                route('favorites.toggle', $this->book)
            );

        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
        ]);
    }

    public function test_authenticated_user_can_remove_favorite(): void
    {
        $this->user->favoriteBooks()->attach($this->book->id);

        $response = $this
            ->actingAs($this->user)
            ->post(
                route('favorites.toggle', $this->book)
            );

        $response->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
        ]);
    }

    public function test_toggle_does_not_create_duplicate_favorites(): void
    {
        $this->actingAs($this->user)
            ->post(route('favorites.toggle', $this->book));

        $this->actingAs($this->user)
            ->post(route('favorites.toggle', $this->book));

        $this->actingAs($this->user)
            ->post(route('favorites.toggle', $this->book));

        $this->assertDatabaseCount('favorites', 1);
    }
}
