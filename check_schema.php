<?php
require __DIR__.'/src/config/database.php';
$stmt = $db->query('DESCRIBE users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
