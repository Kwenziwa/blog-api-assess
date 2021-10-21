<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('verify/{verification_code}',[AuthController::class, 'verifyUser']);

Route::get('password/reset/{verification_code}', [AuthController::class, 'reset_password'])->name('password.reset');

Route::post('update-password', [AuthController::class, 'update_password'])->name('update_password');
