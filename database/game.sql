-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251014.c784570216
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 26, 2025 at 11:33 AM
-- Server version: 10.11.14-MariaDB-0+deb12u2
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
(1, 1, 'player_win', 'hard', 11, NULL, -3564, 1025, '2025-12-26 11:29:41');

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
(11, 1, 11, 'X', 'movement', 8, 2, '[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"]', '[\"O\",null,\"X\",null,\"X\",\"O\",\"X\",\"O\",null]', '2025-12-26 11:29:41', 1806);

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
(1, 1, NULL, 'pvc-hard', 'completed', 1, '2025-12-26 11:29:05', '2025-12-26 11:29:41', '2025-12-26 11:29:41', 'movement', 'X', '{\"board\":[\"O\",null,null,null,\"X\",\"O\",\"X\",\"O\",\"X\"],\"placedCount\":{\"X\":3,\"O\":3},\"phase\":\"movement\",\"turn\":\"X\",\"currentMovingPlayer\":\"X\",\"gameOver\":false}', 0, 0);

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
(1, 'megamind', 'udechimarvellous@gmail.com', '$2y$12$Qzn78PPkoyjk5gNv2hF3zO5.h2Oa88mAI8XhQkQFqwhM17ziPjg/O', 'uploads/avatars/avatar_1_1766744222.jpg', '2025-12-26 10:16:41', NULL, 0, 1, 0, 0, 1025);

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
(1, 1, 1, 'medium', 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `game_moves`
--
ALTER TABLE `game_moves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `game_sessions`
--
ALTER TABLE `game_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `matchmaking_queue`
--
ALTER TABLE `matchmaking_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
