<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Http\Controllers\ReadingPlanController;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reading_plans(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect('/login');
    }

    public function test_user_can_create_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->addWeek()->format('Y-m-d'),
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Pending->value,
        ]);
    }

    public function test_past_target_date_cannot_be_registered(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->subDay()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('target_date');
        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_index_contains_only_authenticated_users_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPlan = $this->createPlan($user);
        $this->createPlan($otherUser);

        $this->actingAs($user);

        $request = Request::create('/reading-plans', 'GET');

        $view = app(ReadingPlanController::class)->index($request);
        $readingPlans = $view->getData()['readingPlans'];

        $this->assertCount(1, $readingPlans);
        $this->assertSame($ownPlan->id, $readingPlans->first()->id);
    }

    public function test_user_can_update_target_date(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user);

        $newDate = now()->addMonth()->format('Y-m-d');

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => $newDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newDate,
        ]);
    }

    public function test_user_can_complete_reading_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertNotNull($plan->completed_at);
    }

    public function test_user_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user);

        $response = $this
            ->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    private function createPlan(User $user): ReadingPlan
    {
        $book = $this->createBook($user);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->format('Y-m-d'),
            'status' => ReadingPlanStatus::Pending,
            'completed_at' => null,
        ]);
    }

    private function createBook(User $user): Book
    {
        static $number = 0;
        $number++;

        return Book::create([
            'user_id' => $user->id,
            'title' => "テスト書籍{$number}",
            'author' => 'テスト著者',
            'isbn' => '9781234567'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'published_date' => '2026-10-11',
            'description' => null,
            'image_url' => null,
        ]);
    }
}
