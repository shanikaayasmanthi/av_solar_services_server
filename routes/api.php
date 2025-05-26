<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//public routes
Route::post('/login', [AuthController::class,'login']);
Route::post('/register', [AuthController::class,'register']);


//protecterd routes
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth:sanctum');