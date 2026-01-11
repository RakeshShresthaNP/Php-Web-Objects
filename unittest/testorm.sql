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

-- Dumping structure for table testorm.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table testorm.categories: ~3 rows (approximately)
DELETE FROM `categories`;
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
	(1, 'Electronics', 'Gadgets, phones, and laptops', '2026-01-11 14:41:26'),
	(2, 'Home & Kitchen', 'Appliances and furniture', '2026-01-11 14:41:26'),
	(3, 'Digital Services', 'Software licenses and subscriptions', '2026-01-11 14:41:26');

-- Dumping structure for table testorm.comments
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `d_created` datetime DEFAULT current_timestamp(),
  `d_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `d_deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table testorm.comments: 1 rows
DELETE FROM `comments`;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` (`id`, `order_id`, `user_id`, `comment_text`, `metadata`, `d_created`, `d_updated`, `d_deleted`) VALUES
	(1, 1, 1, 'Great!', NULL, '2026-01-11 20:32:44', '2026-01-11 20:32:44', NULL);
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;

-- Dumping structure for table testorm.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_ref` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','shipped') DEFAULT 'pending',
  `d_created` datetime DEFAULT current_timestamp(),
  `d_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `d_deleted` datetime DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_ref` (`order_ref`),
  KEY `fk_order_user` (`user_id`)
) ENGINE=Aria AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table testorm.orders: 2 rows
DELETE FROM `orders`;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` (`id`, `user_id`, `order_ref`, `total_amount`, `status`, `d_created`, `d_updated`, `d_deleted`, `category_id`) VALUES
	(1, 1, 'REF1', 1000.00, 'pending', '2026-01-11 20:32:44', '2026-01-11 20:34:13', NULL, 1),
	(3, 3, 'tt123', 500.00, 'pending', '2026-01-11 15:41:47', '2026-01-11 15:41:47', NULL, NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;

-- Dumping structure for table testorm.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table testorm.order_items: 1 rows
DELETE FROM `order_items`;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
	(1, 1, 1, 1, 1000.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;

-- Dumping structure for table testorm.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT NULL,
  `d_created` datetime DEFAULT current_timestamp(),
  `d_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `d_deleted` datetime DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table testorm.products: 1 rows
DELETE FROM `products`;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` (`id`, `name`, `price`, `stock`, `category`, `d_created`, `d_updated`, `d_deleted`, `category_id`) VALUES
	(1, 'Laptop', 1000.00, 0, 'Tech', '2026-01-11 20:32:44', '2026-01-11 15:58:53', NULL, 1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;

-- Dumping structure for table testorm.site_analytics
CREATE TABLE IF NOT EXISTS `site_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_path` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_type` enum('mobile','desktop','tablet') DEFAULT NULL,
  `d_created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table testorm.site_analytics: ~4 rows (approximately)
DELETE FROM `site_analytics`;
INSERT INTO `site_analytics` (`id`, `page_path`, `user_id`, `session_id`, `ip_address`, `device_type`, `d_created`) VALUES
	(1, '/home', 1, NULL, NULL, 'desktop', '2026-01-10 10:00:00'),
	(2, '/products', 1, NULL, NULL, 'desktop', '2026-01-10 10:05:00'),
	(3, '/home', 2, NULL, NULL, 'mobile', '2026-01-11 11:00:00'),
	(4, '/cart', 2, NULL, NULL, 'mobile', '2026-01-11 11:02:00');

-- Dumping structure for table testorm.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `d_created` datetime DEFAULT current_timestamp(),
  `d_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `d_deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=Aria AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci PAGE_CHECKSUM=1;

-- Dumping data for table testorm.users: 3 rows
DELETE FROM `users`;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `settings`, `d_created`, `d_updated`, `d_deleted`) VALUES
	(1, 'Rakesh', 'rakesh@test.com', NULL, '2026-01-11 21:43:53', '2026-01-11 21:43:53', NULL),
	(2, 'Test', 'test@test.com', NULL, '2026-01-11 15:58:53', '2026-01-11 15:58:53', NULL),
	(3, 'Transaction Test User', 'test@example.com', NULL, '2026-01-11 15:58:53', '2026-01-11 15:58:53', NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
