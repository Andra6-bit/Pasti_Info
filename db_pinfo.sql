-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 20, 2026 at 01:32 PM
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
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competitions`
--

INSERT INTO `competitions` (`id`, `title`, `image`, `location`, `date_range`, `category`, `description`) VALUES
(19, 'Gemastik XIX', 'comp_6a0d74feb1c0c.png', 'Online', '2026-05-21,2026-05-23', 'Hacking', '[ ðŸ’¥ðŸ“¢ OPEN REGISTRATION ARTEFAC\'s COMPETITION 2026 â€¼ï¸]\r\n\r\nThe biggest and the most anticipated competition has arrived!!! So, it\'s our prime time ðŸ‘©ðŸ»â€ðŸŽ¤ðŸª„\r\nWhat\'s going on? ðŸ«£ðŸ’­\r\n\r\nðŸ—“ï¸â€¼ï¸ Note and save these important dates so you don\'t miss out on registering and joining all competitions ðŸ’ŒðŸª„\r\n\r\nðŸ…ðŸ€BASKET COMPETITION\r\nOpen for SMP & SMA/K se-Solo Raya\r\nðŸ…ðŸŽ­MONOLOGUE COMPETITION\r\nOpen for SMA/K & Universitas se-Indonesia\r\nðŸ…ðŸ•ºðŸ¼ DANCE COMPETITION\r\nOpen for Umum, Remaja, Dewasa\r\n\r\nRegister here! We look forward to your amazing presenceðŸª„ See you ðŸ‘‹ðŸ¼\r\nhttps://linktr.ee/artefac\r\n\r\nStay with us, something special awaits ðŸ‘€\r\n\r\nYou can catch up with us on our social media platform!\r\nInstagram: @artefacuns\r\nTwitter: @artefacuns\r\nYoutube: Artefac UNS\r\nEmail: artefacfebuns@gmail.com\r\n\r\n\r\n#ARTEFACUNS2026\r\n#LAHIRDANBESAR'),
(20, 'Essay National', 'comp_6a0d7752600ca.jpeg', 'Online', '2026-04-30,2026-06-02', 'Essay', 'IMM KOMISARIAT EKONOMI DAN BISNIS PRESENTS âœ¨\r\n\r\nKompetisi kreatif untuk menuangkan ide dan inovasi dalam bentuk karya terbaik! ðŸš€\r\n\r\nðŸŒŸ Tema:\r\n\"Sinergi Estetika: Manifestasi Kreativitas dalam Harmoni Ekonomi\"\r\n\r\nðŸ“Œ Jenis Lomba & Ketentuan Peserta:\r\nðŸŽ¨ Desain Logo (Siswa SMA/SMK Sederajat)\r\nðŸ“¸ Fotografi (Umum) \r\n\r\nðŸ’° Biaya Pendaftaran (HTM):\r\n* Gelombang 1: Rp10.000,-\r\n* Gelombang 2: Rp20.000,-\r\n\r\nðŸ—“ï¸ Timeline:\r\nðŸ“¥ Registrasi & Pengumpulan Karya:\r\n* Gelombang 1: 1 â€“ 13 Mei\r\n* Gelombang 2: 15 â€“ 30 Mei\r\n* Batas Akhir Pengumpulan: 30 Mei\r\n\r\nðŸ“ Penilaian: 31 Mei â€“ 2 Juni\r\nðŸ† Pengumuman: 3 Juni\r\n\r\nðŸ“² Guidebook & Pendaftaran:\r\nGuidebook: https://heyzine.com/flip-book/02501a6c87.html\r\n\r\nPendaftaran: Pendaftaran Fotografi\r\nhttps://forms.gle/2G6iF2mvubzo3fy96\r\n\r\nPendaftaran Desain Logo\r\nhttps://forms.gle/UC5Q8n8dV7tBABPe7\r\n\r\nðŸ’³ Pembayaran\r\nSeabank\r\n901578145251 a.n Reka Dwi Utami\r\n\r\nBank Mandiri\r\n1850005201667 a.n Desta Natalie Putri\r\n\r\nðŸ“±info lebih lanjut\r\nwhatsapp\r\n08882749344 (Natalie) \r\n085169437039 (Indra)'),
(21, 'Fotografi National', 'comp_6a0d777d7b6ea.png', 'Online', '2026-06-25,2026-07-23', 'Fotografi', 'IMM KOMISARIAT EKONOMI DAN BISNIS PRESENTS âœ¨\r\n\r\nKompetisi kreatif untuk menuangkan ide dan inovasi dalam bentuk karya terbaik! ðŸš€\r\n\r\nðŸŒŸ Tema:\r\n\"Sinergi Estetika: Manifestasi Kreativitas dalam Harmoni Ekonomi\"\r\n\r\nðŸ“Œ Jenis Lomba & Ketentuan Peserta:\r\nðŸŽ¨ Desain Logo (Siswa SMA/SMK Sederajat)\r\nðŸ“¸ Fotografi (Umum) \r\n\r\nðŸ’° Biaya Pendaftaran (HTM):\r\n* Gelombang 1: Rp10.000,-\r\n* Gelombang 2: Rp20.000,-\r\n\r\nðŸ—“ï¸ Timeline:\r\nðŸ“¥ Registrasi & Pengumpulan Karya:\r\n* Gelombang 1: 1 â€“ 13 Mei\r\n* Gelombang 2: 15 â€“ 30 Mei\r\n* Batas Akhir Pengumpulan: 30 Mei\r\n\r\nðŸ“ Penilaian: 31 Mei â€“ 2 Juni\r\nðŸ† Pengumuman: 3 Juni\r\n\r\nðŸ“² Guidebook & Pendaftaran:\r\nGuidebook: https://heyzine.com/flip-book/02501a6c87.html\r\n\r\nPendaftaran: Pendaftaran Fotografi\r\nhttps://forms.gle/2G6iF2mvubzo3fy96\r\n\r\nPendaftaran Desain Logo\r\nhttps://forms.gle/UC5Q8n8dV7tBABPe7\r\n\r\nðŸ’³ Pembayaran\r\nSeabank\r\n901578145251 a.n Reka Dwi Utami\r\n\r\nBank Mandiri\r\n1850005201667 a.n Desta Natalie Putri\r\n\r\nðŸ“±info lebih lanjut\r\nwhatsapp\r\n08882749344 (Natalie) \r\n085169437039 (Indra)'),
(22, 'Lomba Akuntansi International', 'comp_6a0d77da07095.jpeg', 'Offline', '2026-04-26,2026-08-13', 'Math', 'âœ¨ðŸš€ ARE YOU READY TO BE THE NEXT ACCOUNTING CHAMPION? ðŸš€âœ¨\r\n\r\nðŸŽ¯ OLIMPIADE AKUNTANSI 2026 hadir untuk kamu yang:\r\nðŸ”¥ Suka tantangan\r\nðŸ“Š Jago hitung-hitungan\r\nðŸ† Pengen bawa pulang prestasi\r\n\r\nðŸ’¥ Tunjukkan kemampuan terbaikmu dan kalahkan peserta dari berbagai sekolah!\r\n\r\nðŸ“… Save the date:\r\nðŸ§  Technical Meeting: 20 Juni 2026\r\nðŸ Hari H: 25 Juni 2026\r\n\r\nðŸ’° Benefit yang bikin makin semangat:\r\nðŸ† Trophy Juara 1, 2, 3\r\nðŸ“œ Sertifikat\r\nðŸ’¸ Uang pembinaan\r\nðŸŽ Cinderamata + voucher spesial\r\n\r\nâš ï¸ Slot terbatas & harga naik tiap gelombang!\r\nJadiâ€¦ kamu masih mau nunggu? ðŸ˜\r\n\r\nðŸ‘¥ Ajak tim terbaikmu sekarang juga dan jadi yang paling unggul!\r\n\r\nðŸ“² Daftar sekarang ( https://forms.gle/fEeanbVCecGZqYYG7 )\r\nðŸ“ž CP: \r\n- Desta 0859-1875-23019\r\n- Jihan 0813-3478-1320\r\n\r\nðŸ”¥ *DONâ€™T JUST WATCH, BE THE WINNER!* ðŸ”¥');

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
(6, 'dani', 'dani@gmail.com', 'dani'),
(9, 'budbud', 'bud@gmail.com', 'budi');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
