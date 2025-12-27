-- Make session_id nullable for AI training summary records
-- Training summaries don't reference specific game sessions

-- First, drop the foreign key constraint
ALTER TABLE `ai_training_data` DROP FOREIGN KEY `ai_training_data_ibfk_1`;

-- Make session_id nullable
ALTER TABLE `ai_training_data` MODIFY `session_id` INT(11) NULL;

-- Re-add the foreign key constraint (but now it allows NULL)
ALTER TABLE `ai_training_data`
ADD CONSTRAINT `ai_training_data_ibfk_1`
FOREIGN KEY (`session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE;
