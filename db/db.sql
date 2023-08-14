/*
SQLyog Community v13.1.7 (64 bit)
MySQL - 10.5.9-MariaDB : Database - rbm
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `catatans` */

DROP TABLE IF EXISTS `catatans`;

CREATE TABLE `catatans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tgl_catatan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `judul_catatan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `catatans` */

/*Table structure for table `detail_part_services` */

DROP TABLE IF EXISTS `detail_part_services`;

CREATE TABLE `detail_part_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_services` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_sparepart` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_part` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_input` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `detail_part_services` */

/*Table structure for table `failed_jobs` */

DROP TABLE IF EXISTS `failed_jobs`;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `failed_jobs` */

/*Table structure for table `handphones` */

DROP TABLE IF EXISTS `handphones`;

CREATE TABLE `handphones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `desc_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `merk_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kondisi_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stok_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `harga_beli_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_jual_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `handphones` */

/*Table structure for table `kategori_handphones` */

DROP TABLE IF EXISTS `kategori_handphones`;

CREATE TABLE `kategori_handphones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `foto_kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `kategori_handphones` */

/*Table structure for table `kategori_spareparts` */

DROP TABLE IF EXISTS `kategori_spareparts`;

CREATE TABLE `kategori_spareparts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `foto_kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `kategori_spareparts` */

/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values 
(1,'2014_10_12_000000_create_users_table',1),
(2,'2014_10_12_100000_create_password_resets_table',1),
(3,'2019_08_19_000000_create_failed_jobs_table',1),
(4,'2019_12_14_000001_create_personal_access_tokens_table',1),
(5,'2023_03_18_100937_create_sessions_table',1),
(6,'2023_03_18_140753_create_handphones_table',1),
(7,'2023_03_18_140811_create_kategori_handphones_table',1),
(8,'2023_03_18_140854_create_kategori_spareparts_table',1),
(9,'2023_03_19_030653_create_user_details_table',1),
(10,'2023_03_20_043055_create_sevices_table',1),
(11,'2023_03_20_043156_create_catatans_table',1),
(12,'2023_03_20_043323_create_spareparts_table',1),
(13,'2023_03_21_111942_create_pengeluaran_tokos_table',2),
(14,'2023_03_21_113027_create_pemasukkan_lains_table',2),
(15,'2023_03_21_132908_create_pengeluaran_operasionals_table',2),
(16,'2023_03_22_082650_create_detail_part_services_table',3);

/*Table structure for table `password_resets` */

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `password_resets` */

/*Table structure for table `pemasukkan_lains` */

DROP TABLE IF EXISTS `pemasukkan_lains`;

CREATE TABLE `pemasukkan_lains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tgl_pemasukkan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `judul_pemasukan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan_pemasukkan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah_pemasukkan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pemasukkan_lains` */

insert  into `pemasukkan_lains`(`id`,`tgl_pemasukkan`,`judul_pemasukan`,`catatan_pemasukkan`,`jumlah_pemasukkan`,`created_at`,`updated_at`) values 
(1,'2023-03-23','tse','tes','0','2023-03-23 07:54:22','2023-03-23 07:54:22');

/*Table structure for table `pengeluaran_operasionals` */

DROP TABLE IF EXISTS `pengeluaran_operasionals`;

CREATE TABLE `pengeluaran_operasionals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tgl_pengeluaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pengeluaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_pegawai` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jml_pengeluaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `desc_pengeluaran` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pengeluaran_operasionals` */

/*Table structure for table `pengeluaran_tokos` */

DROP TABLE IF EXISTS `pengeluaran_tokos`;

CREATE TABLE `pengeluaran_tokos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tanggal_pengeluaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pengeluaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan_pengeluaran` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah_pengeluaran` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pengeluaran_tokos` */

/*Table structure for table `personal_access_tokens` */

DROP TABLE IF EXISTS `personal_access_tokens`;

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `personal_access_tokens` */

/*Table structure for table `sessions` */

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sessions` */

