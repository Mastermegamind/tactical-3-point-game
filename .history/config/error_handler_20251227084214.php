<?php
/**
 * Global Error Handler
 * Catches all PHP errors, exceptions, and logs them to database and file
 */

// Prevent multiple declarations
if (class_exists('ErrorHandler', false)) {
    return;
}

class ErrorHandler {
    private static $db;
    private static $displayErrors = true;
    private static $logErrors = true;

    public static function init() {
        // Load environment settings
        if (file_exists(__DIR__ . '/../.env')) {
            $env = parse_ini_file(__DIR__ . '/../.env');
            self::$displayErrors = ($env['DISPLAY_ERRORS'] ?? 'true') === 'true';
            self::$logErrors = ($env['LOG_ERRORS'] ?? 'true') === 'true';

            // Set PHP error reporting
            if (isset($env['ERROR_REPORTING'])) {
                error_reporting(E_ALL);
            }

            // Set display errors
            ini_set('display_errors', self::$displayErrors ? '1' : '0');
            ini_set('display_startup_errors', self::$displayErrors ? '1' : '0');
        }

        // Set error and exception handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);

        // Initialize database connection LAZILY (only when needed)
        // This prevents circular dependency with database.php
    }

    /**
     * Get database connection (lazy loading)
     */
    private static function getDb() {
        if (self::$db === null) {
            try {
                // Only require database.php when we actually need it
                require_once __DIR__ . '/database.php';
                self::$db = Database::getInstance()->getConnection();
            } catch (Exception $e) {
                // If database fails, only log to file
                error_log("Failed to connect to database for error logging: " . $e->getMessage());
            }
        }
        return self::$db;
    }

    /**
     * Handle regular PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Don't handle suppressed errors (@)
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);
        $errorData = [
            'type' => $errorType,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];

        self::logError($errorData);

        // Display error if enabled
        if (self::$displayErrors) {
            self::displayError($errorData);
        }

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $errorData = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'exception_class' => get_class($exception)
        ];

        self::logError($errorData);

        // Display error if enabled
        if (self::$displayErrors) {
            self::displayError($errorData);
        }
    }

    /**
     * Handle fatal errors
     */
    public static function handleFatalError() {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => []
            ];

            self::logError($errorData);

            // Display error if enabled
            if (self::$displayErrors) {
                self::displayError($errorData);
            }
        }
    }

    /**
     * Log error to database and file
     */
    private static function logError($errorData) {
        if (!self::$logErrors) {
            return;
        }

        // Log to file
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $errorData['type'],
            $errorData['message'],
            $errorData['file'],
            $errorData['line']
        );

        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        error_log($logMessage, 3, $logDir . '/error.log');

        // Log to database (using lazy loading)
        $db = self::getDb();
        if ($db) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO error_logs (
                        error_type, error_message, error_file, error_line,
                        stack_trace, request_uri, request_method, user_agent, ip_address
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $errorData['type'],
                    $errorData['message'],
                    $errorData['file'],
                    $errorData['line'],
                    json_encode($errorData['trace']),
                    $_SERVER['REQUEST_URI'] ?? '',
                    $_SERVER['REQUEST_METHOD'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
            } catch (Exception $e) {
                // If database logging fails, only log to file
                error_log("Failed to log error to database: " . $e->getMessage());
            }
        }
    }

    /**
     * Display error in user-friendly format
     */
    private static function displayError($errorData) {
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($isAjax) {
            // Return JSON for AJAX requests
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'type' => $errorData['type'],
                'message' => $errorData['message'],
                'file' => $errorData['file'],
                'line' => $errorData['line']
            ]);
            exit;
        }

        // HTML error display for regular requests
        echo self::renderErrorHTML($errorData);
    }

    /**
     * Render error as HTML
     */
    private static function renderErrorHTML($errorData) {
        $traceHtml = '';
        if (!empty($errorData['trace'])) {
            $traceHtml = '<div class="error-trace">';
            $traceHtml .= '<h4>Stack Trace:</h4>';
            $traceHtml .= '<ol>';
            foreach ($errorData['trace'] as $i => $trace) {
                $file = $trace['file'] ?? 'unknown';
                $line = $trace['line'] ?? 0;
                $function = $trace['function'] ?? '';
                $class = $trace['class'] ?? '';
                $type = $trace['type'] ?? '';

                $traceHtml .= sprintf(
                    '<li>%s%s%s() in <strong>%s</strong> on line <strong>%d</strong></li>',
                    $class,
                    $type,
                    $function,
                    $file,
                    $line
                );
            }
            $traceHtml .= '</ol>';
            $traceHtml .= '</div>';
        }

        // Read context from file
        $contextHtml = self::getFileContext($errorData['file'], $errorData['line']);

        return <<<HTML
<div style="
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    margin: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
">
    <div style="display: flex; align-items: center; margin-bottom: 15px;">
        <div style="font-size: 48px; margin-right: 15px;">⚠️</div>
        <div>
            <h2 style="margin: 0; font-size: 24px;">{$errorData['type']}</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">An error occurred in your application</p>
        </div>
    </div>

    <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; margin-bottom: 15px;">
        <h3 style="margin: 0 0 10px 0; font-size: 18px;">Error Message:</h3>
        <p style="margin: 0; font-size: 16px; font-family: monospace;">{$errorData['message']}</p>
    </div>

    <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; margin-bottom: 15px;">
        <h3 style="margin: 0 0 10px 0; font-size: 18px;">Location:</h3>
        <p style="margin: 0; font-family: monospace;">
            <strong>File:</strong> {$errorData['file']}<br>
            <strong>Line:</strong> {$errorData['line']}
        </p>
    </div>

    {$contextHtml}

    <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 6px;">
        {$traceHtml}
    </div>

    <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 6px; font-size: 14px;">
        <strong>💡 Tip:</strong> To hide error details in production, set <code>DISPLAY_ERRORS=false</code> in your .env file
    </div>
</div>
HTML;
    }

    /**
     * Get file context around error line
     */
    private static function getFileContext($file, $line, $contextLines = 5) {
        if (!file_exists($file)) {
            return '';
        }

        $lines = file($file);
        $start = max(0, $line - $contextLines - 1);
        $end = min(count($lines), $line + $contextLines);

        $html = '<div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; margin-bottom: 15px;">';
        $html .= '<h3 style="margin: 0 0 10px 0; font-size: 18px;">Code Context:</h3>';
        $html .= '<pre style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 13px; line-height: 1.5;">';

        for ($i = $start; $i < $end; $i++) {
            $currentLine = $i + 1;
            $code = htmlspecialchars($lines[$i]);

            if ($currentLine == $line) {
                $html .= sprintf(
                    '<span style="background: rgba(255,0,0,0.3); display: block; margin: 0 -10px; padding: 0 10px;"><strong style="color: #ff6b6b;">→ %d:</strong> %s</span>',
                    $currentLine,
                    $code
                );
            } else {
                $html .= sprintf(
                    '<span style="opacity: 0.7;">  %d:</span> %s',
                    $currentLine,
                    $code
                );
            }
        }

        $html .= '</pre>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get error type name
     */
    private static function getErrorType($errno) {
        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        if (defined('E_STRICT')) {
            $errorTypes[E_STRICT] = 'Strict Standards';
        }

        return $errorTypes[$errno] ?? 'Unknown Error';
    }
}

// Initialize error handler
ErrorHandler::init();