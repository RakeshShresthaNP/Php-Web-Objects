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
  `reply_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`,`d_created`)
) ENGINE=Aria AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwo.chat_logs: 1 rows
DELETE FROM `chat_logs`;
/*!40000 ALTER TABLE `chat_logs` DISABLE KEYS */;
INSERT INTO `chat_logs` (`id`, `sender_id`, `message`, `file_path`, `file_name`, `is_read`, `status`, `d_created`, `d_updated`, `reply_to`) VALUES
	(1, 3, 'get the file', 'public/uploads/chat/2026/01/1769845782_bankaccountaddressverificationdocument.jpg', 'bankaccountaddressverificationdocument.jpg', 0, 1, '2026-01-31 07:49:42', '2026-01-31 07:49:42', NULL);
/*!40000 ALTER TABLE `chat_logs` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
