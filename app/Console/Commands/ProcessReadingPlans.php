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

    /**
     * 進行中の読書計画を確認し、目標日に応じて通知・期限切れ処理を行う。
     *
     * 目標日の3日前と当日に通知する。
     * 目標日の3日後以降は期限切れに変更し、
     * 3日後ちょうどの場合のみ期限超過通知を送る。
     */
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

            if ($daysFromTarget <= -3) {
                $readingPlan->update([
                    'status' => ReadingPlanStatus::Expired,
                ]);

                if ($daysFromTarget === -3) {
                    $this->notify(
                        $readingPlan,
                        'three_days_after'
                    );
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * 同じ読書計画・通知タイミングの通知が未作成の場合のみ送信する。
     *
     * 通知データのreading_plan_idとtimingを照合し、
     * バッチ再実行による通知の重複作成を防止する。
     */
    private function notify(
        ReadingPlan $readingPlan,
        string $timing
    ): void {
        $alreadyNotified = $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $readingPlan->user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                $timing
            )
        );
    }
}
