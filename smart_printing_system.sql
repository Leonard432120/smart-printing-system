-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 01, 2025 at 09:10 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_printing_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `attended_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) NOT NULL DEFAULT 'Absent',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trails`
--

DROP TABLE IF EXISTS `audit_trails`;
CREATE TABLE IF NOT EXISTS `audit_trails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_trails`
--

INSERT INTO `audit_trails` (`id`, `user_id`, `user_name`, `action`, `description`, `created_at`) VALUES
(1, 2, '', 'Update Print Status', 'Changed status of transaction #1 to Completed', '2025-07-18 15:43:29'),
(2, 2, '', 'Update Print Status', 'Changed status of transaction #1 to Completed', '2025-07-18 15:44:19'),
(3, 2, '', 'Update Print Status', 'Changed status of transaction #1 to Completed', '2025-07-18 15:46:59'),
(4, 2, '', 'Update Print Status', 'Changed status of transaction #3 to Rejected', '2025-07-18 16:26:10'),
(5, 2, '', 'Update Print Status', 'Changed status of transaction #8 to Completed', '2025-07-19 08:32:06'),
(6, 2, '', 'Update Print Status', 'Changed status of transaction #12 to Completed', '2025-07-21 09:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `level_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `numeric_value` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `level_id` (`level_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `level_id`, `name`, `numeric_value`) VALUES
(1, 1, 'Standard 1', 1),
(2, 1, 'Standard 2', 2),
(3, 1, 'Standard 3', 3),
(4, 1, 'Standard 4', 4),
(5, 1, 'Standard 5', 5),
(6, 1, 'Standard 6', 6),
(7, 1, 'Standard 7', 7),
(8, 1, 'Standard 8', 8),
(9, 2, 'Form 1', 1),
(10, 2, 'Form 2', 2),
(11, 2, 'Form 3', 3),
(12, 2, 'Form 4', 4);

-- --------------------------------------------------------

--
-- Table structure for table `class_chat`
--

DROP TABLE IF EXISTS `class_chat`;
CREATE TABLE IF NOT EXISTS `class_chat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scheduled_class_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `scheduled_class_id` (`scheduled_class_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_discussions`
--

DROP TABLE IF EXISTS `class_discussions`;
CREATE TABLE IF NOT EXISTS `class_discussions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `posted_at` datetime NOT NULL,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `class_discussions`
--

INSERT INTO `class_discussions` (`id`, `class_id`, `user_id`, `message`, `posted_at`, `parent_id`) VALUES
(1, 17, 2, 'hy', '2025-07-18 11:12:44', NULL),
(2, 19, 1, 'HY', '2025-07-18 11:19:49', NULL),
(3, 19, 3, 'hello ponje', '2025-07-18 14:18:49', NULL),
(4, 19, 1, 'yes boss', '2025-07-18 14:19:46', NULL),
(5, 19, 3, 'what', '2025-07-18 14:44:42', 2),
(6, 19, 3, 'what', '2025-07-18 14:46:53', 2),
(7, 19, 3, 'yaaaa', '2025-07-18 14:47:31', 5),
(8, 19, 3, 'kkkkk', '2025-07-18 14:47:51', 5),
(9, 19, 3, 'yes', '2025-07-18 15:15:10', 5),
(10, 19, 3, 'yyy', '2025-07-18 15:15:36', 3),
(11, 19, 3, 'yes karonga', '2025-07-18 15:16:07', 4),
(12, 19, 3, 'what wuli fada', '2025-07-18 15:16:21', 11),
(13, 19, 3, 'eya', '2025-07-18 15:33:39', 5),
(14, 17, 2, 'yes ponje', '2025-07-18 15:41:05', NULL),
(15, 17, 2, 'yes ponje', '2025-07-18 15:43:25', NULL),
(16, 17, 2, 'umat chan ponje', '2025-07-18 15:51:59', 1),
(17, 20, 2, 'hy sir', '2025-07-21 11:20:54', 0),
(18, 20, 1, 'yes', '2025-07-21 11:22:01', 0),
(19, 20, 2, 'yrrrrrrrrrrrrrrrrrrrrrrrr', '2025-07-21 11:23:02', 18);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `user_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `progress` int DEFAULT '0',
  `completion_status` varchar(50) DEFAULT 'Not Started',
  `enrolled_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(100) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `full_name`, `user_id`, `lesson_id`, `status`, `progress`, `completion_status`, `enrolled_at`, `email`, `payment_status`) VALUES
