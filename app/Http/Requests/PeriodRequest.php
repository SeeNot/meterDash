<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Period;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodRequest extends FormRequest
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
        return [
            'preset' => ['nullable', 'string', Rule::in(Period::PRESETS)],
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'required_with:from', 'after_or_equal:from'],
        ];
    }

    /**
     * Resolve the requested period, preferring an explicit date range.
     *
     * @param  CarbonImmutable|null  $earliest  The date of the first reading on record.
     */
    public function period(?CarbonImmutable $earliest = null): Period
    {
        $from = $this->date('from');
        $to = $this->date('to');

        if ($from !== null && $to !== null) {
            return Period::custom($from->toImmutable(), $to->toImmutable());
        }

        return Period::fromPreset($this->string('preset')->toString() ?: null, $earliest);
    }
}
