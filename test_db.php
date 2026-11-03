<?php

require_once __DIR__ . '/config/database.php';

try {
    $pdo = db();

    $result = $pdo->query(
        "SELECT current_database(), current_user"
    )->fetch();

    echo "SUPABASE CONNECTION SUCCESSFUL!\n";
    echo "Database: " . $result['current_database'] . "\n";
    echo "User: " . $result['current_user'] . "\n";

} catch (Throwable $e) {
    echo "CONNECTION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
