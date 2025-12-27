<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = file_get_contents(__DIR__ . '/add-chat-rematch.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;

    try {
        $conn->exec($statement . ';');
        echo "✓ Executed successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠ Table already exists, skipped\n";
        } else {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nMigration completed!\n";
