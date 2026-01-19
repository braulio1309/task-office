<?php

use App\Http\Controllers\App\Chat\ChatUserController;
use App\Http\Controllers\App\Chat\MessageController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'chat'], function () {

    Route::get('users', [ChatUserController::class, 'index']);


});

Route::apiResource('messages', MessageController::class);
Route::get('user-messages/{id}', [MessageController::class, 'userMessage']);
Route::post('messages/{id}/mark-as-read', [MessageController::class, 'markAsRead']);
Route::get('messages-unread-count', [MessageController::class, 'unreadCount']);