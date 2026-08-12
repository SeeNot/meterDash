<?php

declare(strict_types=1);

namespace App\Enums;

enum TariffPlanType: string
{
    case Fixed = 'fixed';
    case Dynamic = 'dynamic';

    /**
     * Get the human readable label for the plan type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed',
            self::Dynamic => 'Dynamic (spot)',
        };
    }

    /**
     * Describe how the plan's energy rate is applied.
     */
    public function rateDescription(): string
    {
        return match ($this) {
            self::Fixed => 'Flat price per kWh for the whole contract.',
            self::Dynamic => 'Supplier margin added on top of the monthly market price.',
        };
    }
}
