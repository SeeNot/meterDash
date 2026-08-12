<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TariffPlanData;
use App\Enums\TariffPlanType;
use App\Http\Requests\StoreTariffPlanRequest;
use App\Http\Requests\UpdateTariffPlanRequest;
use App\Models\TariffPlan;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class TariffPlanController extends Controller
{
    /**
     * List the tariff plans available for comparison.
     */
    public function index(): Response
    {
        return inertia('Plans/Index', [
            'plans' => TariffPlan::query()
                ->orderBy('name')
                ->get()
                ->map(fn (TariffPlan $plan): array => TariffPlanData::fromModel($plan)->toArray())
                ->all(),
            'types' => TariffPlanType::options(),
        ]);
    }

    /**
     * Add a tariff plan.
     */
    public function store(StoreTariffPlanRequest $request): RedirectResponse
    {
        TariffPlan::create($request->validated());

        return back()->with('success', 'Plan added.');
    }

    /**
     * Update a tariff plan's rates.
     */
    public function update(UpdateTariffPlanRequest $request, TariffPlan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return back()->with('success', 'Plan updated.');
    }

    /**
     * Remove a tariff plan.
     */
    public function destroy(TariffPlan $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }
}
