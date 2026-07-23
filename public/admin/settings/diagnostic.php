<?php
/**
 * Diagnostic Script for Meetings Module
 * 
 * Run this script to identify the exact cause of HTTP 500 errors
 * on the meetings settings page.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Meetings Module Diagnostic Tool</h2>";
echo "<ul style='font-family: monospace; line-height: 1.6;'>";

function logResult($step, $status, $details = '') {
    $color = $status === 'OK' ? 'green' : ($status === 'WARNING' ? 'orange' : 'red');
    echo "<li><strong>Step: $step</strong> - <span style='color: $color; font-weight: bold;'>$status</span>";
    if ($details) {
        echo "<br><span style='color: #555;'>=> $details</span>";
    }
    echo "</li><hr>";
}

// 1. Check PHP Version
logResult('PHP Version', 'OK', phpversion());

// 2. Check APP_ENCRYPTION_KEY
$envPath = __DIR__ . '/../../../.env';
$encryptionKey = '';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    if (preg_match('/APP_ENCRYPTION_KEY=(.+)/', $envContent, $matches)) {
        $encryptionKey = trim($matches[1]);
    }
} else {
    $encryptionKey = getenv('APP_ENCRYPTION_KEY') ?: $_ENV['APP_ENCRYPTION_KEY'] ?? '';
}

if (empty($encryptionKey)) {
    logResult('Encryption Key', 'ERROR', 'APP_ENCRYPTION_KEY is completely missing from environment variables or .env file.');
} elseif (strlen($encryptionKey) !== 32) {
    logResult('Encryption Key Length', 'ERROR', 'APP_ENCRYPTION_KEY must be exactly 32 characters long. Current length: ' . strlen($encryptionKey) . ' (Value hidden for security)');
} else {
    logResult('Encryption Key', 'OK', 'APP_ENCRYPTION_KEY is present and 32 characters long.');
}

// 3. Try to Bootstrap
try {
    require_once __DIR__ . '/../../../src/config/meetings_bootstrap.php';
    logResult('Bootstrap File Inclusion', 'OK', 'Successfully loaded meetings_bootstrap.php');
} catch (\Throwable $e) {
    logResult('Bootstrap File Inclusion', 'ERROR', 'Failed to load bootstrap: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    die('</ul><h3>Diagnostic stopped due to critical error.</h3>');
}

// 4. Check Database Connection
global $db;
if (!$db instanceof PDO) {
    logResult('Database Connection', 'ERROR', '$db is not a valid PDO instance.');
    die('</ul><h3>Diagnostic stopped due to critical error.</h3>');
} else {
    logResult('Database Connection', 'OK', 'Connected successfully.');
}

// 5. Check Required Tables
$tables = ['provider_accounts', 'meeting_settings', 'classrooms', 'class_sessions'];
foreach ($tables as $table) {
    try {
        $result = $db->query("SELECT 1 FROM `$table` LIMIT 1");
        if ($result !== false) {
            logResult("Database Table: `$table`", 'OK', 'Table exists and is readable.');
        } else {
            logResult("Database Table: `$table`", 'ERROR', 'Query failed. Table might not exist.');
        }
    } catch (\PDOException $e) {
        logResult("Database Table: `$table`", 'ERROR', 'Exception: ' . $e->getMessage());
    }
}

// 6. Test Controller Initialization
try {
    $container = new \App\Utils\Container();
    $container->set(PDO::class, function() use (&$db) { return $db; });
    $container->set(\App\Repositories\ProviderAccountRepository::class, fn($c) => new \App\Repositories\ProviderAccountRepository($c->get(PDO::class)));
    
    $repo = $container->get(\App\Repositories\ProviderAccountRepository::class);
    $controller = new \App\Controllers\Admin\AdminMeetingSettingsController($db, $repo);
    
    logResult('Controller Initialization', 'OK', 'Successfully initialized AdminMeetingSettingsController');
} catch (\Throwable $e) {
    logResult('Controller Initialization', 'ERROR', 'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    die('</ul><h3>Diagnostic stopped due to critical error.</h3>');
}

// 7. Test fetching accounts
try {
    $googleAccounts = $repo->findAllByProvider('google_meet');
    logResult('Fetch Provider Accounts', 'OK', 'Successfully fetched ' . count($googleAccounts) . ' Google Meet accounts.');
} catch (\Throwable $e) {
    logResult('Fetch Provider Accounts', 'ERROR', 'Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

echo "</ul>";
echo "<h3 style='color: green;'>Diagnostic completed. Please copy the output above and share it with the developer.</h3>";
