<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * The date window the dashboard is reporting on.
 */
final readonly class Period
{
    public const PRESETS = ['30d', '3m', '12m', 'ytd', 'all', 'custom'];

    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public string $preset,
    ) {}

    /**
     * Resolve a period from a preset name, falling back to the last 12 months.
     *
     * `all` needs the date of the first reading, which the caller supplies
     * because this object has no business querying the database.
     */
    public static function fromPreset(?string $preset, ?CarbonImmutable $earliest = null): self
    {
        /** @var CarbonImmutable $today */
        $today = Date::now()->startOfDay();

        return match ($preset) {
            '30d' => new self($today->subDays(30), $today, '30d'),
            '3m' => new self($today->subMonths(3), $today, '3m'),
            'ytd' => new self($today->startOfYear(), $today, 'ytd'),
            'all' => new self($earliest ?? $today->subMonths(12), $today, 'all'),
            default => new self($today->subMonths(12), $today, '12m'),
        };
    }

    /**
     * Build a custom period from an explicit pair of dates.
     */
    public static function custom(CarbonImmutable $from, CarbonImmutable $to): self
    {
        return new self($from->startOfDay(), $to->startOfDay(), 'custom');
    }

    /**
     * @return array{from: string, to: string, preset: string}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'preset' => $this->preset,
        ];
    }
}
