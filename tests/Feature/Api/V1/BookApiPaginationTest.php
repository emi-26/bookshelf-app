<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_list_uses_default_twenty_per_page(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        for ($i = 0; $i < 21; $i++) {
            $book = Book::create([
                'user_id' => $user->id,
                'title' => 'テスト書籍'.$i,
                'author' => 'テスト著者',
                'isbn' => '97812345678'.sprintf('%02d', $i),
                'published_date' => '2026-10-25',
                'description' => null,
                'image_url' => null,
            ]);

            $book->genres()->attach($genre->id);
        }

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.last_page', 2);
    }
}
