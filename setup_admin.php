<?php
require_once __DIR__ . '/config/app.php';
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Run this script from the command line only.\n"); }
echo "Create initial AgriTrack administrator\n";
$name = trim(readline("Full name: "));
$email = strtolower(trim(readline("Email: ")));
echo "Password (minimum 10 characters): ";
$pass = trim(readline());
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 10) {
    fwrite(STDERR, "Invalid details. Name, valid email, and a 10-character password are required.\n"); exit(1);
}
try {
    $stmt = db()->prepare("INSERT INTO app_users(full_name,email,password_hash,role) VALUES(:n,:e,:p,'Administrator')");
    $stmt->execute(['n'=>$name,'e'=>$email,'p'=>password_hash($pass, PASSWORD_DEFAULT)]);
    echo "Administrator created. Delete or keep this CLI-only script outside your public web root.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Could not create administrator. The email may already exist or the database may be unavailable.\n"); exit(1);
}
