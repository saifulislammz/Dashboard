<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// $_ENV থেকে পড়ো, না পেলে getenv() দিয়ে Docker env variable পড়ো
$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'exam_auth';
$dbUser = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

try {
    $db = new \PDO("mysql:dbname={$dbName};host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
} catch (\PDOException $e) {
    // Log error internally in a real app, but don't expose to the user
    die("Database Connection Error. Please try again later.");
}

$auth = new \Delight\Auth\Auth($db);
