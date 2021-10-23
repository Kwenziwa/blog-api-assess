<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\PostController;

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

Route::group(['prefix' => 'users'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('recover-password', [AuthController::class, 'recover_password']);
});

Route::group(['middleware' => 'jwt.verify'], function () {

     // User Auth End Points
    Route::group(['prefix' => 'users'], function () {
        Route::post('profile-update', [AuthController::class, 'profile_update']);
        Route::post('avater-update', [AuthController::class, 'profile_avater_update']);
        Route::post('password-update', [AuthController::class, 'change_password']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'getAuthenticatedUser']);
    });

    //Post End Points
    Route::group(['prefix' => 'posts'], function () {
        Route::post('create', [PostController::class, 'CreatePost']);
        Route::post('update', [PostController::class, 'updatePost']);
        Route::post('delete', [PostController::class, 'deletePost']);
        Route::get('all-list-post', [PostController::class, 'getAllPosts']);
        Route::get('my-post-list', [PostController::class, 'getMyPosts']);
        Route::post('search', [PostController::class, 'searchPost']);
        Route::post('view-post', [PostController::class, 'getPost']);
        Route::post('post-vote', [PostController::class, 'upvotedDownvoted']);
        Route::get('user-posts-voted', [PostController::class, 'postUserVodted']);





        Route::group(['prefix' => 'comments'], function () {

            Route::post('create', [PostController::class, 'CommentPost']);
            Route::post('comment-vote', [PostController::class, 'upvotedDownvotedComment']);

        });

    });




});
