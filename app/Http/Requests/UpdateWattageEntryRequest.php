<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WattageEntry;
use App\Rules\MonotonicMeterReading;
use Illuminate\Validation\Rule;

class UpdateWattageEntryRequest extends WattageEntryRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $entry = $this->route('entry');
        $id = $entry instanceof WattageEntry ? $entry->id : null;

        return [
            'measured_at' => [
                'required',
                'date',
                'before_or_equal:today',
                Rule::unique('wattage_entries', 'measured_at')->ignore($id),
            ],
            'reading_kwh' => [
                'required',
                'numeric',
                'min:0',
                new MonotonicMeterReading($this->string('measured_at')->toString() ?: null, $id),
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
