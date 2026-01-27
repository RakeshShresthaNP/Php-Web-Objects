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
  `message` text DEFAULT NULL,
  `file_path` varchar(512) DEFAULT NULL COMMENT 'Path to the reassembled file',
  `file_name` varchar(255) DEFAULT NULL COMMENT 'Original name of the uploaded file',
  `is_read` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1 COMMENT '1: active, 0: deleted',
  `d_created` datetime DEFAULT current_timestamp(),
  `d_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_created` (`d_created`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.chat_logs: ~0 rows (approximately)
DELETE FROM `chat_logs`;

-- Dumping structure for table pwo.documentextractions
CREATE TABLE IF NOT EXISTS `documentextractions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(10) unsigned NOT NULL DEFAULT 0,
  `document_id` uuid DEFAULT NULL,
  `extractedjson` json NOT NULL CHECK (json_valid(`extractedjson`)),
  `aimodel` varchar(50) DEFAULT NULL,
  `confidencescore` decimal(5,2) DEFAULT NULL,
  `ishumanverified` tinyint(1) DEFAULT 0,
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
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
  `filename` varchar(255) DEFAULT NULL,
  `s3path` varchar(512) DEFAULT NULL,
  `mimetype` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
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
  `dtimestamp` timestamp NULL DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
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
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
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
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_partnershost` (`hostname`),
  KEY `key_partners` (`c_name`,`email`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_partners: 2 rows
DELETE FROM `mst_partners`;
/*!40000 ALTER TABLE `mst_partners` DISABLE KEYS */;
INSERT INTO `mst_partners` (`id`, `c_name`, `hostname`, `sitetitle`, `email`, `phone1`, `phone2`, `contactfax`, `address1`, `address2`, `city`, `state`, `country`, `zip`, `remarks`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 'Test', 'localhost', 'Pwo Title', 'test@test.com', '', '', '', '', '', 'Kathmandu', 'BG', 'NP', '92630', '', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(2, 'Test2', 'localhost2', 'Pwo Title2', 'test2@test.com', '', '', '', '', '', 'Kathmandu', 'BG', 'NP', '92630', '', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);
/*!40000 ALTER TABLE `mst_partners` ENABLE KEYS */;

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
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_partnersettingss` (`partner_id`,`mailhost`,`mailusername`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_partner_settings: 1 rows
DELETE FROM `mst_partner_settings`;
/*!40000 ALTER TABLE `mst_partner_settings` DISABLE KEYS */;
INSERT INTO `mst_partner_settings` (`id`, `partner_id`, `secretkey`, `mailhost`, `mailport`, `mailusername`, `mailpassword`, `geoip_api_key`, `firebase_api_key`, `gemini_api_key`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 1, 'Nepal@123', 'smtp.gmail.com', '587', '', '', '', '', '', '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);
/*!40000 ALTER TABLE `mst_partner_settings` ENABLE KEYS */;

-- Dumping structure for table pwo.mst_reportpivots
CREATE TABLE IF NOT EXISTS `mst_reportpivots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(50) DEFAULT NULL,
  `desc` varchar(200) DEFAULT NULL,
  `viewsql` text DEFAULT NULL,
  `perms` int(11) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
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
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
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
  `c_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `homepath` varchar(100) DEFAULT NULL,
  `realname` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_usersc_name` (`partner_id`,`c_name`),
  UNIQUE KEY `key_usersemail` (`email`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_users: 3 rows
DELETE FROM `mst_users`;
/*!40000 ALTER TABLE `mst_users` DISABLE KEYS */;
INSERT INTO `mst_users` (`id`, `partner_id`, `c_name`, `email`, `phone`, `homepath`, `realname`, `password`, `perms`, `status`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 1, 'superadmin', 'superadmin@gmail.com', '', 'manage', 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'superadmin', 1, '2026-01-01 02:00:00', 0, '2026-01-01 02:00:00', 0),
	(2, 1, 'admin', 'admin@gmail.com', '', 'dashboard', 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'admin', 1, '2026-01-01 02:00:00', 1, '2026-01-24 13:43:48', 1),
	(3, 1, 'user', 'user@gmail.com', '', 'dashboard', 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'user', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1);
/*!40000 ALTER TABLE `mst_users` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_auditlogs
CREATE TABLE IF NOT EXISTS `sys_auditlogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actionname` varchar(50) DEFAULT NULL COMMENT 'LOGIN, DOC_DELETE',
  `entitytype` varchar(50) DEFAULT NULL COMMENT 'users, invoices',
  `entityid` varchar(50) DEFAULT NULL,
  `datadiff` json NOT NULL CHECK (json_valid(`datadiff`)),
  `ipdetails` json NOT NULL CHECK (json_valid(`ipdetails`)),
  `devicedetails` json NOT NULL CHECK (json_valid(`devicedetails`)),
  `ipaddress` varchar(45) GENERATED ALWAYS AS (json_value(`ipdetails`,'$.ip')) VIRTUAL,
  `country` char(2) GENERATED ALWAYS AS (json_value(`ipdetails`,'$.country_name')) VIRTUAL,
  `os` varchar(50) GENERATED ALWAYS AS (json_value(`devicedetails`,'$.os_title')) VIRTUAL,
  `browser` varchar(50) GENERATED ALWAYS AS (json_value(`devicedetails`,'$.browser_tiltle')) VIRTUAL,
  `hashchain` char(64) DEFAULT NULL COMMENT 'SHA-256 of current and previous record datadiff field',
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_auditlogsuseraction` (`user_id`,`actionname`,`entitytype`,`entityid`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1 TRANSACTIONAL=1;

-- Dumping data for table pwo.sys_auditlogs: 0 rows
DELETE FROM `sys_auditlogs`;
/*!40000 ALTER TABLE `sys_auditlogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_auditlogs` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_blocked_ips
CREATE TABLE IF NOT EXISTS `sys_blocked_ips` (
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT 'Brute force detected',
  `blocked_at` timestamp NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`ip_address`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_blocked_ips: 0 rows
DELETE FROM `sys_blocked_ips`;
/*!40000 ALTER TABLE `sys_blocked_ips` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_blocked_ips` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_job_queues
CREATE TABLE IF NOT EXISTS `sys_job_queues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_name` varchar(100) NOT NULL,
  `payload` json NOT NULL CHECK (json_valid(`payload`)),
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `available_at` timestamp NOT NULL DEFAULT utc_timestamp(),
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_availability` (`status`,`available_at`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_job_queues: 0 rows
DELETE FROM `sys_job_queues`;
/*!40000 ALTER TABLE `sys_job_queues` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_job_queues` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_login_attempts
CREATE TABLE IF NOT EXISTS `sys_login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(191) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_login_attempts: 0 rows
DELETE FROM `sys_login_attempts`;
/*!40000 ALTER TABLE `sys_login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_login_attempts` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_methods
CREATE TABLE IF NOT EXISTS `sys_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(32) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `controllername` varchar(32) DEFAULT NULL,
  `controllermethod` varchar(32) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_usersc_name` (`controllername`,`controllermethod`),
  KEY `key_methods` (`c_name`,`module_id`,`status`,`perms`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_methods: 34 rows
DELETE FROM `sys_methods`;
/*!40000 ALTER TABLE `sys_methods` DISABLE KEYS */;
INSERT INTO `sys_methods` (`id`, `c_name`, `module_id`, `controllername`, `controllermethod`, `description`, `perms`, `status`, `d_created`, `u_created`, `d_updated`, `u_updated`) VALUES
	(1, 'home_index', 2, 'home', 'index', '', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(2, 'home_manage_index', 2, 'home', 'manage_index', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(3, 'home_dashboard_index', 2, 'home', 'dashboard_index', '', 'admin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(4, 'login_index', 3, 'login', 'index', '', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(5, 'login_forgotpass', 3, 'login', 'forgotpass', '', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(6, 'login_logout', 3, 'login', 'logout', '', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(7, 'pages_index', 4, 'pages', 'index', '', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(8, 'pages_dashboard_advancedforms', 4, 'pages', 'dashboard_advancedforms', '', 'admin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(9, 'pages_dashboard_simpletables', 4, 'pages', 'dashboard_simpletables', '', 'admin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(10, 'pages_manage_advancedforms', 4, 'pages', 'manage_advancedforms', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(11, 'pages_manage_simpletables', 4, 'pages', 'manage_simpletables', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(12, 'users_manage_index', 5, 'users', 'manage_index', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(13, 'users_manage_add', 5, 'users', 'manage_add', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(14, 'users_manage_edit', 5, 'users', 'manage_edit', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(15, 'users_manage_disable', 5, 'users', 'manage_disable', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(16, 'users_manage_enable', 5, 'users', 'manage_enable', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(17, 'auth_api_login', 1, 'auth', 'api_login', '', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(18, 'auth_api_refresh', 1, 'auth', 'api_refresh', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(19, 'auth_api_logout', 1, 'auth', 'api_logout', '', 'none', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(20, 'auth_api_codes', 1, 'auth', 'api_codes', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(21, 'user_api_info', 6, 'user', 'api_info', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(22, 'profile_api_search', 7, 'profile', 'api_search', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(23, 'timezone_api_gettimezone', 8, 'timezone', 'api_gettimezone', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(24, 'financetest_index', 11, 'financetest', 'index', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(25, 'geminitest_index', 12, 'geminitest', 'index', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(26, 'mathtest_index', 10, 'mathtest', 'index', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(27, 'mltest_index', 9, 'mltest', 'index', '', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(28, 'planimport_api_importhr', 13, 'planimport', 'api_importhr', '', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(29, 'chat_send', 14, 'chat', 'send', NULL, 'none', 1, '2026-01-24 09:46:56', 1, NULL, NULL),
	(30, 'chat_history', 14, 'chat', 'history', NULL, 'admin,superadmin,user', 1, '2026-01-24 09:46:56', 1, NULL, NULL),
	(31, 'chat_delete', 14, 'chat', 'delete', NULL, 'admin,superadmin,user', 1, '2026-01-24 09:48:48', 1, NULL, NULL),
	(33, 'chat_uploadchunk', 14, 'chat', 'uploadchunk', NULL, 'user,admin,superadmin', 1, '2026-01-25 01:23:06', NULL, NULL, NULL),
	(34, 'chat_markread', 14, 'chat', 'markread', 'Updates is_read status for message marks', 'user,admin,superadmin', 1, '2026-01-26 09:45:00', 1, NULL, NULL),
	(35, 'chat_typing', 14, 'chat', 'typing', 'Broadcasts typing status to partners', 'user,admin,superadmin', 1, '2026-01-26 09:45:00', 1, NULL, NULL);
/*!40000 ALTER TABLE `sys_methods` ENABLE KEYS */;

-- Dumping structure for table pwo.sys_modules
CREATE TABLE IF NOT EXISTS `sys_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(255) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `d_created` timestamp NULL DEFAULT utc_timestamp(),
  `u_created` bigint(20) DEFAULT NULL,
  `d_updated` timestamp NULL DEFAULT NULL,
  `u_updated` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_modulescname` (`c_name`),
  KEY `key_modules` (`status`,`perms`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_modules: 14 rows
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
	(13, 'planimport', 'superadmin', 1, '2026-01-01 02:00:00', 1, '2026-01-01 02:00:00', 1),
	(14, 'chat', 'none', 1, '2026-01-24 09:46:56', 1, NULL, NULL);
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
