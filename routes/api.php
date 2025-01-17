<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Api\SparepartApiController;
use FontLib\Table\Type\name;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/create-service', [DashboardController::class, 'create_service_api']);
    Route::post('/pending-services', [DashboardController::class, 'get_pending_services']);
    Route::get('/services/completed-today', [ServiceController::class, 'getCompletedToday']);
    // detail service
    Route::get('/services/getServiceDetails/{id}', [SparepartApiController::class, 'getServiceDetails']);
    // update service
    Route::put('/services/{id}', [SparepartApiController::class, 'updateService']);
    // cari sparepart
    Route::get('/sparepart-toko/search', [SparepartApiController::class, 'searchSparepartToko']);
    Route::post('/service/search-sparepart', [SparepartApiController::class, 'search_sparepart']);
    // crud sparepart toko
    Route::post('/sparepart-toko', [SparepartApiController::class, 'storeSparepartToko']);
    Route::delete('/sparepart-toko/{id}', [SparepartApiController::class, 'deleteSparepartToko']);
    // crud sparepart luar
    Route::post('/sparepart-luar', [SparepartApiController::class, 'storeSparepartLuar']);
    Route::put('/sparepart-luar/{id}', [SparepartApiController::class, 'updateSparepartLuar']);
    Route::delete('/sparepart-luar/{id}', [SparepartApiController::class, 'deleteSparepartLuar']);

    Route::put('/service/updateServiceStatus/{id}', [SparepartApiController::class, 'updateServiceStatus']);

    Route::get('/validate-token', [AuthController::class, 'validateToken']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::post('login', [AuthController::class, 'login']);
