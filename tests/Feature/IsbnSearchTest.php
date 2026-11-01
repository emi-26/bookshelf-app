<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search_isbn(): void
    {
        $response = $this->get(
            route('books.isbn.show', '9784101010014')
        );

        $response->assertRedirect('/login');
    }

    public function test_isbn_must_be_13_digits(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.isbn.show', '123'));

        $response->assertStatus(422);

        $response->assertJson([
            'error' => 'ISBNは13桁で入力してください。',
        ]);
    }

    public function test_book_information_can_be_fetched_by_isbn(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => [
                                '著者A',
                                '著者B',
                            ],
                            'description' => 'テスト説明',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                            'publishedDate' => '2026-10-01',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.isbn.show', '9784101010014'));

        $response->assertOk();

        $response->assertJson([
            'title' => 'テスト書籍',
            'author' => '著者A, 著者B',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
            'published_date' => '2026-10-01',
        ]);
    }

    public function test_error_is_returned_when_book_is_not_found(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.isbn.show', '9784101010014'));

        $response->assertNotFound();

        $response->assertJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    public function test_error_is_returned_when_google_books_api_fails(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response(
                [],
                500
            ),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.isbn.show', '9784101010014'));

        $response->assertStatus(502);

        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }
}
