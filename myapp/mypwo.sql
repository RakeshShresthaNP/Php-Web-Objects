-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               11.8.5-MariaDB
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS=0;

-- 1. chat_logs
CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
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
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. documentextractions
CREATE TABLE IF NOT EXISTS `documentextractions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(10) unsigned NOT NULL DEFAULT 0,
  `document_id` uuid DEFAULT NULL,
  `extractedjson` json NOT NULL,
  `aimodel` varchar(50) DEFAULT NULL,
  `confidencescore` decimal(5,2) DEFAULT NULL,
  `ishumanverified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. documents
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
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. marketdatas
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
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. mlmodelmetadatas
CREATE TABLE IF NOT EXISTS `mlmodelmetadatas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(100) DEFAULT NULL,
  `version` varchar(10) DEFAULT NULL,
  `accuracymetrics` json NOT NULL,
  `serializedpath` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. mst_partners
CREATE TABLE IF NOT EXISTS `mst_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(64) DEFAULT NULL,
  `hostname` varchar(256) DEFAULT NULL,
  `sitetitle` varchar(256) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `city` varchar(32) DEFAULT '',
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_partnershost` (`hostname`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mst_partners` (`id`, `c_name`, `hostname`, `sitetitle`, `email`, `city`, `created_at`, `created_by`) VALUES
(1, 'Test', 'localhost', 'Pwo Title', 'test@test.com', 'Kathmandu', '2026-01-01 02:00:00', 1),
(2, 'Test2', 'localhost2', 'Pwo Title2', 'test2@test.com', 'Kathmandu', '2026-01-01 02:00:00', 1);

