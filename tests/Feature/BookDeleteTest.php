<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookDeleteTest extends TestCase
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

    public function test_owner_can_delete_book(): void
    {
        $book = $this->createBook();

        $response = $this->actingAs($this->user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $book = $this->createBook();

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_related_data_is_deleted_when_book_is_deleted(): void
    {
        $book = $this->createBook();

        $otherUser = User::factory()->create();

        $reviewId = DB::table('reviews')->insertGetId([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('favorites')->insert([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('review_likes')->insert([
            'user_id' => $this->user->id,
            'review_id' => $reviewId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $reviewId,
        ]);
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
