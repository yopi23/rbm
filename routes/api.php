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
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\StockOpnameController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Admin\HpController;
use App\Http\Controllers\Api\HpApiController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\FinancialReportApiController;
use App\Http\Controllers\Api\PengeluaranApiController;
use App\Http\Controllers\Admin\EmployeeManagementController;
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

    // Attendance routes
    Route::post('/attendance/scan', [Api\AttendanceController::class, 'scanQrCode']);
    Route::post('/attendance/request-leave', [Api\AttendanceController::class, 'requestLeave']);
    Route::get('/attendance/status', [Api\AttendanceController::class, 'getStatus']);

      // Manual Attendance by Admin (API untuk mobile)
    Route::get('/employees/list', [EmployeeManagementController::class, 'getEmployeeList']);
    Route::post('/attendance/manual-checkin', [EmployeeManagementController::class, 'manualCheckIn']);
    Route::post('/attendance/manual-checkout', [EmployeeManagementController::class, 'manualCheckOut']);
    Route::get('/attendance/employee-status/{employeeId}', [EmployeeManagementController::class, 'getEmployeeAttendanceStatus']);

    // Admin Employee Withdrawal Routes
    Route::post('/admin/penarikan-karyawan', [UserDataController::class, 'adminWithdrawEmployee']);
    Route::get('/admin/penarikan-history', [UserDataController::class, 'adminWithdrawalHistory']);
    Route::get('/admin/penarikan-summary', [UserDataController::class, 'adminWithdrawalSummary']);

    Route::get('/karyawan', [UserDataController::Class, 'getKaryawan']);

    Route::post('/create-service', [DashboardController::class, 'create_service_api']);
    Route::post('/pending-services', [DashboardController::class, 'get_pending_services']);
    Route::get('/services/completed-today', [ServiceApiController::class, 'getCompletedToday']);
    Route::get('/services/completedAll', [ServiceApiController::class, 'getCompletedservice']);
    Route::get('/services/{serviceId}/status', [ServiceApiController::class, 'checkServiceStatus']);
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
    Route::get('/sparepart/suggestions', [SalesApiController::class, 'searchSuggestions']);
    Route::get('/spareparts/search', [SalesApiController::class, 'search']);
     Route::get('spareparts/{id}', [SalesApiController::class, 'getSparepartById']);
    Route::get('/spareparts/popular', [SalesApiController::class, 'getPopularSearches']);

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

    // routes/api.php
    Route::post('/send-message', [WhatsAppMessageController::class, 'sendMessage']);

    Route::get('/suppliers', [OrderApiController::class, 'getSuppliers']);


    // Warranty (Garansi) routes
    Route::post('/warranty/store', [SparepartApiController::class, 'storeGaransiService']);
    Route::put('/warranty/{id}', [SparepartApiController::class, 'updateGaransiService']);
    Route::delete('/warranty/{id}', [SparepartApiController::class, 'deleteGaransiService']);
    Route::get('/warranty/{kode_service}', [SparepartApiController::class, 'getGaransiService']);

    // Service Notes routes
    Route::post('/service-notes/store', [SparepartApiController::class, 'storeCatatanService']);
    Route::put('/service-notes/{id}', [SparepartApiController::class, 'updateCatatanService']);
    Route::delete('/service-notes/{id}', [SparepartApiController::class, 'deleteCatatanService']);
    Route::get('/service-notes/{service_id}', [SparepartApiController::class, 'getCatatanService']);
    Route::get('/services/indicators', [SparepartApiController::class, 'getServiceIndicators']);

    // Pengeluaran Toko
    Route::get('pengeluaran-toko', [PengeluaranApiController::class, 'getPengeluaranToko'])->name('api.pengeluaran-toko.index');
    Route::post('pengeluaran-toko', [PengeluaranApiController::class, 'storePengeluaranToko'])->name('api.pengeluaran-toko.store');
    Route::get('pengeluaran-toko/{id}', [PengeluaranApiController::class, 'showPengeluaranToko'])->name('api.pengeluaran-toko.show');
    Route::put('pengeluaran-toko/{id}', [PengeluaranApiController::class, 'updatePengeluaranToko'])->name('api.pengeluaran-toko.update');
    Route::delete('pengeluaran-toko/{id}', [PengeluaranApiController::class, 'deletePengeluaranToko'])->name('api.pengeluaran-toko.delete');

    // Pengeluaran Operasional
    Route::get('pengeluaran-operasional', [PengeluaranApiController::class, 'getPengeluaranOperasional'])->name('api.pengeluaran-operasional.index');
    Route::post('pengeluaran-operasional', [PengeluaranApiController::class, 'storePengeluaranOperasional'])->name('api.pengeluaran-operasional.store');
    Route::get('pengeluaran-operasional/{id}', [PengeluaranApiController::class, 'showPengeluaranOperasional'])->name('api.pengeluaran-operasional.show');
    Route::put('pengeluaran-operasional/{id}', [PengeluaranApiController::class, 'updatePengeluaranOperasional'])->name('api.pengeluaran-operasional.update');
    Route::delete('pengeluaran-operasional/{id}', [PengeluaranApiController::class, 'deletePengeluaranOperasional'])->name('api.pengeluaran-operasional.delete');

    // Helper endpoints
    Route::get('employees', [PengeluaranApiController::class, 'getEmployees'])->name('api.employees');
    Route::get('pengeluaran-summary', [PengeluaranApiController::class, 'getSummary'])->name('api.pengeluaran.summary');
    //end pengeluaran

     // Main Laporan Keuangan
    Route::get('/financial-report', [FinancialReportApiController::class, 'getFinancialReport']);

    // Detailed reports by type
    Route::get('/financial-report/detailed', [FinancialReportApiController::class, 'getDetailedReport']);

    // Daily breakdown report
    Route::get('/financial-report/daily', [FinancialReportApiController::class, 'getDailyReport']);

    // NEW: Service loss details
    Route::get('/financial-report/service-loss', [FinancialReportApiController::class, 'getServiceLossDetails']);

    // Financial summary for dashboard
    Route::get('/financial-report/summary', [FinancialReportApiController::class, 'getFinancialSummary']);

    // Technician profit analysis
    Route::get('/financial-report/technician-analysis', [FinancialReportApiController::class, 'getTechnicianProfitAnalysis']);

    // Export functionality
    Route::post('/financial-report/export', [FinancialReportApiController::class, 'exportFinancialReport']);


    Route::prefix('customer')->group(function () {
        Route::get('/', [CustomerApiController::class, 'index']);
        Route::post('/', [CustomerApiController::class, 'store']);
        Route::get('/{id}', [CustomerApiController::class, 'show']);
        Route::put('/{id}', [CustomerApiController::class, 'update']);
        Route::delete('/{id}', [CustomerApiController::class, 'destroy']);

        // Additional API routes
        Route::get('/status/{status}', [CustomerApiController::class, 'getByStatus']);
        Route::post('/search', [CustomerApiController::class, 'search']);

        // Get new kode toko for form
        Route::get('/generate-kode', [CustomerApiController::class, 'getNewKodeToko']);
    });

    //TG
    Route::get('/hp', [HpController::class, 'api']);
    Route::post('/hp/suggest', [HpController::class, 'apiSuggest']);

    // API untuk pencarian data HP
    Route::get('/hp/search', [HpApiController::class, 'search']);
    // Alias dengan parameter query lebih eksplisit
    Route::get('/hp', [HpApiController::class, 'search']);

    // Pencarian khusus berdasarkan tipe HP
    Route::get('/hp/type', [HpApiController::class, 'searchByType']);

    // Dapatkan data filter untuk pencarian
    Route::get('/hp/filters', [HpApiController::class, 'filters']);

    // Dapatkan data detail HP berdasarkan ID
    Route::get('/hp/{id}', [HpApiController::class, 'detail']);

    //absen
    Route::prefix('attendance')->group(function () {
        // QR Code Generation untuk karyawan
        Route::post('/generate-employee-qr', [EmployeeManagementController::class, 'generateEmployeeQrCode']);

        // Scan QR Code karyawan oleh admin
        Route::post('/scan-employee-qr', [EmployeeManagementController::class, 'scanEmployeeQrCode']);

        // Legacy QR Code scan (untuk compatibility jika masih ada yang pakai sistem lama)
        Route::post('/scan/{token}', [EmployeeManagementController::class, 'scanQrCode']);

        // Request leave dari mobile
        Route::post('/request-leave', [EmployeeManagementController::class, 'requestLeave']);

        // Get attendance history for mobile
        Route::get('/history/{userId}', [EmployeeManagementController::class, 'getAttendanceHistory']);

        // Get current attendance status
        Route::get('/status/{userId}', [EmployeeManagementController::class, 'getCurrentAttendanceStatus']);
    });

    // Employee API Routes
    Route::prefix('employee')->group(function () {


        // Get user schedule for mobile
        Route::get('/schedule/{userId}', [EmployeeManagementController::class, 'getUserScheduleAPI']);

        // Get salary info for mobile
        Route::get('/salary/{userId}', [EmployeeManagementController::class, 'getSalaryInfo']);
    });


    Route::get('/commissions/today', [CommissionController::class, 'getTodayCommissions']);
    Route::get('/commissions/my-today', [CommissionController::class, 'getMyTodayCommission']);


});


