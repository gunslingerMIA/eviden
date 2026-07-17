-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2026 at 09:10 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_eviden`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `judul_dokumen` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `ekstensi` varchar(10) DEFAULT NULL,
  `ukuran_file` int(11) DEFAULT NULL,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `share_token` varchar(64) DEFAULT NULL,
  `folder_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tahun_mulai` year(4) DEFAULT NULL,
  `tahun_selesai` year(4) DEFAULT NULL,
  `uploader_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `judul_dokumen`, `file_path`, `ekstensi`, `ukuran_file`, `is_shared`, `share_token`, `folder_id`, `tahun_mulai`, `tahun_selesai`, `uploader_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(9, 'PROFIL MPP KOTA PEKALONGAN 2025', 'documents/Or4sHwqB3UNRTxHa5NO8ZI3XuF8EncPU9B3enBL3.xlsx', 'xlsx', 243087, 1, 'HxonfnHBKFfpcKA3sPw7VrDPD1ffbGgt', NULL, NULL, NULL, 1, '2026-07-17 00:01:40', '2026-07-17 00:08:04', NULL),
(10, 'Lambang_Kota_Pekalongan', 'documents/bYnZGm4aq6FgogEo2xpvtQAQLqRvxvXpY8L9LNDs.png', 'png', 324107, 1, '7TQfiVpZ1lODVTyHanBdsDektfxugQfc', NULL, NULL, NULL, 1, '2026-07-17 00:08:52', '2026-07-17 00:09:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_indicator`
--

CREATE TABLE `document_indicator` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `indicator_id` bigint(20) UNSIGNED NOT NULL,
  `ditautkan_oleh` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_evaluasi` varchar(255) NOT NULL,
  `instansi_penilai` varchar(255) DEFAULT NULL,
  `tahun` year(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `nama_evaluasi`, `instansi_penilai`, `tahun`, `created_at`, `updated_at`) VALUES
(1, 'Evaluasi Kinerja Zona Integritas', 'Kementerian PANRB', '2026', '2026-07-16 21:36:28', '2026-07-16 21:36:28');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_indicators`
--

CREATE TABLE `evaluation_indicators` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `evaluation_id` bigint(20) UNSIGNED NOT NULL,
  `nama_indikator` varchar(255) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `pic_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_indicators`
--

INSERT INTO `evaluation_indicators` (`id`, `evaluation_id`, `nama_indikator`, `deskripsi`, `pic_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'Peningkatan Kualitas Pelayanan Publik', 'Bukti berupa maklumat pelayanan, survei kepuasan pelanggan, dan standar prosedur pelayanan.', 1, '2026-07-16 21:36:28', '2026-07-16 21:36:28'),
(2, 1, 'Penerapan Sistem Pemerintahan Berbasis Elektronik (SPBE)', 'Bukti berupa dokumentasi penggunaan aplikasi e-office, sertifikasi server, dan SOP keamanan IT.', 2, '2026-07-16 21:36:28', '2026-07-16 21:36:28');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
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
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_folder` varchar(255) NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dibuat_oleh` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`id`, `nama_folder`, `parent_id`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(5, 'Punya Didik', NULL, 1, '2026-07-17 00:00:29', '2026-07-17 00:00:29');

-- --------------------------------------------------------

--
-- Table structure for table `folder_indicator`
--

CREATE TABLE `folder_indicator` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `folder_id` bigint(20) UNSIGNED NOT NULL,
  `indicator_id` bigint(20) UNSIGNED NOT NULL,
  `ditautkan_oleh` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_17_033620_create_evaluations_table', 1),
(5, '2026_07_17_033626_create_evaluation_indicators_table', 1),
(6, '2026_07_17_033636_create_folders_table', 1),
(7, '2026_07_17_033647_create_documents_table', 1),
(8, '2026_07_17_033706_create_document_indicator_table', 1),
(11, '2026_07_17_055651_add_sharing_to_documents_table', 2),
(12, '2026_07_17_063744_create_folder_indicator_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
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
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Ck5WldDEk0VwcvxNLcVZnS2G69j6GRoazHGpieuH', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWE1tTGVXQnh2WVVmOW9vMmN4ck9TZGlGV1BHVmJ1UVQ2c0U4cVdNUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9mb2xkZXJzIjtzOjU6InJvdXRlIjtzOjEzOiJmb2xkZXJzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1784272208);

-- --------------------------------------------------------

--
-- Table structure for table `users`
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
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Didik Yogo Suro Prasojo, S.Kom', 'didikysp619@gmail.com', NULL, '$2y$12$TZu4aHs7nevoIvnbhBNLNes6I8Hc0x7QAAbqYYZUBAciUlRPdnKPu', NULL, '2026-07-16 21:36:27', '2026-07-16 21:36:27'),
(2, 'Pegawai Dua', 'pegawai2@eviden.com', NULL, '$2y$12$lArKHS3LMg03yjVtGZX3VelrDP44KswQT05.4DxeDNmdbQAAp0LIe', NULL, '2026-07-16 21:36:28', '2026-07-16 21:36:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documents_share_token_unique` (`share_token`),
  ADD KEY `documents_folder_id_foreign` (`folder_id`),
  ADD KEY `documents_uploader_id_foreign` (`uploader_id`);

--
-- Indexes for table `document_indicator`
--
ALTER TABLE `document_indicator`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_indicator_document_id_indicator_id_unique` (`document_id`,`indicator_id`),
  ADD KEY `document_indicator_indicator_id_foreign` (`indicator_id`),
  ADD KEY `document_indicator_ditautkan_oleh_foreign` (`ditautkan_oleh`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluation_indicators_evaluation_id_foreign` (`evaluation_id`),
  ADD KEY `evaluation_indicators_pic_user_id_foreign` (`pic_user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folders_parent_id_foreign` (`parent_id`),
  ADD KEY `folders_dibuat_oleh_foreign` (`dibuat_oleh`);

--
-- Indexes for table `folder_indicator`
--
ALTER TABLE `folder_indicator`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folder_indicator_folder_id_indicator_id_unique` (`folder_id`,`indicator_id`),
  ADD KEY `folder_indicator_indicator_id_foreign` (`indicator_id`),
  ADD KEY `folder_indicator_ditautkan_oleh_foreign` (`ditautkan_oleh`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `document_indicator`
--
ALTER TABLE `document_indicator`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `folder_indicator`
--
ALTER TABLE `folder_indicator`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_uploader_id_foreign` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `document_indicator`
--
ALTER TABLE `document_indicator`
  ADD CONSTRAINT `document_indicator_ditautkan_oleh_foreign` FOREIGN KEY (`ditautkan_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_indicator_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_indicator_indicator_id_foreign` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  ADD CONSTRAINT `evaluation_indicators_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_indicators_pic_user_id_foreign` FOREIGN KEY (`pic_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `folders_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `folders_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folder_indicator`
--
ALTER TABLE `folder_indicator`
  ADD CONSTRAINT `folder_indicator_ditautkan_oleh_foreign` FOREIGN KEY (`ditautkan_oleh`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folder_indicator_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folder_indicator_indicator_id_foreign` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
