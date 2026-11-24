<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;

class ProcessReadingPlans extends Command
{
    protected $signature = 'reading-plans:process';

    protected $description = '読書計画のステータス更新とリマインダー通知を実行する';

    /**
     * 進行中および期限切れの読書計画を確認し、
     * 目標日に応じてステータス更新と通知を行う。
     *
     * 目標日の3日前と当日にリマインダー通知を送る。
     * 目標日の翌日以降は進行中の計画を期限切れに変更する。
     * 目標日の3日後には期限切れの計画に期限超過通知を送る。
     */
    public function handle(): int
    {
        $readingPlans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->whereIn('status', [
                ReadingPlanStatus::InProgress->value,
                ReadingPlanStatus::Expired->value,
            ])
            ->get();

        foreach ($readingPlans as $readingPlan) {
            $daysFromTarget = today()->diffInDays(
                $readingPlan->target_date,
                false
            );

            if ($readingPlan->status === ReadingPlanStatus::InProgress) {
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

                if ($daysFromTarget <= -1) {
                    $readingPlan->update([
                        'status' => ReadingPlanStatus::Expired,
                    ]);
                }
            }

            if (
                $readingPlan->status === ReadingPlanStatus::Expired
                && $daysFromTarget === -3
            ) {
                $this->notify(
                    $readingPlan,
                    'three_days_after'
                );
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