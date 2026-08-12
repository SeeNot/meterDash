<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WattageEntry;
use App\Rules\MonotonicMeterReading;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWattageEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'measured_at.unique' => 'A reading has already been recorded for that date.',
            'measured_at.before_or_equal' => 'A reading cannot be dated in the future.',
        ];
    }
}
