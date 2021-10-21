<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\AuthController;

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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('recover-password', [AuthController::class, 'recover_password']);

Route::group(['middleware' => 'jwt.verify'], function () {

    Route::post('profile-update', [AuthController::class, 'profile_update']);
    Route::post('avater-update', [AuthController::class, 'profile_avater_update']);
    Route::post('password-update', [AuthController::class, 'change_password']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [AuthController::class, 'getAuthenticatedUser']);

});