(8, 'leonardmlungupro', 0, 1, 'pending', 0, 'Not Started', '2025-07-18 14:17:02', 'leonardmlungupro@gmail.com', 'Paid'),
(9, 'ict-01-25-22', 0, 1, 'pending', 0, 'Not Started', '2025-07-18 15:31:37', 'ict-01-25-22@unilia.ac.mw', 'Paid'),
(10, NULL, 0, 1, 'Paid', 0, 'Not Started', '2025-07-18 16:02:21', 'leonardponjemlungu@gmail.com', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `lands`
--

DROP TABLE IF EXISTS `lands`;
CREATE TABLE IF NOT EXISTS `lands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `location` varchar(255) NOT NULL,
  `size` decimal(10,2) NOT NULL COMMENT 'in acres',
  `price` decimal(15,2) NOT NULL,
  `status` enum('available','sold','reserved') DEFAULT 'available',
  `featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lands`
--

INSERT INTO `lands` (`id`, `title`, `description`, `location`, `size`, `price`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(1, 'Pusi', 'tiye tigure', 'Lilongwe', 30.00, 3000.00, 'available', 1, '2025-07-31 13:39:56', '2025-07-31 13:39:56'),
(2, 'Pusi', 'tiye tigure', 'Lilongwe', 30.00, 3000.00, 'available', 1, '2025-07-31 13:40:27', '2025-07-31 13:40:27'),
(3, 'PUSI AGAIN', 'JERJT', 'Lilongwe', 89000.00, 7000.00, 'available', 1, '2025-07-31 13:41:34', '2025-07-31 13:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `land_features`
--

DROP TABLE IF EXISTS `land_features`;
CREATE TABLE IF NOT EXISTS `land_features` (
  `id` int NOT NULL AUTO_INCREMENT,
  `land_id` int NOT NULL,
  `feature` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `land_id` (`land_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `land_features`
--

INSERT INTO `land_features` (`id`, `land_id`, `feature`) VALUES
(1, 3, 'PUNGA');

-- --------------------------------------------------------

--
-- Table structure for table `land_images`
--

DROP TABLE IF EXISTS `land_images`;
CREATE TABLE IF NOT EXISTS `land_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `land_id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `land_id` (`land_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `land_images`
--

INSERT INTO `land_images` (`id`, `land_id`, `image_path`, `is_primary`) VALUES
(1, 1, '1753969196_WhatsApp Image 2025-07-20 at 5.17.49 PM.jpeg', 1),
(2, 2, '1753969227_WhatsApp Image 2025-07-20 at 5.17.49 PM.jpeg', 1),
(3, 3, '1753969294_WhatsApp Image 2025-07-20 at 5.17.50 PM.jpeg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `land_requests`
--

DROP TABLE IF EXISTS `land_requests`;
CREATE TABLE IF NOT EXISTS `land_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `land_id` int NOT NULL,
  `user_id` int NOT NULL,
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `message` text,
  PRIMARY KEY (`id`),
  KEY `land_id` (`land_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

DROP TABLE IF EXISTS `lessons`;
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `duration` varchar(50) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `duration_weeks` int DEFAULT '0',
  `fee_type` varchar(20) DEFAULT 'paid',
  `image` varchar(255) DEFAULT 'default.jpg',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `title`, `description`, `duration`, `fee`, `instructor`, `schedule`, `created_at`, `duration_weeks`, `fee_type`, `image`) VALUES
(1, 'COMPUTER BASICS', 'It\'s all for the beginner\'s who are willing to families themselves with some computer basics', NULL, 2000.00, 'Mungu Ni Dawa', 'Monday & Tuesday (10:00-12:00)', '2025-07-15 09:12:56', 5, 'Paid', 'default.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_materials`
--

DROP TABLE IF EXISTS `lesson_materials`;
CREATE TABLE IF NOT EXISTS `lesson_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_id` int NOT NULL,
  `type` enum('pdf','video','slide') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `thumbnail` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lesson_materials`
--

INSERT INTO `lesson_materials` (`id`, `lesson_id`, `type`, `title`, `description`, `file_path`, `uploaded_at`, `thumbnail`) VALUES
(9, 1, 'pdf', 'hy', 'hjki', '1753089459_Network_Topologies.pdf', '2025-07-21 09:17:40', 'assets/images/thumb_1753089459.jpeg'),
(8, 1, 'pdf', 'NETWORKS II', 'This is just the continuation of the previous course', '1752735325_Network Topologies.pdf', '2025-07-17 06:55:25', 'assets/images/pdf_icon.png'),
(7, 1, 'pdf', 'NETWORK TOPOLOGY', 'why this is useful', '1752734645_Network Topologies.pdf', '2025-07-17 06:44:05', 'assets/images/thumb_1752734645.png');

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

DROP TABLE IF EXISTS `levels`;
CREATE TABLE IF NOT EXISTS `levels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`id`, `name`, `description`) VALUES
(1, 'Primary', 'Standard 1 through Standard 8'),
(2, 'Secondary', 'Form 1 through Form 4'),
(3, 'Tertiary', 'Semester 1 through Semester 8');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT NULL,
  `recipient_email` varchar(100) DEFAULT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `recipient_email`, `message`, `is_read`, `created_at`, `user_id`) VALUES
(1, 'Print Completion', NULL, 'Your document (Ref: docjerjjw) is ready for pickup', 0, '2025-07-18 15:39:11', 2),
(2, 'Print Completion', NULL, 'Your document (Ref: docjerjjw) is ready for pickup', 0, '2025-07-18 15:43:29', 2),
(3, 'Print Completion', NULL, 'Your document (Ref: docjerjjw) is ready for pickup', 0, '2025-07-18 15:44:19', 2),
(4, 'Print Completion', NULL, 'Your document (Ref: docjerjjw) is ready for pickup', 0, '2025-07-21 09:24:34', 2);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `payment_status` varchar(20) DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `full_name`, `service_id`, `document_path`, `status`, `payment_status`, `created_at`, `notes`, `email`, `phone`) VALUES
(7, 1, 'Leonardponje mlungu', 5, NULL, 'pending', 'unpaid', '2025-07-15 23:50:18', 'ponjbbb', 'ict-01-22-22@unilia.ac.mw', '+265984487611'),
(5, 1, 'Leonardponje mlungu', 1, NULL, 'pending', 'unpaid', '2025-07-15 23:42:56', 'ponje', 'ict-01-22-22@unilia.ac.mw', '+265984487611'),
(6, 1, 'Leonardponje mlungu', 1, NULL, 'pending', 'unpaid', '2025-07-15 23:46:31', 'ponje', 'ict-01-22-22@unilia.ac.mw', '+265984487611'),
(8, 1, 'Leonardponje mlungu', 4, NULL, 'pending', 'unpaid', '2025-07-16 00:00:39', 'ujn', 'ict-01-22-22@unilia.ac.mw', '+265984487611'),
(9, 1, 'Leonardponje mlungu', 4, NULL, 'pending', 'unpaid', '2025-07-16 00:03:43', 'ujn', 'ict-01-22-22@unilia.ac.mw', '+265984487611'),
(10, 1, 'Leonardponje mlungu', 4, NULL, 'Processing', 'unpaid', '2025-07-16 00:23:16', 'ujn', 'ict-01-22-22@unilia.ac.mw', '+265984487611'),
(11, 2, 'Leonardponje mlungu', 4, NULL, 'pending', 'unpaid', '2025-07-21 09:28:04', '4r', 'leonardponjemlungu@gmail.com', '0984487611');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `lesson_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `confirmed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=MyISAM AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `transaction_id`, `email`, `transaction_reference`, `user_id`, `order_id`, `lesson_id`, `amount`, `status`, `payment_method`, `paid_at`, `payment_date`, `confirmed`) VALUES
(79, 'TX_687e0f0b60df8_1753091851', 'leonardponjemlungu@gmail.com', NULL, NULL, 17, NULL, 4200.00, 'pending', NULL, '2025-07-21 09:57:31', '2025-07-21 11:57:31', 0),
(78, 'TX_687e0e1a9ac76_1753091610', 'leonardponjemlungu@gmail.com', NULL, NULL, 16, NULL, 4200.00, 'pending', NULL, '2025-07-21 09:53:30', '2025-07-21 11:53:30', 0),
(77, 'TX_687e0e05b1981_1753091589', 'leonardponjemlungu@gmail.com', NULL, NULL, 15, NULL, 700.00, 'pending', NULL, '2025-07-21 09:53:09', '2025-07-21 11:53:09', 0),
(80, '18', 'leonardponjemlungu@gmail.com', 'DOC_687e400879264_1753104392', NULL, NULL, NULL, 700.00, 'pending', NULL, '2025-07-21 13:26:32', '2025-07-21 15:26:32', 0),
(81, '19', 'leonardponjemlungu@gmail.com', 'DOC_687e4466af215_1753105510', NULL, NULL, NULL, 700.00, 'pending', NULL, '2025-07-21 13:45:10', '2025-07-21 15:45:10', 0),
(82, '19', 'leonardponjemlungu@gmail.com', 'DOC_687e458b60c65_1753105803', NULL, NULL, NULL, 700.00, 'pending', NULL, '2025-07-21 13:50:03', '2025-07-21 15:50:03', 0),
(83, '19', 'leonardponjemlungu@gmail.com', 'DOC_687e45cc419ab_1753105868', NULL, NULL, NULL, 700.00, 'success', NULL, '2025-07-21 14:05:32', '2025-07-21 14:05:32', 1);

