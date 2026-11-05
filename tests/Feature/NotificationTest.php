<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownNotification = $this->createNotification(
            $user,
            '自分の通知'
        );

        $this->createNotification(
            $otherUser,
            '他人の通知'
        );

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('自分の通知');
        $response->assertDontSee('他人の通知');

        $this->assertSame(
            $user->id,
            $ownNotification->notifiable_id
        );
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = $this->createNotification(
            $user,
            '既読テスト'
        );

        $this->assertNull($notification->read_at);

        $response = $this
            ->actingAs($user)
            ->post(
                route('notifications.read', $notification->id)
            );

        $response->assertRedirect(route('notifications.index'));

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $notification = $this->createNotification(
            $otherUser,
            '他人の通知'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('notifications.read', $notification->id)
            );

        $response->assertNotFound();

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }

    private function createNotification(
        User $user,
        string $title
    ): DatabaseNotification {
        return DatabaseNotification::create([
            'id' => fake()->uuid(),
            'type' => 'test-notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'title' => $title,
                'body' => 'テスト通知です。',
                'timing' => 'three_days_before',
            ],
            'read_at' => null,
        ]);
    }
}
