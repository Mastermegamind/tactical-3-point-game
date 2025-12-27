-- Chat messages table
CREATE TABLE IF NOT EXISTS `game_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `game_chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rematch requests table
CREATE TABLE IF NOT EXISTS `rematch_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_session_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','expired') DEFAULT 'pending',
  `new_session_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_requester` (`requester_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_status` (`status`),
  KEY `idx_original_session` (`original_session_id`),
  CONSTRAINT `rematch_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rematch_requests_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rematch_requests_ibfk_3` FOREIGN KEY (`original_session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rematch_requests_ibfk_4` FOREIGN KEY (`new_session_id`) REFERENCES `game_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
