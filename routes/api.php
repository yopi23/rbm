<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\ServiceApiController;
use App\Http\Controllers\Api\SparepartApiController;
use App\Http\Controllers\Api\PengambilanController;
use App\Http\Controllers\Api\SalesApiController;
use App\Http\Controllers\Api\UserDataController;
use App\Http\Controllers\Api\WhatsAppMessageController;
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
    Route::get('/services/completed-today', [ServiceApiController::class, 'getCompletedToday']);
    Route::get('/services/completedAll', [ServiceApiController::class, 'getCompletedservice']);
    // detail service
    Route::get('/services/getServiceDetails/{id}', [SparepartApiController::class, 'getServiceDetails']);
    // update service
    Route::put('/services/{id}', [SparepartApiController::class, 'updateService']);
    Route::delete('/services/{id}/delete', [SparepartApiController::class, 'delete_service']);
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

    Route::post('/pengambilan', [PengambilanController::class, 'store']);
    Route::get('/services/available', [PengambilanController::class, 'getAvailableServices']);
    Route::get('/kategori-laci', [PengambilanController::class, 'getKategoriLaciList']);

    Route::get('/validate-token', [AuthController::class, 'validateToken']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/spareparts/search', [SalesApiController::class, 'search']);

    Route::get('/service/search', [ServiceApiController::class, 'allservice']);
    // Route::prefix('api')->group(function () {
    Route::get('/sales-history', [SalesApiController::class, 'getSalesHistory']);
    Route::post('/sales', [SalesApiController::class, 'createSale']);
    Route::post('/updateSale', [SalesApiController::class, 'updateSaleStatus']);
    Route::post('/pemasukan', [SalesApiController::class, 'createPemasukkanLainApi']);
    Route::get('/sales/{id}/detail', [SalesApiController::class, 'getSaleDetail']);
    Route::put('/sales/{id}/update', [SalesApiController::class, 'updateSale']);
    Route::delete('/sales/{id}/delete', [SalesApiController::class, 'deleteSale']);
    // });

    Route::get('/user-profile/{kode_user}', [UserDataController::class, 'getUserProfile']);
    Route::post('/penarikan', [UserDataController::class, 'store_penarikan']);

});
// routes/api.php
Route::post('/send-message', [WhatsAppMessageController::class, 'sendMessage']);

Route::post('login', [AuthController::class, 'login']);
