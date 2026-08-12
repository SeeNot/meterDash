<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * The consumption derived from one pair of consecutive meter readings.
 */
final readonly class ConsumptionInterval
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public int $days,
        public float $kwh,
        public float $kwhPerDay,
    ) {}

    /**
     * Build an interval from the two readings that bound it.
     */
    public static function between(
        CarbonImmutable $from,
        CarbonImmutable $to,
        float $kwh,
    ): self {
        $days = (int) $from->diffInDays($to);

        return new self(
            from: $from,
            to: $to,
            days: $days,
            kwh: $kwh,
            kwhPerDay: $kwh / $days,
        );
    }

    /**
     * @return array{from: string, to: string, label: string, days: int, kwh: float, kwhPerDay: float}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'label' => $this->to->format('M Y'),
            'days' => $this->days,
            'kwh' => round($this->kwh, 3),
            'kwhPerDay' => round($this->kwhPerDay, 3),
        ];
    }
}
