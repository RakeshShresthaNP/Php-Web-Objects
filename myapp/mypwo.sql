-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               11.8.5-MariaDB - MariaDB Server
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table pwo.chat_logs
CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL COMMENT 'Links to mst_users.id',
  `reply_to` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(512) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`,`target_id`,`created_at`,`reply_to`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping structure for table pwo.documentextractions
CREATE TABLE IF NOT EXISTS `documentextractions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(10) unsigned NOT NULL DEFAULT 0,
  `document_id` uuid DEFAULT NULL,
  `extractedjson` json NOT NULL CHECK (json_valid(`extractedjson`)),
  `aimodel` varchar(50) DEFAULT NULL,
  `confidencescore` decimal(5,2) DEFAULT NULL,
  `ishumanverified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_documentextractions` (`partner_id`,`document_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping structure for table pwo.documents
CREATE TABLE IF NOT EXISTS `documents` (
  `id` uuid NOT NULL,
  `partner_id` int(11) NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `s3path` varchar(512) DEFAULT NULL,
  `mimetype` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_documents` (`partner_id`,`user_id`,`filename`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping structure for event pwo.e_clearsessions
DELIMITER //
CREATE EVENT `e_clearsessions` ON SCHEDULE EVERY 15 MINUTE STARTS '2026-01-01 02:00:00' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM sys_sessions WHERE lastaccessed < NOW() - INTERVAL 1 HOUR//
DELIMITER ;

-- Dumping structure for function pwo.f_split_string
DELIMITER //
CREATE FUNCTION `f_split_string`(`x` VARCHAR(255), `delim` VARCHAR(12), `pos` INT) RETURNS varchar(255) CHARSET utf8mb3 COLLATE utf8mb3_unicode_ci
RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(x, delim, pos), LENGTH(SUBSTRING_INDEX(x, delim, pos -1)) + 1), delim, '')//
DELIMITER ;

-- Dumping structure for table pwo.marketdatas
CREATE TABLE IF NOT EXISTS `marketdatas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(10) NOT NULL,
  `price` decimal(18,8) NOT NULL,
  `volume` bigint(20) DEFAULT NULL,
  `dtimestamp` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_marketdatas` (`c_name`,`dtimestamp`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping structure for table pwo.mlmodelmetadatas
CREATE TABLE IF NOT EXISTS `mlmodelmetadatas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(100) DEFAULT NULL,
  `version` varchar(10) DEFAULT NULL,
  `accuracymetrics` json NOT NULL CHECK (json_valid(`accuracymetrics`)),
  `serializedpath` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_mlmodelmetadatas` (`c_name`,`version`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping structure for table pwo.mst_partners
CREATE TABLE IF NOT EXISTS `mst_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(64) DEFAULT NULL,
  `hostname` varchar(256) DEFAULT NULL,
  `sitetitle` varchar(256) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `phone1` varchar(32) DEFAULT '',
  `phone2` varchar(32) DEFAULT NULL,
  `contactfax` varchar(32) DEFAULT NULL,
  `address1` varchar(64) DEFAULT '',
  `address2` varchar(64) DEFAULT NULL,
  `city` varchar(32) DEFAULT '',
  `state` varchar(32) DEFAULT '',
  `country` varchar(32) DEFAULT 'US',
  `zip` varchar(32) DEFAULT '',
  `remarks` varchar(128) DEFAULT '',
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_partnershost` (`hostname`),
  KEY `key_partners` (`c_name`,`email`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Data for mst_partners
INSERT INTO `mst_partners` (`id`, `c_name`, `hostname`, `sitetitle`, `email`, `city`, `country`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
	(1, 'Test', 'localhost', 'Pwo Title', 'test@test.com', 'Kathmandu', 'NP', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(2, 'Test2', 'localhost2', 'Pwo Title2', 'test2@test.com', 'Kathmandu', 'NP', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);

-- Dumping structure for table pwo.mst_partner_settings
CREATE TABLE IF NOT EXISTS `mst_partner_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `secretkey` varchar(256) DEFAULT NULL,
  `mailhost` varchar(256) DEFAULT NULL,
  `mailport` varchar(256) DEFAULT NULL,
  `mailusername` varchar(256) DEFAULT NULL,
  `mailpassword` varchar(256) DEFAULT NULL,
  `geoip_api_key` varchar(256) DEFAULT NULL,
  `firebase_api_key` varchar(256) DEFAULT NULL,
  `gemini_api_key` varchar(256) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_partnersettingss` (`partner_id`,`mailhost`,`mailusername`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Data for mst_partner_settings
INSERT INTO `mst_partner_settings` (`id`, `partner_id`, `secretkey`, `mailhost`, `mailport`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
	(1, 1, 'Nepal@123', 'smtp.gmail.com', '587', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);

-- Dumping structure for table pwo.mst_reportpivots
CREATE TABLE IF NOT EXISTS `mst_reportpivots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(50) DEFAULT NULL,
  `desc` varchar(200) DEFAULT NULL,
