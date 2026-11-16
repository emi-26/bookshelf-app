<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_users_plan_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($owner);

        $response = $this
            ->actingAs($otherUser)
            ->get(route('reading-plans.edit', $plan));

        $response->assertForbidden();
    }

    public function test_user_cannot_complete_another_users_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($owner);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('reading-plans.complete', $plan));

        $response->assertForbidden();

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $plan->status
        );

        $this->assertNull($plan->completed_at);
    }

    public function test_user_cannot_delete_another_users_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($owner);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $plan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'user_id' => $owner->id,
        ]);
    }

    private function createPlan(User $user): ReadingPlan
    {
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '認可テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9789876543888',
            'published_date' => '2026-10-01',
            'description' => null,
            'image_url' => null,
        ]);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }
}