-- 7. mst_partner_settings
CREATE TABLE IF NOT EXISTS `mst_partner_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `secretkey` varchar(256) DEFAULT NULL,
  `mailhost` varchar(256) DEFAULT NULL,
  `mailport` varchar(256) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. mst_users
CREATE TABLE IF NOT EXISTS `mst_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL DEFAULT 0,
  `c_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `realname` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_usersemail` (`email`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mst_users` (`id`, `partner_id`, `c_name`, `email`, `realname`, `password`, `perms`, `status`, `created_at`, `created_by`) VALUES
(1, 1, 'superadmin', 'superadmin@gmail.com', 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'superadmin', 1, '2026-01-01 02:00:00', 1),
(2, 1, 'admin', 'admin@gmail.com', 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'admin', 1, '2026-01-01 02:00:00', 1),
(3, 1, 'user', 'user@gmail.com', 'Rakesh Shrestha', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'user', 1, '2026-01-01 02:00:00', 1);

-- 9. sys_auditlogs
CREATE TABLE IF NOT EXISTS `sys_auditlogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actionname` varchar(50) DEFAULT NULL,
  `entitytype` varchar(50) DEFAULT NULL,
  `entityid` varchar(50) DEFAULT NULL,
  `datadiff` json NOT NULL,
  `ipdetails` json NOT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria TRANSACTIONAL=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. sys_modules
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
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sys_modules` (`id`, `c_name`, `perms`, `status`, `created_at`, `created_by`) VALUES
(1, 'auth', 'none', 1, '2026-01-01 02:00:00', 1), (2, 'home', 'none', 1, '2026-01-01 02:00:00', 1),
(3, 'login', 'none', 1, '2026-01-01 02:00:00', 1), (4, 'pages', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1),
(5, 'users', 'superadmin', 1, '2026-01-01 02:00:00', 1), (6, 'user', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1),
(7, 'settings', 'superadmin', 1, '2026-01-01 02:00:00', 1), (8, 'logs', 'superadmin', 1, '2026-01-01 02:00:00', 1),
(9, 'api', 'none', 1, '2026-01-01 02:00:00', 1), (10, 'profile', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1),
(11, 'dashboard', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1), (12, 'notifications', 'none', 1, '2026-01-01 02:00:00', 1),
(13, 'reports', 'superadmin,admin', 1, '2026-01-01 02:00:00', 1), (14, 'chat', 'none', 1, '2026-01-24 09:46:56', 1),
(15, 'supportsystem', 'superadmin,admin,demo', 1, '2026-01-29 00:37:15', 1);

-- 11. sys_methods (ALL 36 ROWS RESTORED)
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
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sys_methods` (`id`, `c_name`, `module_id`, `controllername`, `controllermethod`, `perms`, `status`, `created_at`, `created_by`) VALUES
(1, 'home_index', 2, 'home', 'index', 'none', 1, '2026-01-01 02:00:00', 1), (2, 'login_index', 3, 'login', 'index', 'none', 1, '2026-01-01 02:00:00', 1),
(3, 'login_process', 3, 'login', 'process', 'none', 1, '2026-01-01 02:00:00', 1), (4, 'login_logout', 3, 'login', 'logout', 'none', 1, '2026-01-01 02:00:00', 1),
(5, 'pages_index', 4, 'pages', 'index', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1), (6, 'pages_view', 4, 'pages', 'view', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1),
(7, 'pages_add', 4, 'pages', 'add', 'admin,superadmin', 1, '2026-01-01 02:00:00', 1), (8, 'pages_edit', 4, 'pages', 'edit', 'admin,superadmin', 1, '2026-01-01 02:00:00', 1),
(9, 'pages_delete', 4, 'pages', 'delete', 'superadmin', 1, '2026-01-01 02:00:00', 1), (10, 'user_profile', 6, 'user', 'profile', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1),
(11, 'user_settings', 6, 'user', 'settings', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1), (12, 'users_manage_index', 5, 'users', 'manage_index', 'superadmin', 1, '2026-01-01 02:00:00', 1),
(13, 'users_manage_add', 5, 'users', 'manage_add', 'superadmin', 1, '2026-01-01 02:00:00', 1), (14, 'users_manage_edit', 5, 'users', 'manage_edit', 'superadmin', 1, '2026-01-01 02:00:00', 1),
(15, 'users_manage_delete', 5, 'users', 'manage_delete', 'superadmin', 1, '2026-01-01 02:00:00', 1), (16, 'settings_index', 7, 'settings', 'index', 'superadmin', 1, '2026-01-01 02:00:00', 1),
(17, 'settings_update', 7, 'settings', 'update', 'superadmin', 1, '2026-01-01 02:00:00', 1), (18, 'logs_index', 8, 'logs', 'index', 'superadmin', 1, '2026-01-01 02:00:00', 1),
(19, 'logs_view', 8, 'logs', 'view', 'superadmin', 1, '2026-01-01 02:00:00', 1), (20, 'api_v1_index', 9, 'api', 'v1_index', 'none', 1, '2026-01-01 02:00:00', 1),
(21, 'dashboard_index', 11, 'dashboard', 'index', 'admin,superadmin,user,demo', 1, '2026-01-01 02:00:00', 1), (22, 'notifications_list', 12, 'notifications', 'list', 'none', 1, '2026-01-01 02:00:00', 1),
(23, 'reports_generate', 13, 'reports', 'generate', 'superadmin,admin', 1, '2026-01-01 02:00:00', 1), (24, 'auth_verify', 1, 'auth', 'verify', 'none', 1, '2026-01-01 02:00:00', 1),
(25, 'auth_reset', 1, 'auth', 'reset', 'none', 1, '2026-01-01 02:00:00', 1), (26, 'chat_index', 14, 'chat', 'index', 'none', 1, '2026-01-24 09:46:56', 1),
(27, 'chat_history', 14, 'chat', 'history', 'none', 1, '2026-01-24 09:46:56', 1), (28, 'chat_send', 14, 'chat', 'send', 'none', 1, '2026-01-24 09:46:56', 1),
(29, 'support_ticket_list', 15, 'supportsystem', 'ticket_list', 'superadmin,admin,demo', 1, '2026-01-29 00:37:15', 1), (30, 'support_ticket_view', 15, 'supportsystem', 'ticket_view', 'superadmin,admin,demo', 1, '2026-01-29 00:37:15', 1),
(31, 'support_ticket_add', 15, 'supportsystem', 'ticket_add', 'superadmin,admin,demo', 1, '2026-01-29 00:37:15', 1), (32, 'support_ticket_edit', 15, 'supportsystem', 'ticket_edit', 'superadmin,admin,demo', 1, '2026-01-29 00:37:15', 1),
(33, 'support_ticket_delete', 15, 'supportsystem', 'ticket_delete', 'superadmin', 1, '2026-01-29 00:37:15', 1), (34, 'supportsystem_manage_index', 15, 'supportsystem', 'manage_index', 'superadmin,admin,demo', 1, '2026-01-29 00:47:30', 1),
(35, 'support_config', 15, 'supportsystem', 'config', 'superadmin', 1, '2026-01-29 00:47:30', 1), (36, 'support_analytics', 15, 'supportsystem', 'analytics', 'superadmin,admin', 1, '2026-01-29 00:47:30', 1);

-- --------------------------------------------------------
-- FOREIGN KEY IMPLEMENTATION (Maintaining original table order above)
-- --------------------------------------------------------

ALTER TABLE `mst_partner_settings` ADD CONSTRAINT `fk_settings_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE;
ALTER TABLE `mst_users` ADD CONSTRAINT `fk_users_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE;
ALTER TABLE `sys_methods` ADD CONSTRAINT `fk_methods_module` FOREIGN KEY (`module_id`) REFERENCES `sys_modules` (`id`) ON DELETE CASCADE;
ALTER TABLE `documents` ADD CONSTRAINT `fk_docs_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`);
ALTER TABLE `documents` ADD CONSTRAINT `fk_docs_user` FOREIGN KEY (`user_id`) REFERENCES `mst_users` (`id`);

SET FOREIGN_KEY_CHECKS=1;
