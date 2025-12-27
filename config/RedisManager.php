<?php

/**
 * Redis Manager - Centralized Redis connection and caching utilities
 * Handles all Redis operations for the game system
 */
class RedisManager {
    private static $instance = null;
    private $redis = null;
    private $enabled = true;
    private $prefix = 'game:';

    // Cache TTL constants (in seconds)
    const TTL_SESSION = 3600;           // 1 hour
    const TTL_AI_STRATEGY = 1800;       // 30 minutes
    const TTL_LEADERBOARD = 300;        // 5 minutes
    const TTL_GAME_STATE = 7200;        // 2 hours
    const TTL_USER_STATS = 600;         // 10 minutes
    const TTL_TRAINING_DATA = 3600;     // 1 hour
    const TTL_SHORT = 60;               // 1 minute

    private function __construct() {
        try {
            $this->redis = new Redis();
            $host = getenv('REDIS_HOST') ?: '127.0.0.1';
            $port = getenv('REDIS_PORT') ?: 6379;
            $password = getenv('REDIS_PASSWORD') ?: null;

            $connected = $this->redis->connect($host, $port, 2.5);

            if (!$connected) {
                throw new Exception("Could not connect to Redis");
            }

            if ($password) {
                $this->redis->auth($password);
            }

            // Set key prefix
            $this->redis->setOption(Redis::OPT_PREFIX, $this->prefix);

            // Use PHP serialization for complex data
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

            error_log("Redis connected successfully");

        } catch (Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            $this->enabled = false;
            $this->redis = null;
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if Redis is enabled and connected
     */
    public function isEnabled() {
        return $this->enabled && $this->redis !== null;
    }

    /**
     * Get Redis connection
     */
    public function getRedis() {
        return $this->redis;
    }

    /**
     * Set a value with optional TTL
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->isEnabled()) return false;

        try {
            if ($ttl) {
                return $this->redis->setex($key, $ttl, $value);
            } else {
                return $this->redis->set($key, $value);
            }
        } catch (Exception $e) {
            error_log("Redis set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a value
     */
    public function get($key) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->get($key);
        } catch (Exception $e) {
            error_log("Redis get error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete one or more keys
     */
    public function delete($key) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->del($key);
        } catch (Exception $e) {
            error_log("Redis delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if key exists
     */
    public function exists($key) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->exists($key);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Increment a counter
     */
    public function increment($key, $amount = 1) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->incrBy($key, $amount);
        } catch (Exception $e) {
            error_log("Redis increment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add member to sorted set (for leaderboards)
     */
    public function zAdd($key, $score, $member) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->zAdd($key, $score, $member);
        } catch (Exception $e) {
            error_log("Redis zAdd error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get sorted set range (for leaderboards)
     */
    public function zRange($key, $start, $end, $withScores = false) {
        if (!$this->isEnabled()) return [];

        try {
            return $this->redis->zRevRange($key, $start, $end, $withScores);
        } catch (Exception $e) {
            error_log("Redis zRange error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get member rank in sorted set
     */
    public function zRank($key, $member) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->zRevRank($key, $member);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get member score in sorted set
     */
    public function zScore($key, $member) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->zScore($key, $member);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Set hash field
     */
    public function hSet($key, $field, $value) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->hSet($key, $field, $value);
        } catch (Exception $e) {
            error_log("Redis hSet error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get hash field
     */
    public function hGet($key, $field) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->hGet($key, $field);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get all hash fields
     */
    public function hGetAll($key) {
        if (!$this->isEnabled()) return [];

        try {
            return $this->redis->hGetAll($key);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Delete hash field
     */
    public function hDel($key, $field) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->hDel($key, $field);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Set expiration time
     */
    public function expire($key, $seconds) {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->expire($key, $seconds);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete keys by pattern
     */
    public function deletePattern($pattern) {
        if (!$this->isEnabled()) return 0;

        try {
            $keys = $this->redis->keys($pattern);
            if (empty($keys)) return 0;

            return $this->redis->del($keys);
        } catch (Exception $e) {
            error_log("Redis deletePattern error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cache AI strategies for a difficulty level
     */
    public function cacheAIStrategies($difficulty, $strategies) {
        $key = "ai:strategies:{$difficulty}";
        return $this->set($key, $strategies, self::TTL_AI_STRATEGY);
    }

    /**
     * Get cached AI strategies
     */
    public function getAIStrategies($difficulty) {
        $key = "ai:strategies:{$difficulty}";
        return $this->get($key);
    }

    /**
     * Invalidate AI strategies cache
     */
    public function invalidateAIStrategies($difficulty = null) {
        if ($difficulty) {
            $this->delete("ai:strategies:{$difficulty}");
            $this->deletePattern("ai:strategies:{$difficulty}:*");
        } else {
            $this->deletePattern("ai:strategies:*");
        }
    }

    /**
     * Cache training data
     */
    public function cacheTrainingData($difficulty, $data) {
        $key = "ai:training:{$difficulty}";
        return $this->set($key, $data, self::TTL_TRAINING_DATA);
    }

    /**
     * Get cached training data
     */
    public function getTrainingData($difficulty) {
        $key = "ai:training:{$difficulty}";
        return $this->get($key);
    }

    /**
     * Invalidate training data cache
     */
    public function invalidateTrainingData($difficulty = null) {
        if ($difficulty) {
            $this->delete("ai:training:{$difficulty}");
        } else {
            $this->deletePattern("ai:training:*");
        }
    }

    /**
     * Update leaderboard
     */
    public function updateLeaderboard($userId, $username, $rating) {
        $key = "leaderboard:global";
        $member = json_encode(['user_id' => $userId, 'username' => $username]);
        return $this->zAdd($key, $rating, $member);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard($limit = 10) {
        $key = "leaderboard:global";
        $results = $this->zRange($key, 0, $limit - 1, true);

        $leaderboard = [];
        foreach ($results as $member => $score) {
            $data = json_decode($member, true);
            $data['rating'] = $score;
            $leaderboard[] = $data;
        }

        return $leaderboard;
    }

    /**
     * Get user rank
     */
    public function getUserRank($userId, $username) {
        $key = "leaderboard:global";
        $member = json_encode(['user_id' => $userId, 'username' => $username]);
        $rank = $this->zRank($key, $member);

        return $rank !== false ? $rank + 1 : null;
    }

    /**
     * Cache game state
     */
    public function cacheGameState($sessionId, $gameState) {
        $key = "game:state:{$sessionId}";
        return $this->set($key, $gameState, self::TTL_GAME_STATE);
    }

    /**
     * Get cached game state
     */
    public function getGameState($sessionId) {
        $key = "game:state:{$sessionId}";
        return $this->get($key);
    }

    /**
     * Delete game state
     */
    public function deleteGameState($sessionId) {
        $key = "game:state:{$sessionId}";
        return $this->delete($key);
    }

    /**
     * Cache user stats
     */
    public function cacheUserStats($userId, $stats) {
        $key = "user:stats:{$userId}";
        return $this->set($key, $stats, self::TTL_USER_STATS);
    }

    /**
     * Get cached user stats
     */
    public function getUserStats($userId) {
        $key = "user:stats:{$userId}";
        return $this->get($key);
    }

    /**
     * Invalidate user stats
     */
    public function invalidateUserStats($userId) {
        $key = "user:stats:{$userId}";
        return $this->delete($key);
    }

    /**
     * Increment online users counter
     */
    public function incrementOnlineUsers() {
        return $this->increment("stats:online_users");
    }

    /**
     * Decrement online users counter
     */
    public function decrementOnlineUsers() {
        return $this->increment("stats:online_users", -1);
    }

    /**
     * Get online users count
     */
    public function getOnlineUsersCount() {
        $count = $this->get("stats:online_users");
        return $count !== false ? (int)$count : 0;
    }

    /**
     * Track active game session
     */
    public function trackActiveGame($sessionId, $players) {
        $key = "active:games";
        return $this->hSet($key, $sessionId, json_encode($players));
    }

    /**
     * Remove active game session
     */
    public function removeActiveGame($sessionId) {
        $key = "active:games";
        return $this->hDel($key, $sessionId);
    }

    /**
     * Get active games count
     */
    public function getActiveGamesCount() {
        if (!$this->isEnabled()) return 0;

        try {
            $key = "active:games";
            return $this->redis->hLen($key);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get all active games
     */
    public function getActiveGames() {
        $key = "active:games";
        $games = $this->hGetAll($key);

        $result = [];
        foreach ($games as $sessionId => $data) {
            $result[$sessionId] = json_decode($data, true);
        }

        return $result;
    }

    /**
     * Flush all cache (use with caution)
     */
    public function flushAll() {
        if (!$this->isEnabled()) return false;

        try {
            return $this->redis->flushDB();
        } catch (Exception $e) {
            error_log("Redis flush error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats() {
        if (!$this->isEnabled()) {
            return ['enabled' => false];
        }

        try {
            $info = $this->redis->info();

            return [
                'enabled' => true,
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0',
                'total_connections' => $info['total_connections_received'] ?? 0,
                'total_commands' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        } catch (Exception $e) {
            return ['enabled' => true, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate($info) {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) return 0;

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
