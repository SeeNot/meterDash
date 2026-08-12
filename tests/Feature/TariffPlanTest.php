<?php

declare(strict_types=1);

use App\Enums\TariffPlanType;
use App\Models\TariffPlan;
use Inertia\Testing\AssertableInertia;

it('lists the plans', function (): void {
    TariffPlan::factory()->fixed()->create(['name' => 'Steady 12']);
    TariffPlan::factory()->dynamic()->inactive()->create(['name' => 'Market Follow']);

    $this->get('/plans')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Plans/Index')
            ->count('plans', 2)
            ->where('plans.0.name', 'Market Follow')
            ->where('plans.0.is_active', false)
            ->where('plans.1.typeLabel', 'Fixed')
            ->count('types', 2)
        );
});

it('adds a plan', function (): void {
    $this->post('/plans', [
        'name' => 'Market Follow',
        'type' => 'dynamic',
        'energy_rate_eur' => 0.012,
        'standing_charge_eur' => 4.99,
        'is_active' => true,
    ])->assertRedirect();

    $plan = TariffPlan::query()->sole();

    expect($plan->name)->toBe('Market Follow')
        ->and($plan->type)->toBe(TariffPlanType::Dynamic)
        ->and($plan->energy_rate_eur)->toBe(0.012)
        ->and($plan->standing_charge_eur)->toBe(4.99);
});

it('rejects a plan type it does not know', function (): void {
    $this->post('/plans', [
        'name' => 'Time of use',
        'type' => 'time_of_use',
        'energy_rate_eur' => 0.15,
        'standing_charge_eur' => 3.0,
    ])->assertSessionHasErrors('type');

    expect(TariffPlan::query()->count())->toBe(0);
});

it('rejects a negative rate', function (): void {
    $this->post('/plans', [
        'name' => 'Backwards',
        'type' => 'fixed',
        'energy_rate_eur' => -0.10,
        'standing_charge_eur' => 3.0,
    ])->assertSessionHasErrors('energy_rate_eur');
});

it('requires a name', function (): void {
    $this->post('/plans', [
        'type' => 'fixed',
        'energy_rate_eur' => 0.18,
        'standing_charge_eur' => 3.0,
    ])->assertSessionHasErrors('name');
});

it('updates a plan', function (): void {
    $plan = TariffPlan::factory()->fixed(rate: 0.18)->create(['name' => 'Steady 12']);

    $this->put("/plans/{$plan->id}", [
        'name' => 'Steady 12 (renewed)',
        'type' => 'fixed',
        'energy_rate_eur' => 0.21,
        'standing_charge_eur' => 5.5,
        'is_active' => false,
    ])->assertRedirect();

    expect($plan->refresh()->name)->toBe('Steady 12 (renewed)')
        ->and($plan->energy_rate_eur)->toBe(0.21)
        ->and($plan->is_active)->toBeFalse();
});

it('deletes a plan', function (): void {
    $plan = TariffPlan::factory()->fixed()->create();

    $this->delete("/plans/{$plan->id}")->assertRedirect();

    expect(TariffPlan::query()->count())->toBe(0);
});
