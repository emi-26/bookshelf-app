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

class ReadingPlanFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_plans_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();

        $pendingPlan = $this->createPlan(
            $user,
            ReadingPlanStatus::Pending
        );

        $this->createPlan(
            $user,
            ReadingPlanStatus::Completed
        );

        $this->actingAs($user);

        $request = Request::create('/reading-plans', 'GET', [
            'status' => ReadingPlanStatus::Pending->value,
        ]);

        $view = app(ReadingPlanController::class)->index($request);
        $readingPlans = $view->getData()['readingPlans'];

        $this->assertCount(1, $readingPlans);
        $this->assertSame($pendingPlan->id, $readingPlans->first()->id);
    }

    public function test_user_cannot_update_another_users_plan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = $this->createPlan(
            $otherUser,
            ReadingPlanStatus::Pending
        );

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => now()->addMonth()->format('Y-m-d'),
            ]);

        $response->assertForbidden();
    }

    private function createPlan(
        User $user,
        ReadingPlanStatus $status
    ): ReadingPlan {
        $book = $this->createBook($user);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->format('Y-m-d'),
            'status' => $status,
            'completed_at' => $status === ReadingPlanStatus::Completed
                ? now()
                : null,
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
            'isbn' => '9781234568'.str_pad(
                (string) $number,
                3,
                '0',
                STR_PAD_LEFT
            ),
            'published_date' => '2026-10-11',
            'description' => null,
            'image_url' => null,
        ]);
    }
}
