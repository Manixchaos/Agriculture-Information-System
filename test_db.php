<?php
// Diagnostic only. Run from terminal:  php test_db.php
// (blocked from the browser by .htaccess)
require_once __DIR__ . '/config/database.php';

try {
    $pdo = db();
    $r = $pdo->query("SELECT current_database() AS db, current_user AS usr, version() AS ver")->fetch();
    echo "SUPABASE CONNECTION SUCCESSFUL!\n";
    echo "Database: {$r['db']}\nUser: {$r['usr']}\n{$r['ver']}\n";

    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables found in public schema: " . ($tables ? implode(', ', $tables) : "NONE - run database/schema.sql and seed.sql in the Supabase SQL Editor") . "\n";
} catch (Throwable $e) {
    echo "CONNECTION FAILED!\nError: " . $e->getMessage() . "\n";
}
