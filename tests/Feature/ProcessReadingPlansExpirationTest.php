<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReadingPlansExpirationTest extends TestCase
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

    public function test_plan_expires_one_day_after_target_date(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, today()->subDay());

        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_plan_expires_two_days_after_target_date(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, today()->subDays(2));

        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_expired_plan_receives_notification_three_days_after_target_date(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, today()->subDay());

        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        $this->assertDatabaseCount('notifications', 0);

        Carbon::setTestNow('2026-11-06 12:00:00');

        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        $this->assertDatabaseCount('notifications', 0);

        Carbon::setTestNow('2026-11-07 12:00:00');

        $this->artisan('reading-plans:process')->assertExitCode(0);

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->fresh()->status
        );

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );

        $this->assertSame(
            $plan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertDatabaseCount('notifications', 1);
    }

    private function createPlan(
        User $user,
        Carbon $targetDate
    ): ReadingPlan {
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '期限切れテスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9789876543888',
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
