<?php

namespace App\Models;

use Database\Factories\TechnicianScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianSchedule extends Model
{
    /** @use HasFactory<TechnicianScheduleFactory> */
    use HasFactory;

    /**
     * Day-of-week convention matching Carbon::dayOfWeek (0 = Sunday).
     */
    public const SUNDAY = 0;

    public const MONDAY = 1;

    public const TUESDAY = 2;

    public const WEDNESDAY = 3;

    public const THURSDAY = 4;

    public const FRIDAY = 5;

    public const SATURDAY = 6;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'technician_id',
        'day_of_week',
        'is_working',
        'start_time',
        'end_time',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_working' => 'boolean',
        ];
    }

    /**
     * Get the technician owning this schedule row.
     *
     * @return BelongsTo<Technician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * Determine whether the given window fits inside this working day.
     *
     * Times are `H:i` strings; the window must start at or after the shift
     * start and end at or before the shift end.
     */
    public function covers(string $start, string $end): bool
    {
        if (! $this->is_working || $this->start_time === null || $this->end_time === null) {
            return false;
        }

        return $start >= substr((string) $this->start_time, 0, 5)
            && $end <= substr((string) $this->end_time, 0, 5);
    }

    /**
     * The default weekly template seeded for new technicians.
     *
     * Friday is off, Saturday runs 10:00–18:00, every other day 08:00–17:00.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultWeek(): array
    {
        $days = [];

        foreach (range(0, 6) as $day) {
            $days[] = match ($day) {
                self::FRIDAY => ['day_of_week' => $day, 'is_working' => false, 'start_time' => null, 'end_time' => null],
                self::SATURDAY => ['day_of_week' => $day, 'is_working' => true, 'start_time' => '10:00', 'end_time' => '18:00'],
                default => ['day_of_week' => $day, 'is_working' => true, 'start_time' => '08:00', 'end_time' => '17:00'],
            };
        }

        return $days;
    }
}
