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

-- Dumping structure for table pwo.documentextractions
CREATE TABLE IF NOT EXISTS `documentextractions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(10) unsigned NOT NULL DEFAULT 0,
  `document_id` uuid DEFAULT NULL,
  `extractedjson` json NOT NULL CHECK (json_valid(`extractedjson`)),
  `aimodel` varchar(50) DEFAULT NULL,
  `confidencescore` decimal(5,2) DEFAULT NULL,
  `ishumanverified` tinyint(1) DEFAULT 0,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_documentextractions` (`partner_id`,`document_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.documentextractions: 0 rows
DELETE FROM `documentextractions`;
/*!40000 ALTER TABLE `documentextractions` DISABLE KEYS */;
/*!40000 ALTER TABLE `documentextractions` ENABLE KEYS */;

-- Dumping structure for table pwo.documents
CREATE TABLE IF NOT EXISTS `documents` (
  `id` uuid NOT NULL,
  `partner_id` int(11) NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `s3path` varchar(512) DEFAULT NULL,
  `mimetype` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_documents` (`partner_id`,`user_id`,`filename`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.documents: 0 rows
DELETE FROM `documents`;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;

-- Dumping structure for event pwo.e_clearsessions
DELIMITER //
CREATE EVENT `e_clearsessions` ON SCHEDULE EVERY 15 MINUTE STARTS '2026-01-01 02:00:00' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM sys_sessions WHERE lastaccessed < NOW() - INTERVAL 1 HOUR//
DELIMITER ;

-- Dumping structure for function pwo.f_split_string
DELIMITER //
CREATE FUNCTION `f_split_string`(`x` VARCHAR(255),
	`delim` VARCHAR(12),
	`pos` INT
) RETURNS varchar(255) CHARSET utf8mb3 COLLATE utf8mb3_unicode_ci
RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(x, delim, pos),
       LENGTH(SUBSTRING_INDEX(x, delim, pos -1)) + 1),
       delim, '')//
DELIMITER ;

