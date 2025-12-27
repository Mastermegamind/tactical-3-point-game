-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 26, 2025 at 09:25 PM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `game`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_training_data`
--

CREATE TABLE `ai_training_data` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `game_outcome` enum('player_win','ai_win','draw') NOT NULL,
  `difficulty_level` enum('easy','medium','hard') NOT NULL,
  `total_moves` int(11) NOT NULL,
  `winning_pattern` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`winning_pattern`)),
  `game_duration_seconds` int(11) DEFAULT NULL,
  `player_rating` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_training_data`
--

INSERT INTO `ai_training_data` (`id`, `session_id`, `game_outcome`, `difficulty_level`, `total_moves`, `winning_pattern`, `game_duration_seconds`, `player_rating`, `created_at`) VALUES
(1, 1, 'player_win', 'hard', 11, NULL, -3564, 1025, '2025-12-26 11:29:41'),
(2, 2, 'ai_win', 'hard', 12, NULL, -3558, 1015, '2025-12-26 11:54:11'),
(3, 4, 'player_win', 'hard', 13, NULL, -3539, 1040, '2025-12-26 11:59:27'),
(4, 5, 'player_win', 'hard', 15, NULL, -3540, 1065, '2025-12-26 12:01:44'),
(5, 6, 'player_win', 'hard', 7, NULL, -3575, 1090, '2025-12-26 12:02:26'),
(6, 7, 'ai_win', 'hard', 12, NULL, -3555, 1080, '2025-12-26 12:03:45'),
(7, 8, 'ai_win', 'hard', 26, NULL, -3514, 1070, '2025-12-26 12:05:27'),
(8, 9, 'player_win', 'hard', 13, NULL, -3562, 1095, '2025-12-26 12:06:13'),
(9, 10, 'player_win', 'hard', 13, NULL, -3574, 1120, '2025-12-26 12:06:52'),
(10, 11, 'player_win', 'hard', 13, NULL, -3548, 1145, '2025-12-26 12:08:15'),
(11, 13, 'ai_win', 'hard', 8, NULL, -3550, 1135, '2025-12-26 12:20:39'),
(12, 14, 'ai_win', 'hard', 8, NULL, -3555, 1125, '2025-12-26 12:23:30'),
(13, 15, 'player_win', 'hard', 11, NULL, -3564, 1150, '2025-12-26 12:24:26'),
(14, 18, 'player_win', 'hard', 11, NULL, -3562, 1175, '2025-12-26 16:50:12'),
(15, 19, 'player_win', 'hard', 13, NULL, -3552, 1200, '2025-12-26 16:51:12'),
(16, 20, 'player_win', 'hard', 19, NULL, -3499, 1225, '2025-12-26 17:04:43'),
(17, 21, 'player_win', 'hard', 13, NULL, -3560, 1250, '2025-12-26 17:09:52'),
(18, 23, 'player_win', 'easy', 5, NULL, -3592, 1025, '2025-12-26 17:17:00'),
(19, 24, 'ai_win', 'hard', 10, NULL, -3472, 1015, '2025-12-26 17:20:32'),
(20, 25, 'ai_win', 'hard', 54, NULL, -3397, 1005, '2025-12-26 17:24:39'),
(21, 26, 'ai_win', 'hard', 8, NULL, -3584, 1240, '2025-12-26 17:26:02'),
(22, 27, 'ai_win', 'medium', 6, NULL, -3568, 990, '2025-12-26 17:49:52'),
(23, 28, 'player_win', 'easy', 5, NULL, -3592, 1025, '2025-12-26 18:58:33'),
(24, 29, 'ai_win', 'hard', 6, NULL, -3592, 1015, '2025-12-26 18:58:53'),
(25, 30, 'ai_win', 'hard', 14, NULL, -3543, 1005, '2025-12-26 19:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `game_moves`
--

CREATE TABLE `game_moves` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `move_number` int(11) NOT NULL,
  `player` enum('X','O') NOT NULL,
  `move_type` enum('placement','movement') NOT NULL,
  `from_position` int(11) DEFAULT NULL,
  `to_position` int(11) NOT NULL,
  `board_state_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`board_state_before`)),
  `board_state_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`board_state_after`)),
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `think_time_ms` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_moves`
--

INSERT INTO `game_moves` (`id`, `session_id`, `move_number`, `player`, `move_type`, `from_position`, `to_position`, `board_state_before`, `board_state_after`, `timestamp`, `think_time_ms`) VALUES
(1, 1, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 11:29:13', 0),
(2, 1, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 11:29:14', 0),
(3, 1, 3, 'X', 'placement', NULL, 1, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '2025-12-26 11:29:16', 0),
(4, 1, 4, 'O', 'placement', NULL, 7, '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '2025-12-26 11:29:17', 0),
(5, 1, 5, 'X', 'placement', NULL, 3, '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '[\"O\",\"X\",null,\"X\",\"X\",null,null,\"O\",null]', '2025-12-26 11:29:22', 0),
(6, 1, 6, 'O', 'placement', NULL, 5, '[\"O\",\"X\",null,\"X\",\"X\",null,null,\"O\",null]', '[\"O\",\"X\",null,\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 11:29:23', 0),
(7, 1, 7, 'X', 'movement', 3, 6, '[\"O\",\"X\",null,\"X\",\"X\",\"O\",null,\"O\",null]', '[\"O\",\"X\",null,null,\"X\",\"O\",\"X\",\"O\",null]', '2025-12-26 11:29:30', 1302),
(8, 1, 8, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"X\",\"O\",\"X\",\"O\",null]', '[null,\"X\",\"O\",null,\"X\",\"O\",\"X\",\"O\",null]', '2025-12-26 11:29:31', 1924),
(9, 1, 9, 'X', 'movement', 1, 8, '[null,\"X\",\"O\",null,\"X\",\"O\",\"X\",\"O\",null]', '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 11:29:37', 2126),
(10, 1, 10, 'O', 'movement', 2, 0, '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 11:29:37', 2709),
(11, 1, 11, 'X', 'movement', 8, 2, '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[\"O\",null,\"X\",null,\"X\",\"O\",\"X\",\"O\",null]', '2025-12-26 11:29:41', 1806),
(12, 2, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 11:53:36', 0),
(13, 2, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 11:53:37', 0),
(14, 2, 3, 'X', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,\"X\",null,\"X\",null,null,null,null]', '2025-12-26 11:53:39', 0),
(15, 2, 4, 'O', 'placement', NULL, 6, '[\"O\",null,\"X\",null,\"X\",null,null,null,null]', '[\"O\",null,\"X\",null,\"X\",null,\"O\",null,null]', '2025-12-26 11:53:40', 0),
(16, 2, 5, 'X', 'placement', NULL, 3, '[\"O\",null,\"X\",null,\"X\",null,\"O\",null,null]', '[\"O\",null,\"X\",\"X\",\"X\",null,\"O\",null,null]', '2025-12-26 11:53:43', 0),
(17, 2, 6, 'O', 'placement', NULL, 5, '[\"O\",null,\"X\",\"X\",\"X\",null,\"O\",null,null]', '[\"O\",null,\"X\",\"X\",\"X\",\"O\",\"O\",null,null]', '2025-12-26 11:53:44', 0),
(18, 2, 7, 'X', 'movement', 2, 7, '[\"O\",null,\"X\",\"X\",\"X\",\"O\",\"O\",null,null]', '[\"O\",null,null,\"X\",\"X\",\"O\",\"O\",\"X\",null]', '2025-12-26 11:53:49', 863),
(19, 2, 8, 'O', 'movement', 0, 1, '[\"O\",null,null,\"X\",\"X\",\"O\",\"O\",\"X\",null]', '[null,\"O\",null,\"X\",\"X\",\"O\",\"O\",\"X\",null]', '2025-12-26 11:53:50', 1882),
(20, 2, 9, 'X', 'movement', 3, 0, '[null,\"O\",null,\"X\",\"X\",\"O\",\"O\",\"X\",null]', '[\"X\",\"O\",null,null,\"X\",\"O\",\"O\",\"X\",null]', '2025-12-26 11:54:00', 549),
(21, 2, 10, 'O', 'movement', 1, 8, '[\"X\",\"O\",null,null,\"X\",\"O\",\"O\",\"X\",null]', '[\"X\",null,null,null,\"X\",\"O\",\"O\",\"X\",\"O\"]', '2025-12-26 11:54:01', 2543),
(22, 2, 11, 'X', 'movement', 7, 2, '[\"X\",null,null,null,\"X\",\"O\",\"O\",\"X\",\"O\"]', '[\"X\",null,\"X\",null,\"X\",\"O\",\"O\",null,\"O\"]', '2025-12-26 11:54:10', 3308),
(23, 2, 12, 'O', 'movement', 5, 7, '[\"X\",null,\"X\",null,\"X\",\"O\",\"O\",null,\"O\"]', '[\"X\",null,\"X\",null,\"X\",null,\"O\",\"O\",\"O\"]', '2025-12-26 11:54:11', 4374),
(24, 3, 1, 'X', 'placement', NULL, 0, '[null,null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,null,null,null,null,null]', '2025-12-26 11:55:26', 0),
(25, 3, 2, 'O', 'placement', NULL, 4, '[\"X\",null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,\"O\",null,null,null,null]', '2025-12-26 11:55:27', 0),
(26, 3, 3, 'X', 'placement', NULL, 6, '[\"X\",null,null,null,\"O\",null,null,null,null]', '[\"X\",null,null,null,\"O\",null,\"X\",null,null]', '2025-12-26 11:55:30', 0),
(27, 3, 4, 'O', 'placement', NULL, 3, '[\"X\",null,null,null,\"O\",null,\"X\",null,null]', '[\"X\",null,null,\"O\",\"O\",null,\"X\",null,null]', '2025-12-26 11:55:31', 0),
(28, 3, 5, 'X', 'placement', NULL, 5, '[\"X\",null,null,\"O\",\"O\",null,\"X\",null,null]', '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,null]', '2025-12-26 11:55:33', 0),
(29, 3, 6, 'O', 'placement', NULL, 2, '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,null]', '[\"X\",null,\"O\",\"O\",\"O\",\"X\",\"X\",null,null]', '2025-12-26 11:55:34', 0),
(30, 3, 7, 'X', 'movement', 0, 8, '[\"X\",null,\"O\",\"O\",\"O\",\"X\",\"X\",null,null]', '[null,null,\"O\",\"O\",\"O\",\"X\",\"X\",null,\"X\"]', '2025-12-26 11:55:41', 1882),
(31, 3, 8, 'O', 'movement', 2, 7, '[null,null,\"O\",\"O\",\"O\",\"X\",\"X\",null,\"X\"]', '[null,null,null,\"O\",\"O\",\"X\",\"X\",\"O\",\"X\"]', '2025-12-26 11:55:42', 3615),
(32, 3, 9, 'X', 'movement', 8, 1, '[null,null,null,\"O\",\"O\",\"X\",\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:55:48', 795),
(33, 3, 10, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:55:49', 1851),
(34, 3, 11, 'X', 'movement', 5, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:55:56', 375),
(35, 3, 12, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:55:57', 697),
(36, 3, 13, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:01', 391),
(37, 3, 14, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:02', 1522),
(38, 3, 15, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:07', 478),
(39, 3, 16, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:08', 1566),
(40, 3, 17, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:12', 631),
(41, 3, 18, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:13', 1662),
(42, 3, 19, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:20', 350),
(43, 3, 20, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:21', 1428),
(44, 3, 21, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:26', 536),
(45, 3, 22, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:27', 1597),
(46, 3, 23, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:32', 518),
(47, 3, 24, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:33', 1858),
(48, 3, 25, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:35', 490),
(49, 3, 26, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:36', 1596),
(50, 3, 27, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:38', 465),
(51, 3, 28, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:39', 1536),
(52, 3, 29, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:43', 1230),
(53, 3, 30, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:44', 2296),
(54, 3, 31, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:46', 464),
(55, 3, 32, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:47', 1780),
(56, 3, 33, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:49', 992),
(57, 3, 34, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:56:50', 2118),
(58, 3, 35, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:54', 525),
(59, 3, 36, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:56:55', 1533),
(60, 3, 37, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:57', 249),
(61, 3, 38, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:56:58', 1316),
(62, 3, 39, 'X', 'movement', 5, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:00', 790),
(63, 3, 40, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:01', 1816),
(64, 3, 41, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:02', 436),
(65, 3, 42, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:04', 1569),
(66, 3, 43, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:05', 450),
(67, 3, 44, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:06', 1557),
(68, 3, 45, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:09', 609),
(69, 3, 46, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:10', 1794),
(70, 3, 47, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:12', 437),
(71, 3, 48, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:13', 1690),
(72, 3, 49, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:15', 747),
(73, 3, 50, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:16', 1863),
(74, 3, 51, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:19', 1295),
(75, 3, 52, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:20', 2571),
(76, 3, 53, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:22', 446),
(77, 3, 54, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:23', 1425),
(78, 3, 55, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:30', 644),
(79, 3, 56, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:31', 779),
(80, 3, 57, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:35', 1495),
(81, 3, 58, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:36', 2740),
(82, 3, 59, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:38', 509),
(83, 3, 60, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:39', 1560),
(84, 3, 61, 'X', 'movement', 5, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:40', 445),
(85, 3, 62, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:41', 1518),
(86, 3, 63, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:44', 406),
(87, 3, 64, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:45', 1491),
(88, 3, 65, 'X', 'movement', 5, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:46', 492),
(89, 3, 66, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:48', 1722),
(90, 3, 67, 'X', 'movement', 6, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:51', 658),
(91, 3, 68, 'O', 'movement', 3, 2, '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:57:53', 2333),
(92, 3, 69, 'X', 'movement', 8, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:57', 744),
(93, 3, 70, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:57:58', 1842),
(94, 3, 71, 'X', 'movement', 5, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:57:59', 452),
(95, 3, 72, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:58:00', 1568),
(96, 3, 73, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:58:03', 1352),
(97, 3, 74, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:58:04', 2749),
(98, 3, 75, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:58:06', 489),
(99, 3, 76, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 11:58:07', 1922),
(100, 3, 77, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:58:10', 576),
(101, 3, 78, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 11:58:11', 1637),
(102, 3, 79, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:58:12', 475),
(103, 3, 80, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 11:58:13', 1846),
(104, 4, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 11:58:28', 0),
(105, 4, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 11:58:29', 0),
(106, 4, 3, 'X', 'placement', NULL, 8, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,\"X\"]', '2025-12-26 11:58:42', 0),
(107, 4, 4, 'O', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,null,null,\"X\"]', '[\"O\",null,\"O\",null,\"X\",null,null,null,\"X\"]', '2025-12-26 11:58:43', 0),
(108, 4, 5, 'X', 'placement', NULL, 1, '[\"O\",null,\"O\",null,\"X\",null,null,null,\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",null,null,null,\"X\"]', '2025-12-26 11:58:46', 0),
(109, 4, 6, 'O', 'placement', NULL, 7, '[\"O\",\"X\",\"O\",null,\"X\",null,null,null,\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",null,null,\"O\",\"X\"]', '2025-12-26 11:58:47', 0),
(110, 4, 7, 'X', 'movement', 8, 6, '[\"O\",\"X\",\"O\",null,\"X\",null,null,\"O\",\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 11:58:54', 998),
(111, 4, 8, 'O', 'movement', 0, 5, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '[null,\"X\",\"O\",null,\"X\",\"O\",\"X\",\"O\",null]', '2025-12-26 11:58:55', 2186),
(112, 4, 9, 'X', 'movement', 6, 8, '[null,\"X\",\"O\",null,\"X\",\"O\",\"X\",\"O\",null]', '[null,\"X\",\"O\",null,\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 11:59:09', 3552),
(113, 4, 10, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",null,\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",\"X\",null,null,\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 11:59:10', 1188),
(114, 4, 11, 'X', 'movement', 1, 2, '[\"O\",\"X\",null,null,\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,\"X\",null,\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 11:59:16', 511),
(115, 4, 12, 'O', 'movement', 0, 6, '[\"O\",null,\"X\",null,\"X\",\"O\",null,\"O\",\"X\"]', '[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '2025-12-26 11:59:17', 1455),
(116, 4, 13, 'X', 'movement', 2, 0, '[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '[\"X\",null,null,null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '2025-12-26 11:59:27', 2555),
(117, 5, 1, 'X', 'placement', NULL, 0, '[null,null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,null,null,null,null,null]', '2025-12-26 12:00:52', 0),
(118, 5, 2, 'O', 'placement', NULL, 4, '[\"X\",null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,\"O\",null,null,null,null]', '2025-12-26 12:00:53', 0),
(119, 5, 3, 'X', 'placement', NULL, 1, '[\"X\",null,null,null,\"O\",null,null,null,null]', '[\"X\",\"X\",null,null,\"O\",null,null,null,null]', '2025-12-26 12:00:59', 0),
(120, 5, 4, 'O', 'placement', NULL, 2, '[\"X\",\"X\",null,null,\"O\",null,null,null,null]', '[\"X\",\"X\",\"O\",null,\"O\",null,null,null,null]', '2025-12-26 12:01:00', 0),
(121, 5, 5, 'X', 'placement', NULL, 6, '[\"X\",\"X\",\"O\",null,\"O\",null,null,null,null]', '[\"X\",\"X\",\"O\",null,\"O\",null,\"X\",null,null]', '2025-12-26 12:01:04', 0),
(122, 5, 6, 'O', 'placement', NULL, 3, '[\"X\",\"X\",\"O\",null,\"O\",null,\"X\",null,null]', '[\"X\",\"X\",\"O\",\"O\",\"O\",null,\"X\",null,null]', '2025-12-26 12:01:05', 0),
(123, 5, 7, 'X', 'movement', 1, 5, '[\"X\",\"X\",\"O\",\"O\",\"O\",null,\"X\",null,null]', '[\"X\",null,\"O\",\"O\",\"O\",\"X\",\"X\",null,null]', '2025-12-26 12:01:14', 606),
(124, 5, 8, 'O', 'movement', 2, 1, '[\"X\",null,\"O\",\"O\",\"O\",\"X\",\"X\",null,null]', '[\"X\",\"O\",null,\"O\",\"O\",\"X\",\"X\",null,null]', '2025-12-26 12:01:15', 2350),
(125, 5, 9, 'X', 'movement', 6, 7, '[\"X\",\"O\",null,\"O\",\"O\",\"X\",\"X\",null,null]', '[\"X\",\"O\",null,\"O\",\"O\",\"X\",null,\"X\",null]', '2025-12-26 12:01:20', 616),
(126, 5, 10, 'O', 'movement', 1, 2, '[\"X\",\"O\",null,\"O\",\"O\",\"X\",null,\"X\",null]', '[\"X\",null,\"O\",\"O\",\"O\",\"X\",null,\"X\",null]', '2025-12-26 12:01:21', 1728),
(127, 5, 11, 'X', 'movement', 0, 6, '[\"X\",null,\"O\",\"O\",\"O\",\"X\",null,\"X\",null]', '[null,null,\"O\",\"O\",\"O\",\"X\",\"X\",\"X\",null]', '2025-12-26 12:01:29', 929),
(128, 5, 12, 'O', 'movement', 2, 8, '[null,null,\"O\",\"O\",\"O\",\"X\",\"X\",\"X\",null]', '[null,null,null,\"O\",\"O\",\"X\",\"X\",\"X\",\"O\"]', '2025-12-26 12:01:30', 1865),
(129, 5, 13, 'X', 'movement', 7, 0, '[null,null,null,\"O\",\"O\",\"X\",\"X\",\"X\",\"O\"]', '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:01:38', 2241),
(130, 5, 14, 'O', 'movement', 3, 1, '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",\"O\",null,null,\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:01:39', 4069),
(131, 5, 15, 'X', 'movement', 5, 3, '[\"X\",\"O\",null,null,\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",\"O\",null,\"X\",\"O\",null,\"X\",null,\"O\"]', '2025-12-26 12:01:44', 1588),
(132, 6, 1, 'X', 'placement', NULL, 2, '[null,null,null,null,null,null,null,null,null]', '[null,null,\"X\",null,null,null,null,null,null]', '2025-12-26 12:02:05', 0),
(133, 6, 2, 'O', 'placement', NULL, 4, '[null,null,\"X\",null,null,null,null,null,null]', '[null,null,\"X\",null,\"O\",null,null,null,null]', '2025-12-26 12:02:06', 0),
(134, 6, 3, 'X', 'placement', NULL, 7, '[null,null,\"X\",null,\"O\",null,null,null,null]', '[null,null,\"X\",null,\"O\",null,null,\"X\",null]', '2025-12-26 12:02:17', 0),
(135, 6, 4, 'O', 'placement', NULL, 0, '[null,null,\"X\",null,\"O\",null,null,\"X\",null]', '[\"O\",null,\"X\",null,\"O\",null,null,\"X\",null]', '2025-12-26 12:02:18', 0),
(136, 6, 5, 'X', 'placement', NULL, 8, '[\"O\",null,\"X\",null,\"O\",null,null,\"X\",null]', '[\"O\",null,\"X\",null,\"O\",null,null,\"X\",\"X\"]', '2025-12-26 12:02:19', 0),
(137, 6, 6, 'O', 'placement', NULL, 6, '[\"O\",null,\"X\",null,\"O\",null,null,\"X\",\"X\"]', '[\"O\",null,\"X\",null,\"O\",null,\"O\",\"X\",\"X\"]', '2025-12-26 12:02:20', 0),
(138, 6, 7, 'X', 'movement', 7, 5, '[\"O\",null,\"X\",null,\"O\",null,\"O\",\"X\",\"X\"]', '[\"O\",null,\"X\",null,\"O\",\"X\",\"O\",null,\"X\"]', '2025-12-26 12:02:25', 646),
(139, 7, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:03:01', 0),
(140, 7, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:03:03', 0),
(141, 7, 3, 'X', 'placement', NULL, 3, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,\"X\",\"X\",null,null,null,null]', '2025-12-26 12:03:11', 0),
(142, 7, 4, 'O', 'placement', NULL, 5, '[\"O\",null,null,\"X\",\"X\",null,null,null,null]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,null,null]', '2025-12-26 12:03:13', 0),
(143, 7, 5, 'X', 'placement', NULL, 7, '[\"O\",null,null,\"X\",\"X\",\"O\",null,null,null]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"X\",null]', '2025-12-26 12:03:18', 0),
(144, 7, 6, 'O', 'placement', NULL, 1, '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"X\",null]', '[\"O\",\"O\",null,\"X\",\"X\",\"O\",null,\"X\",null]', '2025-12-26 12:03:20', 0),
(145, 7, 7, 'X', 'movement', 3, 2, '[\"O\",\"O\",null,\"X\",\"X\",\"O\",null,\"X\",null]', '[\"O\",\"O\",\"X\",null,\"X\",\"O\",null,\"X\",null]', '2025-12-26 12:03:29', 7230),
(146, 7, 8, 'O', 'movement', 0, 6, '[\"O\",\"O\",\"X\",null,\"X\",\"O\",null,\"X\",null]', '[null,\"O\",\"X\",null,\"X\",\"O\",\"O\",\"X\",null]', '2025-12-26 12:03:30', 8442),
(147, 7, 9, 'X', 'movement', 4, 8, '[null,\"O\",\"X\",null,\"X\",\"O\",\"O\",\"X\",null]', '[null,\"O\",\"X\",null,null,\"O\",\"O\",\"X\",\"X\"]', '2025-12-26 12:03:36', 546),
(148, 7, 10, 'O', 'movement', 1, 3, '[null,\"O\",\"X\",null,null,\"O\",\"O\",\"X\",\"X\"]', '[null,null,\"X\",\"O\",null,\"O\",\"O\",\"X\",\"X\"]', '2025-12-26 12:03:37', 1942),
(149, 7, 11, 'X', 'movement', 7, 4, '[null,null,\"X\",\"O\",null,\"O\",\"O\",\"X\",\"X\"]', '[null,null,\"X\",\"O\",\"X\",\"O\",\"O\",null,\"X\"]', '2025-12-26 12:03:44', 976),
(150, 7, 12, 'O', 'movement', 5, 0, '[null,null,\"X\",\"O\",\"X\",\"O\",\"O\",null,\"X\"]', '[\"O\",null,\"X\",\"O\",\"X\",null,\"O\",null,\"X\"]', '2025-12-26 12:03:45', 1457),
(151, 8, 1, 'X', 'placement', NULL, 0, '[null,null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,null,null,null,null,null]', '2025-12-26 12:04:04', 0),
(152, 8, 2, 'O', 'placement', NULL, 4, '[\"X\",null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,\"O\",null,null,null,null]', '2025-12-26 12:04:05', 0),
(153, 8, 3, 'X', 'placement', NULL, 6, '[\"X\",null,null,null,\"O\",null,null,null,null]', '[\"X\",null,null,null,\"O\",null,\"X\",null,null]', '2025-12-26 12:04:07', 0),
(154, 8, 4, 'O', 'placement', NULL, 3, '[\"X\",null,null,null,\"O\",null,\"X\",null,null]', '[\"X\",null,null,\"O\",\"O\",null,\"X\",null,null]', '2025-12-26 12:04:08', 0),
(155, 8, 5, 'X', 'placement', NULL, 5, '[\"X\",null,null,\"O\",\"O\",null,\"X\",null,null]', '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,null]', '2025-12-26 12:04:09', 0),
(156, 8, 6, 'O', 'placement', NULL, 2, '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,null]', '[\"X\",null,\"O\",\"O\",\"O\",\"X\",\"X\",null,null]', '2025-12-26 12:04:11', 0),
(157, 8, 7, 'X', 'movement', 0, 7, '[\"X\",null,\"O\",\"O\",\"O\",\"X\",\"X\",null,null]', '[null,null,\"O\",\"O\",\"O\",\"X\",\"X\",\"X\",null]', '2025-12-26 12:04:26', 5110),
(158, 8, 8, 'O', 'movement', 2, 8, '[null,null,\"O\",\"O\",\"O\",\"X\",\"X\",\"X\",null]', '[null,null,null,\"O\",\"O\",\"X\",\"X\",\"X\",\"O\"]', '2025-12-26 12:04:27', 6943),
(159, 8, 9, 'X', 'movement', 6, 0, '[null,null,null,\"O\",\"O\",\"X\",\"X\",\"X\",\"O\"]', '[\"X\",null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\"]', '2025-12-26 12:04:31', 753),
(160, 8, 10, 'O', 'movement', 3, 2, '[\"X\",null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\"]', '[\"X\",null,\"O\",null,\"O\",\"X\",null,\"X\",\"O\"]', '2025-12-26 12:04:32', 1794),
(161, 8, 11, 'X', 'movement', 7, 6, '[\"X\",null,\"O\",null,\"O\",\"X\",null,\"X\",\"O\"]', '[\"X\",null,\"O\",null,\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:04:37', 1320),
(162, 8, 12, 'O', 'movement', 2, 3, '[\"X\",null,\"O\",null,\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:04:38', 2415),
(163, 8, 13, 'X', 'movement', 6, 7, '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\"]', '2025-12-26 12:04:51', 1930),
(164, 8, 14, 'O', 'movement', 3, 2, '[\"X\",null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\"]', '[\"X\",null,\"O\",null,\"O\",\"X\",null,\"X\",\"O\"]', '2025-12-26 12:04:52', 2945),
(165, 8, 15, 'X', 'movement', 7, 6, '[\"X\",null,\"O\",null,\"O\",\"X\",null,\"X\",\"O\"]', '[\"X\",null,\"O\",null,\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:04:56', 1731),
(166, 8, 16, 'O', 'movement', 2, 3, '[\"X\",null,\"O\",null,\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:04:57', 2814),
(167, 8, 17, 'X', 'movement', 6, 2, '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\"]', '2025-12-26 12:05:04', 2691),
(168, 8, 18, 'O', 'movement', 3, 1, '[\"X\",null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\"]', '[\"X\",\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\"]', '2025-12-26 12:05:05', 950),
(169, 8, 19, 'X', 'movement', 5, 7, '[\"X\",\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\"]', '[\"X\",\"O\",\"X\",null,\"O\",null,null,\"X\",\"O\"]', '2025-12-26 12:05:12', 1694),
(170, 8, 20, 'O', 'movement', 1, 3, '[\"X\",\"O\",\"X\",null,\"O\",null,null,\"X\",\"O\"]', '[\"X\",null,\"X\",\"O\",\"O\",null,null,\"X\",\"O\"]', '2025-12-26 12:05:13', 2803),
(171, 8, 21, 'X', 'movement', 2, 5, '[\"X\",null,\"X\",\"O\",\"O\",null,null,\"X\",\"O\"]', '[\"X\",null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\"]', '2025-12-26 12:05:16', 1265),
(172, 8, 22, 'O', 'movement', 3, 2, '[\"X\",null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\"]', '[\"X\",null,\"O\",null,\"O\",\"X\",null,\"X\",\"O\"]', '2025-12-26 12:05:17', 2416),
(173, 8, 23, 'X', 'movement', 7, 6, '[\"X\",null,\"O\",null,\"O\",\"X\",null,\"X\",\"O\"]', '[\"X\",null,\"O\",null,\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:05:22', 2744),
(174, 8, 24, 'O', 'movement', 2, 3, '[\"X\",null,\"O\",null,\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '2025-12-26 12:05:23', 3838),
(175, 8, 25, 'X', 'movement', 5, 1, '[\"X\",null,null,\"O\",\"O\",\"X\",\"X\",null,\"O\"]', '[\"X\",\"X\",null,\"O\",\"O\",null,\"X\",null,\"O\"]', '2025-12-26 12:05:26', 757),
(176, 8, 26, 'O', 'movement', 8, 5, '[\"X\",\"X\",null,\"O\",\"O\",null,\"X\",null,\"O\"]', '[\"X\",\"X\",null,\"O\",\"O\",\"O\",\"X\",null,null]', '2025-12-26 12:05:27', 2007),
(177, 9, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:05:36', 0),
(178, 9, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:05:37', 0),
(179, 9, 3, 'X', 'placement', NULL, 5, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",\"X\",null,null,null]', '2025-12-26 12:05:39', 0),
(180, 9, 4, 'O', 'placement', NULL, 3, '[\"O\",null,null,null,\"X\",\"X\",null,null,null]', '[\"O\",null,null,\"O\",\"X\",\"X\",null,null,null]', '2025-12-26 12:05:40', 0),
(181, 9, 5, 'X', 'placement', NULL, 6, '[\"O\",null,null,\"O\",\"X\",\"X\",null,null,null]', '[\"O\",null,null,\"O\",\"X\",\"X\",\"X\",null,null]', '2025-12-26 12:05:43', 0),
(182, 9, 6, 'O', 'placement', NULL, 2, '[\"O\",null,null,\"O\",\"X\",\"X\",\"X\",null,null]', '[\"O\",null,\"O\",\"O\",\"X\",\"X\",\"X\",null,null]', '2025-12-26 12:05:44', 0),
(183, 9, 7, 'X', 'movement', 5, 1, '[\"O\",null,\"O\",\"O\",\"X\",\"X\",\"X\",null,null]', '[\"O\",\"X\",\"O\",\"O\",\"X\",null,\"X\",null,null]', '2025-12-26 12:05:49', 555),
(184, 9, 8, 'O', 'movement', 0, 7, '[\"O\",\"X\",\"O\",\"O\",\"X\",null,\"X\",null,null]', '[null,\"X\",\"O\",\"O\",\"X\",null,\"X\",\"O\",null]', '2025-12-26 12:05:50', 1684),
(185, 9, 9, 'X', 'movement', 6, 8, '[null,\"X\",\"O\",\"O\",\"X\",null,\"X\",\"O\",null]', '[null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 12:05:59', 5277),
(186, 9, 10, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '[\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 12:06:00', 7000),
(187, 9, 11, 'X', 'movement', 1, 6, '[\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\",\"X\"]', '[\"O\",null,null,\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '2025-12-26 12:06:05', 954),
(188, 9, 12, 'O', 'movement', 0, 2, '[\"O\",null,null,\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '2025-12-26 12:06:06', 2043),
(189, 9, 13, 'X', 'movement', 6, 0, '[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '[\"X\",null,\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 12:06:13', 627),
(190, 10, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:06:28', 0),
(191, 10, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:06:29', 0),
(192, 10, 3, 'X', 'placement', NULL, 5, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",\"X\",null,null,null]', '2025-12-26 12:06:31', 0),
(193, 10, 4, 'O', 'placement', NULL, 3, '[\"O\",null,null,null,\"X\",\"X\",null,null,null]', '[\"O\",null,null,\"O\",\"X\",\"X\",null,null,null]', '2025-12-26 12:06:32', 0),
(194, 10, 5, 'X', 'placement', NULL, 6, '[\"O\",null,null,\"O\",\"X\",\"X\",null,null,null]', '[\"O\",null,null,\"O\",\"X\",\"X\",\"X\",null,null]', '2025-12-26 12:06:33', 0),
(195, 10, 6, 'O', 'placement', NULL, 2, '[\"O\",null,null,\"O\",\"X\",\"X\",\"X\",null,null]', '[\"O\",null,\"O\",\"O\",\"X\",\"X\",\"X\",null,null]', '2025-12-26 12:06:34', 0),
(196, 10, 7, 'X', 'movement', 5, 1, '[\"O\",null,\"O\",\"O\",\"X\",\"X\",\"X\",null,null]', '[\"O\",\"X\",\"O\",\"O\",\"X\",null,\"X\",null,null]', '2025-12-26 12:06:37', 309),
(197, 10, 8, 'O', 'movement', 0, 7, '[\"O\",\"X\",\"O\",\"O\",\"X\",null,\"X\",null,null]', '[null,\"X\",\"O\",\"O\",\"X\",null,\"X\",\"O\",null]', '2025-12-26 12:06:38', 1424),
(198, 10, 9, 'X', 'movement', 6, 8, '[null,\"X\",\"O\",\"O\",\"X\",null,\"X\",\"O\",null]', '[null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 12:06:42', 854),
(199, 10, 10, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '[\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 12:06:43', 2010),
(200, 10, 11, 'X', 'movement', 1, 6, '[\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\",\"X\"]', '[\"O\",null,null,\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '2025-12-26 12:06:47', 418),
(201, 10, 12, 'O', 'movement', 0, 2, '[\"O\",null,null,\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '2025-12-26 12:06:48', 1766),
(202, 10, 13, 'X', 'movement', 6, 0, '[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '[\"X\",null,\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 12:06:51', 1770),
(203, 11, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:07:31', 0),
(204, 11, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:07:32', 0),
(205, 11, 3, 'X', 'placement', NULL, 8, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,\"X\"]', '2025-12-26 12:07:34', 0),
(206, 11, 4, 'O', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,null,null,\"X\"]', '[\"O\",null,\"O\",null,\"X\",null,null,null,\"X\"]', '2025-12-26 12:07:35', 0),
(207, 11, 5, 'X', 'placement', NULL, 1, '[\"O\",null,\"O\",null,\"X\",null,null,null,\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",null,null,null,\"X\"]', '2025-12-26 12:07:37', 0),
(208, 11, 6, 'O', 'placement', NULL, 7, '[\"O\",\"X\",\"O\",null,\"X\",null,null,null,\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",null,null,\"O\",\"X\"]', '2025-12-26 12:07:38', 0),
(209, 11, 7, 'X', 'movement', 8, 3, '[\"O\",\"X\",\"O\",null,\"X\",null,null,\"O\",\"X\"]', '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '2025-12-26 12:07:43', 653),
(210, 11, 8, 'O', 'movement', 0, 5, '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 12:07:44', 1655),
(211, 11, 9, 'X', 'movement', 1, 8, '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 12:07:54', 3207),
(212, 11, 10, 'O', 'movement', 2, 0, '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 12:07:55', 1025),
(213, 11, 11, 'X', 'movement', 3, 6, '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 12:08:00', 590),
(214, 11, 12, 'O', 'movement', 0, 2, '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 12:08:01', 1708),
(215, 11, 13, 'X', 'movement', 6, 0, '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[\"X\",null,\"O\",null,\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 12:08:14', 708),
(216, 13, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:20:03', 0),
(217, 13, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:20:04', 0),
(218, 13, 3, 'X', 'placement', NULL, 1, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '2025-12-26 12:20:08', 0),
(219, 13, 4, 'O', 'placement', NULL, 7, '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '2025-12-26 12:20:09', 0),
(220, 13, 5, 'X', 'placement', NULL, 2, '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '[\"O\",\"X\",\"X\",null,\"X\",null,null,\"O\",null]', '2025-12-26 12:20:10', 0),
(221, 13, 6, 'O', 'placement', NULL, 6, '[\"O\",\"X\",\"X\",null,\"X\",null,null,\"O\",null]', '[\"O\",\"X\",\"X\",null,\"X\",null,\"O\",\"O\",null]', '2025-12-26 12:20:11', 0),
(222, 13, 7, 'X', 'movement', 1, 3, '[\"O\",\"X\",\"X\",null,\"X\",null,\"O\",\"O\",null]', '[\"O\",null,\"X\",\"X\",\"X\",null,\"O\",\"O\",null]', '2025-12-26 12:20:37', 2588),
(223, 13, 8, 'O', 'movement', 0, 8, '[\"O\",null,\"X\",\"X\",\"X\",null,\"O\",\"O\",null]', '[null,null,\"X\",\"X\",\"X\",null,\"O\",\"O\",\"O\"]', '2025-12-26 12:20:38', 3615),
(224, 14, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:22:50', 0),
(225, 14, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:22:52', 0),
(226, 14, 3, 'X', 'placement', NULL, 6, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '2025-12-26 12:22:54', 0),
(227, 14, 4, 'O', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 12:22:55', 0),
(228, 14, 5, 'X', 'placement', NULL, 1, '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 12:23:15', 0),
(229, 14, 6, 'O', 'placement', NULL, 7, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 12:23:16', 0),
(230, 14, 7, 'X', 'movement', 1, 5, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '[\"O\",null,\"O\",null,\"X\",\"X\",\"X\",\"O\",null]', '2025-12-26 12:23:29', 4399),
(231, 14, 8, 'O', 'movement', 7, 1, '[\"O\",null,\"O\",null,\"X\",\"X\",\"X\",\"O\",null]', '[\"O\",\"O\",\"O\",null,\"X\",\"X\",\"X\",null,null]', '2025-12-26 12:23:30', 5417),
(232, 15, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:23:54', 0),
(233, 15, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 12:23:55', 0),
(234, 15, 3, 'X', 'placement', NULL, 5, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",\"X\",null,null,null]', '2025-12-26 12:23:58', 0),
(235, 15, 4, 'O', 'placement', NULL, 3, '[\"O\",null,null,null,\"X\",\"X\",null,null,null]', '[\"O\",null,null,\"O\",\"X\",\"X\",null,null,null]', '2025-12-26 12:23:59', 0),
(236, 15, 5, 'X', 'placement', NULL, 6, '[\"O\",null,null,\"O\",\"X\",\"X\",null,null,null]', '[\"O\",null,null,\"O\",\"X\",\"X\",\"X\",null,null]', '2025-12-26 12:24:03', 0),
(237, 15, 6, 'O', 'placement', NULL, 2, '[\"O\",null,null,\"O\",\"X\",\"X\",\"X\",null,null]', '[\"O\",null,\"O\",\"O\",\"X\",\"X\",\"X\",null,null]', '2025-12-26 12:24:04', 0),
(238, 15, 7, 'X', 'movement', 5, 1, '[\"O\",null,\"O\",\"O\",\"X\",\"X\",\"X\",null,null]', '[\"O\",\"X\",\"O\",\"O\",\"X\",null,\"X\",null,null]', '2025-12-26 12:24:10', 1319),
(239, 15, 8, 'O', 'movement', 0, 7, '[\"O\",\"X\",\"O\",\"O\",\"X\",null,\"X\",null,null]', '[null,\"X\",\"O\",\"O\",\"X\",null,\"X\",\"O\",null]', '2025-12-26 12:24:11', 3114),
(240, 15, 9, 'X', 'movement', 1, 8, '[null,\"X\",\"O\",\"O\",\"X\",null,\"X\",\"O\",null]', '[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '2025-12-26 12:24:20', 1318),
(241, 15, 10, 'O', 'movement', 2, 0, '[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '[\"O\",null,null,\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '2025-12-26 12:24:21', 3656),
(242, 15, 11, 'X', 'movement', 8, 2, '[\"O\",null,null,\"O\",\"X\",null,\"X\",\"O\",\"X\"]', '[\"O\",null,\"X\",\"O\",\"X\",null,\"X\",\"O\",null]', '2025-12-26 12:24:25', 837),
(243, 17, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 16:42:19', 0),
(244, 17, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 16:42:20', 0),
(245, 17, 3, 'X', 'placement', NULL, 1, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '2025-12-26 16:42:28', 0),
(246, 17, 4, 'O', 'placement', NULL, 7, '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '2025-12-26 16:42:30', 0),
(247, 17, 5, 'X', 'placement', NULL, 6, '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '[\"O\",\"X\",null,null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 16:42:33', 0),
(248, 17, 6, 'O', 'placement', NULL, 2, '[\"O\",\"X\",null,null,\"X\",null,\"X\",\"O\",null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 16:42:34', 0),
(249, 17, 7, 'X', 'movement', 6, 5, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '[\"O\",\"X\",\"O\",null,\"X\",\"X\",null,\"O\",null]', '2025-12-26 16:42:45', 6315),
(250, 17, 8, 'O', 'movement', 0, 3, '[\"O\",\"X\",\"O\",null,\"X\",\"X\",null,\"O\",null]', '[null,\"X\",\"O\",\"O\",\"X\",\"X\",null,\"O\",null]', '2025-12-26 16:42:46', 8165),
(251, 17, 9, 'X', 'movement', 1, 0, '[null,\"X\",\"O\",\"O\",\"X\",\"X\",null,\"O\",null]', '[\"X\",null,\"O\",\"O\",\"X\",\"X\",null,\"O\",null]', '2025-12-26 16:42:54', 1516),
(252, 17, 10, 'O', 'movement', 2, 8, '[\"X\",null,\"O\",\"O\",\"X\",\"X\",null,\"O\",null]', '[\"X\",null,null,\"O\",\"X\",\"X\",null,\"O\",\"O\"]', '2025-12-26 16:42:55', 2627),
(253, 18, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 16:49:37', 0),
(254, 18, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 16:49:39', 0),
(255, 18, 3, 'X', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,\"X\",null,\"X\",null,null,null,null]', '2025-12-26 16:49:46', 0),
(256, 18, 4, 'O', 'placement', NULL, 6, '[\"O\",null,\"X\",null,\"X\",null,null,null,null]', '[\"O\",null,\"X\",null,\"X\",null,\"O\",null,null]', '2025-12-26 16:49:47', 0),
(257, 18, 5, 'X', 'placement', NULL, 3, '[\"O\",null,\"X\",null,\"X\",null,\"O\",null,null]', '[\"O\",null,\"X\",\"X\",\"X\",null,\"O\",null,null]', '2025-12-26 16:49:48', 0),
(258, 18, 6, 'O', 'placement', NULL, 5, '[\"O\",null,\"X\",\"X\",\"X\",null,\"O\",null,null]', '[\"O\",null,\"X\",\"X\",\"X\",\"O\",\"O\",null,null]', '2025-12-26 16:49:49', 0),
(259, 18, 7, 'X', 'movement', 2, 1, '[\"O\",null,\"X\",\"X\",\"X\",\"O\",\"O\",null,null]', '[\"O\",\"X\",null,\"X\",\"X\",\"O\",\"O\",null,null]', '2025-12-26 16:49:53', 772),
(260, 18, 8, 'O', 'movement', 0, 7, '[\"O\",\"X\",null,\"X\",\"X\",\"O\",\"O\",null,null]', '[null,\"X\",null,\"X\",\"X\",\"O\",\"O\",\"O\",null]', '2025-12-26 16:49:54', 1920),
(261, 18, 9, 'X', 'movement', 1, 8, '[null,\"X\",null,\"X\",\"X\",\"O\",\"O\",\"O\",null]', '[null,null,null,\"X\",\"X\",\"O\",\"O\",\"O\",\"X\"]', '2025-12-26 16:50:03', 1284),
(262, 18, 10, 'O', 'movement', 5, 0, '[null,null,null,\"X\",\"X\",\"O\",\"O\",\"O\",\"X\"]', '[\"O\",null,null,\"X\",\"X\",null,\"O\",\"O\",\"X\"]', '2025-12-26 16:50:04', 2597),
(263, 18, 11, 'X', 'movement', 8, 5, '[\"O\",null,null,\"X\",\"X\",null,\"O\",\"O\",\"X\"]', '[\"O\",null,null,\"X\",\"X\",\"X\",\"O\",\"O\",null]', '2025-12-26 16:50:12', 783),
(264, 19, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 16:50:34', 0),
(265, 19, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 16:50:35', 0),
(266, 19, 3, 'X', 'placement', NULL, 1, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '2025-12-26 16:50:38', 0),
(267, 19, 4, 'O', 'placement', NULL, 7, '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '2025-12-26 16:50:39', 0),
(268, 19, 5, 'X', 'placement', NULL, 6, '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '[\"O\",\"X\",null,null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 16:50:43', 0),
(269, 19, 6, 'O', 'placement', NULL, 2, '[\"O\",\"X\",null,null,\"X\",null,\"X\",\"O\",null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 16:50:44', 0),
(270, 19, 7, 'X', 'movement', 6, 3, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '2025-12-26 16:50:49', 3347),
(271, 19, 8, 'O', 'movement', 0, 5, '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 16:50:50', 4259),
(272, 19, 9, 'X', 'movement', 1, 8, '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 16:50:56', 919),
(273, 19, 10, 'O', 'movement', 2, 0, '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 16:50:56', 1889),
(274, 19, 11, 'X', 'movement', 3, 6, '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 16:51:07', 701),
(275, 19, 12, 'O', 'movement', 0, 2, '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 16:51:08', 1943),
(276, 19, 13, 'X', 'movement', 6, 0, '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[\"X\",null,\"O\",null,\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 16:51:12', 767),
(277, 20, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:03:05', 0),
(278, 20, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:03:06', 0),
(279, 20, 3, 'X', 'placement', NULL, 6, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '2025-12-26 17:03:11', 0),
(280, 20, 4, 'O', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 17:03:12', 0),
(281, 20, 5, 'X', 'placement', NULL, 1, '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 17:03:15', 0),
(282, 20, 6, 'O', 'placement', NULL, 7, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 17:03:16', 0),
(283, 20, 7, 'X', 'movement', 6, 3, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '2025-12-26 17:03:25', 3027),
(284, 20, 8, 'O', 'movement', 0, 5, '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 17:03:26', 4554),
(285, 20, 9, 'X', 'movement', 1, 8, '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:03:34', 1150),
(286, 20, 10, 'O', 'movement', 2, 0, '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:03:37', 2727),
(287, 20, 11, 'X', 'movement', 3, 6, '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 17:03:42', 1007);
INSERT INTO `game_moves` (`id`, `session_id`, `move_number`, `player`, `move_type`, `from_position`, `to_position`, `board_state_before`, `board_state_after`, `timestamp`, `think_time_ms`) VALUES
(288, 20, 12, 'O', 'movement', 0, 2, '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 17:03:44', 2324),
(289, 20, 13, 'X', 'movement', 6, 3, '[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:04:18', 17607),
(290, 20, 14, 'O', 'movement', 2, 0, '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:04:19', 18740),
(291, 20, 15, 'X', 'movement', 8, 2, '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,\"X\",\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 17:04:30', 1923),
(292, 20, 16, 'O', 'movement', 0, 6, '[\"O\",null,\"X\",\"X\",\"X\",\"O\",null,\"O\",null]', '[null,null,\"X\",\"X\",\"X\",\"O\",\"O\",\"O\",null]', '2025-12-26 17:04:31', 3362),
(293, 20, 17, 'X', 'movement', 3, 8, '[null,null,\"X\",\"X\",\"X\",\"O\",\"O\",\"O\",null]', '[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '2025-12-26 17:04:37', 2857),
(294, 20, 18, 'O', 'movement', 5, 0, '[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '[\"O\",null,\"X\",null,\"X\",null,\"O\",\"O\",\"X\"]', '2025-12-26 17:04:38', 4418),
(295, 20, 19, 'X', 'movement', 4, 5, '[\"O\",null,\"X\",null,\"X\",null,\"O\",\"O\",\"X\"]', '[\"O\",null,\"X\",null,null,\"X\",\"O\",\"O\",\"X\"]', '2025-12-26 17:04:42', 625),
(296, 21, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:09:20', 0),
(297, 21, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:09:21', 0),
(298, 21, 3, 'X', 'placement', NULL, 6, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '2025-12-26 17:09:24', 0),
(299, 21, 4, 'O', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 17:09:25', 0),
(300, 21, 5, 'X', 'placement', NULL, 1, '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 17:09:26', 0),
(301, 21, 6, 'O', 'placement', NULL, 7, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 17:09:28', 0),
(302, 21, 7, 'X', 'movement', 6, 3, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '2025-12-26 17:09:33', 973),
(303, 21, 8, 'O', 'movement', 0, 5, '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 17:09:34', 2157),
(304, 21, 9, 'X', 'movement', 1, 8, '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:09:39', 1092),
(305, 21, 10, 'O', 'movement', 2, 0, '[null,null,\"O\",\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:09:40', 2095),
(306, 21, 11, 'X', 'movement', 3, 2, '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"O\",\"X\"]', '[\"O\",null,\"X\",null,\"X\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:09:47', 4094),
(307, 21, 12, 'O', 'movement', 0, 6, '[\"O\",null,\"X\",null,\"X\",\"O\",null,\"O\",\"X\"]', '[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '2025-12-26 17:09:48', 5582),
(308, 21, 13, 'X', 'movement', 2, 0, '[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '[\"X\",null,null,null,\"X\",\"O\",\"O\",\"O\",\"X\"]', '2025-12-26 17:09:52', 654),
(309, 23, 1, 'X', 'placement', NULL, 0, '[null,null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,null,null,null,null,null]', '2025-12-26 17:16:54', 0),
(310, 23, 2, 'O', 'placement', NULL, 2, '[\"X\",null,null,null,null,null,null,null,null]', '[\"X\",null,\"O\",null,null,null,null,null,null]', '2025-12-26 17:16:55', 0),
(311, 23, 3, 'X', 'placement', NULL, 3, '[\"X\",null,\"O\",null,null,null,null,null,null]', '[\"X\",null,\"O\",\"X\",null,null,null,null,null]', '2025-12-26 17:16:57', 0),
(312, 23, 4, 'O', 'placement', NULL, 4, '[\"X\",null,\"O\",\"X\",null,null,null,null,null]', '[\"X\",null,\"O\",\"X\",\"O\",null,null,null,null]', '2025-12-26 17:16:58', 0),
(313, 23, 5, 'X', 'placement', NULL, 6, '[\"X\",null,\"O\",\"X\",\"O\",null,null,null,null]', '[\"X\",null,\"O\",\"X\",\"O\",null,\"X\",null,null]', '2025-12-26 17:17:00', 0),
(314, 24, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:18:50', 0),
(315, 24, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:18:51', 0),
(316, 24, 3, 'X', 'placement', NULL, 6, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '2025-12-26 17:18:58', 0),
(317, 24, 4, 'O', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,\"X\",null,null]', '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 17:18:59', 0),
(318, 24, 5, 'X', 'placement', NULL, 1, '[\"O\",null,\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '2025-12-26 17:19:07', 0),
(319, 24, 6, 'O', 'placement', NULL, 7, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",null,null]', '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '2025-12-26 17:19:08', 0),
(320, 24, 7, 'X', 'movement', 6, 3, '[\"O\",\"X\",\"O\",null,\"X\",null,\"X\",\"O\",null]', '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '2025-12-26 17:20:07', 6821),
(321, 24, 8, 'O', 'movement', 0, 5, '[\"O\",\"X\",\"O\",\"X\",\"X\",null,null,\"O\",null]', '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 17:20:08', 8024),
(322, 24, 9, 'X', 'movement', 1, 0, '[null,\"X\",\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '[\"X\",null,\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 17:20:31', 1210),
(323, 24, 10, 'O', 'movement', 7, 8, '[\"X\",null,\"O\",\"X\",\"X\",\"O\",null,\"O\",null]', '[\"X\",null,\"O\",\"X\",\"X\",\"O\",null,null,\"O\"]', '2025-12-26 17:20:32', 2406),
(324, 25, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:21:19', 0),
(325, 25, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:21:20', 0),
(326, 25, 3, 'X', 'placement', NULL, 1, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '2025-12-26 17:21:27', 0),
(327, 25, 4, 'O', 'placement', NULL, 7, '[\"O\",\"X\",null,null,\"X\",null,null,null,null]', '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '2025-12-26 17:21:28', 0),
(328, 25, 5, 'X', 'placement', NULL, 3, '[\"O\",\"X\",null,null,\"X\",null,null,\"O\",null]', '[\"O\",\"X\",null,\"X\",\"X\",null,null,\"O\",null]', '2025-12-26 17:21:37', 0),
(329, 25, 6, 'O', 'placement', NULL, 5, '[\"O\",\"X\",null,\"X\",\"X\",null,null,\"O\",null]', '[\"O\",\"X\",null,\"X\",\"X\",\"O\",null,\"O\",null]', '2025-12-26 17:21:38', 0),
(330, 25, 7, 'X', 'movement', 3, 6, '[\"O\",\"X\",null,\"X\",\"X\",\"O\",null,\"O\",null]', '[\"O\",\"X\",null,null,\"X\",\"O\",\"X\",\"O\",null]', '2025-12-26 17:21:52', 1514),
(331, 25, 8, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"X\",\"O\",\"X\",\"O\",null]', '[null,\"X\",\"O\",null,\"X\",\"O\",\"X\",\"O\",null]', '2025-12-26 17:21:53', 2699),
(332, 25, 9, 'X', 'movement', 4, 8, '[null,\"X\",\"O\",null,\"X\",\"O\",\"X\",\"O\",null]', '[null,\"X\",\"O\",null,null,\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 17:21:57', 1081),
(333, 25, 10, 'O', 'movement', 2, 4, '[null,\"X\",\"O\",null,null,\"O\",\"X\",\"O\",\"X\"]', '[null,\"X\",null,null,\"O\",\"O\",\"X\",\"O\",\"X\"]', '2025-12-26 17:21:58', 1922),
(334, 25, 11, 'X', 'movement', 6, 3, '[null,\"X\",null,null,\"O\",\"O\",\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"X\",\"O\",\"O\",null,\"O\",\"X\"]', '2025-12-26 17:22:05', 799),
(335, 25, 12, 'O', 'movement', 5, 2, '[null,\"X\",null,\"X\",\"O\",\"O\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",\"X\",\"O\",null,null,\"O\",\"X\"]', '2025-12-26 17:22:06', 2013),
(336, 25, 13, 'X', 'movement', 8, 6, '[null,\"X\",\"O\",\"X\",\"O\",null,null,\"O\",\"X\"]', '[null,\"X\",\"O\",\"X\",\"O\",null,\"X\",\"O\",null]', '2025-12-26 17:22:09', 916),
(337, 25, 14, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",\"X\",\"O\",null,\"X\",\"O\",null]', '[\"O\",\"X\",null,\"X\",\"O\",null,\"X\",\"O\",null]', '2025-12-26 17:22:10', 1755),
(338, 25, 15, 'X', 'movement', 3, 8, '[\"O\",\"X\",null,\"X\",\"O\",null,\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:22:29', 8044),
(339, 25, 16, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:22:30', 9264),
(340, 25, 17, 'X', 'movement', 6, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:22:35', 1667),
(341, 25, 18, 'O', 'movement', 3, 2, '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:22:36', 2508),
(342, 25, 19, 'X', 'movement', 8, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:22:41', 1231),
(343, 25, 20, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:22:41', 2097),
(344, 25, 21, 'X', 'movement', 5, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:22:46', 2198),
(345, 25, 22, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:22:47', 3040),
(346, 25, 23, 'X', 'movement', 6, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:22:56', 937),
(347, 25, 24, 'O', 'movement', 3, 2, '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:22:57', 2104),
(348, 25, 25, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:23:03', 1726),
(349, 25, 26, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:23:04', 2940),
(350, 25, 27, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:23:17', 2076),
(351, 25, 28, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:23:18', 3271),
(352, 25, 29, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:23:25', 1259),
(353, 25, 30, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:23:26', 2447),
(354, 25, 31, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:23:32', 1249),
(355, 25, 32, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:23:33', 2461),
(356, 25, 33, 'X', 'movement', 8, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:23:35', 1050),
(357, 25, 34, 'O', 'movement', 3, 0, '[null,\"X\",null,\"O\",\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:23:36', 1929),
(358, 25, 35, 'X', 'movement', 6, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:23:43', 2406),
(359, 25, 36, 'O', 'movement', 0, 2, '[\"O\",\"X\",null,null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:23:44', 3626),
(360, 25, 37, 'X', 'movement', 8, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:23:48', 1100),
(361, 25, 38, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '2025-12-26 17:23:49', 1971),
(362, 25, 39, 'X', 'movement', 5, 8, '[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null]', '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:23:58', 1729),
(363, 25, 40, 'O', 'movement', 0, 3, '[\"O\",\"X\",null,null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:23:59', 2958),
(364, 25, 41, 'X', 'movement', 6, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:24:04', 932),
(365, 25, 42, 'O', 'movement', 3, 2, '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:24:05', 1803),
(366, 25, 43, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:24:12', 2608),
(367, 25, 44, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:24:13', 3770),
(368, 25, 45, 'X', 'movement', 6, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:24:16', 835),
(369, 25, 46, 'O', 'movement', 3, 2, '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:24:16', 1682),
(370, 25, 47, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:24:21', 3159),
(371, 25, 48, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:24:22', 4059),
(372, 25, 49, 'X', 'movement', 6, 5, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:24:26', 809),
(373, 25, 50, 'O', 'movement', 3, 2, '[null,\"X\",null,\"O\",\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '2025-12-26 17:24:27', 1649),
(374, 25, 51, 'X', 'movement', 5, 6, '[null,\"X\",\"O\",null,\"O\",\"X\",null,\"O\",\"X\"]', '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:24:30', 2210),
(375, 25, 52, 'O', 'movement', 2, 3, '[null,\"X\",\"O\",null,\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 17:24:31', 3057),
(376, 25, 53, 'X', 'movement', 8, 2, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[null,\"X\",\"X\",\"O\",\"O\",null,\"X\",\"O\",null]', '2025-12-26 17:24:38', 1830),
(377, 25, 54, 'O', 'movement', 7, 5, '[null,\"X\",\"X\",\"O\",\"O\",null,\"X\",\"O\",null]', '[null,\"X\",\"X\",\"O\",\"O\",\"O\",\"X\",null,null]', '2025-12-26 17:24:39', 3047),
(378, 26, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:25:51', 0),
(379, 26, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 17:25:52', 0),
(380, 26, 3, 'X', 'placement', NULL, 3, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,\"X\",\"X\",null,null,null,null]', '2025-12-26 17:25:53', 0),
(381, 26, 4, 'O', 'placement', NULL, 5, '[\"O\",null,null,\"X\",\"X\",null,null,null,null]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,null,null]', '2025-12-26 17:25:54', 0),
(382, 26, 5, 'X', 'placement', NULL, 7, '[\"O\",null,null,\"X\",\"X\",\"O\",null,null,null]', '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"X\",null]', '2025-12-26 17:25:57', 0),
(383, 26, 6, 'O', 'placement', NULL, 1, '[\"O\",null,null,\"X\",\"X\",\"O\",null,\"X\",null]', '[\"O\",\"O\",null,\"X\",\"X\",\"O\",null,\"X\",null]', '2025-12-26 17:25:58', 0),
(384, 26, 7, 'X', 'movement', 3, 6, '[\"O\",\"O\",null,\"X\",\"X\",\"O\",null,\"X\",null]', '[\"O\",\"O\",null,null,\"X\",\"O\",\"X\",\"X\",null]', '2025-12-26 17:26:01', 622),
(385, 26, 8, 'O', 'movement', 5, 2, '[\"O\",\"O\",null,null,\"X\",\"O\",\"X\",\"X\",null]', '[\"O\",\"O\",\"O\",null,\"X\",null,\"X\",\"X\",null]', '2025-12-26 17:26:02', 1648),
(386, 27, 1, 'X', 'placement', NULL, 1, '[null,null,null,null,null,null,null,null,null]', '[null,\"X\",null,null,null,null,null,null,null]', '2025-12-26 17:49:35', 0),
(387, 27, 2, 'O', 'placement', NULL, 8, '[null,\"X\",null,null,null,null,null,null,null]', '[null,\"X\",null,null,null,null,null,null,\"O\"]', '2025-12-26 17:49:36', 0),
(388, 27, 3, 'X', 'placement', NULL, 2, '[null,\"X\",null,null,null,null,null,null,\"O\"]', '[null,\"X\",\"X\",null,null,null,null,null,\"O\"]', '2025-12-26 17:49:44', 0),
(389, 27, 4, 'O', 'placement', NULL, 4, '[null,\"X\",\"X\",null,null,null,null,null,\"O\"]', '[null,\"X\",\"X\",null,\"O\",null,null,null,\"O\"]', '2025-12-26 17:49:46', 0),
(390, 27, 5, 'X', 'placement', NULL, 6, '[null,\"X\",\"X\",null,\"O\",null,null,null,\"O\"]', '[null,\"X\",\"X\",null,\"O\",null,\"X\",null,\"O\"]', '2025-12-26 17:49:50', 0),
(391, 27, 6, 'O', 'placement', NULL, 0, '[null,\"X\",\"X\",null,\"O\",null,\"X\",null,\"O\"]', '[\"O\",\"X\",\"X\",null,\"O\",null,\"X\",null,\"O\"]', '2025-12-26 17:49:52', 0),
(392, 28, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 18:58:28', 0),
(393, 28, 2, 'O', 'placement', NULL, 5, '[null,null,null,null,\"X\",null,null,null,null]', '[null,null,null,null,\"X\",\"O\",null,null,null]', '2025-12-26 18:58:29', 0),
(394, 28, 3, 'X', 'placement', NULL, 6, '[null,null,null,null,\"X\",\"O\",null,null,null]', '[null,null,null,null,\"X\",\"O\",\"X\",null,null]', '2025-12-26 18:58:31', 0),
(395, 28, 4, 'O', 'placement', NULL, 1, '[null,null,null,null,\"X\",\"O\",\"X\",null,null]', '[null,\"O\",null,null,\"X\",\"O\",\"X\",null,null]', '2025-12-26 18:58:32', 0),
(396, 28, 5, 'X', 'placement', NULL, 2, '[null,\"O\",null,null,\"X\",\"O\",\"X\",null,null]', '[null,\"O\",\"X\",null,\"X\",\"O\",\"X\",null,null]', '2025-12-26 18:58:33', 0),
(397, 29, 1, 'X', 'placement', NULL, 0, '[null,null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,null,null,null,null,null]', '2025-12-26 18:58:47', 0),
(398, 29, 2, 'O', 'placement', NULL, 4, '[\"X\",null,null,null,null,null,null,null,null]', '[\"X\",null,null,null,\"O\",null,null,null,null]', '2025-12-26 18:58:48', 0),
(399, 29, 3, 'X', 'placement', NULL, 3, '[\"X\",null,null,null,\"O\",null,null,null,null]', '[\"X\",null,null,\"X\",\"O\",null,null,null,null]', '2025-12-26 18:58:50', 0),
(400, 29, 4, 'O', 'placement', NULL, 6, '[\"X\",null,null,\"X\",\"O\",null,null,null,null]', '[\"X\",null,null,\"X\",\"O\",null,\"O\",null,null]', '2025-12-26 18:58:51', 0),
(401, 29, 5, 'X', 'placement', NULL, 1, '[\"X\",null,null,\"X\",\"O\",null,\"O\",null,null]', '[\"X\",\"X\",null,\"X\",\"O\",null,\"O\",null,null]', '2025-12-26 18:58:52', 0),
(402, 29, 6, 'O', 'placement', NULL, 2, '[\"X\",\"X\",null,\"X\",\"O\",null,\"O\",null,null]', '[\"X\",\"X\",\"O\",\"X\",\"O\",null,\"O\",null,null]', '2025-12-26 18:58:53', 0),
(403, 30, 1, 'X', 'placement', NULL, 4, '[null,null,null,null,null,null,null,null,null]', '[null,null,null,null,\"X\",null,null,null,null]', '2025-12-26 18:59:10', 0),
(404, 30, 2, 'O', 'placement', NULL, 0, '[null,null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,null]', '2025-12-26 18:59:11', 0),
(405, 30, 3, 'X', 'placement', NULL, 8, '[\"O\",null,null,null,\"X\",null,null,null,null]', '[\"O\",null,null,null,\"X\",null,null,null,\"X\"]', '2025-12-26 18:59:13', 0),
(406, 30, 4, 'O', 'placement', NULL, 2, '[\"O\",null,null,null,\"X\",null,null,null,\"X\"]', '[\"O\",null,\"O\",null,\"X\",null,null,null,\"X\"]', '2025-12-26 18:59:14', 0),
(407, 30, 5, 'X', 'placement', NULL, 1, '[\"O\",null,\"O\",null,\"X\",null,null,null,\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",null,null,null,\"X\"]', '2025-12-26 18:59:15', 0),
(408, 30, 6, 'O', 'placement', NULL, 7, '[\"O\",\"X\",\"O\",null,\"X\",null,null,null,\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",null,null,\"O\",\"X\"]', '2025-12-26 18:59:16', 0),
(409, 30, 7, 'X', 'movement', 8, 5, '[\"O\",\"X\",\"O\",null,\"X\",null,null,\"O\",\"X\"]', '[\"O\",\"X\",\"O\",null,\"X\",\"X\",null,\"O\",null]', '2025-12-26 18:59:35', 1601),
(410, 30, 8, 'O', 'movement', 0, 3, '[\"O\",\"X\",\"O\",null,\"X\",\"X\",null,\"O\",null]', '[null,\"X\",\"O\",\"O\",\"X\",\"X\",null,\"O\",null]', '2025-12-26 18:59:36', 2848),
(411, 30, 9, 'X', 'movement', 5, 8, '[null,\"X\",\"O\",\"O\",\"X\",\"X\",null,\"O\",null]', '[null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 18:59:47', 1147),
(412, 30, 10, 'O', 'movement', 2, 0, '[null,\"X\",\"O\",\"O\",\"X\",null,null,\"O\",\"X\"]', '[\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\",\"X\"]', '2025-12-26 18:59:47', 2513),
(413, 30, 11, 'X', 'movement', 4, 6, '[\"O\",\"X\",null,\"O\",\"X\",null,null,\"O\",\"X\"]', '[\"O\",\"X\",null,\"O\",null,null,\"X\",\"O\",\"X\"]', '2025-12-26 18:59:54', 844),
(414, 30, 12, 'O', 'movement', 0, 4, '[\"O\",\"X\",null,\"O\",null,null,\"X\",\"O\",\"X\"]', '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '2025-12-26 18:59:55', 2254),
(415, 30, 13, 'X', 'movement', 8, 0, '[null,\"X\",null,\"O\",\"O\",null,\"X\",\"O\",\"X\"]', '[\"X\",\"X\",null,\"O\",\"O\",null,\"X\",\"O\",null]', '2025-12-26 19:00:03', 2666),
(416, 30, 14, 'O', 'movement', 7, 5, '[\"X\",\"X\",null,\"O\",\"O\",null,\"X\",\"O\",null]', '[\"X\",\"X\",null,\"O\",\"O\",\"O\",\"X\",null,null]', '2025-12-26 19:00:04', 3924);

-- --------------------------------------------------------

--
-- Table structure for table `game_sessions`
--

CREATE TABLE `game_sessions` (
  `id` int(11) NOT NULL,
  `player1_id` int(11) NOT NULL,
  `player2_id` int(11) DEFAULT NULL,
  `game_mode` enum('pvp','pvc-easy','pvc-medium','pvc-hard') NOT NULL,
  `status` enum('active','paused','completed','abandoned') DEFAULT 'active',
  `winner_id` int(11) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT current_timestamp(),
  `last_move_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `current_phase` enum('placement','movement') DEFAULT 'placement',
  `current_turn` enum('X','O') DEFAULT 'X',
  `board_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`board_state`)),
  `player1_score` int(11) DEFAULT 0,
  `player2_score` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_sessions`
