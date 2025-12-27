<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Read and execute the SQL file
$sql = file_get_contents(__DIR__ . '/fix-missing-tables.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) continue;

    try {
        $conn->exec($statement . ';');
        echo "✓ Executed successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false ||
            strpos($e->getMessage(), 'Multiple primary key') !== false) {
            echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
        } else {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nDatabase migration completed!\n";