-- Dumping structure for table pwo.marketdatas
CREATE TABLE IF NOT EXISTS `marketdatas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(10) NOT NULL COMMENT 'ticker',
  `price` decimal(18,8) NOT NULL,
  `volume` bigint(20) DEFAULT NULL,
  `dtimestamp` timestamp NOT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_marketdatas` (`c_name`,`dtimestamp`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.marketdatas: 0 rows
DELETE FROM `marketdatas`;
/*!40000 ALTER TABLE `marketdatas` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketdatas` ENABLE KEYS */;

-- Dumping structure for table pwo.mlmodelmetadatas
CREATE TABLE IF NOT EXISTS `mlmodelmetadatas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(100) DEFAULT NULL,
  `version` varchar(10) DEFAULT NULL,
  `accuracymetrics` json NOT NULL CHECK (json_valid(`accuracymetrics`)),
  `serializedpath` varchar(255) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_mlmodelmetadatas` (`c_name`,`version`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mlmodelmetadatas: 0 rows
DELETE FROM `mlmodelmetadatas`;
/*!40000 ALTER TABLE `mlmodelmetadatas` DISABLE KEYS */;
/*!40000 ALTER TABLE `mlmodelmetadatas` ENABLE KEYS */;

-- Dumping structure for table pwo.mst_partners
CREATE TABLE IF NOT EXISTS `mst_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(64) DEFAULT NULL,
  `hostname` varchar(256) NOT NULL,
  `sitetitle` varchar(256) NOT NULL,
  `email` varchar(256) NOT NULL,
  `phone1` varchar(32) NOT NULL DEFAULT '',
  `phone2` varchar(32) DEFAULT NULL,
  `contactfax` varchar(32) DEFAULT NULL,
  `address1` varchar(64) NOT NULL DEFAULT '',
  `address2` varchar(64) DEFAULT NULL,
  `city` varchar(32) NOT NULL DEFAULT '',
  `state` varchar(32) NOT NULL DEFAULT '',
  `country` varchar(32) NOT NULL DEFAULT 'US',
  `zip` varchar(32) NOT NULL DEFAULT '',
  `remarks` varchar(128) NOT NULL DEFAULT '',
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_partners` (`c_name`,`hostname`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_partners: 2 rows
DELETE FROM `mst_partners`;
/*!40000 ALTER TABLE `mst_partners` DISABLE KEYS */;
INSERT INTO `mst_partners` (`id`, `c_name`, `hostname`, `sitetitle`, `email`, `phone1`, `phone2`, `contactfax`, `address1`, `address2`, `city`, `state`, `country`, `zip`, `remarks`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 'Test', 'localhost', 'Pwo Title', 'test@test.com', '', '', '', '', '', 'Kathmandu', 'BG', 'NP', '92630', '', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(2, 'Test2', 'test.com', 'Pwo Title2', 'test2@test.com', '', '', '', '', '', 'Kathmandu', 'BG', 'NP', '92630', '', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);
/*!40000 ALTER TABLE `mst_partners` ENABLE KEYS */;

-- Dumping structure for table pwo.mst_partner_settings
CREATE TABLE IF NOT EXISTS `mst_partner_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `mailtype` varchar(256) DEFAULT NULL,
  `mailhost` varchar(256) DEFAULT NULL,
  `mailport` varchar(256) DEFAULT NULL,
  `mailusername` varchar(256) DEFAULT NULL,
  `mailpassword` varchar(256) DEFAULT NULL,
  `mailencryption` varchar(256) DEFAULT NULL,
  `geoip_api_key` varchar(256) DEFAULT NULL,
  `firebase_api_key` varchar(256) DEFAULT NULL,
  `gemini_api_key` varchar(256) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_partnersettingss` (`partner_id`,`mailhost`,`mailusername`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_partner_settings: 1 rows
DELETE FROM `mst_partner_settings`;
/*!40000 ALTER TABLE `mst_partner_settings` DISABLE KEYS */;
INSERT INTO `mst_partner_settings` (`id`, `partner_id`, `mailtype`, `mailhost`, `mailport`, `mailusername`, `mailpassword`, `mailencryption`, `geoip_api_key`, `firebase_api_key`, `gemini_api_key`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 1, '', '', '', '', '', '', '', '', '', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);
/*!40000 ALTER TABLE `mst_partner_settings` ENABLE KEYS */;

-- Dumping structure for table pwo.mst_reportpivots
CREATE TABLE IF NOT EXISTS `mst_reportpivots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(50) DEFAULT NULL,
  `desc` varchar(200) DEFAULT NULL,
  `viewsql` text DEFAULT NULL,
  `perms` int(11) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_reportpivots: 0 rows
DELETE FROM `mst_reportpivots`;
/*!40000 ALTER TABLE `mst_reportpivots` DISABLE KEYS */;
/*!40000 ALTER TABLE `mst_reportpivots` ENABLE KEYS */;

-- Dumping structure for table pwo.mst_reports
CREATE TABLE IF NOT EXISTS `mst_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(200) DEFAULT NULL,
  `c_sql` text DEFAULT NULL,
  `perms` int(11) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_reports: 0 rows
DELETE FROM `mst_reports`;
/*!40000 ALTER TABLE `mst_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `mst_reports` ENABLE KEYS */;

-- Dumping structure for table pwo.mst_users
CREATE TABLE IF NOT EXISTS `mst_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(10) unsigned NOT NULL DEFAULT 0,
  `c_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `homepath` varchar(100) NOT NULL,
  `realname` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `perms` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_usersc_name` (`partner_id`,`c_name`),
  UNIQUE KEY `key_usersemail` (`email`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_users: 3 rows
DELETE FROM `mst_users`;
/*!40000 ALTER TABLE `mst_users` DISABLE KEYS */;
INSERT INTO `mst_users` (`id`, `partner_id`, `c_name`, `email`, `phone`, `homepath`, `realname`, `password`, `perms`, `status`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 1, 'superadmin', 'superadmin@gmail.com', '', 'manage', 'Rakesh Shrestha', '$2y$12$5qwHKGAGImFrsQILwLldW.DMSc9FX6EWuCT2.n9yzaESaKGbqYAZm', 'superadmin', 1, '2026-01-01 02:00:00', 0, '2026-01-01 02:00:00', 0),
	(2, 1, 'admin', 'admin@gmail.com', '', 'dashboard', 'dashboard', '$2y$12$5qwHKGAGImFrsQILwLldW.DMSc9FX6EWuCT2.n9yzaESaKGbqYAZm', 'admin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(3, 1, 'user', 'user@gmail.com', '', 'dashboard', 'Rakesh Shrestha', '$2y$12$5qwHKGAGImFrsQILwLldW.DMSc9FX6EWuCT2.n9yzaESaKGbqYAZm', 'user', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);
/*!40000 ALTER TABLE `mst_users` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_auditlogs
CREATE TABLE IF NOT EXISTS `sys_auditlogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actionname` varchar(50) NOT NULL COMMENT 'LOGIN, DOC_DELETE',
  `entitytype` varchar(50) DEFAULT NULL COMMENT 'users, invoices',
  `entityid` varchar(50) DEFAULT NULL,
  `datadiff` json NOT NULL CHECK (json_valid(`datadiff`)),
  `ipdetails` json NOT NULL CHECK (json_valid(`ipdetails`)),
  `devicedetails` json NOT NULL CHECK (json_valid(`devicedetails`)),
  `ipaddress` varchar(45) GENERATED ALWAYS AS (json_value(`ipdetails`,'$.ip')) VIRTUAL,
  `country` char(2) GENERATED ALWAYS AS (json_value(`ipdetails`,'$.country_name')) VIRTUAL,
  `os` varchar(50) GENERATED ALWAYS AS (json_value(`devicedetails`,'$.os_title')) VIRTUAL,
  `browser` varchar(50) GENERATED ALWAYS AS (json_value(`devicedetails`,'$.browser_tiltle')) VIRTUAL,
  `hashchain` char(64) NOT NULL COMMENT 'SHA-256 of current and previous record datadiff field',
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_auditlogsuseraction` (`user_id`,`actionname`,`entitytype`,`entityid`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- Dumping data for table pwo.sys_auditlogs: 0 rows
DELETE FROM `sys_auditlogs`;
/*!40000 ALTER TABLE `sys_auditlogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_auditlogs` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_methods
CREATE TABLE IF NOT EXISTS `sys_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(32) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `controllername` varchar(32) DEFAULT NULL,
  `controllermethod` varchar(32) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_methods` (`c_name`,`module_id`,`controllername`,`controllermethod`,`perms`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_methods: 0 rows
DELETE FROM `sys_methods`;
/*!40000 ALTER TABLE `sys_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_methods` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_modules
CREATE TABLE IF NOT EXISTS `sys_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(255) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT current_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_modules` (`c_name`,`perms`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_modules: 13 rows
DELETE FROM `sys_modules`;
/*!40000 ALTER TABLE `sys_modules` DISABLE KEYS */;
INSERT INTO `sys_modules` (`id`, `c_name`, `perms`, `status`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 'auth', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(2, 'home', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(3, 'login', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(4, 'pages', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(5, 'users', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(6, 'user', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(7, 'profile', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(8, 'timezone', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(9, 'mltest', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(10, 'mathtest', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(11, 'financetest', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(12, 'geminitest', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(13, 'planimport', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);
/*!40000 ALTER TABLE `sys_modules` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_sessions
CREATE TABLE IF NOT EXISTS `sys_sessions` (
  `id` varchar(32) NOT NULL,
  `sdata` varchar(2500) NOT NULL,
  `lastaccessed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Dumping data for table pwo.sys_sessions: 0 rows
DELETE FROM `sys_sessions`;
/*!40000 ALTER TABLE `sys_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_sessions` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
