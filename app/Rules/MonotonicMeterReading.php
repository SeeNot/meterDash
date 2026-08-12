<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\WattageEntry;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Date;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Keeps the meter series monotonically increasing.
 *
 * A cumulative meter only ever counts up, so a reading must sit between its
 * neighbours. Without this a typo silently produces a negative interval that
 * the calculator then has to discard.
 */
final readonly class MonotonicMeterReading implements ValidationRule
{
    public function __construct(
        private ?string $measuredAt,
        private ?int $ignoreId = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->measuredAt === null || ! is_numeric($value)) {
            return;
        }

        $reading = (float) $value;

        /** @var CarbonImmutable $date */
        $date = Date::parse($this->measuredAt)->startOfDay();

        $previous = $this->neighbour($date, earlier: true);

        if ($previous instanceof WattageEntry && $reading < $previous->reading_kwh) {
            $fail(sprintf(
                'The reading must be at least %s kWh, the value recorded on %s.',
                $previous->reading_kwh,
                $previous->measured_at->toFormattedDateString(),
            ));

            return;
        }

        $next = $this->neighbour($date, earlier: false);

        if ($next instanceof WattageEntry && $reading > $next->reading_kwh) {
            $fail(sprintf(
                'The reading must not exceed %s kWh, the value recorded on %s.',
                $next->reading_kwh,
                $next->measured_at->toFormattedDateString(),
            ));
        }
    }

    /**
     * Find the reading immediately before or after the given date.
     */
    private function neighbour(CarbonImmutable $date, bool $earlier): ?WattageEntry
    {
        $query = WattageEntry::query()
            ->whereDate('measured_at', $earlier ? '<' : '>', $date)
            ->orderBy('measured_at', $earlier ? 'desc' : 'asc');

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        return $query->first();
    }
}
