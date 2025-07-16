<?php
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatteryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvertorController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SolarPanelController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/login', function () {
//     return response()->json(['message' => 'Unauthenticated.'], 401);
// })->name('login'); // IMPORTANT: Name it 'login'


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//public routes
Route::post('/login', [AuthController::class,'login']);
Route::post('/register', [AuthController::class,'register']);


//protecterd routesa
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

//web apis
Route::post('/addcustomers', [CustomerController::class, 'store'])->middleware('auth:sanctum');
Route::post('/openproject', [ProjectController::class, 'openProject'])->middleware('auth:sanctum');
Route::get('/find-customer', [CustomerController::class, 'find'])->middleware('auth:sanctum');
Route::get('/get-projects', [ProjectController::class, 'getAllProjects'])->middleware('auth:sanctum');
Route::get('/get-project', [ProjectController::class, 'getprojectData'])->middleware('auth:sanctum');
Route::get('/get-customer', [ProjectController::class, 'getCustomerData'])->middleware('auth:sanctum');
Route::get('/get-project-count',[ProjectController::class,'getProjectCount'])->middleware('auth:sanctum');
Route::get('/get-service-counts',[ServiceController::class,'getServiceCounts'])->middleware('auth:sanctum');
Route::get('/get-solar-panel',[SolarPanelController::class,'getSolarPanels'])->middleware('auth:sanctum');
Route::get('/get-services-summary',[ServiceController::class,'getServicesSummery'])->middleware('auth:sanctum');
Route::get('/get-inverters',[InvertorController::class,'getInvertors'])->middleware('auth:sanctum');
Route::get('/get-batteries',[BatteryController::class,'getBatteries'])->middleware('auth:sanctum');
Route::get('/get-next-service-round',[ServiceController::class,'getNextServiceRound'])->middleware('auth:sanctum');
Route::get('/search-supervisors',[UserController::class,'searchSupervisors'])->middleware('auth:sanctum');
Route::post('/schedule-next-service',[ServiceController::class,'scheduleNextService'])->middleware('auth:sanctum');
Route::get('/search-customer',[UserController::class,'searchCustomer'])->middleware('auth:sanctum');
