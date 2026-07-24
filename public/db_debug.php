<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== DIAGNOSTIC SCRIPT START ===\n\n";

echo "1. Checking Environment Variables...\n";
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    echo "- .env file found.\n";
    $envContent = file_get_contents($envPath);
    if (preg_match('/DB_DATABASE=(.*)/', $envContent, $matches)) {
        echo "- DB_DATABASE in .env: " . trim($matches[1]) . "\n";
    }
} else {
    echo "- .env file NOT found.\n";
}

echo "\n2. Loading Database Connection (using app config)...\n";
try {
    // Include the actual app database connection
    require_once __DIR__ . '/../src/config/database.php';
    echo "- Database Connection Successful.\n\n";

    // Test PDO attributes and caching
    echo "3. Testing Server & Caching Variables...\n";
    $isPersistent = $db->getAttribute(\PDO::ATTR_PERSISTENT);
    echo "- PDO ATTR_PERSISTENT: " . ($isPersistent ? "true" : "false") . "\n";
    echo "- PHP SAPI: " . php_sapi_name() . "\n";
    
    if (function_exists('opcache_get_status')) {
        $opcache = opcache_get_status(false);
        echo "- OPcache enabled: " . ($opcache && $opcache['opcache_enabled'] ? "Yes" : "No") . "\n";
    } else {
        echo "- OPcache: Not installed/disabled.\n";
    }

    echo "\n4. Checking 'quiz_attempts' Table Schema...\n";
    $stmt = $db->query("DESCRIBE quiz_attempts");
    $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    $hasVoiceNote = false;
    echo "- Columns found in 'quiz_attempts':\n";
    foreach ($columns as $col) {
        echo "  -> " . $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'voice_note') {
            $hasVoiceNote = true;
        }
    }

    echo "\n";
    if ($hasVoiceNote) {
        echo "✅ RESULT: 'voice_note' column EXISTS in the table according to MySQL.\n";
        echo "If you still get an error, it is 100% a caching issue. (Restart PHP-FPM or Nginx/Apache on your server).\n";
    } else {
        echo "❌ RESULT: 'voice_note' column is MISSING in this specific database/table.\n";
        echo "This means the database your app is connecting to does NOT have the column.\n";
    }

} catch (\Exception $e) {
    echo "\n❌ EXCEPTION CAUGHT: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSTIC SCRIPT END ===\n";
echo "\nPlease copy this entire text and paste it to me in the chat.";
