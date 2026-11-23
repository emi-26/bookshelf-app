<?php

namespace Tests\Feature\Api\V1;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiBearerTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_book_with_bearer_token(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '小説',
        ]);

        // テスト用のBearerトークンを発行する
        $token = $user->createToken('test-api-token')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/books', [
                'title' => 'Bearer認証テスト書籍',
                'author' => 'テスト著者',
                'isbn' => '9781234567890',
                'published_date' => null,
                'description' => '認証動作確認用',
                'image_url' => null,
                'genres' => [$genre->id],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Bearer認証テスト書籍');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Bearer認証テスト書籍',
            'isbn' => '9781234567890',
        ]);
    }
}
