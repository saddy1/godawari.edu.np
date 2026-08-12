<?php

namespace App\Models\Hajiri;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class HajiriSetting extends Model
{
    protected $fillable = [
        'office_start_time',
        'office_end_time',
        'late_grace_minutes',
        'early_grace_minutes',
        'weekend_days',
    ];

    protected $casts = [
        'late_grace_minutes' => 'integer',
        'early_grace_minutes' => 'integer',
        'weekend_days' => 'array',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return new static([
                'office_start_time' => '10:00:00',
                'office_end_time' => '16:00:00',
                'late_grace_minutes' => 10,
                'early_grace_minutes' => 10,
                'weekend_days' => [0, 6],
            ]);
        }

        $setting = static::query()->orderBy('id')->first();

        if ($setting) {
            return $setting;
        }

        return static::query()->create([
            'office_start_time' => '10:00:00',
            'office_end_time' => '16:00:00',
            'late_grace_minutes' => 10,
            'early_grace_minutes' => 10,
            'weekend_days' => [0, 6],
        ]);
    }

    public function weekendDays(): array
    {
        $days = $this->weekend_days;

        if ($days === null) {
            $days = [0, 6];
        }

        $days = array_map('intval', is_array($days) ? $days : []);
        $days = array_values(array_unique(array_filter($days, fn ($day) => $day >= 0 && $day <= 6)));
        sort($days);

        return $days;
    }

    public function isWeekend(int $dayOfWeek): bool
    {
        return in_array($dayOfWeek, $this->weekendDays(), true);
    }
}
