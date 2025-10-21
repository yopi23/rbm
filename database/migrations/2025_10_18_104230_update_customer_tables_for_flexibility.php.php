<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateCustomerTablesForFlexibility extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customer_tables')) return;

        // Isi kolom status_toko null agar aman sebelum diubah
        DB::table('customer_tables')
            ->whereNull('status_toko')
            ->update(['status_toko' => 'retail']);

        // === RENAME KOLOM tanpa DBAL ===
        if (Schema::hasColumn('customer_tables', 'nama_pelanggan')) {
            DB::statement("ALTER TABLE `customer_tables` CHANGE `nama_pelanggan` `nama_kontak` VARCHAR(255)");
        }

        if (Schema::hasColumn('customer_tables', 'nomor_toko')) {
            DB::statement("ALTER TABLE `customer_tables` CHANGE `nomor_toko` `nomor_telepon` VARCHAR(255)");
        }

        if (Schema::hasColumn('customer_tables', 'alamat_toko')) {
            DB::statement("ALTER TABLE `customer_tables` CHANGE `alamat_toko` `alamat` TEXT NULL");
        }

        // === TAMBAH KOLOM BARU ===
        if (!Schema::hasColumn('customer_tables', 'tipe_pelanggan')) {
            DB::statement("ALTER TABLE `customer_tables` ADD COLUMN `tipe_pelanggan` ENUM('Retail','Grosir') DEFAULT 'Retail' AFTER `nomor_telepon`");
        }

        // === UPDATE DATA ===
        DB::statement("
            UPDATE customer_tables
            SET tipe_pelanggan = CASE
                WHEN status_toko = 'konter' THEN 'Grosir'
                ELSE 'Retail'
            END
        ");

        // === HAPUS KOLOM LAMA ===
        if (Schema::hasColumn('customer_tables', 'status_toko')) {
            DB::statement("ALTER TABLE `customer_tables` DROP COLUMN `status_toko`");
        }
    }

    public function down()
    {
        if (!Schema::hasTable('customer_tables')) return;

        // Tambahkan kembali kolom lama
        if (!Schema::hasColumn('customer_tables', 'status_toko')) {
            DB::statement("ALTER TABLE `customer_tables` ADD COLUMN `status_toko` VARCHAR(50) DEFAULT 'retail' AFTER `nomor_telepon`");
        }

        // Pindahkan data kembali
        DB::statement("
            UPDATE customer_tables
            SET status_toko = CASE
                WHEN LOWER(tipe_pelanggan) = 'grosir' THEN 'konter'
                ELSE 'retail'
            END
        ");

        // Hapus kolom baru
        if (Schema::hasColumn('customer_tables', 'tipe_pelanggan')) {
            DB::statement("ALTER TABLE `customer_tables` DROP COLUMN `tipe_pelanggan`");
        }

        // Kembalikan nama kolom
        if (Schema::hasColumn('customer_tables', 'nama_kontak')) {
            DB::statement("ALTER TABLE `customer_tables` CHANGE `nama_kontak` `nama_pelanggan` VARCHAR(255)");
        }

        if (Schema::hasColumn('customer_tables', 'nomor_telepon')) {
            DB::statement("ALTER TABLE `customer_tables` CHANGE `nomor_telepon` `nomor_toko` VARCHAR(255)");
        }

        if (Schema::hasColumn('customer_tables', 'alamat')) {
            DB::statement("ALTER TABLE `customer_tables` CHANGE `alamat` `alamat_toko` TEXT NULL");
        }
    }
}
