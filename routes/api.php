<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UtilityController;

Route::post('/utility/sync/start', [UtilityController::class, 'start']);
Route::post('/utility/sync/finish', [UtilityController::class, 'finish']);
Route::post('/utility/sync/status', [UtilityController::class, 'status']);