Route::prefix('orders')->middleware(['auth:sanctum'])->group(function () {
    // Mendapatkan data dengan berbagai filter
    Route::get('/', [OrderApiController::class, 'getOrders']);
    Route::get('/recent', [OrderApiController::class, 'getRecentOrders']); // Endpoint baru untuk pesanan terbaru
    Route::get('/summary', [OrderApiController::class, 'getOrdersSummary']); // Endpoint baru untuk ringkasan

    // Detail pesanan
    Route::get('/{id}', [OrderApiController::class, 'getOrderDetail']);
    Route::get('/{id}/low-stock-items', [OrderApiController::class, 'getLowStockItems']);

    // Pencarian sparepart untuk ditambahkan ke pesanan
    Route::get('/search/spareparts', [OrderApiController::class, 'searchSpareparts']);

    // Membuat dan mengelola pesanan
    Route::post('/', [OrderApiController::class, 'createOrder']);
    Route::put('/{id}', [OrderApiController::class, 'updateOrder']);
    Route::post('/{id}/finalize', [OrderApiController::class, 'finalizeOrder']);

    // Mengelola item pesanan
    Route::post('/{id}/items', [OrderApiController::class, 'addOrderItem']);
    Route::post('/{id}/items/multiple', [OrderApiController::class, 'addMultipleItems']);
    Route::delete('/items/{itemId}', [OrderApiController::class, 'removeOrderItem']);

});
// API Routes untuk Stock Opname
Route::prefix('stock-opname')->middleware(['auth:sanctum'])->group(function () {
    // Periode Stock Opname
    Route::get('/periods', [StockOpnameController::class, 'getPeriods']);
    Route::get('/periods/{id}', [StockOpnameController::class, 'getPeriodDetail']);
    Route::post('/periods', [StockOpnameController::class, 'createPeriod']);
    Route::put('/periods/{id}/start', [StockOpnameController::class, 'startProcess']);
    Route::put('/periods/{id}/complete', [StockOpnameController::class, 'completePeriod']);
    Route::put('/periods/{id}/cancel', [StockOpnameController::class, 'cancelPeriod']);

    // Item Stock Opname
    Route::get('/periods/{id}/pending-items', [StockOpnameController::class, 'getPendingItems']);
    Route::get('/periods/{id}/checked-items', [StockOpnameController::class, 'getCheckedItems']);
    Route::post('/periods/{id}/scan', [StockOpnameController::class, 'scanSparepart']);
    Route::post('/periods/{periodId}/items/{detailId}/check', [StockOpnameController::class, 'saveItemCheck']);

    // Penyesuaian Stok
    Route::get('/periods/{periodId}/items/{detailId}/adjustment', [StockOpnameController::class, 'getAdjustmentDetail']);
    Route::post('/periods/{periodId}/items/{detailId}/adjustment', [StockOpnameController::class, 'saveAdjustment']);
    Route::post('/periods/{periodId}/add-new-item', [StockOpnameController::class, 'addNewItem']);

    // Laporan
    Route::get('/periods/{id}/report', [StockOpnameController::class, 'getReport']);
    Route::get('/periods/{id}/items-with-selisih', [StockOpnameController::class, 'getItemsWithSelisih']);

    // Tambahkan endpoint untuk kategori dan supplier
    Route::get('/categories', [StockOpnameController::class, 'getCategories']);
    Route::get('/suppliers', [StockOpnameController::class, 'getSuppliers']);
});
Route::prefix('customer')->group(function () {
    Route::get('/', [CustomerApiController::class, 'index']);
    Route::post('/', [CustomerApiController::class, 'store']);
    Route::get('/{id}', [CustomerApiController::class, 'show']);
    Route::put('/{id}', [CustomerApiController::class, 'update']);
    Route::delete('/{id}', [CustomerApiController::class, 'destroy']);

    // Additional API routes
    Route::get('/status/{status}', [CustomerApiController::class, 'getByStatus']);
    Route::post('/search', [CustomerApiController::class, 'search']);

    // Get new kode toko for form
    Route::get('/generate-kode', [CustomerApiController::class, 'getNewKodeToko']);
});
Route::get('/spareparts/cari', [SalesApiController::class, 'cari']);
Route::get('/cek-service', [ServiceApiController::class,'cekService']);
Route::post('login', [AuthController::class, 'login']);
