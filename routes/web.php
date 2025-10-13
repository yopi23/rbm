<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HandphoneController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PengeluaranController;
use App\Http\Controllers\Admin\PengembalianController;
use App\Http\Controllers\Admin\PenjualanController;
use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\Admin\PresentaseController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SparePartController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\FinancialController;
use App\Http\Controllers\Administrator\LaporanOwnerController;
use App\Http\Controllers\Administrator\OwnerController;
use App\Http\Controllers\Ajax\AjaxRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FrontController\PageController;
use App\Http\Controllers\FrontController\PesananController as FrontControllerPesananController;
use App\Http\Controllers\FrontController\ProdukController;
use App\Http\Controllers\FrontController\ServiceController as FrontControllerServiceController;
use App\Http\Controllers\FrontController\SparepartController as FrontControllerSparepartController;
use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaciController;
use App\Http\Controllers\SearchController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\Admin\StockManagementController;
use App\Http\Controllers\Admin\PembelianController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\EmployeeManagementController;
use App\Http\Controllers\Admin\HpController;
use App\Http\Controllers\Admin\AsetController;
use App\Http\Controllers\Admin\PenaltyRulesController;
use App\Http\Controllers\Admin\AttendanceCronController;
use App\Http\Controllers\Admin\BebanOperasionalController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Administrator\TokenController;
use App\Http\Controllers\Administrator\PlanController;
use App\Http\Controllers\Administrator\SubscriptionLogController;
use App\Http\Controllers\Admin\PriceSettingController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\ReviewController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Front
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
Route::post('/sign_up', [AuthController::class, 'sign_up'])->name('sign_up');
Route::post('/signout', [AuthController::class, 'logout'])->name('signout');

//Home
Route::get('/', [PageController::class, 'index'])->name('home');
//Service
Route::get('/service', [FrontControllerServiceController::class, 'index'])->name('service');
//Produk
Route::get('/product', [ProdukController::class, 'index'])->name('product');
//Sparepart
Route::get('/spareparts', [FrontControllerSparepartController::class, 'index'])->name('spareparts');

//Keranjang
Route::get('/keranjang', [FrontControllerPesananController::class, 'cart'])->name('cart');
Route::put('/keranjang/{id}/add_produk', [FrontControllerPesananController::class, 'pesan_produk'])->name('add_produk_cart');
Route::put('/keranjang/{id}/add_sparepart', [FrontControllerPesananController::class, 'pesan_sparepart'])->name('add_sparepart_cart');
Route::delete('/keranjang/{id}/delete_produk', [FrontControllerPesananController::class, 'delete_produk_in_cart'])->name('delete_produk_cart');
Route::delete('/keranjang/{id}/delete_sparepart', [FrontControllerPesananController::class, 'delete_sparepart_in_cart'])->name('delete_sparepart_cart');
//Checkout
Route::get('/checkout', [FrontControllerPesananController::class, 'checkout'])->name('checkout');
Route::post('/checkout/submit', [FrontControllerPesananController::class, 'buat_pesanan'])->name('buat_pesanan');

//Ajax Controller
Route::get('search_kode_invite', [AjaxRequestController::class, 'search_kode_invite'])->name('search_kode_invite');

// Route untuk form pengisian laci
// Route::get('/laci/form', [LaciController::class, 'form'])->name('laci.form');
// Route::post('/laci/store', [LaciController::class, 'store'])->name('laci.store');
// Route::get('pembelian/search-variants-ajax', [\App\Http\Controllers\Admin\PembelianController::class, 'searchVariantsAjax'])->name('pembelian.search-variants-ajax');

Route::get('/laci/form', [LaciController::class, 'form'])->name('laci.form');
Route::post('/laci/store', [LaciController::class, 'store'])->name('laci.store');
Route::post('/laci/updatereal', [LaciController::class, 'updatereal'])->name('laci.updatereal');
Route::post('/laci/kategori', [LaciController::class, 'kategori_laci'])->name('kategori_laci');
Route::delete('/laci/kategori', [LaciController::class, 'deleteKategoriLaci'])->name('delete_kategori_laci');

// New DataTable routes
Route::get('/laci/riwayat/data', [LaciController::class, 'getRiwayatData'])->name('laci.riwayat.data');
Route::get('/laci/komisi/data', [LaciController::class, 'getKomisiData'])->name('laci.komisi.data');
Route::get('/laci/penarikan/data', [LaciController::class, 'getPenarikanData'])->name('laci.penarikan.data');

Route::middleware(['auth'])->group(function () {

    /**
     * ===================================================================
     * ROUTE UNTUK ADMIN / OWNER (Jabatan 1)
     * Mengelola langganan mereka sendiri.
     * ===================================================================
     */
    Route::prefix('subscriptions')->name('subscriptions.')->controller(SubscriptionController::class)->group(function () {
        // Halaman utama langganan (GET /subscriptions)
        Route::get('/', 'index')->name('index');

        // Proses aktivasi token (POST /subscriptions/activate)
        Route::post('/activate', 'activateWithToken')->name('activate');

        // Menampilkan halaman pembayaran (GET /subscriptions/payment/{plan})
        Route::get('/payment/{plan}', 'showPayment')->name('payment');
        Route::delete('/payment/{payment}/cancel', 'cancelPayment')->name('payment.cancel');
    });




});
Route::group(['middleware' => 'checkRole:0'], function () {
 /**
     * ===================================================================
     * ROUTE UNTUK ADMINISTRATOR / SUPER ADMIN (Jabatan 0)
     * Mengelola token untuk semua admin.
     * ===================================================================
     */
    // Sebaiknya, grup ini dilindungi oleh middleware khusus untuk Super Admin
    // Contoh: ->middleware('isAdministrator')
    Route::prefix('administrator')->name('administrator.')->group(function () {

        Route::prefix('tokens')->name('tokens.')->controller(TokenController::class)->group(function() {
            // Halaman utama manajemen token (GET /administrator/tokens)
            Route::get('/', 'index')->name('index');

            // Proses pembuatan token baru (POST /administrator/tokens)
            Route::post('/', 'store')->name('store');
            Route::resource('plans', PlanController::class);
            Route::get('subscription-logs', [SubscriptionLogController::class, 'index'])->name('logs.index');
        });

    });
});

