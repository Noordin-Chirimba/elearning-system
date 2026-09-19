-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 10, 2024 at 05:10 PM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-learningsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`) VALUES
(3, 'Admin1@gmail.com', '$2y$10$MnouDId2Ip3QLfW74F/kvOtxx8q06NzQWNM2VfdAOeGd81RpNpo7i'),
(2, 'Noordin@gmail.com', '$2y$10$gfowjWEX5.3fMetXdb3CIeoHfmfM1fbUT83kSWBTIwZgN7yM86.6y');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`, `description`, `price`) VALUES
(1, 'Course 1', 'Description for Course 1', '100.00'),
(2, 'Course 2', 'Description for Course 2', '150.00'),
(3, 'Course 3', 'Description for Course 3', '200.00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Noordin', 'Noordin@gmail.com', '$2y$10$LTrPF4EkzprpSMg4CA1znOw0LbaH7mFsP9cw0sJz2Zci.ZkIaEbXa', '2024-06-28 19:24:11'),
(2, 'hanleck', 'han@gmail.com', '$2y$10$5BLJ3T.4mfpQw/sfq/t.MOeb/Lo4Mg5Qr33u14z.z/TfNE5bT9GQK', '2024-06-28 19:29:46'),
(3, 'Noordin', 'Noordin@gmail.com', '$2y$10$YdJC/uRqR3dxvBH.QSaVm.VO4v9XGkk8/UO0/FYwj.lO168nd1Tim', '2024-07-05 20:59:36'),
(4, 'john', 'john@gmail.com', '$2y$10$77TnBN7CFVblQbEO53vnO.BvGH2hqJDfJr7S.3w7v1WKoE8PDNPl2', '2024-07-05 21:02:18'),
(5, 'Noordin', 'Noordin@gmail.com', '$2y$10$kP2xljqoiO/.9KEdt2fN9.TxWG3cXO5UNoB7ZeXlXbqYC44tTRIGG', '2024-07-08 08:54:56'),
(6, 'Noordin', 'Noordin@gmail.com', '$2y$10$t805bTqQABWqvrBKDn98cOCFIPm6FXSSK/mlrmev/nAwltR7444ju', '2024-07-08 08:55:34'),
(7, 'Noordin', 'Noordin@gmail.com', '$2y$10$llri9/1cKCIeTMlIAlxahOQFrwumalUoZpFa7CByUckso9qCHY9wq', '2024-07-08 08:57:26'),
(8, 'Noor', 'Noordin@gmail.com', '$2y$10$RqY8I0b89gyIKL6tqK1dQeoSYu5FKZigo2e2TCWdgTNXh5Dwwnxou', '2024-07-08 09:04:57'),
(9, 'Nonax', 'Nonax@gmail.com', '$2y$10$txkwoM9ZeliNJnfL0KpsSuFNBu.ZD2BmCWz92U.YA12U9qy5AAApq', '2024-07-08 11:26:40'),
(38, 'Noordin Chirimba', 'haroonchirimba2@gmail.com', '$2y$10$0WA.1mlueQ6eTn8WXMK9fu.vNo8PpO5gwAYpR/8FHggELkOy6L24e', '2024-07-09 07:12:13'),
(39, 'imran Tawakali', 'imranttawakali@gmail.com', '$2y$10$eo7F.TUv8XJ/6V5uXbibZO8TgJqX/6tTJhXAUq2RhFcvrE5H2kANa', '2024-07-09 15:15:44');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
