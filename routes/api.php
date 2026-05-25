<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VideoController;

Route::get('/videos', [VideoController::class, 'index']);
Route::get('/videos/{video}', [VideoController::class, 'show']);
Route::get('/stats', [VideoController::class, 'stats']);