<?php

declare(strict_types=1);

use App\Http\Controllers\TariffPlanController;
use App\Http\Controllers\WattageController;
use App\Http\Controllers\WattageEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WattageController::class, 'index'])->name('dashboard');

Route::post('/entries', [WattageEntryController::class, 'store'])->name('entries.store');
Route::put('/entries/{entry}', [WattageEntryController::class, 'update'])->name('entries.update');
Route::delete('/entries/{entry}', [WattageEntryController::class, 'destroy'])->name('entries.destroy');

Route::get('/plans', [TariffPlanController::class, 'index'])->name('plans.index');
Route::post('/plans', [TariffPlanController::class, 'store'])->name('plans.store');
Route::put('/plans/{plan}', [TariffPlanController::class, 'update'])->name('plans.update');
Route::delete('/plans/{plan}', [TariffPlanController::class, 'destroy'])->name('plans.destroy');
