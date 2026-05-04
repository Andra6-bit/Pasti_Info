-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 09:02 PM
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
-- Table structure for table `competitions`
--

CREATE TABLE `competitions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `location` varchar(100) DEFAULT NULL,
  `date_range` varchar(100) DEFAULT NULL,
  `category` enum('Design','Programming','Hacking','All') DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competitions`
--

INSERT INTO `competitions` (`id`, `title`, `image`, `location`, `date_range`, `category`, `description`) VALUES
(10, 'turnamen', 'comp_69f8dee4a38aa.jpeg', 'online', '1 jan', 'Programming', 'adadfa'),
(11, 'adafd', 'comp_69f8df0071282.jpeg', 'online', '2 jan', 'Design', 'adfaf'),
(12, 'adfad', 'comp_69f8df0c269bf.jpeg', 'afda', 'adaf', 'Hacking', 'adfad'),
(13, 'adfad', 'comp_69f8df19014bb.png', 'adafd', 'adfadf', 'Hacking', 'adfad'),
(14, 'ad', 'comp_69f8df2877438.png', 'dadf', 'fadf', 'All', 'adfa'),
(15, 'dfs', 'comp_69f8df3335bb8.jpeg', 'dafd', 'df', 'Programming', 'adfad'),
(16, 'adfad', 'comp_69f8df3fb4294.jpeg', 'adfa', 'adfas', 'Programming', 'adfa'),
(17, 'adfaf', 'comp_69f8dfa266245.png', 'sfafadf', 'adf', 'All', 'adf'),
(18, 'ag', 'comp_69f8dfac63b64.png', 'agda', 'agf', 'Programming', 'adfad');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`) VALUES
(4, 'admin', 'admin@pinfo.com', 'admin123'),
(5, 'budi', 'dafa@gmail.com', 'budi123'),
(6, 'dani', 'dani@gmail.com', 'dani');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `competitions`
--
ALTER TABLE `competitions`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
