<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWattageEntryRequest;
use App\Http\Requests\UpdateWattageEntryRequest;
use App\Models\WattageEntry;
use Illuminate\Http\RedirectResponse;

class WattageEntryController extends Controller
{
    /**
     * Record a new meter reading.
     */
    public function store(StoreWattageEntryRequest $request): RedirectResponse
    {
        WattageEntry::create($request->validated());

        return back()->with('success', 'Reading recorded.');
    }

    /**
     * Correct an existing meter reading.
     */
    public function update(UpdateWattageEntryRequest $request, WattageEntry $entry): RedirectResponse
    {
        $entry->update($request->validated());

        return back()->with('success', 'Reading updated.');
    }

    /**
     * Remove a meter reading.
     */
    public function destroy(WattageEntry $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('success', 'Reading deleted.');
    }
}
