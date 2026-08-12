<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TariffPlanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TariffPlanRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(TariffPlanType::class)],
            'energy_rate_eur' => ['required', 'numeric', 'min:0', 'max:10'],
            'standing_charge_eur' => ['required', 'numeric', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
