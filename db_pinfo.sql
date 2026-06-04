-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: db_pinfo
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (7,'Bisnis'),(10,'Debat'),(1,'Design'),(13,'Esports'),(8,'Fotografi'),(3,'Hacking'),(4,'Matematika'),(9,'Musik'),(12,'Olahraga'),(2,'Programming'),(11,'Riset'),(6,'Robotika'),(5,'Sains'),(14,'Umum');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competition_categories`
--

DROP TABLE IF EXISTS `competition_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competition_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competition_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `competition_id` (`competition_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `competition_categories_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `competition_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competition_categories`
--

LOCK TABLES `competition_categories` WRITE;
/*!40000 ALTER TABLE `competition_categories` DISABLE KEYS */;
INSERT INTO `competition_categories` VALUES (5,2,3),(6,2,4),(7,2,2),(8,2,6),(10,3,13);
/*!40000 ALTER TABLE `competition_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competitions`
--

DROP TABLE IF EXISTS `competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` char(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `format` varchar(100) DEFAULT NULL,
  `date_range` varchar(100) DEFAULT NULL,
  `target_audience` varchar(100) DEFAULT NULL,
  `registration_fee` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `registration_link` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `telegram_chat_id` varchar(50) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'unpaid',
  `approval_status` varchar(20) DEFAULT 'pending',
  `submission_status` varchar(20) DEFAULT 'draft',
  `xendit_invoice_id` varchar(100) DEFAULT NULL,
  `xendit_invoice_url` varchar(500) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `refund_status` varchar(20) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competitions`
--

LOCK TABLES `competitions` WRITE;
/*!40000 ALTER TABLE `competitions` DISABLE KEYS */;
INSERT INTO `competitions` VALUES (2,'6053545684','Gemastik XIX','comp_6a1117303bc44.png','Online','2026-04-26,2026-05-21','Umum',0,'Hacking, Matematika, Programming, Robotika','Perbaiki halaman profil pada bagian tab \"Data Diri\" agar dapat berfungsi dengan baik untuk user biasa maupun admin.\r\n\r\nKetentuan:\r\n- User biasa dapat melihat dan mengedit data diri mereka sendiri\r\n- Admin juga dapat mengedit data diri mereka sendiri\r\n- Role tidak boleh bisa diubah dari halaman profile\r\n- Role hanya ditampilkan sebagai informasi/read-only\r\n- Backend juga harus memblokir perubahan role meskipun request dimanipulasi\r\n\r\nTugas:\r\n- Periksa form profile\r\n- Periksa proses update profile\r\n- Pastikan semua field tersimpan dengan benar ke database\r\n- Pastikan session dan validasi tetap aman\r\n- Pastikan role hanya tampil dan tidak editable\r\n- Sinkronkan field profile dengan database terbaru\r\n\r\nFokus:\r\n- functionality\r\n- keamanan update profile\r\n- sinkronisasi database dan form\r\n- validasi input\r\n- consistency antara admin dan user\r\n\r\nInstruksi:\r\n- jangan ubah behavior lain\r\n- gunakan prepared statements jika project memakai itu\r\n- langsung berikan modifikasi kode yang diperlukan\r\n- jangan banyak penjelasan','http://localhost/Pasti_Info/admin/admin.php',NULL,NULL,'paid','approved','published',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-04 14:25:50'),(3,'7986686744','MCGG','comp_6a2129fe4e128.jpeg','Online','2026-05-15,2026-06-06','Umum',15000,'Esports','[📢 MCGG VOL.2 EXTENDED 📢]\r\n\r\nKesempatan masih terbuka! 🔥\r\nPendaftaran Magic Chess Go Go Tournament UNTIDAR 2026 resmi diperpanjang sampai 6 Juni 2026! ♟️🚀\r\n\r\nTurnamen ini terbuka untuk UMUM, jadi siapa pun bisa ikut bertanding dan menunjukkan strategi terbaiknya! Jangan sampai kelewatan karena slot terbatas 🤩\r\n\r\n🏆 Prize Pool: Rp420.000 + Sertifikat\r\n💰 HTM: 15K\r\n🌐 Mode: Online Tournament\r\n🎟️ OPEN 32 SLOT (No Multi Slot)\r\n\r\n🗓️ Timeline Terbaru:\r\n📌 Pendaftaran: 15 Mei – 6 Juni 2026\r\n📌 Technical Meeting: 6 Juni 2026\r\n📌 Pelaksanaan: 7 Juni 2026\r\n⏰ Waktu: 09.00 WIB – Selesai\r\n\r\n💸 Pembayaran:\r\nScan QR Code pada pamflet\r\n\r\n📞 Contact Person:\r\nTanti — 0882007955566\r\nAgnia — 082329451834\r\n\r\n📲 IG: @esports_untidar\r\n📧 [esportsuntidar@gmail.com]\r\n\r\n#MCGG\r\n#MagicChess\r\n#MagicChessGoGo\r\n#TournamentMagicChess\r\n#EsportsUntidar\r\n#MCGGVol2\r\n#TournamentOnline\r\n#MagicChessIndonesia\r\n#Infolomba\r\n#Untidar2026','https://forms.gle/R9TJLDgGMqZwjc7k8',NULL,NULL,'paid','approved','published',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-04 14:32:14');
/*!40000 ALTER TABLE `competitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_competitions`
--

DROP TABLE IF EXISTS `saved_competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saved_competitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `competition_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `competition_id` (`competition_id`),
  CONSTRAINT `saved_competitions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_competitions_ibfk_2` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_competitions`
--

LOCK TABLES `saved_competitions` WRITE;
/*!40000 ALTER TABLE `saved_competitions` DISABLE KEYS */;
INSERT INTO `saved_competitions` VALUES (7,2,2,'2026-05-23 05:05:52');
/*!40000 ALTER TABLE `saved_competitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('submission_fee','20000');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telegram_notification_logs`
--

DROP TABLE IF EXISTS `telegram_notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telegram_notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegram_notification_logs`
--

LOCK TABLES `telegram_notification_logs` WRITE;
/*!40000 ALTER TABLE `telegram_notification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `telegram_notification_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_category_subscriptions`
--

DROP TABLE IF EXISTS `user_category_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_category_subscriptions` (
  `user_id` int(11) NOT NULL COMMENT 'ID User (referensi ke kolom id pada tabel users)',
  `category_id` int(11) NOT NULL COMMENT 'ID Kategori (referensi ke kolom id pada tabel categories)',
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu / Tanggal user mulai melakukan subscription pada kategori',
  PRIMARY KEY (`user_id`,`category_id`),
  KEY `fk_ucs_category` (`category_id`),
  CONSTRAINT `fk_ucs_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ucs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_category_subscriptions`
--

LOCK TABLES `user_category_subscriptions` WRITE;
/*!40000 ALTER TABLE `user_category_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_category_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default_user.png',
  `role` enum('admin','user') DEFAULT 'user',
  `telegram_chat_id` varchar(50) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@gmail.com','admin','default_user.png','admin','1123456',''),(2,'budbud','budi@gmail.com','budi','default_user.png','user','122234',''),(3,'Muhammad Dafa Falah Labib','dafafalah1616@gmail.com',NULL,'default_user.png','user',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-04 14:36:13
