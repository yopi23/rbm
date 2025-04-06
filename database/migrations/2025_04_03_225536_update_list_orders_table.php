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
        // Tambah kolom baru menggunakan SQL langsung
        if (!Schema::hasColumn('list_orders', 'order_id')) {
            DB::statement("ALTER TABLE `list_orders` ADD COLUMN `order_id` BIGINT UNSIGNED NULL AFTER `id`");
        }

        if (!Schema::hasColumn('list_orders', 'sparepart_id')) {
            DB::statement("ALTER TABLE `list_orders` ADD COLUMN `sparepart_id` BIGINT UNSIGNED NULL AFTER `order_id`");
        }

        // Tambah kolom jumlah setelah nama_order atau nama_item (tergantung mana yang ada)
        $afterColumn = Schema::hasColumn('list_orders', 'nama_item') ? 'nama_item' : 'nama_order';
        if (!Schema::hasColumn('list_orders', 'jumlah')) {
            DB::statement("ALTER TABLE `list_orders` ADD COLUMN `jumlah` INT DEFAULT 1 AFTER `{$afterColumn}`");
        }

        if (!Schema::hasColumn('list_orders', 'status_item')) {
            DB::statement("ALTER TABLE `list_orders` ADD COLUMN `status_item` ENUM('pending', 'dikirim', 'diterima') DEFAULT 'pending' AFTER `jumlah`");
        }

        if (!Schema::hasColumn('list_orders', 'harga_perkiraan')) {
            DB::statement("ALTER TABLE `list_orders` ADD COLUMN `harga_perkiraan` DECIMAL(12,2) NULL AFTER `status_item`");
        }

        if (!Schema::hasColumn('list_orders', 'deleted_at')) {
            DB::statement("ALTER TABLE `list_orders` ADD COLUMN `deleted_at` TIMESTAMP NULL");
        }

        // Rename kolom menggunakan SQL langsung
        if (Schema::hasColumn('list_orders', 'tgl_order') && !Schema::hasColumn('list_orders', 'tanggal_order')) {
            DB::statement("ALTER TABLE `list_orders` CHANGE COLUMN `tgl_order` `tanggal_order` VARCHAR(255)");
        }

        if (Schema::hasColumn('list_orders', 'nama_order') && !Schema::hasColumn('list_orders', 'nama_item')) {
            DB::statement("ALTER TABLE `list_orders` CHANGE COLUMN `nama_order` `nama_item` VARCHAR(255)");
        }

        if (Schema::hasColumn('list_orders', 'catatan_order') && !Schema::hasColumn('list_orders', 'catatan_item')) {
            DB::statement("ALTER TABLE `list_orders` CHANGE COLUMN `catatan_order` `catatan_item` TEXT");
        }

        // Tambahkan index untuk foreign key
        if (Schema::hasColumn('list_orders', 'order_id')) {
            $hasIndex = DB::select("SHOW INDEX FROM `list_orders` WHERE Column_name = 'order_id'");
            if (empty($hasIndex)) {
                DB::statement("ALTER TABLE `list_orders` ADD INDEX `idx_order_id` (`order_id`)");
            }
        }

        if (Schema::hasColumn('list_orders', 'sparepart_id')) {
            $hasIndex = DB::select("SHOW INDEX FROM `list_orders` WHERE Column_name = 'sparepart_id'");
            if (empty($hasIndex)) {
                DB::statement("ALTER TABLE `list_orders` ADD INDEX `idx_sparepart_id` (`sparepart_id`)");
            }
        }

        // Tambahkan foreign key ke tabel orders
        $orderTable = Schema::hasTable('orders') ? 'orders' : 'order';
        if (Schema::hasColumn('list_orders', 'order_id') && Schema::hasTable($orderTable)) {
            try {
                // Cek apakah foreign key sudah ada
                $foreignKeys = DB::select("
                    SELECT *
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'list_orders'
                    AND COLUMN_NAME = 'order_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                if (empty($foreignKeys)) {
                    // Tambahkan foreign key
                    DB::statement("
                        ALTER TABLE `list_orders`
                        ADD CONSTRAINT `fk_list_orders_order_id`
                        FOREIGN KEY (`order_id`)
                        REFERENCES `{$orderTable}` (`id`)
                        ON DELETE CASCADE
                    ");
                }
            } catch (\Exception $e) {
                // Log error tapi jangan crash migrasi
                if (Schema::hasTable('migration_errors')) {
                    DB::table('migration_errors')->insert([
                        'migration' => 'update_list_orders_table',
                        'error' => 'Gagal menambahkan foreign key order_id: ' . $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    // Jika tabel migration_errors belum ada, log ke stderr
                    fwrite(STDERR, 'Error adding order_id foreign key: ' . $e->getMessage() . "\n");
                }
            }
        }

        // Tambahkan foreign key ke tabel spareparts
        if (Schema::hasColumn('list_orders', 'sparepart_id') && Schema::hasTable('spareparts')) {
            try {
                // Cek apakah foreign key sudah ada
                $foreignKeys = DB::select("
                    SELECT *
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'list_orders'
                    AND COLUMN_NAME = 'sparepart_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                if (empty($foreignKeys)) {
                    // Tambahkan foreign key
                    DB::statement("
                        ALTER TABLE `list_orders`
                        ADD CONSTRAINT `fk_list_orders_sparepart_id`
                        FOREIGN KEY (`sparepart_id`)
                        REFERENCES `spareparts` (`id`)
                        ON DELETE SET NULL
                    ");
                }
            } catch (\Exception $e) {
                // Log error tapi jangan crash migrasi
                if (Schema::hasTable('migration_errors')) {
                    DB::table('migration_errors')->insert([
                        'migration' => 'update_list_orders_table',
                        'error' => 'Gagal menambahkan foreign key sparepart_id: ' . $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    // Jika tabel migration_errors belum ada, log ke stderr
                    fwrite(STDERR, 'Error adding sparepart_id foreign key: ' . $e->getMessage() . "\n");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus foreign keys
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'list_orders'
                AND (COLUMN_NAME = 'order_id' OR COLUMN_NAME = 'sparepart_id')
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            if (!empty($foreignKeys)) {
                foreach ($foreignKeys as $key) {
                    DB::statement("ALTER TABLE `list_orders` DROP FOREIGN KEY `{$key->CONSTRAINT_NAME}`");
                }
            }
        } catch (\Exception $e) {
            // Log error
            fwrite(STDERR, 'Error dropping foreign keys: ' . $e->getMessage() . "\n");
        }

        // Hapus index
        try {
            $indexes = DB::select("SHOW INDEX FROM `list_orders` WHERE Column_name IN ('order_id', 'sparepart_id')");
            $indexNames = [];

            foreach ($indexes as $index) {
                if (!in_array($index->Key_name, $indexNames) && $index->Key_name != 'PRIMARY') {
                    $indexNames[] = $index->Key_name;
                }
            }

            foreach ($indexNames as $indexName) {
                DB::statement("ALTER TABLE `list_orders` DROP INDEX `{$indexName}`");
            }
        } catch (\Exception $e) {
            // Log error
            fwrite(STDERR, 'Error dropping indexes: ' . $e->getMessage() . "\n");
        }

        // Hapus kolom yang ditambahkan
        if (Schema::hasColumn('list_orders', 'order_id')) {
            DB::statement("ALTER TABLE `list_orders` DROP COLUMN `order_id`");
        }

        if (Schema::hasColumn('list_orders', 'sparepart_id')) {
            DB::statement("ALTER TABLE `list_orders` DROP COLUMN `sparepart_id`");
        }

        if (Schema::hasColumn('list_orders', 'jumlah')) {
            DB::statement("ALTER TABLE `list_orders` DROP COLUMN `jumlah`");
        }

        if (Schema::hasColumn('list_orders', 'status_item')) {
            DB::statement("ALTER TABLE `list_orders` DROP COLUMN `status_item`");
        }

        if (Schema::hasColumn('list_orders', 'harga_perkiraan')) {
            DB::statement("ALTER TABLE `list_orders` DROP COLUMN `harga_perkiraan`");
        }

        if (Schema::hasColumn('list_orders', 'deleted_at')) {
            DB::statement("ALTER TABLE `list_orders` DROP COLUMN `deleted_at`");
        }

        // Kembalikan nama kolom
        if (Schema::hasColumn('list_orders', 'nama_item') && !Schema::hasColumn('list_orders', 'nama_order')) {
            DB::statement("ALTER TABLE `list_orders` CHANGE COLUMN `nama_item` `nama_order` VARCHAR(255)");
        }

        if (Schema::hasColumn('list_orders', 'catatan_item') && !Schema::hasColumn('list_orders', 'catatan_order')) {
            DB::statement("ALTER TABLE `list_orders` CHANGE COLUMN `catatan_item` `catatan_order` TEXT");
        }

        if (Schema::hasColumn('list_orders', 'tanggal_order') && !Schema::hasColumn('list_orders', 'tgl_order')) {
            DB::statement("ALTER TABLE `list_orders` CHANGE COLUMN `tanggal_order` `tgl_order` VARCHAR(255)");
        }
    }
};
