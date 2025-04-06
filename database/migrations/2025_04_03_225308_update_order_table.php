<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menentukan nama tabel yang akan digunakan
        $tableName = Schema::hasTable('orders') ? 'orders' : 'order';

        // Tambahkan kolom baru menggunakan SQL langsung
        if (!Schema::hasColumn($tableName, 'tanggal_order')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `tanggal_order` DATE NULL AFTER `kode_order`");
        }

        if (!Schema::hasColumn($tableName, 'tanggal_kirim_perkiraan')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `tanggal_kirim_perkiraan` DATE NULL AFTER `tanggal_order`");
        }

        if (!Schema::hasColumn($tableName, 'catatan')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `catatan` TEXT NULL AFTER `status_order`");
        }

        if (!Schema::hasColumn($tableName, 'total_item')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `total_item` INT DEFAULT 0 AFTER `catatan`");
        }

        if (!Schema::hasColumn($tableName, 'user_input') && !Schema::hasColumn($tableName, 'user_id')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `user_input` VARCHAR(255) NULL AFTER `total_item`");
        }

        if (!Schema::hasColumn($tableName, 'deleted_at')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `deleted_at` TIMESTAMP NULL");
        }

        // Ubah nama kolom 'spl_kode' menjadi 'kode_supplier' jika perlu
        if (Schema::hasColumn($tableName, 'spl_kode') && !Schema::hasColumn($tableName, 'kode_supplier')) {
            DB::statement("ALTER TABLE `{$tableName}` CHANGE COLUMN `spl_kode` `kode_supplier` BIGINT UNSIGNED NULL");
        }

        // Tambahkan foreign key jika dibutuhkan (hati-hati dengan ini)
        if (Schema::hasTable('suppliers') && Schema::hasColumn($tableName, 'kode_supplier')) {
            try {
                // Verifikasi apakah kolom kode_supplier memiliki index
                $hasIndex = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Column_name = 'kode_supplier'");

                if (empty($hasIndex)) {
                    // Tambahkan index terlebih dahulu
                    DB::statement("ALTER TABLE `{$tableName}` ADD INDEX `idx_kode_supplier` (`kode_supplier`)");
                }

                // Cek apakah foreign key sudah ada
                $foreignKeys = DB::select("
                    SELECT *
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '{$tableName}'
                    AND COLUMN_NAME = 'kode_supplier'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                if (empty($foreignKeys)) {
                    // Tambahkan foreign key
                    DB::statement("
                        ALTER TABLE `{$tableName}`
                        ADD CONSTRAINT `fk_{$tableName}_kode_supplier`
                        FOREIGN KEY (`kode_supplier`)
                        REFERENCES `suppliers` (`id`)
                        ON DELETE SET NULL
                    ");
                }
            } catch (\Exception $e) {
                // Log error tapi jangan crash migrasi
                if (Schema::hasTable('migration_errors')) {
                    DB::table('migration_errors')->insert([
                        'migration' => 'update_order_table',
                        'error' => 'Gagal menambahkan foreign key: ' . $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    // Jika tabel migration_errors belum ada, log ke stderr
                    fwrite(STDERR, 'Error adding foreign key: ' . $e->getMessage() . "\n");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Menentukan nama tabel yang akan digunakan
        $tableName = Schema::hasTable('orders') ? 'orders' : 'order';

        // Hapus foreign key jika ada
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$tableName}'
                AND COLUMN_NAME = 'kode_supplier'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (!empty($foreignKeys)) {
                foreach ($foreignKeys as $key) {
                    DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$key->CONSTRAINT_NAME}`");
                }
            }
        } catch (\Exception $e) {
            // Log error
            fwrite(STDERR, 'Error dropping foreign key: ' . $e->getMessage() . "\n");
        }

        // Hapus kolom yang ditambahkan
        $columns = [];

        if (Schema::hasColumn($tableName, 'tanggal_order')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `tanggal_order`");
        }

        if (Schema::hasColumn($tableName, 'tanggal_kirim_perkiraan')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `tanggal_kirim_perkiraan`");
        }

        if (Schema::hasColumn($tableName, 'catatan')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `catatan`");
        }

        if (Schema::hasColumn($tableName, 'total_item')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `total_item`");
        }

        if (Schema::hasColumn($tableName, 'user_input') && !Schema::hasColumn($tableName, 'user_id')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `user_input`");
        }

        if (Schema::hasColumn($tableName, 'deleted_at')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `deleted_at`");
        }

        // Kembalikan nama kolom jika diubah
        if (Schema::hasColumn($tableName, 'kode_supplier') && !Schema::hasColumn($tableName, 'spl_kode')) {
            DB::statement("ALTER TABLE `{$tableName}` CHANGE COLUMN `kode_supplier` `spl_kode` BIGINT UNSIGNED NULL");
        }
    }
};
