-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 11, 2026 at 04:10 AM
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
-- Database: `db_pinfo`
--
CREATE DATABASE IF NOT EXISTS `db_pinfo` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `db_pinfo`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(7, 'Bisnis'),
(10, 'Debat'),
(1, 'Design'),
(13, 'Esports'),
(8, 'Fotografi'),
(3, 'Hacking'),
(4, 'Matematika'),
(9, 'Musik'),
(12, 'Olahraga'),
(2, 'Programming'),
(11, 'Riset'),
(6, 'Robotika'),
(5, 'Sains'),
(14, 'Umum');

-- --------------------------------------------------------

--
-- Table structure for table `competitions`
--

CREATE TABLE `competitions` (
  `id` int(11) NOT NULL,
  `uid` char(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `format` varchar(100) DEFAULT NULL,
  `date_range` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `target_audience` varchar(100) DEFAULT NULL,
  `registration_fee` int(11) NOT NULL DEFAULT 0,
  `description` longtext DEFAULT NULL,
  `registration_link` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `payment_status` enum('unpaid','paid','refunded','failed','expired') NOT NULL DEFAULT 'unpaid',
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submission_status` enum('draft','published','unpublished','pending_review','rejected','expired','unpaid') NOT NULL DEFAULT 'draft',
  `xendit_invoice_id` varchar(100) DEFAULT NULL,
  `xendit_invoice_url` varchar(500) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `refund_status` enum('none','pending','success','failed') DEFAULT 'none',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competitions`
--

INSERT INTO `competitions` (`id`, `uid`, `title`, `image`, `format`, `date_range`, `start_date`, `end_date`, `target_audience`, `registration_fee`, `description`, `registration_link`, `user_id`, `payment_status`, `approval_status`, `submission_status`, `xendit_invoice_id`, `xendit_invoice_url`, `paid_at`, `refunded_at`, `refund_status`, `reviewed_by`, `review_note`, `published_at`, `created_at`) VALUES
(12, '3470406527', 'adnfajne', 'comp_6a2a117e2a5ab.jpeg', 'Online', '2026-06-12,2026-06-17', NULL, NULL, 'Umum', 70000, 'ada', 'http://localhost/Pasti_Info/pages/home.php', 2, 'paid', 'approved', 'published', '6a2a117e2035e67b782fdcd1', 'https://checkout-staging.xendit.co/web/6a2a117e2035e67b782fdcd1', '2026-06-11 08:47:08', NULL, 'none', 1, NULL, '2026-06-11 08:48:01', '2026-06-11 08:38:06'),
(13, '1021806702', 'adfadf', 'comp_6a2a1410d187d.png', 'Online', '2026-06-24,2026-06-24', NULL, NULL, 'Umum', 0, 'adfaf', 'http://localhost/Pasti_Info/pages/profile.php', 2, 'paid', 'rejected', 'rejected', '6a2a14112035e67b782fe13f', 'https://checkout-staging.xendit.co/web/6a2a14112035e67b782fe13f', '2026-06-11 08:50:09', NULL, 'failed', 1, 'v  vh', NULL, '2026-06-11 08:49:04');

-- --------------------------------------------------------

--
-- Table structure for table `competition_categories`
--

CREATE TABLE `competition_categories` (
  `id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competition_categories`
--

INSERT INTO `competition_categories` (`id`, `competition_id`, `category_id`) VALUES
(18, 12, 4),
(19, 13, 4);

-- --------------------------------------------------------

--
-- Table structure for table `saved_competitions`
--

CREATE TABLE `saved_competitions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key`, `value`) VALUES
('submission_fee', '20000');

-- --------------------------------------------------------

--
-- Table structure for table `telegram_notification_logs`
--

CREATE TABLE `telegram_notification_logs` (
  `id` int(11) NOT NULL,
  `telegram_chat_id` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `telegram_notification_logs`
--

INSERT INTO `telegram_notification_logs` (`id`, `telegram_chat_id`, `message`, `status`, `attempts`, `error_message`, `created_at`, `updated_at`) VALUES
(1, '6278216449', '🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Gemastik XIX</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11c3adcfc0f819cd7b30fb\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11c3adcfc0f819cd7b30fb</code>', 'sent', 1, NULL, '2026-05-23 22:11:42', '2026-05-23 22:11:42'),
(2, '6278216449', '🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Gemastik XIX</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11cc08d14bf94c48d2a0cc\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11cc08d14bf94c48d2a0cc</code>', 'sent', 1, NULL, '2026-05-23 22:47:21', '2026-05-23 22:47:22'),
(3, '6278216449', '✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n🧾 <b>Invoice ID:</b> <code>6a11cc08d14bf94c48d2a0cc</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.', 'sent', 1, NULL, '2026-05-23 22:47:39', '2026-05-23 22:47:40'),
(4, '6278216449', '🎉 <b>CONGRATS! LOMBA DI-APPROVE!</b>\n\nHalo! Lomba yang Anda kirim telah disetujui oleh Admin dan resmi dipublish:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n\nSekarang lomba Anda dapat dilihat oleh publik di platform LombaID. Terima kasih atas partisipasi Anda!', 'sent', 1, NULL, '2026-05-23 22:53:13', '2026-05-23 22:53:14'),
(5, '6278216449', '🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Gemastik XIX</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11d25dcfc0f819cd7b4259\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11d25dcfc0f819cd7b4259</code>', 'sent', 1, NULL, '2026-05-23 23:14:22', '2026-05-23 23:14:23'),
(6, '6278216449', '✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n🧾 <b>Invoice ID:</b> <code>6a11d25dcfc0f819cd7b4259</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.', 'sent', 1, NULL, '2026-05-23 23:14:32', '2026-05-23 23:14:33'),
(7, '6278216449', '⚠️ <b>LOMBA DITOLAK</b>\n\nHalo! Mohon maaf, lomba yang Anda kirim belum disetujui oleh Admin:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n📝 <b>Alasan Penolakan:</b> jangan yang aneh aneh ya\n\nDana pembayaran Anda akan <b>direfund secara penuh</b> melalui Xendit. Proses refund sedang diajukan.', 'sent', 1, NULL, '2026-05-23 23:29:58', '2026-05-23 23:29:59'),
(8, '6278216449', '🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Fotografi National</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11d96dcfc0f819cd7b4ace\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11d96dcfc0f819cd7b4ace</code>', 'sent', 1, NULL, '2026-05-23 23:44:30', '2026-05-23 23:44:31'),
(9, '6278216449', '✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> Fotografi National\n🧾 <b>Invoice ID:</b> <code>6a11d96dcfc0f819cd7b4ace</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.', 'sent', 1, NULL, '2026-05-23 23:44:43', '2026-05-23 23:44:43'),
(10, '6278216449', '⚠️ <b>LOMBA DITOLAK</b>\n\nHalo! Mohon maaf, lomba yang Anda kirim belum disetujui oleh Admin:\n\n🏆 <b>Lomba:</b> Fotografi National\n📝 <b>Alasan Penolakan:</b> yee apaan ini\n\nDana pembayaran Anda akan <b>direfund secara penuh</b> melalui Xendit. Proses refund sedang diajukan.', 'sent', 1, NULL, '2026-05-23 23:45:10', '2026-05-23 23:45:11'),
(11, '6278216449', '🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>adnfajne</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a2a117e2035e67b782fdcd1\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a2a117e2035e67b782fdcd1</code>', 'sent', 1, NULL, '2026-06-11 08:38:07', '2026-06-11 08:38:08'),
(12, '6278216449', '✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> adnfajne\n🧾 <b>Invoice ID:</b> <code>6a2a117e2035e67b782fdcd1</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.', 'sent', 1, NULL, '2026-06-11 08:47:08', '2026-06-11 08:47:09'),
(13, '6278216449', '🎉 <b>CONGRATS! LOMBA DI-APPROVE!</b>\n\nHalo! Lomba yang Anda kirim telah disetujui oleh Admin dan resmi dipublish:\n\n🏆 <b>Lomba:</b> adnfajne\n\nSekarang lomba Anda dapat dilihat oleh publik di platform LombaID. Terima kasih atas partisipasi Anda!', 'sent', 1, NULL, '2026-06-11 08:48:01', '2026-06-11 08:48:02'),
(14, '6278216449', '🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>adfadf</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a2a14112035e67b782fe13f\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a2a14112035e67b782fe13f</code>', 'sent', 1, NULL, '2026-06-11 08:49:06', '2026-06-11 08:49:07'),
(15, '6278216449', '✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> adfadf\n🧾 <b>Invoice ID:</b> <code>6a2a14112035e67b782fe13f</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.', 'sent', 1, NULL, '2026-06-11 08:50:09', '2026-06-11 08:50:10'),
(16, '6278216449', '⚠️ <b>LOMBA DITOLAK</b>\n\nHalo! Mohon maaf, lomba yang Anda kirim belum disetujui oleh Admin:\n\n🏆 <b>Lomba:</b> adfadf\n📝 <b>Alasan Penolakan:</b> v  vh\n\nDana pembayaran Anda akan <b>direfund secara penuh</b> melalui Xendit. Proses refund sedang diajukan.', 'sent', 1, NULL, '2026-06-11 08:50:28', '2026-06-11 08:50:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) NOT NULL DEFAULT 'default-avatar.jpg',
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `telegram_chat_id` varchar(50) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_picture`, `role`, `telegram_chat_id`, `institution`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$12zO0ZvvFfoVmIY5GzgPq.4HCvDmWYvqDYE.w71SOG9ZfXqVLPd3a', 'user_6a117179967cb.jpg', 'admin', '1123456', ''),
(2, 'budbud', 'budi@gmail.com', '$2y$10$lHnofQwoi8ohzqYp5/bUJOWU0JP.88RbwoJTgt0XjpiaPUeq58OPK', 'default_user.png', 'user', '6278216449', ''),
(3, 'Muhammad Dafa Falah Labib', 'dafafalah1616@gmail.com', NULL, 'default_user.png', 'user', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_category_subscriptions`
--

CREATE TABLE `user_category_subscriptions` (
  `user_id` int(11) NOT NULL COMMENT 'ID User (referensi ke kolom id pada tabel users)',
  `category_id` int(11) NOT NULL COMMENT 'ID Kategori (referensi ke kolom id pada tabel categories)',
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu / Tanggal user mulai melakukan subscription pada kategori'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_category_subscriptions`
--

INSERT INTO `user_category_subscriptions` (`user_id`, `category_id`, `subscribed_at`) VALUES
(1, 2, '2026-05-23 07:46:34'),
(2, 3, '2026-05-23 08:32:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user_id` (`user_id`),
  ADD KEY `idx_activity_logs_action` (`action`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `competitions`
--
ALTER TABLE `competitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD KEY `idx_competitions_user_id` (`user_id`),
  ADD KEY `idx_competitions_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_competitions_submission_status` (`submission_status`),
  ADD KEY `idx_competitions_xendit_invoice_id` (`xendit_invoice_id`),
  ADD KEY `idx_competitions_title` (`title`);

--
-- Indexes for table `competition_categories`
--
ALTER TABLE `competition_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_comp_cat` (`competition_id`,`category_id`),
  ADD KEY `competition_id` (`competition_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `saved_competitions`
--
ALTER TABLE `saved_competitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_saved_comp` (`user_id`,`competition_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `competition_id` (`competition_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `telegram_notification_logs`
--
ALTER TABLE `telegram_notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tnl_status` (`status`),
  ADD KEY `idx_tnl_chat_id` (`telegram_chat_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_users_telegram_chat_id` (`telegram_chat_id`),
  ADD KEY `idx_users_telegram_chat_id` (`telegram_chat_id`);

--
-- Indexes for table `user_category_subscriptions`
--
ALTER TABLE `user_category_subscriptions`
  ADD PRIMARY KEY (`user_id`,`category_id`),
  ADD KEY `fk_ucs_category` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `competition_categories`
--
ALTER TABLE `competition_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `saved_competitions`
--
ALTER TABLE `saved_competitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `telegram_notification_logs`
--
ALTER TABLE `telegram_notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `competitions`
--
ALTER TABLE `competitions`
  ADD CONSTRAINT `fk_competitions_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_competitions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `competition_categories`
--
ALTER TABLE `competition_categories`
  ADD CONSTRAINT `fk_comp_cat_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comp_cat_competition_id` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `saved_competitions`
--
ALTER TABLE `saved_competitions`
  ADD CONSTRAINT `fk_saved_comp_competition_id` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_saved_comp_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_category_subscriptions`
--
ALTER TABLE `user_category_subscriptions`
  ADD CONSTRAINT `fk_ucs_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ucs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
