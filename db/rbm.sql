-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Apr 2023 pada 02.39
-- Versi server: 10.4.27-MariaDB
-- Versi PHP: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rbm`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_rusaks`
--

CREATE TABLE `barang_rusaks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_rusak_barang` varchar(255) NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `jumlah_rusak` varchar(255) NOT NULL,
  `catatan_rusak` text NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `catatans`
--

CREATE TABLE `catatans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_catatan` varchar(255) NOT NULL,
  `judul_catatan` varchar(255) NOT NULL,
  `catatan` text NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_barang_penjualans`
--

CREATE TABLE `detail_barang_penjualans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_penjualan` varchar(255) NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `qty_barang` varchar(255) NOT NULL,
  `detail_harga_jual` varchar(255) NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_barang_pesanans`
--

CREATE TABLE `detail_barang_pesanans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_pesanan` varchar(255) NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `detail_harga_pesan` varchar(255) NOT NULL,
  `qty_barang` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_catatan_services`
--

CREATE TABLE `detail_catatan_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_catatan_service` varchar(255) NOT NULL,
  `kode_services` varchar(255) NOT NULL,
  `kode_user` varchar(255) NOT NULL,
  `catatan_service` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_part_luar_services`
--

CREATE TABLE `detail_part_luar_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_services` varchar(255) NOT NULL,
  `nama_part` varchar(255) NOT NULL,
  `harga_part` varchar(255) NOT NULL,
  `qty_part` varchar(255) NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_part_services`
--

CREATE TABLE `detail_part_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_services` varchar(255) NOT NULL,
  `kode_sparepart` varchar(255) NOT NULL,
  `qty_part` varchar(255) NOT NULL,
  `detail_harga_part_service` varchar(255) NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_sparepart_penjualans`
--

CREATE TABLE `detail_sparepart_penjualans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_penjualan` varchar(255) NOT NULL,
  `kode_sparepart` varchar(255) NOT NULL,
  `qty_sparepart` varchar(255) NOT NULL,
  `detail_harga_jual` varchar(255) NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_sparepart_pesanans`
--

CREATE TABLE `detail_sparepart_pesanans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_pesanan` varchar(255) NOT NULL,
  `kode_sparepart` varchar(255) NOT NULL,
  `detail_harga_pesan` varchar(255) NOT NULL,
  `qty_sparepart` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `garansis`
--

CREATE TABLE `garansis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type_garansi` varchar(255) NOT NULL,
  `kode_garansi` varchar(255) NOT NULL,
  `nama_garansi` varchar(255) NOT NULL,
  `tgl_mulai_garansi` varchar(255) NOT NULL,
  `tgl_exp_garansi` varchar(255) NOT NULL,
  `catatan_garansi` text NOT NULL,
  `status_garansi` varchar(255) NOT NULL DEFAULT '0',
  `user_input` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `handphones`
--

CREATE TABLE `handphones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `kode_kategori` varchar(255) NOT NULL,
  `foto_barang` varchar(255) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `desc_barang` text DEFAULT NULL,
  `merk_barang` varchar(255) NOT NULL,
  `kondisi_barang` varchar(255) NOT NULL,
  `stok_barang` varchar(255) NOT NULL DEFAULT '0',
  `harga_beli_barang` varchar(255) NOT NULL,
  `harga_jual_barang` varchar(255) NOT NULL,
  `status_barang` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_handphones`
--

CREATE TABLE `kategori_handphones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `foto_kategori` varchar(255) NOT NULL,
  `nama_kategori` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_spareparts`
--

CREATE TABLE `kategori_spareparts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `foto_kategori` varchar(255) NOT NULL,
  `nama_kategori` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `list_orders`
--

CREATE TABLE `list_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_order` varchar(255) NOT NULL,
  `nama_order` varchar(255) NOT NULL,
  `catatan_order` text DEFAULT NULL,
  `user_input` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2023_03_18_100937_create_sessions_table', 1),
