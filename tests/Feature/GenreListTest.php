<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreListTest extends TestCase
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

    public function test_guest_cannot_access_genre_list(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_genre_list(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('小説');
        $response->assertSee('0冊');
    }

    public function test_authenticated_user_can_view_genre_detail(): void
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
            ->get(route('genres.show', $this->genre));

        $response->assertOk();
        $response->assertSee('小説');
        $response->assertSee('テスト書籍');
    }
}
