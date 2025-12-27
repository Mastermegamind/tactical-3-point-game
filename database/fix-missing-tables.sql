-- Fix missing AUTO_INCREMENT and constraints for game_challenges and notifications tables

-- Add PRIMARY KEY and indexes for game_challenges
ALTER TABLE `game_challenges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_challenger` (`challenger_id`),
  ADD KEY `idx_challenged` (`challenged_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_created` (`created_at`);

-- Add AUTO_INCREMENT for game_challenges
ALTER TABLE `game_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Add FOREIGN KEY constraints for game_challenges
ALTER TABLE `game_challenges`
  ADD CONSTRAINT `game_challenges_ibfk_1` FOREIGN KEY (`challenger_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_challenges_ibfk_2` FOREIGN KEY (`challenged_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_challenges_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE SET NULL;

-- Add PRIMARY KEY and indexes for notifications
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

-- Add AUTO_INCREMENT for notifications
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Add FOREIGN KEY constraint for notifications
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