--

INSERT INTO `game_sessions` (`id`, `player1_id`, `player2_id`, `game_mode`, `status`, `winner_id`, `started_at`, `last_move_at`, `completed_at`, `current_phase`, `current_turn`, `board_state`, `player1_score`, `player2_score`) VALUES
(1, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 11:29:05', '2025-12-26 11:29:41', '2025-12-26 11:29:41', 'movement', 'X', '{\"board\":[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(2, 1, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 11:53:29', '2025-12-26 11:54:11', '2025-12-26 11:54:11', 'movement', 'O', '{\"board\":[\"X\",null,\"X\",null,\"X\",\"O\",\"O\",null,\"O\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(3, 1, NULL, 'pvc-hard', 'active', NULL, '2025-12-26 11:55:21', '2025-12-26 11:58:18', NULL, 'movement', 'X', '{\"board\":[\"O\",\"X\",null,null,\"O\",\"X\",\"X\",\"O\",null],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(4, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 11:58:26', '2025-12-26 11:59:27', '2025-12-26 11:59:27', 'movement', 'X', '{\"board\":[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(5, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 12:00:44', '2025-12-26 12:01:44', '2025-12-26 12:01:44', 'movement', 'X', '{\"board\":[\"X\",\"O\",null,null,\"O\",\"X\",\"X\",null,\"O\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(6, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 12:02:01', '2025-12-26 12:02:26', '2025-12-26 12:02:26', 'movement', 'X', '{\"board\":[\"O\",null,\"X\",null,\"O\",null,\"O\",\"X\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(7, 1, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 12:03:00', '2025-12-26 12:03:45', '2025-12-26 12:03:45', 'movement', 'O', '{\"board\":[null,null,\"X\",\"O\",\"X\",\"O\",\"O\",null,\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(8, 1, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 12:04:01', '2025-12-26 12:05:27', '2025-12-26 12:05:27', 'movement', 'O', '{\"board\":[\"X\",\"X\",null,\"O\",\"O\",null,\"X\",null,\"O\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(9, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 12:05:35', '2025-12-26 12:06:13', '2025-12-26 12:06:13', 'movement', 'X', '{\"board\":[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(10, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 12:06:26', '2025-12-26 12:06:52', '2025-12-26 12:06:52', 'movement', 'X', '{\"board\":[null,null,\"O\",\"O\",\"X\",null,\"X\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(11, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 12:07:23', '2025-12-26 12:08:15', '2025-12-26 12:08:15', 'movement', 'X', '{\"board\":[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(12, 1, NULL, 'pvc-hard', 'active', NULL, '2025-12-26 12:10:45', '2025-12-26 12:11:04', NULL, 'placement', 'X', '{\"board\":[null,null,null,null,null,null,null,null,null],\"placedCount\":{\"X\":0,\"O\":0},\"phase\":\"placement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(13, 1, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 12:19:49', '2025-12-26 12:20:39', '2025-12-26 12:20:39', 'movement', 'O', '{\"board\":[\"O\",null,\"X\",\"X\",\"X\",null,\"O\",\"O\",null],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(14, 1, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 12:22:45', '2025-12-26 12:23:30', '2025-12-26 12:23:30', 'movement', 'O', '{\"board\":[\"O\",null,\"O\",null,\"X\",\"X\",\"X\",\"O\",null],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(15, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 12:23:50', '2025-12-26 12:24:26', '2025-12-26 12:24:26', 'movement', 'X', '{\"board\":[\"O\",null,null,\"O\",\"X\",null,\"X\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(16, 1, NULL, 'pvc-hard', 'active', NULL, '2025-12-26 16:41:08', '2025-12-26 16:41:08', NULL, 'placement', 'X', '{\"board\":[null,null,null,null,null,null,null,null,null],\"placedCount\":{\"X\":0,\"O\":0},\"phase\":\"placement\",\"turn\":\"X\"}', 0, 0),
(17, 1, NULL, 'pvc-hard', 'active', NULL, '2025-12-26 16:42:14', '2025-12-26 16:42:56', NULL, 'movement', 'X', '{\"board\":[\"X\",null,null,\"O\",\"X\",\"X\",null,\"O\",\"O\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(18, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 16:49:34', '2025-12-26 16:50:12', '2025-12-26 16:50:12', 'movement', 'X', '{\"board\":[\"O\",null,null,\"X\",\"X\",null,\"O\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(19, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 16:50:24', '2025-12-26 16:51:12', '2025-12-26 16:51:12', 'movement', 'X', '{\"board\":[null,null,\"O\",null,\"X\",\"O\",\"X\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(20, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 17:03:02', '2025-12-26 17:04:43', '2025-12-26 17:04:43', 'movement', 'X', '{\"board\":[\"O\",null,\"X\",null,\"X\",null,\"O\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(21, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 17:09:12', '2025-12-26 17:09:52', '2025-12-26 17:09:52', 'movement', 'X', '{\"board\":[null,null,\"X\",null,\"X\",\"O\",\"O\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(22, 2, NULL, 'pvc-easy', 'active', NULL, '2025-12-26 17:16:31', '2025-12-26 17:16:31', NULL, 'placement', 'X', '{\"board\":[null,null,null,null,null,null,null,null,null],\"placedCount\":{\"X\":0,\"O\":0},\"phase\":\"placement\",\"turn\":\"X\"}', 0, 0),
(23, 2, NULL, 'pvc-easy', 'completed', 2, '2025-12-26 17:16:52', '2025-12-26 17:17:00', '2025-12-26 17:17:00', 'placement', 'X', '{\"board\":[\"X\",null,\"O\",\"X\",\"O\",null,null,null,null],\"placedCount\":{\"X\":2,\"O\":2},\"phase\":\"placement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(24, 2, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 17:18:24', '2025-12-26 17:20:32', '2025-12-26 17:20:32', 'movement', 'O', '{\"board\":[\"X\",null,\"O\",\"X\",\"X\",\"O\",null,\"O\",null],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(25, 2, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 17:21:16', '2025-12-26 17:24:39', '2025-12-26 17:24:39', 'movement', 'O', '{\"board\":[null,\"X\",\"X\",\"O\",\"O\",null,\"X\",\"O\",null],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(26, 1, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 17:25:46', '2025-12-26 17:26:02', '2025-12-26 17:26:02', 'movement', 'O', '{\"board\":[\"O\",\"O\",null,null,\"X\",\"O\",\"X\",\"X\",null],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(27, 3, NULL, 'pvc-medium', 'completed', NULL, '2025-12-26 17:49:20', '2025-12-26 17:49:52', '2025-12-26 17:49:52', 'placement', 'O', '{\"board\":[null,\"X\",\"X\",null,\"O\",null,\"X\",null,\"O\"],\"placedCount\":{\"X\":3,\"O\":2},\"phase\":\"placement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(28, 4, NULL, 'pvc-easy', 'completed', 4, '2025-12-26 18:58:25', '2025-12-26 18:58:33', '2025-12-26 18:58:33', 'placement', 'X', '{\"board\":[null,\"O\",null,null,\"X\",\"O\",\"X\",null,null],\"placedCount\":{\"X\":2,\"O\":2},\"phase\":\"placement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(29, 4, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 18:58:45', '2025-12-26 18:58:53', '2025-12-26 18:58:53', 'placement', 'O', '{\"board\":[\"X\",\"X\",null,\"X\",\"O\",null,\"O\",null,null],\"placedCount\":{\"X\":3,\"O\":2},\"phase\":\"placement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(30, 4, NULL, 'pvc-hard', 'completed', NULL, '2025-12-26 18:59:07', '2025-12-26 19:00:04', '2025-12-26 19:00:04', 'movement', 'O', '{\"board\":[\"X\",\"X\",null,\"O\",\"O\",null,\"X\",\"O\",null],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"O\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0),
(31, 4, NULL, 'pvc-hard', 'active', NULL, '2025-12-26 19:00:15', '2025-12-26 19:00:15', NULL, 'placement', 'X', '{\"board\":[null,null,null,null,null,null,null,null,null],\"placedCount\":{\"X\":0,\"O\":0},\"phase\":\"placement\",\"turn\":\"X\"}', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `matchmaking_queue`
--

CREATE TABLE `matchmaking_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `preferred_mode` enum('pvp','ranked') DEFAULT 'pvp',
  `joined_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_challenges`
--

CREATE TABLE `game_challenges` (
  `id` int(11) NOT NULL,
  `challenger_id` int(11) NOT NULL,
  `challenged_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','cancelled','expired') DEFAULT 'pending',
  `session_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('challenge','game_start','game_end','system') DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
--

CREATE TABLE `error_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `error_type` varchar(100) NOT NULL,
  `error_message` text NOT NULL,
  `stack_trace` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `line_number` int(11) DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `session_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0,
  `draws` int(11) DEFAULT 0,
  `rating` int(11) DEFAULT 1000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `created_at`, `last_login`, `is_online`, `wins`, `losses`, `draws`, `rating`) VALUES
(1, 'megamind', 'udechimarvellous@gmail.com', '$2y$12$Qzn78PPkoyjk5gNv2hF3zO5.h2Oa88mAI8XhQkQFqwhM17ziPjg/O', 'uploads/avatars/avatar_1_1766744222.jpg', '2025-12-26 10:16:41', '2025-12-26 16:40:09', 1, 12, 6, 0, 1240),
(2, 'Sam odogwu', 'okpanyisamuel@gmail.com', '$2y$12$bP4BMZoZrK2M7SBqK4XMGen4nPWgH0DlHflePX0Y2LCKqX9el1IY6', 'avatar6.svg', '2025-12-26 17:12:15', NULL, 0, 1, 2, 0, 1005),
(3, 'Never', 'garvynick895@gmail.com', '$2y$12$JuyGM65ZwdjqJbw1yngj6eNCXergqDCjuia0eUB3Jb.G86TYUCy1O', 'avatar4.svg', '2025-12-26 17:47:51', NULL, 0, 0, 1, 0, 990),
(4, 'Maziscanner', 'maziscanner@gmail.com', '$2y$12$2icW18lHjwj.apVlEdtABuMbLXN0msH9SlLSu23u3XDx9ibBlktz2', 'avatar1.svg', '2025-12-26 18:57:00', NULL, 0, 1, 2, 0, 1005);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `user_id` int(11) NOT NULL,
  `sound_enabled` tinyint(1) DEFAULT 1,
  `notifications_enabled` tinyint(1) DEFAULT 1,
  `preferred_difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `auto_match` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`user_id`, `sound_enabled`, `notifications_enabled`, `preferred_difficulty`, `auto_match`) VALUES
(1, 1, 1, 'medium', 0),
(2, 1, 1, 'medium', 0),
(3, 1, 1, 'medium', 0),
(4, 1, 1, 'medium', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_training_data`
--
ALTER TABLE `ai_training_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `idx_outcome` (`game_outcome`),
  ADD KEY `idx_difficulty` (`difficulty_level`);

--
-- Indexes for table `game_moves`
--
ALTER TABLE `game_moves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_move_number` (`move_number`);

--
-- Indexes for table `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `winner_id` (`winner_id`),
  ADD KEY `idx_player1` (`player1_id`),
  ADD KEY `idx_player2` (`player2_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `matchmaking_queue`
--
ALTER TABLE `matchmaking_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_joined` (`joined_at`);

--
-- Indexes for table `game_challenges`
--
ALTER TABLE `game_challenges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_challenger` (`challenger_id`),
  ADD KEY `idx_challenged` (`challenged_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`error_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_online` (`is_online`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_training_data`
--
ALTER TABLE `ai_training_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `game_moves`
--
ALTER TABLE `game_moves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=417;

--
-- AUTO_INCREMENT for table `game_sessions`
--
ALTER TABLE `game_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `matchmaking_queue`
--
ALTER TABLE `matchmaking_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `game_challenges`
--
ALTER TABLE `game_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_training_data`
--
ALTER TABLE `ai_training_data`
  ADD CONSTRAINT `ai_training_data_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_moves`
--
ALTER TABLE `game_moves`
  ADD CONSTRAINT `game_moves_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD CONSTRAINT `game_sessions_ibfk_1` FOREIGN KEY (`player1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_sessions_ibfk_2` FOREIGN KEY (`player2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_sessions_ibfk_3` FOREIGN KEY (`winner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `matchmaking_queue`
--
ALTER TABLE `matchmaking_queue`
  ADD CONSTRAINT `matchmaking_queue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_challenges`
--
ALTER TABLE `game_challenges`
  ADD CONSTRAINT `game_challenges_ibfk_1` FOREIGN KEY (`challenger_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_challenges_ibfk_2` FOREIGN KEY (`challenged_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_challenges_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD CONSTRAINT `error_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
