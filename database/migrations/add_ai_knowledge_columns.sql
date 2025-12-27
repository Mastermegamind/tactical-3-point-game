-- Add AI Knowledge Base columns to ai_training_data table
-- These columns store aggregated learning data from the AI training process

ALTER TABLE `ai_training_data`
ADD COLUMN `position_weights` TEXT NULL AFTER `winning_pattern`,
ADD COLUMN `opening_patterns` TEXT NULL AFTER `position_weights`,
ADD COLUMN `winning_sequences` TEXT NULL AFTER `opening_patterns`,
ADD COLUMN `games_analyzed` INT(11) NULL DEFAULT 0 AFTER `winning_sequences`,
ADD COLUMN `win_rate` DECIMAL(5,2) NULL DEFAULT 0.00 AFTER `games_analyzed`,
ADD COLUMN `avg_moves` DECIMAL(5,2) NULL DEFAULT 0.00 AFTER `win_rate`;
