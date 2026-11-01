<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未完了',
            self::Completed => '完了',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-100 text-yellow-800',
            self::Completed => 'bg-green-100 text-green-800',
        };
    }
}
