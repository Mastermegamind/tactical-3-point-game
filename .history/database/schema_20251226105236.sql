-- Tactical Pebble Game Database Schema

CREATE DATABASE IF NOT EXISTS tactical_pebble_game;
USE tactical_pebble_game;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_online BOOLEAN DEFAULT FALSE,
    wins INT DEFAULT 0,
    losses INT DEFAULT 0,
    draws INT DEFAULT 0,
    rating INT DEFAULT 1000,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_online (is_online),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game sessions table
CREATE TABLE IF NOT EXISTS game_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    player1_id INT NOT NULL,
    player2_id INT NULL,
    game_mode ENUM('pvp', 'pvc-easy', 'pvc-medium', 'pvc-hard') NOT NULL,
    status ENUM('active', 'paused', 'completed', 'abandoned') DEFAULT 'active',
    winner_id INT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_move_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    current_phase ENUM('placement', 'movement') DEFAULT 'placement',
    current_turn ENUM('X', 'O') DEFAULT 'X',
    board_state JSON,
    player1_score INT DEFAULT 0,
    player2_score INT DEFAULT 0,
    FOREIGN KEY (player1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_player1 (player1_id),
    INDEX idx_player2 (player2_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game moves history (for AI training and replay)
CREATE TABLE IF NOT EXISTS game_moves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    move_number INT NOT NULL,
    player ENUM('X', 'O') NOT NULL,
    move_type ENUM('placement', 'movement') NOT NULL,
    from_position INT NULL,
    to_position INT NOT NULL,
    board_state_before JSON,
    board_state_after JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    think_time_ms INT DEFAULT 0,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_move_number (move_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matchmaking queue
CREATE TABLE IF NOT EXISTS matchmaking_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    rating INT NOT NULL,
    preferred_mode ENUM('pvp', 'ranked') DEFAULT 'pvp',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_rating (rating),
    INDEX idx_joined (joined_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User settings
CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    sound_enabled BOOLEAN DEFAULT TRUE,
    notifications_enabled BOOLEAN DEFAULT TRUE,
    preferred_difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    auto_match BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI training data
CREATE TABLE IF NOT EXISTS ai_training_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    game_outcome ENUM('player_win', 'ai_win', 'draw') NOT NULL,
    difficulty_level ENUM('easy', 'medium', 'hard') NOT NULL,
    total_moves INT NOT NULL,
    winning_pattern JSON,
    game_duration_seconds INT,
    player_rating INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
    INDEX idx_outcome (game_outcome),
    INDEX idx_difficulty (difficulty_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
