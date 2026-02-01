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
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) NOT NULL,
  `reply_to` int(11) DEFAULT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(512) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_history` (`sender_id`,`target_id`,`created_at`),
  KEY `idx_unread_check` (`target_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_partner_document` (`partner_id`,`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.documentextractions: ~0 rows (approximately)
DELETE FROM `documentextractions`;

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
  KEY `idx_partner_docs` (`partner_id`,`status`),
  KEY `idx_user_docs` (`user_id`),
  CONSTRAINT `fk_docs_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`),
  CONSTRAINT `fk_docs_user` FOREIGN KEY (`user_id`) REFERENCES `mst_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.documents: ~0 rows (approximately)
DELETE FROM `documents`;

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
  KEY `idx_ticker_time` (`c_name`,`dtimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.marketdatas: ~0 rows (approximately)
DELETE FROM `marketdatas`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.mlmodelmetadatas: ~0 rows (approximately)
DELETE FROM `mlmodelmetadatas`;

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
  UNIQUE KEY `key_partnershost` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.mst_partners: ~2 rows (approximately)
DELETE FROM `mst_partners`;
INSERT INTO `mst_partners` (`id`, `c_name`, `hostname`, `sitetitle`, `email`, `phone1`, `phone2`, `contactfax`, `address1`, `address2`, `city`, `state`, `country`, `zip`, `remarks`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
	(1, 'Test', 'localhost', 'Pwo Title', 'test@test.com', '', NULL, NULL, '', NULL, 'Kathmandu', '', 'US', '', '', '2026-02-01 03:39:35', 1, NULL, NULL),
	(2, 'Test2', 'localhost2', 'Pwo Title2', 'test2@test.com', '', NULL, NULL, '', NULL, 'Kathmandu', '', 'US', '', '', '2026-02-01 03:39:35', 1, NULL, NULL);

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
  KEY `idx_settings_partner` (`partner_id`),
  CONSTRAINT `fk_settings_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.mst_partner_settings: ~1 rows (approximately)
DELETE FROM `mst_partner_settings`;
INSERT INTO `mst_partner_settings` (`id`, `partner_id`, `secretkey`, `mailhost`, `mailport`, `mailusername`, `mailpassword`, `geoip_api_key`, `firebase_api_key`, `gemini_api_key`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
	(1, 1, 'Nepal@123', 'smtp.gmail.com', '587', '', '', '', '', '', '2026-02-01 03:39:35', 1, NULL, NULL);

-- Dumping structure for table pwo.mst_reportpivots
CREATE TABLE IF NOT EXISTS `mst_reportpivots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(50) DEFAULT NULL,
  `desc` varchar(200) DEFAULT NULL,
  `viewsql` text DEFAULT NULL,
  `perms` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_reportpivots: ~0 rows (approximately)
DELETE FROM `mst_reportpivots`;

-- Dumping structure for table pwo.mst_reports
CREATE TABLE IF NOT EXISTS `mst_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(200) DEFAULT NULL,
  `c_sql` text DEFAULT NULL,
  `perms` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.mst_reports: ~0 rows (approximately)
DELETE FROM `mst_reports`;

-- Dumping structure for table pwo.mst_users
CREATE TABLE IF NOT EXISTS `mst_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL DEFAULT 0,
  `c_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `homepath` varchar(100) DEFAULT NULL,
  `realname` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `totp_secret` varchar(32) DEFAULT NULL,
  `totp_enabled` tinyint(1) DEFAULT 0,
  `d_lastlogin` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_partner_username` (`partner_id`,`c_name`),
  UNIQUE KEY `key_usersemail` (`email`),
  CONSTRAINT `fk_users_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.mst_users: ~3 rows (approximately)
DELETE FROM `mst_users`;
INSERT INTO `mst_users` (`id`, `partner_id`, `c_name`, `email`, `phone`, `homepath`, `realname`, `password`, `perms`, `totp_secret`, `totp_enabled`, `d_lastlogin`, `status`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
	(1, 1, 'superadmin', 'superadmin@gmail.com', NULL, NULL, 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'superadmin', NULL, 0, NULL, 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(2, 1, 'admin', 'admin@gmail.com', NULL, NULL, 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'admin', NULL, 0, NULL, 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(3, 1, 'user', 'user@gmail.com', NULL, NULL, 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'user', NULL, 0, NULL, 1, '2026-02-01 03:39:35', 1, NULL, NULL);

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
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_search` (`entitytype`,`entityid`),
  KEY `idx_audit_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci TRANSACTIONAL=1;

-- Dumping data for table pwo.sys_auditlogs: ~0 rows (approximately)
DELETE FROM `sys_auditlogs`;

-- Dumping structure for table pwo.sys_blocked_ips
CREATE TABLE IF NOT EXISTS `sys_blocked_ips` (
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT 'Brute force detected',
  `blocked_at` timestamp NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_blocked_ips: ~0 rows (approximately)
DELETE FROM `sys_blocked_ips`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_job_queues: ~0 rows (approximately)
DELETE FROM `sys_job_queues`;

-- Dumping structure for table pwo.sys_login_attempts
CREATE TABLE IF NOT EXISTS `sys_login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(191) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.sys_login_attempts: ~0 rows (approximately)
DELETE FROM `sys_login_attempts`;

-- Dumping structure for table pwo.sys_methods
CREATE TABLE IF NOT EXISTS `sys_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(32) DEFAULT NULL,
  `module_id` int(11) NOT NULL,
  `controllername` varchar(32) DEFAULT NULL,
  `controllermethod` varchar(32) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_route_lookup` (`controllername`,`controllermethod`),
  KEY `idx_method_module` (`module_id`,`c_name`),
  CONSTRAINT `fk_methods_module` FOREIGN KEY (`module_id`) REFERENCES `sys_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.sys_methods: ~36 rows (approximately)
DELETE FROM `sys_methods`;
INSERT INTO `sys_methods` (`id`, `c_name`, `module_id`, `controllername`, `controllermethod`, `perms`, `status`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
	(1, 'home_index', 2, 'home', 'index', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(2, 'login_index', 3, 'login', 'index', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(3, 'login_process', 3, 'login', 'process', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(4, 'login_logout', 3, 'login', 'logout', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(5, 'pages_index', 4, 'pages', 'index', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(6, 'pages_view', 4, 'pages', 'view', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(7, 'pages_add', 4, 'pages', 'add', 'admin,superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(8, 'pages_edit', 4, 'pages', 'edit', 'admin,superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(9, 'pages_delete', 4, 'pages', 'delete', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(10, 'user_profile', 6, 'user', 'profile', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(11, 'user_settings', 6, 'user', 'settings', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(12, 'users_manage_index', 5, 'users', 'manage_index', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(13, 'users_manage_add', 5, 'users', 'manage_add', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(14, 'users_manage_edit', 5, 'users', 'manage_edit', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(15, 'users_manage_delete', 5, 'users', 'manage_delete', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(16, 'settings_index', 7, 'settings', 'index', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(17, 'settings_update', 7, 'settings', 'update', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(18, 'logs_index', 8, 'logs', 'index', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(19, 'logs_view', 8, 'logs', 'view', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(20, 'api_v1_index', 9, 'api', 'v1_index', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(21, 'dashboard_index', 11, 'dashboard', 'index', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(22, 'notifications_list', 12, 'notifications', 'list', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(23, 'reports_generate', 13, 'reports', 'generate', 'superadmin,admin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(24, 'auth_verify', 1, 'auth', 'verify', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(25, 'auth_reset', 1, 'auth', 'reset', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(26, 'chat_index', 14, 'chat', 'index', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(27, 'chat_history', 14, 'chat', 'history', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(28, 'chat_send', 14, 'chat', 'send', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(29, 'support_ticket_list', 15, 'supportsystem', 'ticket_list', 'superadmin,admin,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(30, 'support_ticket_view', 15, 'supportsystem', 'ticket_view', 'superadmin,admin,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(31, 'support_ticket_add', 15, 'supportsystem', 'ticket_add', 'superadmin,admin,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(32, 'support_ticket_edit', 15, 'supportsystem', 'ticket_edit', 'superadmin,admin,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(33, 'support_ticket_delete', 15, 'supportsystem', 'ticket_delete', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(34, 'supportsystem_manage_index', 15, 'supportsystem', 'manage_index', 'superadmin,admin,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(35, 'support_config', 15, 'supportsystem', 'config', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(36, 'support_analytics', 15, 'supportsystem', 'analytics', 'superadmin,admin', 1, '2026-02-01 03:39:35', 1, NULL, NULL);

-- Dumping structure for table pwo.sys_modules
CREATE TABLE IF NOT EXISTS `sys_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(255) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_modulescname` (`c_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table pwo.sys_modules: ~15 rows (approximately)
DELETE FROM `sys_modules`;
INSERT INTO `sys_modules` (`id`, `c_name`, `perms`, `status`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
	(1, 'auth', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(2, 'home', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(3, 'login', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(4, 'pages', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(5, 'users', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(6, 'user', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(7, 'settings', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(8, 'logs', 'superadmin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(9, 'api', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(10, 'profile', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(11, 'dashboard', 'admin,superadmin,user,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(12, 'notifications', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(13, 'reports', 'superadmin,admin', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(14, 'chat', 'none', 1, '2026-02-01 03:39:35', 1, NULL, NULL),
	(15, 'supportsystem', 'superadmin,admin,demo', 1, '2026-02-01 03:39:35', 1, NULL, NULL);

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
