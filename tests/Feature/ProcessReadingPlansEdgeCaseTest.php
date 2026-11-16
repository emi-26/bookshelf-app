<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReadingPlansEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-05 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_three_days_before_notification_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        $this->createPlan($user, today()->addDays(3));

        $this->artisan('reading-plans:process')->assertExitCode(0);
        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(
            'three_days_before',
            $user->notifications()->first()->data['timing']
        );
    }

    public function test_on_due_date_notification_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        $this->createPlan($user, today());

        $this->artisan('reading-plans:process')->assertExitCode(0);
        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(
            'on_due_date',
            $user->notifications()->first()->data['timing']
        );
    }

    public function test_plan_expires_when_four_days_past_target_date(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, today()->subDays(4));

        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    private function createPlan(
        User $user,
        Carbon $targetDate
    ): ReadingPlan {
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '追加テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9789876543999',
            'published_date' => '2026-10-01',
            'description' => null,
            'image_url' => null,
        ]);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate->format('Y-m-d'),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }
}
