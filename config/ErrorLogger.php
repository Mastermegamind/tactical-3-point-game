<?php

class ErrorLogger {
    private static $instance = null;
    private $conn;

    private function __construct() {
        require_once __DIR__ . '/database.php';
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ErrorLogger();
        }
        return self::$instance;
    }

    /**
     * Log an error to the database
     *
     * @param string $errorType Type of error (e.g., 'database', 'validation', 'authentication')
     * @param string $errorMessage The error message
     * @param array $context Additional context (file, line, trace, etc.)
     * @return bool Success status
     */
    public function log($errorType, $errorMessage, $context = []) {
        try {
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            $stmt = $this->conn->prepare("
                INSERT INTO error_logs (
                    user_id, error_type, error_message, stack_trace,
                    file_path, line_number, request_uri, request_method,
                    user_agent, ip_address, session_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stackTrace = isset($context['trace']) ? $context['trace'] : (isset($context['exception']) ? $context['exception']->getTraceAsString() : null);
            $filePath = isset($context['file']) ? $context['file'] : (isset($context['exception']) ? $context['exception']->getFile() : null);
            $lineNumber = isset($context['line']) ? $context['line'] : (isset($context['exception']) ? $context['exception']->getLine() : null);
            $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
            $ipAddress = $this->getClientIP();

            // Sanitize session data (remove sensitive info)
            $sessionData = isset($_SESSION) ? $this->sanitizeSessionData($_SESSION) : null;
            $sessionDataJson = $sessionData ? json_encode($sessionData) : null;

            $stmt->execute([
                $userId,
                $errorType,
                $errorMessage,
                $stackTrace,
                $filePath,
                $lineNumber,
                $requestUri,
                $requestMethod,
                $userAgent,
                $ipAddress,
                $sessionDataJson
            ]);

            return true;
        } catch (Exception $e) {
            // If logging fails, write to error log file as fallback
            error_log("ErrorLogger failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log an exception
     */
    public function logException(Exception $e, $errorType = 'exception') {
        return $this->log($errorType, $e->getMessage(), ['exception' => $e]);
    }

    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipAddress = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return $ipAddress;
    }

    /**
     * Remove sensitive data from session before logging
     */
    private function sanitizeSessionData($session) {
        $sanitized = $session;

        // Remove password-related data
        $sensitiveKeys = ['password', 'token', 'secret', 'api_key'];
        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '[REDACTED]';
            }
        }

        return $sanitized;
    }

    /**
     * Get recent errors
     */
    public function getRecentErrors($limit = 50, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT el.*, u.username
                FROM error_logs el
                LEFT JOIN users u ON el.user_id = u.id
                ORDER BY el.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to fetch errors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get error count
     */
    public function getErrorCount() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM error_logs");
            $result = $stmt->fetch();
            return $result['count'];
        } catch (Exception $e) {
            error_log("Failed to count errors: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get errors by type
     */
    public function getErrorsByType($type, $limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT el.*, u.username
                FROM error_logs el
                LEFT JOIN users u ON el.user_id = u.id
                WHERE el.error_type = ?
                ORDER BY el.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$type, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to fetch errors by type: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear old errors (older than X days)
     */
    public function clearOldErrors($days = 30) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM error_logs
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Failed to clear old errors: " . $e->getMessage());
            return 0;
        }
    }
}
