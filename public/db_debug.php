<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== AUTO-FIX SCRIPT START ===\n\n";

echo "1. Loading Database Connection...\n";
try {
    require_once __DIR__ . '/../src/config/database.php';
    echo "- Connection Successful.\n\n";

    echo "2. Checking 'quiz_attempts' table for 'voice_note' column...\n";
    $stmt = $db->query("SHOW COLUMNS FROM quiz_attempts LIKE 'voice_note'");
    $exists = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($exists) {
        echo "- 'voice_note' column already EXISTS. No fix needed.\n";
    } else {
        echo "- 'voice_note' column is MISSING. Running ALTER TABLE...\n\n";

        $db->exec("ALTER TABLE quiz_attempts ADD COLUMN voice_note TEXT NULL AFTER voice_reviewed");

        // Verify it was added
        $verify = $db->query("SHOW COLUMNS FROM quiz_attempts LIKE 'voice_note'")->fetch(\PDO::FETCH_ASSOC);
        if ($verify) {
            echo "✅ SUCCESS: 'voice_note' column has been added successfully!\n";
            echo "   Field: " . $verify['Field'] . "\n";
            echo "   Type:  " . $verify['Type'] . "\n";
            echo "   Null:  " . $verify['Null'] . "\n\n";
            echo ">>> Your application error should now be FIXED.\n";
            echo ">>> Please delete this file from the server after confirming the fix!\n";
        } else {
            echo "❌ FAILED: ALTER TABLE ran but column still not found. Please check DB user permissions.\n";
        }
    }

    echo "\n3. Current 'quiz_attempts' column list (after fix):\n";
    $cols = $db->query("DESCRIBE quiz_attempts")->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  -> " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n=== AUTO-FIX SCRIPT END ===\n";
