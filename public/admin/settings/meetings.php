<?php
require_once __DIR__ . '/../../../src/config/meetings_bootstrap.php';

requireRole(ROLE_ADMIN);

use App\Controllers\Admin\AdminMeetingSettingsController;

$controller = new AdminMeetingSettingsController($db, $container->get(App\Repositories\ProviderAccountRepository::class));
$controller->index();


