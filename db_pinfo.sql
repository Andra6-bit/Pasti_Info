-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 22, 2026 at 01:48 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competitions`
--

CREATE TABLE `competitions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `pelaksanaan` varchar(100) DEFAULT NULL,
  `date_range` varchar(100) DEFAULT NULL,
  `target_peserta` varchar(100) DEFAULT NULL,
  `biaya` int(11) DEFAULT 0,
  `description` longtext DEFAULT NULL,
  `link_info` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competitions`
--

INSERT INTO `competitions` (`id`, `title`, `image`, `pelaksanaan`, `date_range`, `target_peserta`, `biaya`, `description`, `link_info`) VALUES
(1, 'Gemastik XIX', 'comp_6a1000197546c.png', 'Offline', '2026-05-22,2026-09-23', 'Mahasiswa', 300000, 'ðŸ“½ï¸ OPEN SUBMISSION HASANUDDIN FILM FESTIVAL 2026 ðŸ“½ï¸\r\n\r\nHalo Sinemawan! Kami datang membawa kabar yang kamu tunggu-tunggu ðŸ‘€âœ¨\r\n\r\nHasanuddin Film Festival 2026 resmi membuka pendaftaran untuk film pendek terbaik dari seluruh Indonesia!\r\n\r\nKalau kamu punya film pendek yang ingin didengar, dirasakan, dan terhubung dengan lebih banyak orangâ€¦ inilah waktunya! ðŸŽ¬ Kirimkan film pendekmu, dan biarkan ceritamu beresonansi lebih luas.\r\n\r\nBerbagai nominasi sudah menunggu karya terbaikmu ðŸ‘€ðŸ”¥ Daftar kiâ€™ nah!\r\n\r\nâ€”\r\nðŸ”— bit.ly/SubmissionUmumHFF2026 & bit.ly/SubmissionPelajarHFF2026\r\n\r\nðŸ“ž Narahubung:\r\n+62 852-9839-4861 (Nayla)\r\n+62 853-9780-4473 (Intan)\r\n\r\n#OpenSubmissionFilm #HFF2026 #ResonanceHFF #HasanuddinFilmFestival #FilmFestival', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `competition_categories`
--

CREATE TABLE `competition_categories` (
  `id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `saved_competitions`
--

INSERT INTO `saved_competitions` (`id`, `user_id`, `competition_id`, `saved_at`) VALUES
(2, 2, 1, '2026-05-22 07:07:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto_profile` varchar(255) DEFAULT 'default_user.png',
  `role` enum('admin','user') DEFAULT 'user',
  `telegram_chat_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `foto_profile`, `role`, `telegram_chat_id`) VALUES
(1, 'admin', 'admin@gmail.com', 'admin', 'default_user.png', 'admin', NULL),
(2, 'budbud', 'budi@gmail.com', 'budi', 'default_user.png', 'user', NULL);

--
-- Indexes for dumped tables
--

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `competition_categories`
--
ALTER TABLE `competition_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `competition_id` (`competition_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `saved_competitions`
--
ALTER TABLE `saved_competitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `competition_id` (`competition_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `competition_categories`
--
ALTER TABLE `competition_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_competitions`
--
ALTER TABLE `saved_competitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `competition_categories`
--
ALTER TABLE `competition_categories`
  ADD CONSTRAINT `competition_categories_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `competition_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_competitions`
--
ALTER TABLE `saved_competitions`
  ADD CONSTRAINT `saved_competitions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_competitions_ibfk_2` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
