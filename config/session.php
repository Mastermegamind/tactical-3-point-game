<?php

require_once __DIR__ . '/RedisManager.php';

class RedisSessionHandler implements SessionHandlerInterface {
    private $redis;
    private $ttl;

    public function __construct(RedisManager $redis) {
        $this->redis = $redis;
        $this->ttl = max((int)ini_get('session.gc_maxlifetime'), RedisManager::TTL_SESSION);
    }

    public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($sessionId) {
        $data = $this->redis->get("session:{$sessionId}");
        return $data !== false ? $data : '';
    }

    public function write($sessionId, $data) {
        return $this->redis->set("session:{$sessionId}", $data, $this->ttl);
    }

    public function destroy($sessionId) {
        $this->redis->delete("session:{$sessionId}");
        return true;
    }

    public function gc($maxlifetime) {
        return true;
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