(6, '2023_03_18_140753_create_handphones_table', 1),
(7, '2023_03_18_140811_create_kategori_handphones_table', 1),
(8, '2023_03_18_140854_create_kategori_spareparts_table', 1),
(9, '2023_03_19_030653_create_user_details_table', 1),
(10, '2023_03_20_043055_create_sevices_table', 1),
(11, '2023_03_20_043156_create_catatans_table', 1),
(12, '2023_03_20_043323_create_spareparts_table', 1),
(13, '2023_03_21_111942_create_pengeluaran_tokos_table', 1),
(14, '2023_03_21_113027_create_pemasukkan_lains_table', 1),
(15, '2023_03_21_132908_create_pengeluaran_operasionals_table', 1),
(16, '2023_03_22_082650_create_detail_part_services_table', 1),
(17, '2023_03_24_065834_create_barang_rusaks_table', 1),
(18, '2023_03_24_073841_create_restok_barangs_table', 1),
(19, '2023_03_24_103403_create_detail_part_luar_services_table', 1),
(20, '2023_03_27_123624_create_restok_spareparts_table', 1),
(21, '2023_03_27_123721_create_sparepart_rusaks_table', 1),
(22, '2023_03_27_123750_create_retur_spareparts_table', 1),
(23, '2023_03_27_124034_create_suppliers_table', 1),
(24, '2023_03_27_125322_create_pesanans_table', 1),
(25, '2023_03_27_125355_create_pengambilans_table', 1),
(26, '2023_03_27_125447_create_detail_sparepart_pesanans_table', 1),
(27, '2023_03_27_125508_create_detail_barang_pesanans_table', 1),
(28, '2023_03_27_125630_create_penjualans_table', 1),
(29, '2023_03_27_125658_create_detail_sparepart_penjualans_table', 1),
(30, '2023_03_27_125734_create_detail_barang_penjualans_table', 1),
(31, '2023_03_29_112714_create_penarikans_table', 1),
(32, '2023_03_31_091921_create_presentase_users_table', 1),
(33, '2023_04_02_093814_create_detail_catatan_services_table', 1),
(34, '2023_04_02_093838_create_garansis_table', 1),
(35, '2023_04_11_105126_create_profit_presentases_table', 1),
(36, '2023_04_12_081753_create_list_orders_table', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pemasukkan_lains`
--

CREATE TABLE `pemasukkan_lains` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_pemasukkan` varchar(255) NOT NULL,
  `judul_pemasukan` varchar(255) NOT NULL,
  `catatan_pemasukkan` text NOT NULL,
  `jumlah_pemasukkan` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penarikans`
--

CREATE TABLE `penarikans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_penarikan` varchar(255) NOT NULL,
  `kode_penarikan` varchar(255) NOT NULL,
  `kode_user` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `jumlah_penarikan` varchar(255) NOT NULL,
  `catatan_penarikan` varchar(255) NOT NULL,
  `status_penarikan` varchar(255) NOT NULL,
  `dari_saldo` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengambilans`
--

CREATE TABLE `pengambilans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_pengambilan` varchar(255) NOT NULL,
  `tgl_pengambilan` varchar(255) NOT NULL,
  `nama_pengambilan` varchar(255) NOT NULL,
  `total_bayar` varchar(255) NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `status_pengambilan` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran_operasionals`
--

CREATE TABLE `pengeluaran_operasionals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_pengeluaran` varchar(255) NOT NULL,
  `nama_pengeluaran` varchar(255) NOT NULL,
  `kategori` varchar(255) NOT NULL,
  `kode_pegawai` varchar(255) NOT NULL,
  `jml_pengeluaran` varchar(255) NOT NULL,
  `desc_pengeluaran` text NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengeluaran_tokos`
--

CREATE TABLE `pengeluaran_tokos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tanggal_pengeluaran` varchar(255) NOT NULL,
  `nama_pengeluaran` varchar(255) NOT NULL,
  `catatan_pengeluaran` text NOT NULL,
  `jumlah_pengeluaran` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualans`
--

CREATE TABLE `penjualans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_penjualan` varchar(255) DEFAULT NULL,
  `kode_penjualan` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `nama_customer` varchar(255) NOT NULL DEFAULT '-',
  `catatan_customer` text DEFAULT NULL,
  `user_input` varchar(255) NOT NULL,
  `status_penjualan` varchar(255) NOT NULL DEFAULT '0',
  `total_penjualan` varchar(255) NOT NULL,
  `total_bayar` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanans`
--

CREATE TABLE `pesanans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_pesanan` varchar(255) NOT NULL,
  `kode_pesanan` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `nama_pemesan` varchar(255) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `no_telp` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status_pesanan` varchar(255) NOT NULL DEFAULT '0',
  `total_pesanan` varchar(255) NOT NULL DEFAULT '0',
  `total_bayar` varchar(255) NOT NULL DEFAULT '0',
  `catatan_pesanan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `presentase_users`
--

CREATE TABLE `presentase_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_user` varchar(255) NOT NULL,
  `presentase` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `profit_presentases`
--

CREATE TABLE `profit_presentases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_profit` varchar(255) NOT NULL,
  `kode_service` varchar(255) NOT NULL,
  `kode_presentase` varchar(255) NOT NULL,
  `kode_user` varchar(255) NOT NULL,
  `profit` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `restok_barangs`
--

CREATE TABLE `restok_barangs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_restok` varchar(255) NOT NULL,
  `tgl_restok` varchar(255) NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `jumlah_restok` varchar(255) NOT NULL,
  `status_restok` varchar(255) NOT NULL,
  `catatan_restok` text NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `restok_spareparts`
--

CREATE TABLE `restok_spareparts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_restok` varchar(255) NOT NULL,
  `kode_supplier` varchar(255) NOT NULL,
  `tgl_restok` varchar(255) NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `jumlah_restok` varchar(255) NOT NULL,
  `status_restok` varchar(255) NOT NULL,
  `catatan_restok` text NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `retur_spareparts`
--

CREATE TABLE `retur_spareparts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_retur_barang` varchar(255) NOT NULL,
  `kode_supplier` varchar(255) NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `jumlah_retur` varchar(255) NOT NULL,
  `catatan_retur` text NOT NULL,
  `status_retur` varchar(255) NOT NULL DEFAULT '0',
  `user_input` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('nPfDUWNh5kLW0GQQUofcIQHPr7eRRxspuzDx0LiH', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZmgzV3lZUEZ5MUtTSGlzNkRuZ0Izc05oeEdkRXdnZmZNMjdNUmxLbiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9sb2NhbGhvc3QvcmJtLXdlYi9sb2dpbiI7fX0=', 1682728766),
('TRkdISCfNJfDvcpdagSjj2QJtC0j5NBC89JXbgET', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUzdrZFB2WWRmdTBYU09NdlNuWDM1WVpjbWEyendLN2RieWFpMGpMSCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjMwOiJodHRwOi8vbG9jYWxob3N0L3JibS13ZWIvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1682728722);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sevices`
--

CREATE TABLE `sevices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_service` varchar(255) NOT NULL,
  `tgl_service` varchar(255) NOT NULL,
  `nama_pelanggan` varchar(255) NOT NULL,
  `no_telp` varchar(255) NOT NULL,
  `type_unit` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `total_biaya` varchar(255) NOT NULL DEFAULT '0',
  `dp` varchar(255) NOT NULL DEFAULT '0',
  `id_teknisi` varchar(255) DEFAULT NULL,
  `kode_pengambilan` varchar(255) DEFAULT NULL,
  `status_services` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `spareparts`
--

CREATE TABLE `spareparts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_sparepart` varchar(255) NOT NULL,
  `kode_kategori` varchar(255) NOT NULL,
  `nama_sparepart` varchar(255) NOT NULL,
  `desc_sparepart` text DEFAULT NULL,
  `harga_beli` varchar(255) NOT NULL,
  `harga_jual` varchar(255) NOT NULL,
  `harga_pasang` varchar(255) NOT NULL,
  `stok_sparepart` varchar(255) NOT NULL,
  `foto_sparepart` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sparepart_rusaks`
--

CREATE TABLE `sparepart_rusaks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tgl_rusak_barang` varchar(255) NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `jumlah_rusak` varchar(255) NOT NULL,
  `catatan_rusak` text NOT NULL,
  `user_input` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_supplier` varchar(255) NOT NULL,
  `alamat_supplier` varchar(255) NOT NULL,
  `no_telp_supplier` varchar(255) NOT NULL,
  `kode_owner` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@mail.com', '2023-04-29 00:38:17', '$2y$10$a1CTncZsryfG.l6UkGNKFO9ek44dwO7brD4QpFD9qus3uKqtKcFXG', 'CKwYOMitCbhxmFwpfoWepLLTmsrSbQfq0fjv1NvNhCqgBQuGM1a1TMpjef6j', '2023-04-29 00:38:17', '2023-04-29 00:38:17'),
(2, 'Owner', 'owner@mail.com', NULL, '$2y$10$k3ncj7rOpbjl88MHXE5uo.I0tKycODZBjwDHgAocLWDvHL6l.0MlK', NULL, '2023-04-29 00:39:20', '2023-04-29 00:39:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_details`
--

CREATE TABLE `user_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kode_user` varchar(255) NOT NULL,
  `foto_user` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `alamat_user` varchar(255) NOT NULL,
  `no_telp` varchar(255) NOT NULL,
  `jabatan` varchar(255) NOT NULL,
  `status_user` varchar(255) NOT NULL,
  `id_upline` varchar(255) DEFAULT NULL,
  `saldo` varchar(255) NOT NULL DEFAULT '0',
  `kode_invite` varchar(255) NOT NULL,
  `link_twitter` varchar(255) NOT NULL,
  `link_facebook` varchar(255) NOT NULL,
  `link_instagram` varchar(255) NOT NULL,
  `link_linkedin` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `user_details`
--

INSERT INTO `user_details` (`id`, `kode_user`, `foto_user`, `fullname`, `alamat_user`, `no_telp`, `jabatan`, `status_user`, `id_upline`, `saldo`, `kode_invite`, `link_twitter`, `link_facebook`, `link_instagram`, `link_linkedin`, `created_at`, `updated_at`) VALUES
(1, '1', '-', 'Administrator', '-', '', '0', '1', NULL, '0', '-', 'https://twitter.com/', 'https://web.facebook.com/', 'https://www.instagram.com/', 'https://www.linkedin.com/', NULL, NULL),
(2, '2', '-', 'Owner', '', '-', '1', '1', '2', '0', 'INV2701', '-', '-', '-', '-', '2023-04-29 00:39:20', '2023-04-29 00:39:20');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `barang_rusaks`
--
ALTER TABLE `barang_rusaks`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `catatans`
--
ALTER TABLE `catatans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_barang_penjualans`
--
ALTER TABLE `detail_barang_penjualans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_barang_pesanans`
--
ALTER TABLE `detail_barang_pesanans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_catatan_services`
--
ALTER TABLE `detail_catatan_services`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_part_luar_services`
--
ALTER TABLE `detail_part_luar_services`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_part_services`
--
ALTER TABLE `detail_part_services`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_sparepart_penjualans`
--
ALTER TABLE `detail_sparepart_penjualans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `detail_sparepart_pesanans`
--
ALTER TABLE `detail_sparepart_pesanans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indeks untuk tabel `garansis`
--
ALTER TABLE `garansis`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `handphones`
--
ALTER TABLE `handphones`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kategori_handphones`
--
ALTER TABLE `kategori_handphones`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kategori_spareparts`
--
ALTER TABLE `kategori_spareparts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `list_orders`
--
ALTER TABLE `list_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`);

--
-- Indeks untuk tabel `pemasukkan_lains`
--
ALTER TABLE `pemasukkan_lains`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `penarikans`
--
ALTER TABLE `penarikans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengambilans`
--
ALTER TABLE `pengambilans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengeluaran_operasionals`
--
ALTER TABLE `pengeluaran_operasionals`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengeluaran_tokos`
--
ALTER TABLE `pengeluaran_tokos`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `penjualans`
--
ALTER TABLE `penjualans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indeks untuk tabel `pesanans`
--
ALTER TABLE `pesanans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `presentase_users`
--
ALTER TABLE `presentase_users`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `profit_presentases`
--
ALTER TABLE `profit_presentases`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `restok_barangs`
--
ALTER TABLE `restok_barangs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `restok_spareparts`
--
ALTER TABLE `restok_spareparts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `retur_spareparts`
--
ALTER TABLE `retur_spareparts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `sevices`
--
ALTER TABLE `sevices`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `spareparts`
--
ALTER TABLE `spareparts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `sparepart_rusaks`
--
ALTER TABLE `sparepart_rusaks`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indeks untuk tabel `user_details`
--
ALTER TABLE `user_details`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `barang_rusaks`
--
ALTER TABLE `barang_rusaks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `catatans`
--
ALTER TABLE `catatans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_barang_penjualans`
--
ALTER TABLE `detail_barang_penjualans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_barang_pesanans`
--
ALTER TABLE `detail_barang_pesanans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_catatan_services`
--
ALTER TABLE `detail_catatan_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_part_luar_services`
--
ALTER TABLE `detail_part_luar_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_part_services`
--
ALTER TABLE `detail_part_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_sparepart_penjualans`
--
ALTER TABLE `detail_sparepart_penjualans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_sparepart_pesanans`
--
ALTER TABLE `detail_sparepart_pesanans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `garansis`
--
ALTER TABLE `garansis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `handphones`
--
ALTER TABLE `handphones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kategori_handphones`
--
ALTER TABLE `kategori_handphones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kategori_spareparts`
--
ALTER TABLE `kategori_spareparts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `list_orders`
--
ALTER TABLE `list_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT untuk tabel `pemasukkan_lains`
--
ALTER TABLE `pemasukkan_lains`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `penarikans`
--
ALTER TABLE `penarikans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengambilans`
--
ALTER TABLE `pengambilans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran_operasionals`
--
ALTER TABLE `pengeluaran_operasionals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengeluaran_tokos`
--
ALTER TABLE `pengeluaran_tokos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `penjualans`
--
ALTER TABLE `penjualans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pesanans`
--
ALTER TABLE `pesanans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `presentase_users`
--
ALTER TABLE `presentase_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `profit_presentases`
--
ALTER TABLE `profit_presentases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `restok_barangs`
--
ALTER TABLE `restok_barangs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `restok_spareparts`
--
ALTER TABLE `restok_spareparts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `retur_spareparts`
--
ALTER TABLE `retur_spareparts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sevices`
--
ALTER TABLE `sevices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `spareparts`
--
ALTER TABLE `spareparts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `sparepart_rusaks`
--
ALTER TABLE `sparepart_rusaks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `user_details`
--
ALTER TABLE `user_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
