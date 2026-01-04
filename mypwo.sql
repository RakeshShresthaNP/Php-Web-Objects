/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table pwotest.sys_sessions
CREATE TABLE IF NOT EXISTS `sys_sessions` (
  `id` varchar(32) NOT NULL,
  `sdata` varchar(2500) NOT NULL,
  `last_accessed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Memory DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwotest.sys_sessions: 0 rows
DELETE FROM `sys_sessions`;
/*!40000 ALTER TABLE `sys_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_sessions` ENABLE KEYS */;

-- Dumping structure for table pwotest.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `country` varchar(25) NOT NULL,
  `perms` varchar(10) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `remarks` blob NOT NULL DEFAULT '',
  `registerip` varchar(50) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table pwotest.users: 2 rows
DELETE FROM `users`;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `username`, `password`, `firstname`, `lastname`, `country`, `perms`, `status`, `remarks`, `registerip`, `created`) VALUES
	(1, 'superadmin@gmail.com', '$2y$12$5qwHKGAGImFrsQILwLldW.DMSc9FX6EWuCT2.n9yzaESaKGbqYAZm', 'Super', 'Admin', 'NP', 'superadmin', 1, '', '', '2019-03-11 11:29:42'),
	(2, 'user@gmail.com', '$2y$12$5qwHKGAGImFrsQILwLldW.DMSc9FX6EWuCT2.n9yzaESaKGbqYAZm', 'My', 'User', 'NP', 'user', 1, '', '', '2019-03-11 11:29:42');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
