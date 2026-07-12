<?php
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/Repositories/InvoiceRepository.php';

try {
    $repo = new \App\Repositories\InvoiceRepository($db);
    
    $settings = [
        'institution_name' => 'Rahe Nazat Institute',
        'institution_tagline' => 'Excellence in Education',
        'institution_address' => '123 Education Road, Dhaka, Bangladesh',
        'institution_phone' => '+880 1XXX-XXXXXX',
        'institution_email' => 'info@institute.edu',
        'invoice_footer_note' => 'Thank you for your payment. Please retain this invoice for your records.',
        'institution_logo' => '/uploads/logo.png',
        'invoice_prefix' => 'INV',
    ];

    foreach ($settings as $key => $value) {
        $repo->saveSetting($key, $value);
    }
    
    echo "Database updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
