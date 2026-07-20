<?php

use App\Http\Controllers\VkMiniAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// VK Mini App
Route::post('/vk/decode', [VkMiniAppController::class, 'decode']);
Route::post('/vk/feedback', [VkMiniAppController::class, 'feedback']);
