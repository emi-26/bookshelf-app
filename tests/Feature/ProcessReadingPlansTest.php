<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReadingPlansTest extends TestCase
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

    public function test_notification_is_created_three_days_before_target_date(): void
    {
        $user = User::factory()->create();

        $this->createPlan(
            $user,
            today()->addDays(3)
        );

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );
    }

    public function test_notification_is_created_on_target_date(): void
    {
        $user = User::factory()->create();

        $this->createPlan(
            $user,
            today()
        );

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            'on_due_date',
            $notification->data['timing']
        );
    }

    public function test_plan_expires_and_notification_is_created_three_days_after_target_date(): void
    {
        $user = User::factory()->create();

        $plan = $this->createPlan(
            $user,
            today()->subDays(3)
        );

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $plan->status
        );

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );
    }

    public function test_completed_plan_is_not_processed(): void
    {
        $user = User::factory()->create();

        $plan = $this->createPlan(
            $user,
            today()->subDays(3),
            ReadingPlanStatus::Completed
        );

        $this->artisan('reading-plans:process')
            ->assertExitCode(0);

        $plan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $plan->status
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    private function createPlan(
        User $user,
        Carbon $targetDate,
        ReadingPlanStatus $status = ReadingPlanStatus::InProgress
    ): ReadingPlan {
        $book = $this->createBook($user);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate->format('Y-m-d'),
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
            'title' => "バッチテスト書籍{$number}",
            'author' => 'テスト著者',
            'isbn' => '9789876543'.str_pad(
                (string) $number,
                3,
                '0',
                STR_PAD_LEFT
            ),
            'published_date' => '2026-10-01',
            'description' => null,
            'image_url' => null,
        ]);
    }
}
