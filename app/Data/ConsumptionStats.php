<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Summary statistics across a set of consumption intervals.
 */
final readonly class ConsumptionStats
{
    public function __construct(
        public float $totalKwh,
        public float $averageKwhPerDay,
        public float $medianKwhPerDay,
        public float $minKwhPerDay,
        public float $maxKwhPerDay,
        public int $daysCovered,
        public int $intervalCount,
    ) {}

    /**
     * An all-zero set of statistics, used when there is nothing to measure.
     */
    public static function empty(): self
    {
        return new self(
            totalKwh: 0.0,
            averageKwhPerDay: 0.0,
            medianKwhPerDay: 0.0,
            minKwhPerDay: 0.0,
            maxKwhPerDay: 0.0,
            daysCovered: 0,
            intervalCount: 0,
        );
    }

    /**
     * @return array{totalKwh: float, averageKwhPerDay: float, medianKwhPerDay: float, minKwhPerDay: float, maxKwhPerDay: float, daysCovered: int, intervalCount: int}
     */
    public function toArray(): array
    {
        return [
            'totalKwh' => round($this->totalKwh, 2),
            'averageKwhPerDay' => round($this->averageKwhPerDay, 2),
            'medianKwhPerDay' => round($this->medianKwhPerDay, 2),
            'minKwhPerDay' => round($this->minKwhPerDay, 2),
            'maxKwhPerDay' => round($this->maxKwhPerDay, 2),
            'daysCovered' => $this->daysCovered,
            'intervalCount' => $this->intervalCount,
        ];
    }
}
