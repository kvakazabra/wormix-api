<?php

use App\Http\Controllers\Internal\InternalAchieveLoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('achievements')->group(function () {
    Route::post('login', [InternalAchieveLoginController::class, 'login']);
});
