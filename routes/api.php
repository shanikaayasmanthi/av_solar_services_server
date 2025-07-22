<?php
use App\Http\Controllers\DCController;
use App\Http\Controllers\ACController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatteryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvertorController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RoofWorkController;
use App\Http\Controllers\OutdoorWorkController;
use App\Http\Controllers\MainPanelWorkController;
use App\Http\Controllers\SolarPanelController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


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

Route::post('/addcustomer', [CustomerController::class, 'store'])->middleware('auth:sanctum');
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
Route::post('change-inverters',[InvertorController::class,'changeInverters'])->middleware('auth:sanctum');
Route::get('/get-batteries',[BatteryController::class,'getBatteries'])->middleware('auth:sanctum');
Route::get('/get-next-service-round',[ServiceController::class,'getNextServiceRound'])->middleware('auth:sanctum');
Route::get('/search-supervisors',[UserController::class,'searchSupervisors'])->middleware('auth:sanctum');
Route::post('/schedule-next-service',[ServiceController::class,'scheduleNextService'])->middleware('auth:sanctum');
Route::get('/search-customer',[UserController::class,'searchCustomer'])->middleware('auth:sanctum');
Route::post('add-new-solar-panels',[SolarPanelController::class,'addNewSolarPanels'])->middleware('auth:sanctum');
Route::post('change-solar-panels',[SolarPanelController::class,'changeSolarPanels'])->middleware('auth:sanctum');
Route::get('/get-service-details-by-id',[ServiceController::class,'getServiceDetailsById'])->middleware('auth:sanctum');
Route::get('/users', [UserController::class, 'getAllUsersWithTypeAndStatus'])->middleware('auth:sanctum');
Route::get('/user-types', [UserTypeController::class, 'index'])->middleware('auth:sanctum');
Route::post('/users', [UserController::class, 'store']);

