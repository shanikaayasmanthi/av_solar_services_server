<?php
use App\Http\Controllers\DCController;
use App\Http\Controllers\ACController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RoofWorkController;
use App\Http\Controllers\OutdoorWorkController;
use App\Http\Controllers\MainPanelWorkController;
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
Route::post('/change_password',[AuthController::class,'changePassword'])->middleware('auth:sanctum');

//mobile apis
//get services assigned for supervisor
Route::post('/sup/services',[ServiceController::class,'getSupervisorAllServices'])->middleware('auth:sanctum');
Route::post('sup/set_service_time',[ServiceController::class,'setServiceTime'])->middleware('auth:sanctum');
Route::post('/sup/get_service_ProjectNo',[ServiceController::class,'getProjectNo'])->middleware('auth:sanctum');
Route::post('/sup/get_customer',[ProjectController::class,'getCustomer'])->middleware('auth:sanctum');
Route::post('/sup/get_project',[ProjectController::class,'getprojectDetails'])->middleware('auth:sanctum');
Route::post('/sup/save_service_data',[ServiceController::class,'saveServiceDetails'])->middleware('auth:sanctum');

// get project location
Route::get('/project-location/{id}', [ProjectController::class, 'getLocation'])->middleware('auth:sanctum');
//Route::get('/project-location/{project_id}', [ProjectController::class, 'getLocation']);

//for get completed services summary
Route::post('/sup/get_completed_services_by_project', [ServiceController::class, 'getCompletedServicesByProject'])->middleware('auth:sanctum');

//get all scheduled services that are not yet completed
Route::get('/services/scheduled', [ServiceController::class, 'getAllScheduledServices'])->middleware('auth:sanctum');

// get all projects with atleast one completed services
Route::get('/projects/completed', [ServiceController::class, 'getProjectsWithCompletedServices'])->middleware('auth:sanctum');

// get service rounds of all projects with atleast one completed services by project id
Route::get('/services/completed-by-project-id', [ServiceController::class, 'getCompletedServiceRoundsByProjectId'])->middleware('auth:sanctum');

//get service DC details by service-id
Route::get('/dc/details-by-service-id', [DCController::class, 'getDCServiceDetailsByServiceId'])->middleware('auth:sanctum');

//get service AC details by service-id
Route::get('/ac/details-by-service-id', [ACController::class, 'getACServiceDetailsByServiceId'])->middleware('auth:sanctum');

Route::post('/project/location-capacity', [ProjectController::class, 'getProjectLocationAndCapacity'])->middleware('auth:sanctum');

Route::get('/roof-work/details', [RoofWorkController::class, 'getRoofWorkDetailsByServiceId'])->middleware('auth:sanctum');

Route::get('/outdoor-work/details', [OutdoorWorkController::class, 'getOutdoorWorkDetails'])->middleware('auth:sanctum');

Route::post('/mainpanel/details', [MainPanelWorkController::class, 'getMainPanelWorkDetails'])->middleware('auth:sanctum');

Route::post('/service/technicians', [ServiceController::class, 'getTechniciansByServiceId'])->middleware('auth:sanctum');
