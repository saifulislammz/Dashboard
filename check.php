<?php
require 'src/config/database.php';
$stmt = $db->query('SELECT * FROM quizzes');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