-- --------------------------------------------------------

--
-- Table structure for table `prices`
--

DROP TABLE IF EXISTS `prices`;
CREATE TABLE IF NOT EXISTS `prices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `page_size` varchar(10) DEFAULT NULL,
  `color_type` varchar(50) DEFAULT NULL,
  `color_option` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prices`
--

INSERT INTO `prices` (`id`, `category_id`, `page_size`, `color_type`, `color_option`, `price`) VALUES
(1, NULL, 'A4', 'Black & White', NULL, 85.00),
(2, NULL, 'A4', 'Black & White', NULL, 85.00),
(3, NULL, 'A5', 'Black & White', NULL, 700.00);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_id` int NOT NULL,
  `question` text NOT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_option` char(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
CREATE TABLE IF NOT EXISTS `resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `level_id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `semester_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `uploaded_by` int NOT NULL,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `level_id` (`level_id`),
  KEY `class_id` (`class_id`),
  KEY `semester_id` (`semester_id`),
  KEY `subject_id` (`subject_id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `title`, `description`, `file_path`, `original_filename`, `file_size`, `file_type`, `level_id`, `class_id`, `semester_id`, `subject_id`, `uploaded_by`, `upload_date`, `is_active`) VALUES
(1, 'hhhh', 'hhh', '../uploads/library/687e9c2271401_NetworkTopologies.pdf', 'Network Topologies.pdf', 90493, 'pdf', 1, 3, NULL, NULL, 2, '2025-07-21 21:59:30', 1),
(2, 'askjaskj', 'saklwsa', '../uploads/library/687ea30c187ec_NetworkTopologies.pdf', 'Network Topologies.pdf', 90493, 'pdf', 3, NULL, 8, NULL, 2, '2025-07-21 22:29:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_classes`
--

DROP TABLE IF EXISTS `scheduled_classes`;
CREATE TABLE IF NOT EXISTS `scheduled_classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_id` int NOT NULL,
  `topic` varchar(255) NOT NULL,
  `class_date` datetime NOT NULL,
  `class_link` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `scheduled_classes`
--

INSERT INTO `scheduled_classes` (`id`, `lesson_id`, `topic`, `class_date`, `class_link`, `created_at`) VALUES
(20, 1, 'Introduction to PHP', '2025-07-21 11:31:00', 'https://meet.google.com/zen-cibt-vav', '2025-07-21 09:20:25');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
CREATE TABLE IF NOT EXISTS `semesters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `level_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `numeric_value` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `level_id` (`level_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `level_id`, `name`, `numeric_value`) VALUES
(1, 3, 'Semester 1', 1),
(2, 3, 'Semester 2', 2),
(3, 3, 'Semester 3', 3),
(4, 3, 'Semester 4', 4),
(5, 3, 'Semester 5', 5),
(6, 3, 'Semester 6', 6),
(7, 3, 'Semester 7', 7),
(8, 3, 'Semester 8', 8);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `image`, `category`, `description`, `created_at`, `price`) VALUES
(1, 'Photocopying and printing', 'assets/images/1752583532_logo.png', NULL, NULL, '2025-07-15 12:45:32', 4500.00),
(4, 'Logo Creation', 'assets/images/1752583779_ponje.png', NULL, NULL, '2025-07-15 12:49:39', 6000.00),
(5, 'Computer Lessons', 'assets/images/1752583835_ponje.jpg', NULL, NULL, '2025-07-15 12:50:35', 800.00);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `business_name` varchar(255) NOT NULL DEFAULT 'Smart Printing System',
  `logo_path` varchar(255) DEFAULT 'assets/images/MND.jpeg',
  `contact_email` varchar(100) DEFAULT 'admin@example.com',
  `phone` varchar(50) DEFAULT NULL,
  `address` text,
  `paychangu_api_key` text,
  `mysql_user` varchar(50) DEFAULT 'root',
  `mysql_password` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `site_timezone` varchar(100) DEFAULT 'Africa/Blantyre',
  `footer_text` varchar(255) DEFAULT '© 2025 Mungu Ni Dawa',
  `maintenance_mode` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `business_name`, `logo_path`, `contact_email`, `phone`, `address`, `paychangu_api_key`, `mysql_user`, `mysql_password`, `created_at`, `updated_at`, `site_timezone`, `footer_text`, `maintenance_mode`) VALUES
(1, 'Smart Printing', '/smart-printing-system/assets/images/logo_1752652145_logo_1752216871_MND.jpeg', 'ict-01-25-22@unilia.ac.mw', '', '', NULL, 'root', NULL, '2025-07-16 07:48:03', '2025-07-16 07:49:05', 'Africa/Blantyre', '© 2025 Smart Printing System', 0);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_contact` varchar(20) DEFAULT NULL,
  `reference_code` varchar(100) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `copies` int DEFAULT NULL,
  `color_type` varchar(50) DEFAULT NULL,
  `page_size` varchar(10) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `status` enum('Pending','Processing','Completed','Failed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `customer_name`, `customer_contact`, `reference_code`, `file_name`, `original_filename`, `copies`, `color_type`, `page_size`, `total_amount`, `service_id`, `amount`, `status`, `created_at`) VALUES
(12, 2, 'Leonardponje mlungu', '+265984487611', 'docjerjjw', '/smart-printing-system/uploads/notes/print_687b609fed2d0_Network_Topologies.pdf', 'Network Topologies.pdf', 1, 'Black & White', '0', 85.00, NULL, 85.00, 'Completed', '2025-07-19 09:08:47'),
(13, 2, 'Leonardponje mlungu', '+265984487611', '88888', '/smart-printing-system/uploads/notes/print_687e07e098b43_Network_Topologies.pdf', 'Network Topologies.pdf', 1, 'Black & White', '0', 700.00, NULL, 700.00, '', '2025-07-21 09:26:56'),
(14, 2, 'Leonardponje mlungu', '+265984487611', 'today', '/smart-printing-system/uploads/notes/print_687e0da66c73c_Network_Topologies.pdf', 'Network Topologies.pdf', 1, 'Black & White', '0', 700.00, NULL, 700.00, '', '2025-07-21 09:51:34'),
(15, 2, 'Leonardponje mlungu', '+265984487611', 'today', '/smart-printing-system/uploads/notes/print_687e0e059a82f_Network_Topologies.pdf', 'Network Topologies.pdf', 1, 'Black & White', '0', 700.00, NULL, 700.00, '', '2025-07-21 09:53:09'),
(16, 2, 'Leonardponje mlungu', '+265984487611', 'today', '/smart-printing-system/uploads/notes/print_687e0e1a95790_Network_Topologies.pdf', 'Network Topologies.pdf', 6, 'Black & White', '0', 4200.00, NULL, 4200.00, '', '2025-07-21 09:53:30'),
(17, 2, 'Leonardponje mlungu', '+265984487611', 'today', '/smart-printing-system/uploads/notes/print_687e0f0b5b59b_Network_Topologies.pdf', 'Network Topologies.pdf', 6, 'Black & White', '0', 4200.00, NULL, 4200.00, '', '2025-07-21 09:57:31'),
(18, 2, 'Leonardponje mlungu', '+265984487611', 'docjerjjw', '/smart-printing-system/uploads/notes/print_687e3efae3af5_Network_Topologies.pdf', 'Network Topologies.pdf', 1, 'Black & White', '0', 700.00, NULL, 700.00, '', '2025-07-21 13:22:03'),
(19, 2, 'Leonardponje mlungu', '+265984487611', 'docjerjjw', '/smart-printing-system/uploads/notes/print_687e44667d0d0_Network_Topologies.pdf', 'Network Topologies.pdf', 1, 'Black & White', '0', 700.00, NULL, 700.00, '', '2025-07-21 13:45:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `education_level` int DEFAULT NULL COMMENT '1=Primary, 2=Secondary, 3=Tertiary',
  `class_id` int DEFAULT NULL,
  `semester_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `phone`, `password`, `role`, `created_at`, `education_level`, `class_id`, `semester_id`) VALUES
(1, 'Leonardponje mlungu', 'leonard123', 'ict-01-25-22@unilia.ac.mw', '+265984487611', '$2y$10$i/GWl7hQZmmkS9C2582C7u7YXRwMrU7o6xs5ImAOpDlbOUJ3peRjy', 'user', '2025-07-15 07:04:42', 3, 0, 8),
(2, 'Leonardponje mlungu', 'Admin12', 'leonardponjemlungu@gmail.com', '+265984487611', '$2y$10$NOqHvRwtyTVOEOtlTyVn..66.tVMBp0SpBjekYpZZ5AxYPpbpxm8K', 'admin', '2025-07-15 09:01:32', 3, 0, 8),
(3, 'PONJE JOHN', 'leonard1234', 'leonardmlungupro@gmail.com', '+265984487611', '$2y$10$PQ5sn6x3iiRrNCV0BmH5/.a8TUk1X6i8vZtQvO8LshnyfsryZtdAa', 'user', '2025-07-18 09:22:38', 2, 9, 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
