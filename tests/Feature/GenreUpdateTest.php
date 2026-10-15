<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreUpdateTest extends TestCase
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

    public function test_guest_cannot_access_genre_edit_page(): void
    {
        $response = $this->get(route('genres.edit', $this->genre));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_genre_edit_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('genres.edit', $this->genre));

        $response->assertOk();
        $response->assertSee('ジャンル編集');
        $response->assertSee('漫画');
    }

    public function test_authenticated_user_can_update_genre(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('genres.update', $this->genre), [
                'name' => 'コミック',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $this->genre->id,
            'name' => 'コミック',
        ]);
    }

    public function test_genre_can_be_updated_without_changing_its_name(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('genres.update', $this->genre), [
                'name' => '漫画',
            ]);

        $response->assertSessionDoesntHaveErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $this->genre->id,
            'name' => '漫画',
        ]);
    }

    public function test_genre_cannot_be_updated_to_existing_name(): void
    {
        Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('genres.update', $this->genre), [
                'name' => '小説',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $this->genre->id,
            'name' => '漫画',
        ]);
    }
}