//admin
Route::group(['middleware' => ['auth']], function () {
    //Admin Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [UserController::class, 'view_profile'])->name('profile');
    Route::put('profile/{id}/update', [UserController::class, 'update_profile'])->name('update_profile');
    Route::group(['middleware' => ['authCheck:0']], function () {
        Route::resource('owner', OwnerController::class);
        Route::get('/laporan_owner', [LaporanOwnerController::class, 'index'])->name('laporan_owner');
        Route::get('laporan_owner/print', [PDFController::class, 'print_laporan_owner'])->name('print_laporan_owner');
    });
    Route::get('dashboard/{id}/cetak_nota_service', [PDFController::class, 'nota_service'])->name('nota_service');
    Route::get('dashboard/{id}/cetak_nota_tempel', [PDFController::class, 'nota_tempel'])->name('nota_tempel');
    Route::get('todolist/{id}/cetak_selesai', [PDFController::class, 'nota_tempel_selesai'])->name('nota_tempel_selesai');
    Route::get('sparepart/{id}/cetak_barcode', [PDFController::class, 'tag_name'])->name('Barcode_barang');

    //Pemasukkan Lain
    Route::post('pemasukkan_lain/create', [DashboardController::class, 'create_pemasukkan_lain'])->name('create_pemasukkan_lain');
    Route::delete('pemasukkan_lain/{id}/destroy', [DashboardController::class, 'delete_pemasukkan_lain'])->name('delete_pemasukkan_lain');
    Route::post('service/create_in_dashboard', [DashboardController::class, 'create_service'])->name('create_service_in_dashboard');
    //Catatan
    Route::post('catatan/create', [DashboardController::class, 'create_catatan'])->name('create_catatan');
    Route::delete('catatan/{id}/destroy', [DashboardController::class, 'delete_catatan'])->name('delete_catatan');
    //List Order
    //Catatan
    Route::post('list_order/create', [DashboardController::class, 'create_list_order'])->name('create_list_order');
    Route::delete('list_order/{id}/destroy', [DashboardController::class, 'delete_list_order'])->name('delete_list_order');
    //Penarikan Gaji
    Route::post('penarikan/store', [UserController::class, 'store_penarikan'])->name('store_penarikan');
    Route::delete('penarikan/{id}/destroy', [UserController::class, 'delete_penarikan'])->name('delete_penarikan');
    Route::put('penarikan/{id}/update', [UserController::class, 'update_penarikan'])->name('update_penarikan');

    Route::group(['middleware' => ['authCheck:1']], function () {
        //harga
        // Route::get('pengaturan-harga', [PriceSettingController::class, 'index'])->name('price-settings.index');
        // Route::post('pengaturan-harga', [PriceSettingController::class, 'storeOrUpdate'])->name('price-settings.store');
        //Produk
        Route::get('/produk', [HandphoneController::class, 'view_produk'])->name('produk');
        Route::get('/produk/create', [HandphoneController::class, 'create_produk'])->name('create_produk');
        Route::get('produk/{id}/edit', [HandphoneController::class, 'edit_produk'])->name('edit_produk');
        Route::post('produk/store', [HandphoneController::class, 'store_produk'])->name('store_produk');
        Route::put('produk/{id}/update', [HandphoneController::class, 'update_produk'])->name('update_produk');
        Route::delete('produk/{id}/delete', [HandphoneController::class, 'delete_produk'])->name('delete_produk');

        //Kategori Produk
        Route::get('/admin/api/kategori/{kategori}/attributes', function(App\Models\KategoriSparepart $kategori) {
            return response()->json($kategori->attributes()->with('values')->get());
        });
        Route::get('/kategori_produk', [HandphoneController::class, 'view_kategori'])->name('kategori_produk');
        Route::get('/kategori_produk/create', [HandphoneController::class, 'create_kategori_produk'])->name('create_kategori_produk');
        Route::get('kategori_produk/{id}/edit', [HandphoneController::class, 'edit_kategori_produk'])->name('EditKategoriProduk');
        Route::post('kategori_produk/store', [HandphoneController::class, 'store_kategori_produk'])->name('StoreKategoriProduk');
        Route::put('kategori_produk/{id}/update', [HandphoneController::class, 'update_kategori_produk'])->name('UpdateKategoriProduk');
        Route::delete('kategori_produk/{id}/destroy', [HandphoneController::class, 'delete_kategori_produk'])->name('DeleteKategoriProduk');

        //Sparepart
        Route::get('sparepart', [SparePartController::class, 'view_sparepart'])->name('sparepart');
        Route::get('sparepart/create', [SparePartController::class, 'create_sparepart'])->name('create_sparepart');
        Route::get('sparepart/{id}/edit', [SparePartController::class, 'edit_sparepart'])->name('EditSparepart');
        Route::post('sparepart/store', [SparePartController::class, 'store_sparepart'])->name('StoreSparepart');
        Route::put('sparepart/{id}/update', [SparePartController::class, 'update_sparepart'])->name('UpdateSparepart');
        Route::delete('sparepart/{id}/destroy', [SparePartController::class, 'delete_sparepart'])->name('DeleteSparepart');
        Route::post('/spareparts/bulk-update', [SparePartController::class, 'bulkUpdate'])->name('spareparts.bulk-update');
        Route::post('/spareparts/get-details', [SparePartController::class, 'getDetailsForEdit'])->name('spareparts.get-details');

        //Kategori Sparepart
        Route::get('/kategori_sparepart', [SparePartController::class, 'view_kategori'])->name('kategori_sparepart');
        Route::get('/kategori_sparepart/create', [SparePartController::class, 'create_kategori'])->name('create_kategori_sparepart');
        Route::get('kategori_sparepart/{id}/edit', [SparePartController::class, 'edit_kategori_sparepart'])->name('EditKategoriSparepart');
        Route::post('kategori_sparepart/store', [SparePartController::class, 'store_kategori_sparepart'])->name('StoreKategoriSparepart');
        Route::put('kategori_sparepart/{id}/update', [SparePartController::class, 'update_kategori_sparepart'])->name('UpdateKategoriSparepart');
        Route::delete('kategori_sparepart/{id}/destroy', [SparePartController::class, 'delete_kategori_sparepart'])->name('DeleteKategoriSparepart');

        //penarikan
        Route::put('update_all_penarikan_statuses', [UserController::class, 'updateAllStatuses'])->name('update_all_penarikan_statuses');
        Route::post('/update_stok_sparepart', [SparePartController::class, 'update_stok_sparepart'])->name('update_stok_sparepart');
        Route::post('/pindah_komisi', [ServiceController::class, 'pindahKomisi'])->name('pindahKomisi');

        //zakat
        Route::get('/admin/zakat-usaha', [SparePartController::class, 'view_zakat'])->name('zakat_usaha');
        Route::post('/admin/zakat-usaha/update', [SparePartController::class, 'update_data_zakat'])->name('update_zakat');
    });


    Route::get('/list_all_service', [ServiceController::class, 'list_all_service'])->name('list_all_service');

    //plus
    // Route::get('/stok_sparepart', [SparePartController::class, 'view_stok'])->name('stok_sparepart');

    Route::get('/update-harga-ecer',  [SparePartController::class, 'updateHargaEcer'])->name('update.harga.ecer');
    // Route::post('/plusUpdate', [SparePartController::class, 'processData'])->name('plusUpdate');
    // Route::get('/plus', [SparePartController::class, 'plus'])->name('plus');
    //Stok Produk
    Route::get('/stok_produk', [HandphoneController::class, 'view_stok'])->name('stok_produk');

    //Restok Barang
    Route::get('restok_barang/create', [HandphoneController::class, 'create_restok'])->name('create_restok');
    Route::get('restok_barang/{id}/edit', [HandphoneController::class, 'edit_restok'])->name('edit_restok');
    Route::post('restok_barang/store', [HandphoneController::class, 'store_restok'])->name('store_restok');
    Route::put('restok_barang/{id}/update', [HandphoneController::class, 'update_restok'])->name('update_restok');
    Route::delete('restok_barang/{id}/destroy', [HandphoneController::class, 'delete_restok'])->name('delete_restok');

    //Barang Rusak
    Route::get('barang_rusak/create', [HandphoneController::class, 'create_barang_rusak'])->name('create_barang_rusak');
    Route::get('barang_rusak/{id}/edit', [HandphoneController::class, 'edit_barang_rusak'])->name('edit_barang_rusak');
    Route::post('barang_rusak/store', [HandphoneController::class, 'store_barang_rusak'])->name('store_barang_rusak');
    Route::put('barang_rusak/{id}/update', [HandphoneController::class, 'update_barang_rusak'])->name('update_barang_rusak');
    Route::delete('barang_rusak/{id}/destroy', [HandphoneController::class, 'delete_barang_rusak'])->name('delete_barang_rusak');


    //Stok Sparepart
    Route::get('/stok_sparepart', [SparePartController::class, 'view_stok'])->name('stok_sparepart');
    Route::post('/order/store', [SparePartController::class, 'store'])->name('order.store');
    Route::get('/list/orders', [SparepartController::class, 'view_order'])->name('orders.view');
    Route::post('/orders/update-status', [SparepartController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/update-spl', [SparepartController::class, 'updateSpl'])->name('orders.updateSpl');
    Route::post('/detail-order/update/{id}', [SparepartController::class, 'updateOrderToStock']);



    //Sparepart Restok
    Route::get('sparepart_restok/create', [SparePartController::class, 'create_sparepart_restok'])->name('create_sparepart_restok');
    Route::get('sparepart_restok/{id}/edit', [SparePartController::class, 'edit_sparepart_restok'])->name('edit_sparepart_restok');
    Route::post('sparepart_restok/store', [SparePartController::class, 'store_sparepart_restok'])->name('store_sparepart_restok');
    Route::put('sparepart_restok/{id}/update', [SparePartController::class, 'update_sparepart_restok'])->name('update_sparepart_restok');
    Route::delete('sparepart_restok/{id}/destroy', [SparePartController::class, 'delete_sparepart_restok'])->name('delete_sparepart_restok');

    //Sparepart Retur
    Route::get('sparepart_retur/create', [SparePartController::class, 'create_sparepart_retur'])->name('create_sparepart_retur');
    Route::post('refund/store', [SparePartController::class, 'store_sparepart_retur_toko'])->name('refund.store');
    Route::post('sparepart_retur/store', [SparePartController::class, 'store_sparepart_retur'])->name('store_sparepart_retur');
    Route::put('sparepart_retur/{id}/ubah_status', [SparePartController::class, 'ubah_status_retur'])->name('ubah_status_retur');

    //Sparepart Rusak
    Route::get('sparepart_rusak/create', [SparePartController::class, 'create_sparepart_rusak'])->name('create_sparepart_rusak');
    Route::get('sparepart_rusak/{id}/edit', [SparePartController::class, 'edit_sparepart_rusak'])->name('edit_sparepart_rusak');
    Route::post('sparepart_rusak/store', [SparePartController::class, 'store_sparepart_rusak'])->name('store_sparepart_rusak');
    Route::put('sparepart_rusak/{id}/update', [SparePartController::class, 'update_sparepart_rusak'])->name('update_sparepart_rusak');
    Route::delete('sparepart_rusak/{id}/destroy', [SparePartController::class, 'delete_sparepart_rusak'])->name('delete_sparepart_rusak');

    //Opname Sparepart

    Route::get('/opname_sparepart', [SparePartController::class, 'view_opname'])->name('opname_sparepart');
    Route::put('opname_sparepart/{id}/ubah_stok', [SparePartController::class, 'opname_ubah_stok'])->name('opname_sparepart_ubah_stok');
    Route::get('opname_sparepart/cetak', [PDFController::class, 'opname_sparepart'])->name('cetak_opname');

    //Repair Controller
    Route::get('/all_service', [ServiceController::class, 'view_all'])->name('all_service');
    // Route::get('all_service/{id}/edit', [ServiceController::class,'edit_service'])->name('edit_service');
    //Route::put('all_service/{id}/update', [ServiceController::class,'update_service'])->name('update_service');

    //Sparepart Toko
    Route::get('search_sparepart', [AjaxRequestController::class, 'search_sparepart'])->name('search_sparepart');
    Route::post('sparepart_toko/store', [ServiceController::class, 'store_sparepart_toko'])->name('store_sparepart_toko');
    Route::put('sparepart_toko/{id}/update', [ServiceController::class, 'update_sparepart_toko'])->name('update_sparepart_toko');
    Route::delete('sparepart_toko/{id}/delete', [ServiceController::class, 'delete_sparepart_toko'])->name('delete_sparepart_toko');
    Route::delete('delete_service/{id}/delete', [ServiceController::class, 'delete_service'])->name('delete_service');

    //Sparepart Luar
    Route::post('sparepart_luar/store', [ServiceController::class, 'store_sparepart_luar'])->name('store_sparepart_luar');
    Route::put('sparepart_luar/{id}/update', [ServiceController::class, 'update_sparepart_luar'])->name('update_sparepart_luar');
    Route::delete('sparepart_luar/{id}/delete', [ServiceController::class, 'delete_sparepart_luar'])->name('delete_sparepart_luar');

    //Catatan Service
    Route::post('catatan_service/store', [ServiceController::class, 'store_catatan_service'])->name('store_catatan_service');
    Route::delete('catatan_service/{id}/delete', [ServiceController::class, 'delete_catatan_service'])->name('delete_catatan_service');

    //Garansi Service
    Route::post('garansi_service/store', [ServiceController::class, 'store_garansi_service'])->name('store_garansi_service');
    Route::put('garansi_service/{id}/update', [ServiceController::class, 'update_garansi_service'])->name('update_garansi_service');
    Route::delete('garansi_service/{id}/delete', [ServiceController::class, 'delete_garansi_service'])->name('delete_garansi_service');

    Route::get('/todolist', [ServiceController::class, 'view_to_do'])->name('todolist');
    Route::put('/todolist/{id}/proses', [ServiceController::class, 'proses_service'])->name('proses_service');
    Route::put('/todolist/{id}/oper', [ServiceController::class, 'oper_service'])->name('oper_service');
    Route::get('/todolist/{id}/detail', [ServiceController::class, 'detail_service'])->name('detail_service');
    Route::put('/todolist/{id}/update', [ServiceController::class, 'update_detail_service'])->name('update_detail_service');
    //Transaksi
    //Penjualan
    Route::get('/penjualan', [PenjualanController::class, 'view_penjualan'])->name('penjualan');
    Route::get('/list/transaksi', [PenjualanController::class, 'view_riwayat_penjualan'])->name('transaksi');
    Route::get('penjualan/{id}/edit', [PenjualanController::class, 'edit'])->name('edit_penjualan');
    Route::put('penjualan/{id}/update', [PenjualanController::class, 'update'])->name('update_penjualan');
    Route::get('/penjualan/detail/{id}', [PenjualanController::class, 'getDetailSparepart']);


    Route::post('penjualan/create_detail_sparepart', [PenjualanController::class, 'create_detail_sparepart'])->name('create_detail_sparepart_penjualan');
    Route::delete('penjualan/{id}/delete_detail_sparepart', [PenjualanController::class, 'delete_detail_sparepart'])->name('delete_detail_sparepart_penjualan');
    Route::delete('dashboard/{id}/delete_detail_sparepart', [DashboardController::class, 'delete_detail_sparepart'])->name('delete_detail_part_penjualan');

    Route::post('penjualan/create_detail_barang', [PenjualanController::class, 'create_detail_barang'])->name('create_detail_barang_penjualan');
    Route::delete('penjualan/{id}/delete_detail_barang', [PenjualanController::class, 'delete_detail_barang'])->name('delete_detail_barang_penjualan');
    //Garansi Service
    Route::post('garansi_penjualan/store', [PenjualanController::class, 'store_garansi_penjualan'])->name('store_garansi_penjualan');
    Route::delete('garansi_penjualan/{id}/delete', [PenjualanController::class, 'delete_garansi_penjualan'])->name('delete_garansi_penjualan');

    //Pesanan
    Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan');
    Route::get('/pesanan/{id}/edit', [PesananController::class, 'edit'])->name('edit_pesanan');
    Route::put('/pesanan/{id}/update', [PesananController::class, 'update'])->name('update_pesanan');
    //Pengambilan
    Route::get('/pengembalian', [PengembalianController::class, 'index'])->name('pengembalian');
    Route::put('/pengembalian/{id}/update', [PengembalianController::class, 'update'])->name('update_pengembalian');
    Route::put('/pengembalian/{id}/detail_store', [PengembalianController::class, 'store_detail'])->name('store_detail_pengembalian');
    Route::get('/pengembalian/{id}/pengambilan_detail', [PengembalianController::class, 'pengambilan_detail'])->name('detail_pengembalian');
    Route::delete('/pengembalian/{id}/detail_destroy', [PengembalianController::class, 'destroy_detail'])->name('destroy_detail_pengembalian');
    Route::get('/services/detail/{id}', [DashboardController::class, 'getDetail']);

    //Pengeluaran
    Route::get('/pengeluaran_toko', [PengeluaranController::class, 'view_toko'])->name('pengeluaran_toko');
    Route::get('/pengeluaran_toko/create', [PengeluaranController::class, 'create_pengeluaran_toko'])->name('create_pengeluaran_toko');
    Route::get('pengeluaran_toko/{id}/edit', [PengeluaranController::class, 'edit_pengeluaran_toko'])->name('edit_pengeluaran_toko');
    Route::post('pengeluaran_toko/store', [PengeluaranController::class, 'store_pengeluaran_toko'])->name('store_pengeluaran_toko');
    Route::put('pengeluaran_toko/{id}/update', [PengeluaranController::class, 'update_pengeluaran_toko'])->name('update_pengeluaran_toko');
    Route::delete('pengeluaran_toko/{id}/destroy', [PengeluaranController::class, 'delete_pengeluaran_toko'])->name('delete_pengeluaran_toko');

    Route::group(['middleware' => ['authCheck:1']], function () {
        Route::get('/pengeluaran_opex', [PengeluaranController::class, 'view_operasional'])->name('pengeluaran_operasional');
        Route::get('/pengeluaran_opex/create', [PengeluaranController::class, 'create_pengeluaran_opex'])->name('create_pengeluaran_opex');
        Route::get('pengeluaran_opex/{id}/edit', [PengeluaranController::class, 'edit_pengeluaran_opex'])->name('edit_pengeluaran_opex');
        Route::post('pengeluaran_opex/store', [PengeluaranController::class, 'store_pengeluaran_opex'])->name('store_pengeluaran_opex');
        Route::put('pengeluaran_opex/{id}/update', [PengeluaranController::class, 'update_pengeluaran_opex'])->name('update_pengeluaran_opex');
        Route::delete('pengeluaran_opex/{id}/destroy', [PengeluaranController::class, 'delete_pengeluaran_opex'])->name('delete_pengeluaran_opex');


        Route::get('/laporan', [LaporanController::class, 'view_laporan'])->name('laporan');
        Route::get('laporan/print', [PDFController::class, 'print_laporan'])->name('print_laporan');

        //Presentase
        Route::get('/presentase', [PresentaseController::class, 'index'])->name('presentase');
        Route::post('/presentase/store_or_update', [PresentaseController::class, 'store_or_update'])->name('edit_persentase');
        //Users
        Route::resource('users', UsersController::class);
    });

    //Supplier
    Route::resource('supplier', SupplierController::class);

    // web.php atau api.php
    Route::delete('/hutang/{id}', [LaporanController::class, 'destroy'])->name('hutang.destroy');



    Route::get('/spareparts/search', [App\Http\Controllers\Api\SalesApiController::class, 'search'])->name('search');
    Route::post('/pembelian/search-spareparts-ajax', [App\Http\Controllers\Admin\PembelianController::class, 'searchSparepartsAjax'])
        ->name('pembelian.search-spareparts-ajax');
    // Route::get('pembelian/search-variants-ajax', [\App\Http\Controllers\Admin\PembelianController::class, 'searchVariantsAjax'])->name('pembelian.search-variants-ajax');

    Route::get('/admin/review-migrasi', [App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('review.index');
    Route::get('/admin/review-migrasi/data/{sparepart}', [App\Http\Controllers\Admin\ReviewController::class, 'getMigrationData'])->name('review.get-data');
    // Route::post('/admin/review-migrasi/{sparepart}', [App\Http\Controllers\Admin\ReviewController::class, 'migrateSingle'])->name('review.migrate');

    Route::prefix('admin/review')->name('review.')->group(function () {

        // Halaman utama review & migrasi
        Route::get('/', [ReviewController::class, 'index'])->name('index');

        // ✅ MIGRASI SINGLE ITEM
        Route::post('/migrate/{sparepart}', [ReviewController::class, 'migrateSingle'])
             ->name('migrate.single');

        // 🚀 MIGRASI BATCH (Selected Items)
        Route::post('/migrate-bulk', [ReviewController::class, 'migrateBulk'])
             ->name('migrate.bulk');

        // 🌐 MIGRASI ALL (Background Job)
        Route::post('/migrate-all', [ReviewController::class, 'migrateAll'])
             ->name('migrate.all');

        // 🚀 MIGRASI BATCH DENGAN ATRIBUT (ENDPOINT BARU)
        Route::post('/migrate-bulk-attributes', [ReviewController::class, 'migrateBulkWithAttributes'])
            ->name('migrate.bulk_attributes');

        // 📊 CEK STATUS MIGRASI
        Route::get('/check-status', [ReviewController::class, 'checkMigrationStatus'])
             ->name('check.status');

        // 👁️ PREVIEW SEBELUM MIGRASI
        Route::get('/preview/{sparepart}', [ReviewController::class, 'previewMigration'])
             ->name('preview');

        // 🔙 ROLLBACK MIGRASI
        Route::post('/rollback/{sparepart}', [ReviewController::class, 'rollbackMigration'])
             ->name('rollback');

        // 📋 GET DATA UNTUK MODAL (jika butuh atribut)
        Route::get('/data/{sparepart}', [ReviewController::class, 'getMigrationData'])
             ->name('get.data');
    });

}); //admin

// Route::get('/laci/form', [LaciController::class, 'form'])->name('laci.form');
// Route::post('/laci/store', [LaciController::class, 'store'])->name('laci.store');
Route::post('/laci/real', [LaciController::class, 'updatereal'])->name('laci.real');

// Route::delete('/kategori-laci', [LaciController::class, 'deleteKategoriLaci'])->name('delete_kategori_laci');

// Route::post('/kategori-laci', [LaciController::class, 'kategori_laci'])->name('kategori_laci');

// baru di tambahkan
Route::group(['middleware' => 'checkRole:0,1,2'], function () {
    // Tambahkan routing khusus untuk pengguna dengan jabatan 0, 1, atau 2 di sini
    Route::post('/plusUpdate', [SparePartController::class, 'processData'])->name('plusUpdate');
    Route::get('/plus', [SparePartController::class, 'plus'])->name('plus');
    Route::get('/pekerjaan', [ServiceController::class, 'list_all_service'])->name('job');
    Route::get('/cari-service', [ServiceController::class, 'list_all_service'])->name('cariService');
    Route::post('/serviceUpdate', [ServiceController::class, 'selesaikan'])->name('serviceUpdate');


    Route::prefix('whatsapp')->group(function () {
        Route::get('/', [WhatsAppController::class, 'index'])->name('whatsapp.index');
        Route::post('/devices', [WhatsAppController::class, 'createDevice'])->name('whatsapp.devices.create');
        Route::get('/devices/{id}', [WhatsAppController::class, 'showDevice'])->name('whatsapp.show');
        Route::delete('/devices/{id}', [WhatsAppController::class, 'disconnectDevice'])->name('whatsapp.devices.disconnect');
        Route::get('/devices/{id}/qr-code', [WhatsAppController::class, 'getQrCode'])->name('whatsapp.devices.qrcode');
        Route::get('/devices/{id}/refresh-status', [WhatsAppController::class, 'refreshDeviceStatus'])->name('whatsapp.devices.refresh-status');
    });
});

Route::group(['middleware' => 'checkRole:0,1'], function () {

    Route::resource('customer', CustomerController::class);
     // Routes untuk inventory management
    Route::prefix('admin/inventory')->name('admin.inventory.')->middleware(['auth'])->group(function () {
        // Dashboard dan laporan
        Route::get('/home', [StockManagementController::class, 'dashboard'])->name('home');
        Route::get('/restock-report', [StockManagementController::class, 'restockReport'])->name('restock-report');
        Route::get('/bestsellers', [StockManagementController::class, 'bestSellersReport'])->name('bestsellers');

        // API endpoints untuk data
        Route::get('/reorder-recommendation/{itemId}', [StockManagementController::class, 'getReorderRecommendation'])->name('reorder-recommendation');
        Route::get('/item-chart/{itemId}', [StockManagementController::class, 'getItemStockAndSalesChart'])->name('item-chart');
    });
     // Pembelian Routes
     Route::prefix('admin')->middleware(['auth'])->group(function () {
        Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian.index');
        Route::get('/pembelian/create', [PembelianController::class, 'create'])->name('pembelian.create');
        Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store');
        Route::get('/pembelian/{id}', [PembelianController::class, 'show'])->name('pembelian.show');
        Route::get('/pembelian/{id}/edit', [PembelianController::class, 'edit'])->name('pembelian.edit');
        Route::post('/pembelian/{id}/add-item', [PembelianController::class, 'addItem'])->name('pembelian.add-item');
        Route::patch('pembelian/update-item/{detailId}', [PembelianController::class, 'updateItem'])->name('pembelian.update-item');
        Route::delete('/pembelian/item/{id}', [PembelianController::class, 'removeItem'])->name('pembelian.remove-item');
        Route::post('/pembelian/{id}/finalize', [PembelianController::class, 'finalize'])->name('pembelian.finalize');
        Route::patch('/pembelian/{id}', [PembelianController::class, 'update'])->name('pembelian.update');

        Route::get('pengaturan-harga', [PriceSettingController::class, 'index'])->name('price-settings.index');
        Route::post('pengaturan-harga', [PriceSettingController::class, 'storeOrUpdate'])->name('price-settings.store');
        Route::get('pengaturan-harga/form', [PriceSettingController::class, 'form'])->name('price-settings.form');

        Route::resource('attributes', AttributeController::class);
        Route::post('attributes/{attribute}/values', [AttributeValueController::class, 'store'])->name('attribute-values.store');
        Route::delete('attribute-values/{attributeValue}', [AttributeValueController::class, 'destroy'])->name('attribute-values.destroy');
    });

    // Routes untuk Pesanan
    Route::prefix('admin/order')->name('order.')->middleware(['auth'])->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::put('/{id}', [OrderController::class, 'update'])->name('update');

        // Item management
        Route::post('/{id}/add-item', [OrderController::class, 'addItem'])->name('add-item');
        Route::post('/{id}/add-low-stock-item', [OrderController::class, 'addLowStockItem'])->name('add-low-stock-item');
        Route::put('/update-item/{itemId}', [OrderController::class, 'updateItem'])->name('update-item');
        Route::get('/remove-item/{itemId}', [OrderController::class, 'removeItem'])->name('remove-item');

         // Bulk update status for multiple items in an order
        Route::post('/bulk-update-status', [OrderController::class,'bulkUpdateItemStatus'])
        ->name('bulk-update-status');

        // Update status for a single item
        Route::post('/update-item-status', [OrderController::class,'updateItemStatus'])
            ->name('update-item-status');

        // Transfer single or multiple items to a new order
        Route::post('/transfer-items', [OrderController::class,'transferItemsToNewOrder'])
            ->name('transfer-items');

        // Order status management
        Route::get('/{id}/finalize', [OrderController::class, 'finalize'])->name('finalize');
        Route::get('/{id}/convert-to-purchase', [OrderController::class, 'convertToPurchase'])->name('convert-to-purchase');
        Route::get('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    });
    // Routes untuk Stock Opname
    Route::prefix('admin/stock-opname')->name('stock-opname.')->middleware(['auth'])->group(function () {
        Route::get('/', [StockOpnameController::class, 'index'])->name('index');
        Route::get('/create', [StockOpnameController::class, 'create'])->name('create');
        Route::post('/', [StockOpnameController::class, 'store'])->name('store');
        Route::get('/{id}', [StockOpnameController::class, 'show'])->name('show');

        // Proses stock opname
        Route::get('/{id}/start-process', [StockOpnameController::class, 'startProcess'])->name('start-process');
        Route::get('/{id}/check-items', [StockOpnameController::class, 'checkItems'])->name('check-items');
        Route::post('/{periodId}/check-items/{detailId}', [StockOpnameController::class, 'saveItemCheck'])->name('save-item-check');
        Route::post('/{id}/scan-item', [StockOpnameController::class, 'scanItem'])->name('scan-item');

        // Penyesuaian stok
        Route::get('/{periodId}/adjustment/{detailId}', [StockOpnameController::class, 'adjustmentForm'])->name('adjustment-form');
        Route::post('/{periodId}/adjustment/{detailId}', [StockOpnameController::class, 'saveAdjustment'])->name('save-adjustment');

        // Manajemen periode
        Route::get('/{id}/complete', [StockOpnameController::class, 'completePeriod'])->name('complete-period');
        Route::get('/{id}/cancel', [StockOpnameController::class, 'cancelPeriod'])->name('cancel-period');
        Route::put('/{id}/edit-notes', [StockOpnameController::class, 'editNotes'])->name('edit-notes');

        // Laporan
        Route::get('/{id}/report', [StockOpnameController::class, 'report'])->name('report');
        Route::get('/{id}/export-excel', [StockOpnameController::class, 'exportExcel'])->name('export-excel');
    });

 // absen karyawan
    Route::group(['prefix' => 'admin', 'middleware' => ['auth']], function () {

        // Attendance Management
        Route::prefix('attendance')->group(function () {
            Route::get('/', [EmployeeManagementController::class, 'attendanceIndex'])->name('admin.attendance.index');
            Route::get('/history', [EmployeeManagementController::class, 'attendanceHistoryIndex'])->name('admin.attendance.history');
            Route::get('/detail/{id}', [EmployeeManagementController::class, 'attendanceDetail'])->name('admin.attendance.detail');
            Route::get('/export', [EmployeeManagementController::class, 'exportAttendanceHistory'])->name('admin.attendance.export');

            Route::post('/check-in', [EmployeeManagementController::class, 'attendanceCheckIn'])->name('admin.attendance.check-in');
            Route::post('/check-out', [EmployeeManagementController::class, 'attendanceCheckOut'])->name('admin.attendance.check-out');
            Route::post('/update', [EmployeeManagementController::class, 'updateAttendance'])->name('admin.attendance.update');
            Route::delete('/delete', [EmployeeManagementController::class, 'deleteAttendance'])->name('admin.attendance.delete');
            Route::post('/request-leave', [EmployeeManagementController::class, 'requestLeave'])->name('admin.attendance.request-leave');
            Route::post('/set-outside', [EmployeeManagementController::class, 'setOutsideOffice'])->name('admin.attendance.set-outside');
            Route::get('/reset-outside/{userId}', [EmployeeManagementController::class, 'resetOutsideOffice'])->name('admin.attendance.reset-outside');

            // Outside Office Management
            Route::prefix('outside-office')->group(function () {
                Route::get('/history', [EmployeeManagementController::class, 'outsideOfficeHistoryIndex'])->name('admin.outside-office.history');
                Route::get('/history-ajax', [EmployeeManagementController::class, 'getOutsideOfficeHistoryAjax'])->name('admin.outside-office.history-ajax');
                Route::get('/detail/{id}', [EmployeeManagementController::class, 'outsideOfficeDetail'])->name('admin.outside-office.detail');
                Route::get('/export', [EmployeeManagementController::class, 'exportOutsideOfficeHistory'])->name('admin.outside-office.export');
                Route::post('/mark-return', [EmployeeManagementController::class, 'markReturnFromOutside'])->name('admin.attendance.outside-office.mark-return');
                Route::post('/mark-return-by-log', [EmployeeManagementController::class, 'markReturnByLog'])->name('admin.outside-office.mark-return-by-log');
                Route::post('/violate-log', [EmployeeManagementController::class, 'violateLog'])->name('admin.outside-office.violate-log');
            });
        });

        // Salary Settings Management
        Route::prefix('salary-settings')->group(function () {
            Route::get('/', [EmployeeManagementController::class, 'salarySettingsIndex'])->name('admin.salary-settings.index');
            Route::post('/store', [EmployeeManagementController::class, 'salarySettingsStore'])->name('admin.salary-settings.store');
        });

        // Violations Management
        Route::prefix('violations')->group(function () {
            Route::get('/', [EmployeeManagementController::class, 'violationsIndex'])->name('admin.violations.index');
            Route::get('/{id}', [EmployeeManagementController::class, 'getViolationDetail'])->name('admin.violations.detail');
            Route::post('/store', [EmployeeManagementController::class, 'violationsStore'])->name('admin.violations.store');
            Route::post('/update-status', [EmployeeManagementController::class, 'violationsUpdateStatus'])->name('admin.violations.update-status');
            Route::post('/reverse-penalty', [EmployeeManagementController::class, 'reversePenalty'])->name('admin.violations.reverse-penalty');
            Route::post('/calculate-penalty-preview', [EmployeeManagementController::class, 'calculatePenaltyPreview'])->name('admin.violations.calculate-penalty-preview');
            Route::get('/penalty-history/{userId}', [EmployeeManagementController::class, 'getPenaltyHistory'])->name('admin.violations.penalty-history');
        });

        // Employee Report Management
        Route::prefix('employee')->group(function () {
            Route::get('/monthly-report', [EmployeeManagementController::class, 'monthlyReportIndex'])->name('admin.employee.monthly-report');
            Route::get('/report-detail/{id}', [EmployeeManagementController::class, 'reportDetail'])->name('admin.employee.report-detail');
            Route::get('/report-print/{id}', [EmployeeManagementController::class, 'reportPrint'])->name('admin.employee.report-print');
            Route::get('/balance-info/{userId}', [EmployeeManagementController::class, 'getEmployeeBalanceInfo'])->name('admin.employees.balance-info');
            // routes/web.php
Route::post('/monthly-report/generate', [EmployeeManagementController::class, 'generateMonthlyReport'])->name('admin.employee.generate-monthly-report');
            Route::post('/finalize-report', [EmployeeManagementController::class, 'finalizeReport'])->name('admin.employee.finalize-report');
            Route::post('/mark-paid', [EmployeeManagementController::class, 'markAsPaid'])->name('admin.employee.mark-paid');
        });

        // Work Schedule Management
        Route::prefix('work-schedule')->group(function () {
            Route::get('/', [EmployeeManagementController::class, 'scheduleIndex'])->name('admin.work-schedule.index');
            Route::post('/store', [EmployeeManagementController::class, 'scheduleStore'])->name('admin.work-schedule.store');
            Route::get('/user/{userId}', [EmployeeManagementController::class, 'getUserSchedule'])->name('admin.work-schedule.user');
        });

        // QR Code Management
        Route::prefix('qr')->group(function () {
            Route::post('/scan-employee', [EmployeeManagementController::class, 'scanEmployeeQrCode'])->name('admin.qr.scan-employee');
            Route::post('/generate-employee', [EmployeeManagementController::class, 'generateEmployeeQrCode'])->name('admin.qr.generate-employee');
        });

        // Penalty Rules Management Routes
        Route::prefix('penalty-rules')->group (function () {

            // Main page
            Route::get('/', [PenaltyRulesController::class, 'index'])
                ->name('admin.penalty-rules.index');

            // API endpoints for AJAX
            Route::get('/list', [PenaltyRulesController::class, 'list'])
                ->name('admin.penalty-rules.list');

            // CRUD operations
            Route::post('/', [PenaltyRulesController::class, 'store'])
                ->name('admin.penalty-rules.store');

            Route::get('/{id}', [PenaltyRulesController::class, 'show'])
                ->name('admin.penalty-rules.show');

            Route::put('/{id}', [PenaltyRulesController::class, 'update'])
                ->name('admin.penalty-rules.update');

            Route::delete('/{id}', [PenaltyRulesController::class, 'destroy'])
                ->name('admin.penalty-rules.destroy');

            // Special actions
            Route::post('/seed', [PenaltyRulesController::class, 'seed'])
                ->name('admin.penalty-rules.seed');

            Route::get('/export/csv', [PenaltyRulesController::class, 'export'])
                ->name('admin.penalty-rules.export');
        });

    });

   //ujung absen karyawan

    Route::prefix('admin')->group(function () {
        // HP Data Routes
        Route::get('hp', [HpController::class,'index'])->name('admin.tg.index');
        Route::get('hp/create', [HpController::class,'create'])->name('admin.tg.create');
        Route::post('hp', [HpController::class,'store'])->name('admin.tg.store');
        Route::get('hp/{id}/edit', [HpController::class,'edit'])->name('admin.tg.edit');
        Route::put('hp/{id}', [HpController::class,'update'])->name('admin.tg.update');
        Route::delete('hp/{id}', [HpController::class,'destroy'])->name('admin.tg.destroy');
        // Tambahkan route baru untuk CrossTableController di sini
        Route::get('hp/cross-table', [HpController::class, 'cross'])->name('admin.tg.cross-table');

        // Reference Data Routes (Brand, Screen Size, Camera Position)
        Route::resource('brands', 'admin\BrandController');
        Route::resource('screen-sizes', 'admin\ScreenSizeController');
        Route::resource('camera-positions', 'admin\CameraPositionController');


    });

});
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::resource('asets', AsetController::class);
    // Dashboard Keuangan
    Route::get('/financial', [App\Http\Controllers\Admin\FinancialController::class, 'index'])->name('financial.index');

    // Daftar Transaksi
    Route::get('/financial/transactions', [App\Http\Controllers\Admin\FinancialController::class, 'transactions'])->name('financial.transactions');

    // Tambah Transaksi
    Route::get('/financial/create', [App\Http\Controllers\Admin\FinancialController::class, 'create'])->name('financial.create');
    Route::post('/financial/store', [App\Http\Controllers\Admin\FinancialController::class, 'store'])->name('financial.store');

    // Edit Transaksi
    Route::get('/financial/{id}/edit', [App\Http\Controllers\Admin\FinancialController::class, 'edit'])->name('financial.edit');
    Route::put('/financial/{id}', [App\Http\Controllers\Admin\FinancialController::class, 'update'])->name('financial.update');

    // Hapus Transaksi
    Route::delete('/financial/{id}', [App\Http\Controllers\Admin\FinancialController::class, 'destroy'])->name('financial.destroy');

    // Kategori Keuangan
    Route::get('/financial/categories', [App\Http\Controllers\Admin\FinancialController::class, 'categories'])->name('financial.categories');
    Route::post('/financial/categories', [App\Http\Controllers\Admin\FinancialController::class, 'storeCategory'])->name('financial.categories.store');
    Route::put('/financial/categories/{id}', [App\Http\Controllers\Admin\FinancialController::class, 'updateCategoryStatus'])->name('financial.categories.update');

    // Laporan Keuangan
    Route::get('/financial/cash-flow', [FinancialController::class, 'cashFlowReport'])->name('financial.cashFlowReport');
    Route::get('/financial/balance-sheet', [FinancialController::class, 'balanceSheetReport'])->name('financial.balanceSheetReport');

    Route::get('financial/development-report', [FinancialController::class, 'developmentReport'])->name('financial.developmentReport');
    Route::get('financial/development-report/print', [FinancialController::class, 'printDevelopmentReport'])->name('financial.development.print');
    Route::get('/financial/reports', [App\Http\Controllers\Admin\FinancialController::class, 'reports'])->name('financial.reports');
    Route::post('/financial/reports/pdf', [App\Http\Controllers\Admin\FinancialController::class, 'exportPdf'])->name('financial.export.pdf');
    Route::post('/financial/reports/excel', [App\Http\Controllers\Admin\FinancialController::class, 'exportExcel'])->name('financial.export.excel');

    // Integrasi dengan Service
    Route::get('/financial/service/{serviceId}', [App\Http\Controllers\Admin\FinancialController::class, 'createFromService'])->name('financial.create.from.service');

    Route::get('/modal', [App\Http\Controllers\Admin\ModalController::class, 'index'])->name('modal.index');
    Route::get('/modal/create', [App\Http\Controllers\Admin\ModalController::class, 'create'])->name('modal.create');
    Route::post('/modal', [App\Http\Controllers\Admin\ModalController::class, 'store'])->name('modal.store');
     Route::get('/modal/{id}/edit', [App\Http\Controllers\Admin\ModalController::class, 'edit'])->name('modal.edit');
    Route::put('/modal/{id}', [App\Http\Controllers\Admin\ModalController::class, 'update'])->name('modal.update');
    Route::delete('/modal/{id}', [App\Http\Controllers\Admin\ModalController::class, 'destroy'])->name('modal.destroy');

    Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/close-book', [App\Http\Controllers\Admin\SettingController::class, 'storeCloseBook'])->name('settings.closebook.store');

    Route::get('/distribusi-laba', [App\Http\Controllers\Admin\DistribusiLabaController::class, 'index'])->name('distribusi.index');
    Route::post('/distribusi-laba/setting', [App\Http\Controllers\Admin\DistribusiLabaController::class, 'storeSetting'])->name('distribusi.setting.store');
    Route::post('/distribusi-laba/proses', [App\Http\Controllers\Admin\DistribusiLabaController::class, 'prosesTutupBuku'])->name('distribusi.proses');
     Route::post('/distribusi-laba/harian', [App\Http\Controllers\Admin\DistribusiLabaController::class, 'prosesDistribusiHarian'])->name('distribusi.harian');

    Route::get('/hutang', [App\Http\Controllers\Admin\HutangController::class, 'index'])->name('hutang.index');
    Route::post('/hutang/{id}/bayar', [App\Http\Controllers\Admin\HutangController::class, 'bayar'])->name('hutang.bayar');

    Route::get('/distribusi/preview-harian', [App\Http\Controllers\Admin\DistribusiLabaController::class, 'previewDistribusiHarian'])->name('distribusi.previewHarian');
    Route::get('/distribusi-laba/laporan', [App\Http\Controllers\Admin\DistribusiLabaController::class, 'laporan'])->name('distribusi.laporan');
    Route::get('/distribusi-laba/pencairan', [App\Http\Controllers\Admin\DistribusiLabaController::class, 'pencairan'])->name('distribusi.pencairan');
    Route::post('/distribusi-laba/pencairan', [App\Http\Controllers\Admin\DistribusiLabaController::Class, 'prosesPencairan'])->name('distribusi.prosesPencairan');

    Route::resource('beban-operasional', BebanOperasionalController::class)->except(['show', 'create', 'edit'])->names('beban');

});
// Subcategory Routes
Route::group(['middleware' => ['auth']], function () {
    // View all subcategories
    Route::get('/admin/sub-kategori-sparepart', [App\Http\Controllers\Admin\SparePartController::class, 'view_sub_kategori'])->name('sub_kategori_sparepart');

    // View subcategories for a specific category
    Route::get('/admin/sub-kategori-sparepart/kategori/{kategori_id}', [App\Http\Controllers\Admin\SparePartController::class, 'view_sub_kategori'])->name('sub_kategori_sparepart_by_kategori');

    // Create subcategory form
    Route::get('/admin/sub-kategori-sparepart/create', [App\Http\Controllers\Admin\SparePartController::class, 'create_sub_kategori'])->name('create_sub_kategori_sparepart');

    // Create subcategory form with preselected category
    Route::get('/admin/sub-kategori-sparepart/create/{kategori_id}', [App\Http\Controllers\Admin\SparePartController::class, 'create_sub_kategori'])->name('create_sub_kategori_sparepart_by_kategori');

    // Store subcategory
    Route::post('/admin/sub-kategori-sparepart', [App\Http\Controllers\Admin\SparePartController::class, 'store_sub_kategori_sparepart'])->name('StoreSubKategoriSparepart');

    // Edit subcategory form
    Route::get('/admin/sub-kategori-sparepart/{id}/edit', [App\Http\Controllers\Admin\SparePartController::class, 'edit_sub_kategori_sparepart'])->name('EditSubKategoriSparepart');

    // Update subcategory
    Route::put('/admin/sub-kategori-sparepart/{id}', [App\Http\Controllers\Admin\SparePartController::class, 'update_sub_kategori_sparepart'])->name('UpdateSubKategoriSparepart');

    // Delete subcategory
    Route::delete('/admin/sub-kategori-sparepart/{id}', [App\Http\Controllers\Admin\SparePartController::class, 'delete_sub_kategori_sparepart'])->name('DeleteSubKategoriSparepart');

    // AJAX route to get subcategories by category id
    Route::get('/admin/get-sub-kategori/{kategori_id}', [App\Http\Controllers\Admin\SparePartController::class, 'get_sub_kategori_by_kategori'])->name('GetSubKategoriByKategori');
    Route::get('/admin/pembelian/get-sub-kategori/{kategoriId}', [PembelianController::class, 'getSubKategori']);
});


// Routes untuk pencarian sparepart via AJAX (jika belum ada)
Route::get('admin/sparepart/search-ajax', [SparepartController::class, 'searchAjax'])->name('sparepart.search-ajax');

Route::prefix('admin/cron')->group(function () {

    // Auto check absent employees - Run at 9:00 AM
    Route::get('/check-absent', [AttendanceCronController::class, 'checkAbsent'])
        ->name('admin.cron.check-absent');

    // Auto checkout employees - Run at 5:00 PM
    Route::get('/auto-checkout', [AttendanceCronController::class, 'autoCheckout'])
        ->name('admin.cron.auto-checkout');

    // Status monitoring
    Route::get('/status', [AttendanceCronController::class, 'status'])
        ->name('admin.cron.status');
});
