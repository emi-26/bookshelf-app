<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_genre_create_page(): void
    {
        $response = $this->get(route('genres.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_genre_create_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('genres.create'));

        $response->assertOk();
        $response->assertSee('ジャンル登録');
    }

    public function test_authenticated_user_can_create_genre(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('genres.store'), [
                'name' => '漫画',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => '漫画',
        ]);
    }

    public function test_genre_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('genres.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_genre_name_must_be_unique(): void
    {
        Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('genres.store'), [
                'name' => '小説',
            ]);

        $response->assertSessionHasErrors('name');
    }
}
