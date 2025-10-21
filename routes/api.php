<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\DashboardController; // Assuming this has create_service_api, get_pending_services
use App\Http\Controllers\Api\ServiceApiController; // Your main Service API Controller
use App\Http\Controllers\Api\SparepartApiController; // Your Sparepart/Service Operations API Controller
use App\Http\Controllers\Api\PengambilanController;
use App\Http\Controllers\Api\SalesApiController;
use App\Http\Controllers\Api\UserDataController;
use App\Http\Controllers\Api\WhatsAppMessageController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\StockOpnameController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Admin\HpController; // Assuming for legacy /hp
use App\Http\Controllers\Api\HpApiController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\FinancialReportApiController;
use App\Http\Controllers\Api\PengeluaranApiController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\TokoSettingController;
use App\Http\Controllers\Api\ProductSearchApiController;
use App\Http\Controllers\Admin\EmployeeManagementController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Api\HutangApiController;
use App\Http\Controllers\Api\PembelianApiController;
use Illuminate\Support\Facades\Broadcast;

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
// Public routes (no authentication required)
Route::get('/spareparts/cari', [SalesApiController::class, 'cari']); // Example public search
Route::get('/cek-service', [ServiceApiController::class,'cekService']); // Example public service check
Route::post('login', [AuthController::class, 'login']);
Route::post('/webhooks/macrodroid', [WebhookController::class, 'handleMacrodroid']);
Broadcast::routes(['middleware' => ['auth:sanctum']]);
Route::middleware('auth:sanctum')->group(function () {

    // Pindahkan route ini dari grup di bawah ke sini
    Route::get('/subscription-plans', function () {
        return \App\Models\SubscriptionPlan::orderBy('price')->get();
    });

    // Anda juga bisa meletakkan route lain yang tidak butuh langganan di sini
    // contohnya: /logout, /user-profile-dasar, dll.
    Route::get('/validate-token', [AuthController::class, 'validateToken']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

     // Untuk mendapatkan daftar tagihan pending
    Route::get('/pending-payments', [App\Http\Controllers\Api\SubscriptionApiController::class, 'getPendingPayments']);
    // Untuk memproses aktivasi via token
    Route::post('/subscriptions/activate-token', [App\Http\Controllers\Api\SubscriptionApiController::class, 'activateWithToken']);
    // Untuk mendapatkan data QRIS pembayaran
    Route::get('/subscriptions/payment/{plan}', [App\Http\Controllers\Api\SubscriptionApiController::class, 'showPayment']);
    // Untuk membatalkan pembayaran
    Route::delete('/payments/{payment}/cancel', [App\Http\Controllers\Api\SubscriptionApiController::class, 'cancelPayment']);
    // --------------------


});


Route::middleware('auth:sanctum', 'subscribed.api')->group(function () {

    // Authentication and User Data
    // Route::get('/validate-token', [AuthController::class, 'validateToken']);
    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::get('/product/search', [ProductSearchApiController::class, 'search']);

    //pembeliaan
    // Helper routes
    Route::get('/pembelian/search-variants', [PembelianApiController::class, 'searchVariants']);
    Route::get('/suppliers', [PembelianApiController::class, 'getSuppliers']);
    Route::get('/categories', [PembelianApiController::class, 'getCategories']);
    Route::get('/kategori/{kategori}/attributes', [PembelianApiController::class, 'getAttributesByCategory']);
    // Pembelian routes
    Route::get('/pembelian', [PembelianApiController::class, 'index']);
    Route::post('/pembelian', [PembelianApiController::class, 'store']);
    Route::get('/pembelian/{id}', [PembelianApiController::class, 'show']);
    Route::put('/pembelian/{id}', [PembelianApiController::class, 'update']);
    Route::post('/pembelian/{id}/finalize', [PembelianApiController::class, 'finalize']);

    // Items routes
    Route::post('/pembelian/{id}/items', [PembelianApiController::class, 'addItem']);
    Route::put('/pembelian/items/{detailId}', [PembelianApiController::class, 'updateItem']);
    Route::delete('/pembelian/items/{id}', [PembelianApiController::class, 'removeItem']);
    //end pembeliaan

    // pusat pencarian spareprt
    Route::get('/products/search', [ProductSearchApiController::class, 'search']);
    // sampai sini
    Route::get('/toko-settings', [TokoSettingController::class, 'getSettings']);
    Route::post('/toko-settings', [TokoSettingController::class, 'updateSettings']);

    Route::get('/user-profile/{kode_user}', [UserDataController::class, 'getUserProfile']);
    Route::post('/penarikan', [UserDataController::class, 'store_penarikan']);
    Route::get('/karyawan', [UserDataController::class, 'getKaryawan']);
    Route::post('/admin/penarikan-karyawan', [UserDataController::class, 'adminWithdrawEmployee']);
    // Route::get('/admin/penarikan-history', [UserDataController::class, 'adminWithdrawalHistory']);
    Route::get('/admin/penarikan-summary', [UserDataController::class, 'adminWithdrawalSummary']);
    // NEW: History penarikan routes
    Route::get('/admin-withdrawal-history', [UserDataController::class, 'adminWithdrawalHistory']);
    Route::post('/assign-laci-withdrawal/{withdrawalId}', [UserDataController::class, 'assignLaciToWithdrawal']);
    Route::post('/bulk-assign-laci', [UserDataController::class, 'bulkAssignLaci']);
    Route::get('/admin-withdrawal-summary', [UserDataController::class, 'adminWithdrawalSummary']);
    Route::get('/employee-withdrawal-history', [UserDataController::class, 'employeeWithdrawalHistory']);


    // TAMBAHAN: Route untuk breakdown laci
    Route::get('/laci-breakdown', [FinancialReportApiController::class, 'getLaciBreakdown']);
    // TAMBAHAN: Route untuk detail history laci tertentu
    Route::get('/laci-history/{laciId}', [FinancialReportApiController::class, 'getLaciHistory']);
    // TAMBAHAN: Route untuk export breakdown laci
    Route::get('/laci-breakdown/export', [FinancialReportApiController::class, 'exportLaciBreakdown']);

    // Financial & Reports (existing, tetap sama)
    Route::get('/financial-report', [FinancialReportApiController::class, 'getFinancialReport']);
    Route::get('/profit-allocation/preview', [FinancialReportApiController::class, 'getProfitAllocationPreview']);
    Route::post('/profit-allocation/distribute', [FinancialReportApiController::class, 'processProfitDistribution']);

    Route::get('/allocation/balances', [FinancialReportApiController::class, 'getAllocationBalances']);
    Route::post('/allocation/withdraw', [FinancialReportApiController::class, 'processAllocationWithdrawal']);

    // Admin/Employee Attendance & Payroll related
    Route::post('/attendance/scan', [Api\AttendanceController::class, 'scanQrCode']); // Assuming attendance controller in Api folder
    Route::post('/attendance/request-leave', [Api\AttendanceController::class, 'requestLeave']);
    Route::get('/attendance/status', [Api\AttendanceController::class, 'getStatus']);
    Route::get('/employees/list', [EmployeeManagementController::class, 'getEmployeeList']);
    Route::post('/attendance/manual-checkin', [EmployeeManagementController::class, 'manualCheckIn']);
    Route::post('/attendance/manual-checkout', [EmployeeManagementController::class, 'manualCheckOut']);
    Route::get('/attendance/employee-status/{employeeId}', [EmployeeManagementController::class, 'getEmployeeAttendanceStatus']);

    Route::prefix('attendance')->group(function () {
        Route::post('/generate-employee-qr', [EmployeeManagementController::class, 'generateEmployeeQrCode']);
        Route::post('/scan-employee-qr', [EmployeeManagementController::class, 'scanEmployeeQrCode']);
        Route::post('/scan/{token}', [EmployeeManagementController::class, 'scanQrCode']);
        Route::post('/request-leave', [EmployeeManagementController::class, 'requestLeave']);
        Route::get('/history/{userId}', [EmployeeManagementController::class, 'getAttendanceHistory']);
        Route::get('/status/{userId}', [EmployeeManagementController::class, 'getCurrentAttendanceStatus']);
    });
    Route::prefix('employee')->group(function () {
        Route::get('/schedule/{userId}', [EmployeeManagementController::class, 'getUserScheduleAPI']);
        Route::get('/salary/{userId}', [EmployeeManagementController::class, 'getSalaryInfo']);
    });
    Route::get('/commissions/today', [CommissionController::class, 'getTodayCommissions']);
    Route::get('/commissions/my-today', [CommissionController::class, 'getMyTodayCommission']);

    // Service Management (General & Completed Specific)
    Route::post('/create-service', [DashboardController::class, 'create_service_api']); // Assuming this creates new services
    Route::post('/pending-services', [DashboardController::class, 'get_pending_services']); // Assuming this gets uncompleted services

    // Services General (can be used by both Flutter & Admin Panel)
    Route::get('/services/getServiceDetails/{id}', [SparepartApiController::class, 'getServiceDetails']); // Retrieve full service details
    Route::put('/services/{id}', [SparepartApiController::class, 'updateService']); // Update core service details (can apply to any status)
    Route::delete('/services/{id}/delete', [SparepartApiController::class, 'delete_service']); // Delete service (can apply to any status)
    Route::put('/service/updateServiceStatus/{id}', [SparepartApiController::class, 'updateServiceStatus']); // Update service status and trigger commission logic
    Route::put('/services/{id}/revert-to-queue', [SparepartApiController::class, 'revertServiceToQueue']); //

    // Service Status & Indicators (from ServiceApiController)
    Route::get('/services/completed-today', [ServiceApiController::class, 'getCompletedToday']);
    Route::get('/services/completedAll', [ServiceApiController::class, 'getCompletedservice']);
    Route::get('/services/{serviceId}/status', [ServiceApiController::class, 'checkServiceStatus']);
    Route::get('/services/indicators', [SparepartApiController::class, 'getServiceIndicators']); // Check if this should be in ServiceApiController
    Route::get('/services/search-extended', [ServiceApiController::class, 'searchServiceExtended']);
    Route::get('/services/quick-search', [ServiceApiController::class, 'quickSearchSuggestions']);
    Route::post('/services/advanced-search', [ServiceApiController::class, 'advancedSearchServices']);


    // **ROUTE PENTING DARI FILE LAMA - JANGAN DIHAPUS**
    // Route untuk fungsi search sparepart dengan command system
    Route::post('/service/search-sparepart', [SparepartApiController::class, 'search_sparepart']); // FUNGSI COMMAND SEARCH

    // **ROUTE DETAIL SERVICE LAMA - JANGAN DIHAPUS**
    // Route untuk detail service dengan struktur data lengkap (berbeda dari getServiceDetails)
    Route::get('/services/detail/{id}', [SparepartApiController::class, 'detail_service']); // FUNGSI DETAIL SERVICE LAMA

    // Sparepart Management (General / Initial Service)
    Route::get('/sparepart-toko/search', [SparepartApiController::class, 'searchSparepartToko']); // Search for store parts (generic)
    Route::post('/sparepart-toko', [SparepartApiController::class, 'storeSparepartToko']); // Add store part (generic, no commission recalculation here)
    Route::delete('/sparepart-toko/{detailPartId}', [SparepartApiController::class, 'deletePartTokoFromService']); // Delete store part (generic, no commission recalculation here)

    // **ROUTE SPAREPART LUAR GENERIC - DARI FILE LAMA**
    Route::post('/sparepart-luar', [SparepartApiController::class, 'storeSparepartLuar']); // GENERIC untuk service belum selesai
    Route::put('/sparepart-luar/{id}', [SparepartApiController::class, 'updateSparepartLuar']); // GENERIC untuk service belum selesai
    Route::delete('/sparepart-luar/{id}', [SparepartApiController::class, 'deleteSparepartLuar']); // GENERIC untuk service belum selesai

    // Sparepart Management (Specifically for COMPLETED Services - with Commission Recalculation)
    Route::post('/completed-services/sparepart-toko', [SparepartApiController::class, 'addPartTokoToCompletedService']); // Renamed route, now explicit
    Route::put('/completed-services/sparepart-toko/{detailPartId}', [SparepartApiController::class, 'updatePartTokoQuantityForCompletedService']); // New route for updating qty
    Route::delete('/completed-services/sparepart-toko/{detailPartId}', [SparepartApiController::class, 'deletePartTokoFromCompletedService']); // Renamed route, now explicit

    Route::post('/completed-services/sparepart-luar', [SparepartApiController::class, 'addPartLuarToCompletedService']); // Renamed route, now explicit
    Route::put('/completed-services/sparepart-luar/{detailPartLuarId}', [SparepartApiController::class, 'updatePartLuarForCompletedService']); // Renamed route, now explicit
    Route::delete('/completed-services/sparepart-luar/{detailPartLuarId}', [SparepartApiController::class, 'deletePartLuarFromCompletedService']); // Renamed route, now explicit

    // Explicit Commission Recalculation (manual trigger if needed)
    Route::post('/services/{serviceId}/recalculate-commission', [SparepartApiController::class, 'recalculateCommission']);

    // Pengambilan (Pickup)
    Route::post('/pengambilan', [PengambilanController::class, 'store']);
    Route::get('/services/available', [PengambilanController::class, 'getAvailableServices']);
    Route::get('/kategori-laci', [PengambilanController::class, 'getKategoriLaciList']);

    // Sales (POS) related
    Route::get('/sparepart/suggestions', [SalesApiController::class, 'searchSuggestions']);
    Route::get('/spareparts/search', [SalesApiController::class, 'search']);
    Route::get('spareparts/{id}', [SalesApiController::class, 'getSparepartById']);
    Route::get('/spareparts/popular', [SalesApiController::class, 'getPopularSearches']);
    Route::get('/service/search', [ServiceApiController::class, 'allservice']); // This is for all services search by general query
    Route::get('/sales-history', [SalesApiController::class, 'getSalesHistory']);
    Route::post('/sales', [SalesApiController::class, 'createSale']);
    Route::post('/updateSale', [SalesApiController::class, 'updateSaleStatus']);
    Route::post('/pemasukan', [SalesApiController::class, 'createPemasukkanLainApi']);
    Route::get('/sales/{id}/detail', [SalesApiController::class, 'getSaleDetail']);
    Route::put('/sales/{id}/update', [SalesApiController::class, 'updateSale']);
    Route::delete('/sales/{id}/delete', [SalesApiController::class, 'deleteSale']);
    Route::put('/sales/{id}/cancel', [SalesApiController::class, 'cancelSale']);

    // **ROUTE SUPPLIERS DARI FILE LAMA - JANGAN DIHAPUS**
    Route::get('/suppliers', [OrderApiController::class, 'getSuppliers']); // ROUTE SUPPLIERS LANGSUNG

    // Orders Management (Grouped for clarity)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderApiController::class, 'getOrders']);
        Route::get('/recent', [OrderApiController::class, 'getRecentOrders']);
        Route::get('/summary', [OrderApiController::class, 'getOrdersSummary']);
        Route::get('/{id}', [OrderApiController::class, 'getOrderDetail']);
        Route::get('/{id}/low-stock-items', [OrderApiController::class, 'getLowStockItems']);
        Route::get('/search/spareparts', [OrderApiController::class, 'searchSpareparts']);
        Route::post('/', [OrderApiController::class, 'createOrder']);
        Route::put('/{id}', [OrderApiController::class, 'updateOrder']);
        Route::post('/{id}/finalize', [OrderApiController::class, 'finalizeOrder']);
        Route::post('/{id}/items', [OrderApiController::class, 'addOrderItem']);
        Route::post('/{id}/items/multiple', [OrderApiController::class, 'addMultipleItems']);
        Route::delete('/items/{itemId}', [OrderApiController::class, 'removeOrderItem']);
    });

    // Warranty (Garansi) routes
    Route::post('/warranty/store', [SparepartApiController::class, 'storeGaransiService']);
    Route::put('/warranty/{id}', [SparepartApiController::class, 'updateGaransiService']);
    Route::delete('/warranty/{id}', [SparepartApiController::class, 'deleteGaransiService']);
    Route::get('/warranty/{kode_service}', [SparepartApiController::class, 'getGaransiService']);

    // claim garansi
    Route::post('/services/{originalServiceId}/claim-warranty', [ServiceApiController::class, 'initiateWarrantyClaim']);

    // Service Notes routes
    Route::post('/service-notes/store', [SparepartApiController::class, 'storeCatatanService']);
    Route::put('/service-notes/{id}', [SparepartApiController::class, 'updateCatatanService']);
    Route::delete('/service-notes/{id}', [SparepartApiController::class, 'deleteCatatanService']);
    Route::get('/service-notes/{service_id}', [SparepartApiController::class, 'getCatatanService']);

    // Financial & Reports
    Route::get('/pengeluaran-kategori', [PengeluaranApiController::class, 'getKategoriPengeluaran']);

    Route::get('pengeluaran-toko', [PengeluaranApiController::class, 'getPengeluaranToko'])->name('api.pengeluaran-toko.index');
    Route::post('pengeluaran-toko', [PengeluaranApiController::class, 'storePengeluaranToko'])->name('api.pengeluaran-toko.store');
    Route::get('pengeluaran-toko/{id}', [PengeluaranApiController::class, 'showPengeluaranToko'])->name('api.pengeluaran-toko.show');
    Route::put('pengeluaran-toko/{id}', [PengeluaranApiController::class, 'updatePengeluaranToko'])->name('api.pengeluaran-toko.update');
    Route::delete('pengeluaran-toko/{id}', [PengeluaranApiController::class, 'deletePengeluaranToko'])->name('api.pengeluaran-toko.delete');
    Route::get('pengeluaran-operasional', [PengeluaranApiController::class, 'getPengeluaranOperasional'])->name('api.pengeluaran-operasional.index');
    Route::get('beban-operasional-list', [PengeluaranApiController::class, 'getBebanOperasionalList']);

    Route::post('pengeluaran-operasional', [PengeluaranApiController::class, 'storePengeluaranOperasional'])->name('api.pengeluaran-operasional.store');
    Route::get('pengeluaran-operasional/{id}', [PengeluaranApiController::class, 'showPengeluaranOperasional'])->name('api.pengeluaran-operasional.show');
    Route::put('pengeluaran-operasional/{id}', [PengeluaranApiController::class, 'updatePengeluaranOperasional'])->name('api.pengeluaran-operasional.update');
    Route::delete('pengeluaran-operasional/{id}', [PengeluaranApiController::class, 'deletePengeluaranOperasional'])->name('api.pengeluaran-operasional.delete');

    Route::get('employees', [PengeluaranApiController::class, 'getEmployees'])->name('api.employees');
    Route::get('pengeluaran-summary', [PengeluaranApiController::class, 'getSummary'])->name('api.pengeluaran.summary');
    Route::get('/financial-report', [FinancialReportApiController::class, 'getFinancialReport']);
    Route::get('/financial-report/detailed', [FinancialReportApiController::class, 'getDetailedReport']);
    Route::get('/financial-report/daily', [FinancialReportApiController::class, 'getDailyReport']);
    Route::get('/financial-report/service-loss', [FinancialReportApiController::class, 'getServiceLossDetails']);
    Route::get('/financial-report/summary', [FinancialReportApiController::class, 'getFinancialSummary']);
    Route::get('/financial-report/technician-analysis', [FinancialReportApiController::class, 'getTechnicianProfitAnalysis']);
    Route::post('/financial-report/export', [FinancialReportApiController::class, 'exportFinancialReport']);

    // Route::prefix('device-statistics')->group(function () {
    //     Route::get('/', [FinancialReportApiController::class, 'getDeviceStatistics']);
    //     Route::get('/trends', [FinancialReportApiController::class, 'getDeviceTrends']);
    //     Route::get('/comparison', [FinancialReportApiController::class, 'getDeviceComparison']);
    // });

    // Daily Device Monitoring Routes
    Route::get('/financial-report/daily-device-monitoring', [FinancialReportApiController::class, 'getDailyDeviceMonitoring']);
    Route::get('/financial-report/device-pickup-alerts', [FinancialReportApiController::class, 'getDevicePickupAlerts']);
    Route::post('/financial-report/update-device-pickup-status', [FinancialReportApiController::class, 'updateDevicePickupStatus']);

    // Or add them to the existing financial-report group:
    Route::get('/financial-report/device-statistics', [FinancialReportApiController::class, 'getDeviceStatistics']);
    Route::get('/financial-report/device-trends', [FinancialReportApiController::class, 'getDeviceTrends']);
    Route::get('/financial-report/device-comparison', [FinancialReportApiController::class, 'getDeviceComparison']);

    // Get daily report services (with pagination)
    Route::get('/services/dailyReport', [ServiceApiController::class, 'getDailyReportServices']);
    // Get daily report grouped by date
    Route::get('/services/dailyReportGrouped', [ServiceApiController::class, 'getDailyReportGrouped']);

    Route::get('/hutang', [HutangApiController::class, 'index'])->name('hutang.index');
    Route::post('/hutang/bayar/{id}', [HutangApiController::class, 'bayar'])->name('hutang.bayar');

    // Customer Management
    Route::prefix('customer')->group(function () {
        Route::get('/', [CustomerApiController::class, 'index']);
        Route::post('/', [CustomerApiController::class, 'store']);
        Route::get('/{id}', [CustomerApiController::class, 'show']);
        Route::put('/{id}', [CustomerApiController::class, 'update']);
        Route::delete('/{id}', [CustomerApiController::class, 'destroy']);
        Route::get('/status/{status}', [CustomerApiController::class, 'getByStatus']);
        Route::post('/search', [CustomerApiController::class, 'search']);
        Route::get('/generate-kode', [CustomerApiController::class, 'getNewKodeToko']);
    });

    // HP (Device Model) Management
    // **ROUTE HP DARI FILE LAMA - JANGAN DIHAPUS**
    Route::get('/hp', [HpApiController::class, 'search']); // Use HpApiController for all /hp routes
    Route::get('/hp/search', [HpApiController::class, 'search']);
    Route::get('/hp/type', [HpApiController::class, 'searchByType']);
    Route::get('/hp/filters', [HpApiController::class, 'filters']);
    Route::get('/hp/{id}', [HpApiController::class, 'detail']);
    Route::post('/hp/suggest', [HpController::class, 'apiSuggest']); // Assuming this is for suggestions logic

    // Stock Opname
    Route::prefix('stock-opname')->group(function () {
        Route::get('/periods', [StockOpnameController::class, 'getPeriods']);
        Route::get('/periods/{id}', [StockOpnameController::class, 'getPeriodDetail']);
        Route::post('/periods', [StockOpnameController::class, 'createPeriod']);
        Route::put('/periods/{id}/start', [StockOpnameController::class, 'startProcess']);
        Route::put('/periods/{id}/complete', [StockOpnameController::class, 'completePeriod']);
        Route::put('/periods/{id}/cancel', [StockOpnameController::class, 'cancelPeriod']);
        Route::get('/periods/{id}/pending-items', [StockOpnameController::class, 'getPendingItems']);
        Route::get('/periods/{id}/checked-items', [StockOpnameController::class, 'getCheckedItems']);
        Route::post('/periods/{id}/scan', [StockOpnameController::class, 'scanSparepart']);
        Route::post('/periods/{periodId}/items/{detailId}/check', [StockOpnameController::class, 'saveItemCheck']);
        Route::get('/periods/{periodId}/items/{detailId}/adjustment', [StockOpnameController::class, 'getAdjustmentDetail']);
        Route::post('/periods/{periodId}/items/{detailId}/adjustment', [StockOpnameController::class, 'saveAdjustment']);
        Route::post('/periods/{periodId}/add-new-item', [StockOpnameController::class, 'addNewItem']);
        Route::get('/periods/{id}/report', [StockOpnameController::class, 'getReport']);
        Route::get('/periods/{id}/items-with-selisih', [StockOpnameController::class, 'getItemsWithSelisih']);
        Route::get('/categories', [StockOpnameController::class, 'getCategories']);
        Route::get('/suppliers', [StockOpnameController::class, 'getSuppliers']);
    });

    // WhatsApp Integration
    Route::post('/send-message', [WhatsAppMessageController::class, 'sendMessage']);

}); // End of auth:sanctum middleware group




