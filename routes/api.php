<?php

use App\Http\Controllers\API\V1\TaskController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::post('/createToken', [UserController::class, 'createToken']);
    Route::apiResource('user', UserController::class)->only('store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::delete('/revokeToken', [UserController::class, 'revokeToken']);

        Route::apiResource('user', UserController::class)->except('store');
        Route::apiResource('task', TaskController::class);
    });
});
