<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\VideoController;

Route::get('/', function () {
    return view('welcome');
});
