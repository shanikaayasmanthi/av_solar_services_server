<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ServiceController;
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

//mobile apis
//get services assigned for supervisor
Route::post('/sup/services',[ServiceController::class,'getSupervisorAllServices'])->middleware('auth:sanctum');
Route::post('sup/set_service_time',[ServiceController::class,'setServiceTime'])->middleware('auth:sanctum');
Route::post('/sup/get_service_ProjectNo',[ServiceController::class,'getProjectNo'])->middleware('auth:sanctum');
Route::post('/sup/get_customer',[ProjectController::class,'getCustomer'])->middleware('auth:sanctum');