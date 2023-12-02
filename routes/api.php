<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::controller(AuthController::class)->group(function () {

    Route::post('register', 'register');
    Route::post('login', 'login');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::controller(AuthController::class)->group(function () {

        Route::post('enable-two-factor', 'enableTwoFactorAuthentication');
        Route::post('verify-code-two-factory', 'verifyTwoFactorAuthentication');
    });

    Route::resource('files', FileController::class)->only(['index', 'store']);
    Route::post('files/check-in-out', [FileController::class, 'checInOut'])->middleware('throttle:60,1');
    Route::get('files/get-check-in', [FileController::class, 'getCheckInFiles']);

});
