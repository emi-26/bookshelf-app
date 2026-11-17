<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSortValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $oldBook = Book::create([
            'user_id' => $user->id,
            'title' => '古い本',
            'author' => '著者A',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
        ]);

        $newBook = Book::create([
            'user_id' => $user->id,
            'title' => '新しい本',
            'author' => '著者B',
            'isbn' => '9781234567891',
            'published_date' => '2026-10-01',
        ]);

        $oldBook->forceFill([
            'created_at' => '2026-01-01 00:00:00',
        ])->save();

        $newBook->forceFill([
            'created_at' => '2026-10-01 00:00:00',
        ])->save();
    }

    public function test_latest_sorts_books_by_newest_first(): void
    {
        $response = $this->get('/books?sort=latest');

        $response
            ->assertOk()
            ->assertSeeInOrder(['新しい本', '古い本']);
    }

    public function test_default_sort_is_latest(): void
    {
        $response = $this->get('/books');

        $response
            ->assertOk()
            ->assertSeeInOrder(['新しい本', '古い本']);
    }

    public function test_invalid_sort_is_rejected(): void
    {
        $response = $this->get('/books?sort=abc');

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('sort');
    }
}
