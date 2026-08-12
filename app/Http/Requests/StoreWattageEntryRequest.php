<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\MonotonicMeterReading;

class StoreWattageEntryRequest extends WattageEntryRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'measured_at' => ['required', 'date', 'before_or_equal:today', 'unique:wattage_entries,measured_at'],
            'reading_kwh' => [
                'required',
                'numeric',
                'min:0',
                new MonotonicMeterReading($this->string('measured_at')->toString() ?: null),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
