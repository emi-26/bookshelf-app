<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private ReadingPlan $readingPlan,
        private string $timing
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'timing' => $this->timing,
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
        ];
    }

    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書期限の3日前です',
            'on_due_date' => '読書期限当日です',
            'three_days_after' => '読書期限を3日過ぎています',
            default => '読書計画のお知らせ',
        };
    }

    private function body(): string
    {
        $bookTitle = $this->readingPlan->book->title;

        return match ($this->timing) {
            'three_days_before' => "「{$bookTitle}」の読書期限まであと3日です。",
            'on_due_date' => "「{$bookTitle}」の読書期限は今日です。",
            'three_days_after' => "「{$bookTitle}」の読書期限を3日過ぎています。",
            default => "「{$bookTitle}」の読書計画を確認してください。",
        };
    }
}
