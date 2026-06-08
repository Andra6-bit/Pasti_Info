-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competition_categories`
--

LOCK TABLES `competition_categories` WRITE;
/*!40000 ALTER TABLE `competition_categories` DISABLE KEYS */;
INSERT INTO `competition_categories` VALUES (13,7,3),(14,8,3),(15,9,3),(16,10,4),(17,11,8);
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competitions`
--

LOCK TABLES `competitions` WRITE;
/*!40000 ALTER TABLE `competitions` DISABLE KEYS */;
INSERT INTO `competitions` VALUES (7,'1455374054','Hacking','comp_6a11a890ae700.png','Online','2026-05-01,2026-05-19','SMA',0,'Hacking','adafafad','http://localhost/Pasti_Info/page/home.php',NULL,NULL,'paid','approved','published',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-23 21:30:21'),(8,'6245175131','Gemastik XIX','comp_6a11c3ad4289f.jpeg','Online','2026-05-15,2026-05-25','SD',0,'Hacking','ada','http://localhost/Pasti_Info/pages/profile.php?tab=my-submissions',2,'6278216449','unpaid','pending','unpaid','6a11c3adcfc0f819cd7b30fb','https://checkout-staging.xendit.co/web/6a11c3adcfc0f819cd7b30fb',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-23 22:11:41'),(9,'6457989992','Gemastik XIX','comp_6a11cc08b8634.png','Online','2026-05-23,2026-06-02','SMA',0,'Hacking','ada','http://localhost/Pasti_Info/pages/home.php',2,'6278216449','paid','approved','published','6a11cc08d14bf94c48d2a0cc','https://checkout-staging.xendit.co/web/6a11cc08d14bf94c48d2a0cc','2026-05-23 22:47:39',NULL,NULL,1,NULL,'2026-05-23 22:53:13','2026-05-23 22:47:20'),(10,'6498998528','Gemastik XIX','comp_6a11d25d66e5c.jpeg','Offline','2026-05-29,2026-05-31','Mahasiswa',0,'Matematika','adadadadada','http://localhost/Pasti_Info/page/home.php',2,'6278216449','paid','rejected','rejected','6a11d25dcfc0f819cd7b4259','https://checkout-staging.xendit.co/web/6a11d25dcfc0f819cd7b4259','2026-05-23 23:14:32',NULL,'failed',1,'jangan yang aneh aneh ya',NULL,'2026-05-23 23:14:21'),(11,'7571717257','Fotografi National','comp_6a11d96d8fc51.jpeg','Online','2026-05-15,2026-07-24','Umum',0,'Fotografi','adsadada','http://localhost/Pasti_Info/admin/admin.php',2,'6278216449','paid','rejected','rejected','6a11d96dcfc0f819cd7b4ace','https://checkout-staging.xendit.co/web/6a11d96dcfc0f819cd7b4ace','2026-05-23 23:44:43',NULL,'failed',1,'yee apaan ini',NULL,'2026-05-23 23:44:29');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_competitions`
--

LOCK TABLES `saved_competitions` WRITE;
/*!40000 ALTER TABLE `saved_competitions` DISABLE KEYS */;
INSERT INTO `saved_competitions` VALUES (11,2,9,'2026-06-07 23:52:04');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegram_notification_logs`
--

LOCK TABLES `telegram_notification_logs` WRITE;
/*!40000 ALTER TABLE `telegram_notification_logs` DISABLE KEYS */;
INSERT INTO `telegram_notification_logs` VALUES (1,'6278216449','🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Gemastik XIX</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11c3adcfc0f819cd7b30fb\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11c3adcfc0f819cd7b30fb</code>','sent',1,NULL,'2026-05-23 22:11:42','2026-05-23 22:11:42'),(2,'6278216449','🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Gemastik XIX</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11cc08d14bf94c48d2a0cc\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11cc08d14bf94c48d2a0cc</code>','sent',1,NULL,'2026-05-23 22:47:21','2026-05-23 22:47:22'),(3,'6278216449','✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n🧾 <b>Invoice ID:</b> <code>6a11cc08d14bf94c48d2a0cc</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.','sent',1,NULL,'2026-05-23 22:47:39','2026-05-23 22:47:40'),(4,'6278216449','🎉 <b>CONGRATS! LOMBA DI-APPROVE!</b>\n\nHalo! Lomba yang Anda kirim telah disetujui oleh Admin dan resmi dipublish:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n\nSekarang lomba Anda dapat dilihat oleh publik di platform LombaID. Terima kasih atas partisipasi Anda!','sent',1,NULL,'2026-05-23 22:53:13','2026-05-23 22:53:14'),(5,'6278216449','🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Gemastik XIX</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11d25dcfc0f819cd7b4259\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11d25dcfc0f819cd7b4259</code>','sent',1,NULL,'2026-05-23 23:14:22','2026-05-23 23:14:23'),(6,'6278216449','✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n🧾 <b>Invoice ID:</b> <code>6a11d25dcfc0f819cd7b4259</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.','sent',1,NULL,'2026-05-23 23:14:32','2026-05-23 23:14:33'),(7,'6278216449','⚠️ <b>LOMBA DITOLAK</b>\n\nHalo! Mohon maaf, lomba yang Anda kirim belum disetujui oleh Admin:\n\n🏆 <b>Lomba:</b> Gemastik XIX\n📝 <b>Alasan Penolakan:</b> jangan yang aneh aneh ya\n\nDana pembayaran Anda akan <b>direfund secara penuh</b> melalui Xendit. Proses refund sedang diajukan.','sent',1,NULL,'2026-05-23 23:29:58','2026-05-23 23:29:59'),(8,'6278216449','🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\nHalo! Lomba baru Anda <b>Fotografi National</b> berhasil disubmit.\n\nSilakan lakukan pembayaran sebesar <b>Rp 20.000</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n🔗 <b>Link Pembayaran:</b> <a href=\"https://checkout-staging.xendit.co/web/6a11d96dcfc0f819cd7b4ace\">Bayar Sekarang</a>\n🧾 <b>Invoice ID:</b> <code>6a11d96dcfc0f819cd7b4ace</code>','sent',1,NULL,'2026-05-23 23:44:30','2026-05-23 23:44:31'),(9,'6278216449','✅ <b>PEMBAYARAN BERHASIL!</b>\n\nHalo! Pembayaran submission lomba Anda telah kami terima:\n\n🏆 <b>Lomba:</b> Fotografi National\n🧾 <b>Invoice ID:</b> <code>6a11d96dcfc0f819cd7b4ace</code>\n\nLomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.','sent',1,NULL,'2026-05-23 23:44:43','2026-05-23 23:44:43'),(10,'6278216449','⚠️ <b>LOMBA DITOLAK</b>\n\nHalo! Mohon maaf, lomba yang Anda kirim belum disetujui oleh Admin:\n\n🏆 <b>Lomba:</b> Fotografi National\n📝 <b>Alasan Penolakan:</b> yee apaan ini\n\nDana pembayaran Anda akan <b>direfund secara penuh</b> melalui Xendit. Proses refund sedang diajukan.','sent',1,NULL,'2026-05-23 23:45:10','2026-05-23 23:45:11');
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
INSERT INTO `user_category_subscriptions` VALUES (1,2,'2026-05-23 07:46:34'),(2,3,'2026-05-23 08:32:42');
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
INSERT INTO `users` VALUES (1,'admin','admin@gmail.com','admin','user_6a117179967cb.jpg','admin','1123456',''),(2,'budbud','budi@gmail.com','budi','default_user.png','user','6278216449',''),(3,'Muhammad Dafa Falah Labib','dafafalah1616@gmail.com',NULL,'default_user.png','user',NULL,NULL);
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

-- Dump completed on 2026-06-08  7:02:51
