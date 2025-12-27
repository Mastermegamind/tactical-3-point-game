<?php

require_once __DIR__ . '/RedisManager.php';

class RedisSessionHandler implements SessionHandlerInterface {
    private $redis;
    private $ttl;

    public function __construct(RedisManager $redis) {
        $this->redis = $redis;
        $this->ttl = max((int)ini_get('session.gc_maxlifetime'), RedisManager::TTL_SESSION);
    }

    public function open(string $savePath, string $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $sessionId): string|false {
        $data = $this->redis->get("session:{$sessionId}");
        return $data !== false ? $data : '';
    }

    public function write(string $sessionId, string $data): bool {
        return $this->redis->set("session:{$sessionId}", $data, $this->ttl);
    }

    public function destroy(string $sessionId): bool {
        $this->redis->delete("session:{$sessionId}");
        return true;
    }

    public function gc(int $maxlifetime): int|false {
        return 1;
    }
}

$redisManager = RedisManager::getInstance();
if ($redisManager->isEnabled()) {
    $handler = new RedisSessionHandler($redisManager);
    session_set_save_handler($handler, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
