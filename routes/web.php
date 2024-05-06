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
        //Produk
        Route::get('/produk', [HandphoneController::class, 'view_produk'])->name('produk');
        Route::get('/produk/create', [HandphoneController::class, 'create_produk'])->name('create_produk');
        Route::get('produk/{id}/edit', [HandphoneController::class, 'edit_produk'])->name('edit_produk');
        Route::post('produk/store', [HandphoneController::class, 'store_produk'])->name('store_produk');
        Route::put('produk/{id}/update', [HandphoneController::class, 'update_produk'])->name('update_produk');
        Route::delete('produk/{id}/delete', [HandphoneController::class, 'delete_produk'])->name('delete_produk');

        //Kategori Produk
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
    });


    Route::get('/list_all_service', [ServiceController::class, 'list_all_service'])->name('list_all_service');

    //plus
    // Route::get('/stok_sparepart', [SparePartController::class, 'view_stok'])->name('stok_sparepart');

    Route::get('/update-harga-ecer',  [SparePartController::class, 'updateHargaEcer'])->name('update.harga.ecer');
    Route::post('/plusUpdate', [SparePartController::class, 'processData'])->name('plusUpdate');
    Route::get('/plus', [SparePartController::class, 'plus'])->name('plus');
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

    //Sparepart Restok
    Route::get('sparepart_restok/create', [SparePartController::class, 'create_sparepart_restok'])->name('create_sparepart_restok');
    Route::get('sparepart_restok/{id}/edit', [SparePartController::class, 'edit_sparepart_restok'])->name('edit_sparepart_restok');
    Route::post('sparepart_restok/store', [SparePartController::class, 'store_sparepart_restok'])->name('store_sparepart_restok');
    Route::put('sparepart_restok/{id}/update', [SparePartController::class, 'update_sparepart_restok'])->name('update_sparepart_restok');
    Route::delete('sparepart_restok/{id}/destroy', [SparePartController::class, 'delete_sparepart_restok'])->name('delete_sparepart_restok');

    //Sparepart Retur
    Route::get('sparepart_retur/create', [SparePartController::class, 'create_sparepart_retur'])->name('create_sparepart_retur');
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
    Route::get('penjualan/{id}/edit', [PenjualanController::class, 'edit'])->name('edit_penjualan');
    Route::put('penjualan/{id}/update', [PenjualanController::class, 'update'])->name('update_penjualan');

    Route::post('penjualan/create_detail_sparepart', [PenjualanController::class, 'create_detail_sparepart'])->name('create_detail_sparepart_penjualan');
    Route::delete('penjualan/{id}/delete_detail_sparepart', [PenjualanController::class, 'delete_detail_sparepart'])->name('delete_detail_sparepart_penjualan');

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
    Route::put('/pengembalian/{id}/detail_destroy', [PengembalianController::class, 'destroy_detail'])->name('destroy_detail_pengembalian');
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
});
