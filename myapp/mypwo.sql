SET FOREIGN_KEY_CHECKS=0;

-- 1. chat_logs
CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `reply_to` int(11) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(512) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_composite` (`sender_id`, `target_id`, `created_at`),
  KEY `idx_reply_to` (`reply_to`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 2. documentextractions
CREATE TABLE IF NOT EXISTS `documentextractions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) unsigned NOT NULL,
  `document_id` uuid DEFAULT NULL,
  `extractedjson` json NOT NULL CHECK (json_valid(`extractedjson`)),
  `aimodel` varchar(50) DEFAULT NULL,
  `confidencescore` decimal(5,2) DEFAULT NULL,
  `ishumanverified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_document_id` (`document_id`),
  KEY `idx_extract_composite` (`partner_id`, `created_at`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 3. documents
CREATE TABLE IF NOT EXISTS `documents` (
  `id` uuid NOT NULL,
  `partner_id` int(11) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `s3path` varchar(512) DEFAULT NULL,
  `mimetype` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_composite` (`partner_id`, `status`, `created_at`),
  KEY `idx_doc_user` (`user_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 4. marketdatas
CREATE TABLE IF NOT EXISTS `marketdatas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(10) NOT NULL,
  `price` decimal(18,8) NOT NULL,
  `volume` bigint(20) DEFAULT NULL,
  `dtimestamp` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_market_composite` (`c_name`, `dtimestamp`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 5. mlmodelmetadatas
CREATE TABLE IF NOT EXISTS `mlmodelmetadatas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(100) DEFAULT NULL,
  `version` varchar(10) DEFAULT NULL,
  `accuracymetrics` json NOT NULL CHECK (json_valid(`accuracymetrics`)),
  `serializedpath` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 6. mst_partners
CREATE TABLE IF NOT EXISTS `mst_partners` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(64) DEFAULT NULL,
  `hostname` varchar(256) DEFAULT NULL,
  `sitetitle` varchar(256) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `city` varchar(32) DEFAULT '',
  `country` varchar(32) DEFAULT 'US',
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_partnershost` (`hostname`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

INSERT INTO `mst_partners` (`id`, `c_name`, `hostname`, `sitetitle`, `email`, `city`, `country`) VALUES
(1, 'Test', 'localhost', 'Pwo Title', 'test@test.com', 'Kathmandu', 'NP'),
(2, 'Test2', 'localhost2', 'Pwo Title2', 'test2@test.com', 'Kathmandu', 'NP');

-- 7. mst_partner_settings (RESTORED DATA)
CREATE TABLE IF NOT EXISTS `mst_partner_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) unsigned NOT NULL,
  `secretkey` varchar(256) DEFAULT NULL,
  `mailhost` varchar(256) DEFAULT NULL,
  `mailport` varchar(256) DEFAULT NULL,
  `mailusername` varchar(256) DEFAULT NULL,
  `mailpassword` varchar(256) DEFAULT NULL,
  `geoip_api_key` varchar(256) DEFAULT NULL,
  `firebase_api_key` varchar(256) DEFAULT NULL,
  `gemini_api_key` varchar(256) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_setting` (`partner_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

INSERT INTO `mst_partner_settings` (`partner_id`, `secretkey`, `mailhost`, `mailport`, `mailusername`, `mailpassword`) VALUES
(1, 'sk_test_51Mz', 'smtp.mailtrap.io', '2525', 'user_123', 'pass_123'),
(2, 'sk_live_99X', 'smtp.gmail.com', '587', 'admin@test2.com', 'secure_pass');

-- 8. mst_users
CREATE TABLE IF NOT EXISTS `mst_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) unsigned NOT NULL,
  `c_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `perms` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_partner_user` (`partner_id`, `c_name`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

INSERT INTO `mst_users` (`id`, `partner_id`, `c_name`, `email`, `password`, `perms`) VALUES
(1, 1, 'superadmin', 'superadmin@gmail.com', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'superadmin'),
(2, 1, 'admin', 'admin@gmail.com', '$2y$12$6QXxO0iDsEmJlUCi0Or7E.QzvqzKonyvNAhJKOT3vPY5zOSlTwR42', 'admin');

-- 9. sys_auditlogs
CREATE TABLE IF NOT EXISTS `sys_auditlogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actionname` varchar(50) DEFAULT NULL,
  `entitytype` varchar(50) DEFAULT NULL,
  `entityid` varchar(50) DEFAULT NULL,
  `datadiff` json NOT NULL CHECK (json_valid(`datadiff`)),
  `ipdetails` json NOT NULL CHECK (json_valid(`ipdetails`)),
  `devicedetails` json NOT NULL CHECK (json_valid(`devicedetails`)),
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_composite` (`entitytype`, `entityid`, `created_at`),
  KEY `idx_audit_user` (`user_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 10. sys_blocked_ips
CREATE TABLE IF NOT EXISTS `sys_blocked_ips` (
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `blocked_at` timestamp NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`ip_address`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 11. sys_login_attempts
CREATE TABLE IF NOT EXISTS `sys_login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(191) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT utc_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_composite` (`ip_address`, `attempted_at`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 12. sys_sessions
CREATE TABLE IF NOT EXISTS `sys_sessions` (
  `id` varchar(32) NOT NULL,
  `sdata` varchar(2500) NOT NULL,
  `lastaccessed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb3;

-- 13. mst_notifications
CREATE TABLE IF NOT EXISTS `mst_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_notif_composite` (`user_id`, `is_read`, `created_at`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 14. sys_error_logs
CREATE TABLE IF NOT EXISTS `sys_error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 15. mst_api_keys
CREATE TABLE IF NOT EXISTS `mst_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) unsigned NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_key` (`api_key`),
  KEY `idx_partner_api` (`partner_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- 16. user_permissions
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `module_name` varchar(50) DEFAULT NULL,
  `can_read` tinyint(1) DEFAULT 0,
  `can_write` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT utc_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_module_composite` (`user_id`, `module_name`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- FOREIGN KEY CONSTRAINTS
-- --------------------------------------------------------
ALTER TABLE `mst_partner_settings` ADD CONSTRAINT `fk_settings_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE;
ALTER TABLE `mst_users` ADD CONSTRAINT `fk_users_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE;
ALTER TABLE `chat_logs` ADD CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `mst_users` (`id`) ON DELETE CASCADE;
ALTER TABLE `documents` ADD CONSTRAINT `fk_doc_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE;
ALTER TABLE `documentextractions` ADD CONSTRAINT `fk_extraction_doc` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;
ALTER TABLE `mst_api_keys` ADD CONSTRAINT `fk_api_partner` FOREIGN KEY (`partner_id`) REFERENCES `mst_partners` (`id`) ON DELETE CASCADE;
ALTER TABLE `mst_notifications` ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `mst_users` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_permissions` ADD CONSTRAINT `fk_perm_user` FOREIGN KEY (`user_id`) REFERENCES `mst_users` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;
