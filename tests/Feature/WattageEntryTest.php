<?php

declare(strict_types=1);

use App\Models\WattageEntry;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    Date::setTestNow('2026-06-15');
});

it('records a reading', function (): void {
    $this->post('/entries', [
        'measured_at' => '2026-06-01',
        'reading_kwh' => 45210.5,
        'note' => 'Kitchen meter',
    ])->assertRedirect();

    $entry = WattageEntry::query()->sole();

    expect($entry->measured_at->toDateString())->toBe('2026-06-01')
        ->and($entry->reading_kwh)->toBe(45210.5)
        ->and($entry->note)->toBe('Kitchen meter');
});

it('rejects a second reading for the same date', function (): void {
    WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();

    $this->post('/entries', [
        'measured_at' => '2026-06-01',
        'reading_kwh' => 1100.0,
    ])->assertSessionHasErrors('measured_at');

    expect(WattageEntry::query()->count())->toBe(1);
});

it('rejects a reading dated in the future', function (): void {
    $this->post('/entries', [
        'measured_at' => '2026-06-16',
        'reading_kwh' => 1000.0,
    ])->assertSessionHasErrors('measured_at');
});

it('rejects a reading lower than the one before it', function (): void {
    WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();

    $this->post('/entries', [
        'measured_at' => '2026-06-11',
        'reading_kwh' => 900.0,
    ])->assertSessionHasErrors('reading_kwh');

    expect(WattageEntry::query()->count())->toBe(1);
});

it('rejects a back-dated reading higher than the one after it', function (): void {
    WattageEntry::factory()->reading('2026-06-11', 1000.0)->create();

    $this->post('/entries', [
        'measured_at' => '2026-06-01',
        'reading_kwh' => 1100.0,
    ])->assertSessionHasErrors('reading_kwh');
});

it('accepts a back-dated reading that sits between its neighbours', function (): void {
    WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();
    WattageEntry::factory()->reading('2026-06-21', 1200.0)->create();

    $this->post('/entries', [
        'measured_at' => '2026-06-11',
        'reading_kwh' => 1100.0,
    ])->assertSessionHasNoErrors();

    expect(WattageEntry::query()->count())->toBe(3);
});

it('rejects a negative reading', function (): void {
    $this->post('/entries', [
        'measured_at' => '2026-06-01',
        'reading_kwh' => -5,
    ])->assertSessionHasErrors('reading_kwh');
});

it('updates a reading', function (): void {
    $entry = WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();

    $this->put("/entries/{$entry->id}", [
        'measured_at' => '2026-06-02',
        'reading_kwh' => 1050.0,
        'note' => 'Corrected',
    ])->assertRedirect();

    expect($entry->refresh()->reading_kwh)->toBe(1050.0)
        ->and($entry->measured_at->toDateString())->toBe('2026-06-02')
        ->and($entry->note)->toBe('Corrected');
});

it('does not treat a reading as a duplicate of itself when updating', function (): void {
    $entry = WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();

    $this->put("/entries/{$entry->id}", [
        'measured_at' => '2026-06-01',
        'reading_kwh' => 1234.0,
    ])->assertSessionHasNoErrors();

    expect($entry->refresh()->reading_kwh)->toBe(1234.0);
});

it('rejects an update that breaks the order of the series', function (): void {
    WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();
    $entry = WattageEntry::factory()->reading('2026-06-11', 1100.0)->create();

    $this->put("/entries/{$entry->id}", [
        'measured_at' => '2026-06-11',
        'reading_kwh' => 500.0,
    ])->assertSessionHasErrors('reading_kwh');

    expect($entry->refresh()->reading_kwh)->toBe(1100.0);
});

it('deletes a reading', function (): void {
    $entry = WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();

    $this->delete("/entries/{$entry->id}")->assertRedirect();

    expect(WattageEntry::query()->count())->toBe(0);
});
