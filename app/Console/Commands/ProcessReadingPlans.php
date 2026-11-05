<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;

class ProcessReadingPlans extends Command
{
    protected $signature = 'reading-plans:process';

    protected $description = '読書計画の期限確認とリマインダー通知を実行する';

    public function handle(): int
    {
        $readingPlans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->get();

        foreach ($readingPlans as $readingPlan) {
            $daysFromTarget = today()->diffInDays(
                $readingPlan->target_date,
                false
            );

            if ($daysFromTarget === 3) {
                $this->notify(
                    $readingPlan,
                    'three_days_before'
                );
            }

            if ($daysFromTarget === 0) {
                $this->notify(
                    $readingPlan,
                    'on_due_date'
                );
            }

            if ($daysFromTarget === -3) {
                $readingPlan->update([
                    'status' => ReadingPlanStatus::Expired,
                ]);

                $this->notify(
                    $readingPlan,
                    'three_days_after'
                );
            }
        }

        return self::SUCCESS;
    }

    private function notify(
        ReadingPlan $readingPlan,
        string $timing
    ): void {
        $readingPlan->user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                $timing
            )
        );
    }
}