insert  into `sessions`(`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) values 
('9lIDEHbS7YDP4AQ7HSSchxcwfXCkVtYFzjnqSkj5',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidU0waXZjWVloVnJPbnJBUGQyNTVqOERCSk9IaUd3U1ZjZGMydVhHeSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9sb2NhbGhvc3QvcmJtLXdlYi9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1679410017),
('DBotKlE9fqgU6i5ZnWcy97P8dTCJh2yXVpFcaP7c',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoicENaSk5IdnhPOWhKMlJhZHpCY1dGM2tOcHhRQWI2ZjhGRENxQmVjRCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1679410025),
('fPQfB42j5041aOWMFjukBvGGVmWeQgS4KYxlAqGW',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZDc3UnFpMXJTUkp2SzBRUWxuM2xmOTVnMVN2N3FmS0VLbTkwaWQ4YSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly9sb2NhbGhvc3QvcmJtLXdlYiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1679407623),
('jMk2pdWM58hwSGvaatzgOWFUBnK7eJElafsnQlZd',3,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWDI3RDZ2YzN1cFB6aTNFd2UyTFZxZXZzd3ZvcGZZZlpEME1GT0FINyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzI6Imh0dHA6Ly9sb2NhbGhvc3QvcmJtLXdlYi9wcm9maWxlIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mzt9',1679552011),
('mBm7Q7YRwPNX3zIQeofTA4HyUAG1UFC5ao5TCWOV',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYlVhTU5PTUJla3hHWWlEelQwRG10a2dqaEFjVUQ5M0lxYXg2YVJLUiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0ODoiaHR0cDovL2xvY2FsaG9zdC9yYm0td2ViL3BlbmdlbHVhcmFuX3Rva28vY3JlYXRlIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly9sb2NhbGhvc3QvcmJtLXdlYi9wZW5nZWx1YXJhbl90b2tvL2NyZWF0ZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1679410017);

/*Table structure for table `sevices` */

DROP TABLE IF EXISTS `sevices`;

CREATE TABLE `sevices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_service` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tgl_service` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pelanggan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_biaya` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `id_teknisi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_services` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sevices` */

/*Table structure for table `spareparts` */

DROP TABLE IF EXISTS `spareparts`;

CREATE TABLE `spareparts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_sparepart` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_sparepart` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `desc_sparepart` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_beli` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_jual` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_pasang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stok_sparepart` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_sparepart` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `spareparts` */

/*Table structure for table `user_details` */

DROP TABLE IF EXISTS `user_details`;

CREATE TABLE `user_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_telp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jabatan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_invite` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_twitter` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_facebook` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_instagram` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_linkedin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `user_details` */

insert  into `user_details`(`id`,`kode_user`,`foto_user`,`fullname`,`alamat_user`,`no_telp`,`jabatan`,`status_user`,`kode_invite`,`link_twitter`,`link_facebook`,`link_instagram`,`link_linkedin`,`created_at`,`updated_at`) values 
(2,'2','-','Kasir','','-','2','1','INV2964','-','-','-','-','2023-03-22 20:46:36','2023-03-23 13:01:47'),
(3,'3','-','Admin 2','','-','0','1','INV3818','-','-','-','-','2023-03-23 13:11:57','2023-03-23 13:11:57'),
(4,'4','-','Administrator','','-','0','1','INV4886','-','-','-','-','2023-03-23 13:13:06','2023-03-23 13:13:06');

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`) values 
(2,'Kasir','kasir@mail.com',NULL,'$2y$10$gL2.KnelkBrckgAIq1QmMuN43as.eKmF0EzBYWIcwF3YDTrFjRMcS',NULL,'2023-03-22 20:46:36','2023-03-23 13:01:47'),
(3,'Admin 2','admins@mail.com',NULL,'$2y$10$K4BG2mBg5JogrpmoqB8Z0OFIFezqh7z.NqqXjHwqlEvXYHrF6RoYm',NULL,'2023-03-23 13:11:57','2023-03-23 13:11:57'),
(4,'Administrator','admin@admin.com',NULL,'$2y$10$mJvamxsrSuMKiWs3c1Rr8uMmlaFNLj84m96uGd2LwrwszNzzkiCeW',NULL,'2023-03-23 13:13:06','2023-03-23 13:13:06');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
