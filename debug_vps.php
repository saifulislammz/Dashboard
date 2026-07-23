<?php
/**
 * Run this script on your VPS to debug the "Unknown named parameter $provider" error.
 * You can run it via terminal: php debug_vps.php
 * Or access it via browser if placed in the public folder.
 */

// Adjust the path to autoload.php depending on where you place this file.
// If it's in the project root:
$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    // If it's in the public folder:
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
}

if (!file_exists($autoloadPath)) {
    die("autoload.php not found. Please place this file in the project root or public directory.\n");
}

require $autoloadPath;

echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n\n";

try {
    // 1. Check SessionDTO
    if (class_exists(\App\DTOs\SessionDTO::class)) {
        $ref = new ReflectionClass(\App\DTOs\SessionDTO::class);
        echo "=== SessionDTO Constructor Parameters ===\n";
        $hasProvider = false;
        foreach ($ref->getConstructor()->getParameters() as $param) {
            echo "- " . $param->getName() . "\n";
            if ($param->getName() === 'provider') {
                $hasProvider = true;
            }
        }
        if (!$hasProvider) {
            echo "\n[ERROR] The parameter 'provider' is MISSING in SessionDTO on this server!\n";
        }
    } else {
        echo "Class \App\DTOs\SessionDTO not found.\n";
    }

    echo "\n------------------------------------------------\n\n";

    // 2. Check MeetingResultDTO
    if (class_exists(\App\DTOs\MeetingResultDTO::class)) {
        $ref2 = new ReflectionClass(\App\DTOs\MeetingResultDTO::class);
        echo "=== MeetingResultDTO Constructor Parameters ===\n";
        $hasProvider2 = false;
        foreach ($ref2->getConstructor()->getParameters() as $param) {
            echo "- " . $param->getName() . "\n";
            if ($param->getName() === 'provider') {
                $hasProvider2 = true;
            }
        }
        if (!$hasProvider2) {
            echo "\n[ERROR] The parameter 'provider' is MISSING in MeetingResultDTO on this server!\n";
        }
    } else {
        echo "Class \App\DTOs\MeetingResultDTO not found.\n";
    }

    echo "\n================================================\n";
    echo "CONCLUSION:\n";
    if (isset($hasProvider) && !$hasProvider) {
        echo "The file src/DTOs/SessionDTO.php on your VPS is outdated.\n";
        echo "Please upload the latest version of this file to the VPS.\n";
    } elseif (isset($hasProvider2) && !$hasProvider2) {
        echo "The file src/DTOs/MeetingResultDTO.php on your VPS is outdated.\n";
        echo "Please upload the latest version of this file to the VPS.\n";
    } else {
        echo "The 'provider' parameter exists in both DTOs.\n";
        echo "If you still see the error, please check if there is an error stack trace in your logs, or let the AI know.\n";
    }
} catch (\Throwable $e) {
    echo "\nException Occurred while checking:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
