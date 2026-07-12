<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$success = $_GET['success'] ?? $success ?? '';
$error   = $_GET['error'] ?? $error ?? '';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Meeting Integrations</h1>
        </div>

        <?php if (!empty($error)): ?>
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button onclick="switchTab('google')" id="tab-google" class="border-primary text-primary whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                        Google Meet
                    </button>
                    <button onclick="switchTab('zoom')" id="tab-zoom" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                        Zoom
                    </button>
                    <button onclick="switchTab('global')" id="tab-global" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                        Global Settings
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- TAB 1: Google Meet -->
                <div id="content-google" class="space-y-6">
                    <div class="flex items-center gap-4 border-b border-gray-100 pb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-medium text-gray-900">Google Workspace Connection</h2>
                            <p class="text-sm text-gray-500">Connect a Google account to automatically generate Google Meet links for live sessions.</p>
                        </div>
                    </div>

                    <?php if ($googleAccount && $googleAccount['is_connected']): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-green-800">Status: Connected</h3>
                                    <p class="mt-1 text-sm text-green-700">Account: <strong><?= htmlspecialchars($googleAccount['account_email'] ?? '') ?></strong></p>
                                </div>
                                <form action="" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="disconnect_google">
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500 bg-white border border-red-200 py-1.5 px-3 rounded-md shadow-sm">Disconnect</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <form action="" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                            <input type="hidden" name="action" value="save_google">
                            
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="google_client_id" class="block text-sm font-medium text-gray-700">Client ID</label>
                                    <input type="text" name="google_client_id" id="google_client_id" value="<?= htmlspecialchars($googleAccount['client_id'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="google_client_secret" class="block text-sm font-medium text-gray-700">Client Secret</label>
                                    <input type="password" name="google_client_secret" id="google_client_secret" value="<?= htmlspecialchars($googleAccount['client_secret'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                            </div>

                            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                                <p class="text-sm text-green-700"><strong>Redirect URI:</strong> Set this exactly in Google Cloud Console:</p>
                                <code class="mt-2 block bg-white p-2 rounded text-xs"><?= htmlspecialchars($this->getGoogleRedirectUri()) ?></code>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                                    Save Credentials
                                </button>
                                <?php if (!empty($googleAccount['client_id'])): ?>
                                    <a href="<?= htmlspecialchars($this->getGoogleAuthUrl($googleAccount['client_id'])) ?>" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition-colors">
                                        Connect Google Account
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: Zoom -->
                <div id="content-zoom" class="hidden space-y-6">
                    <div class="flex items-center gap-4 border-b border-gray-100 pb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-medium text-gray-900">Zoom Server-to-Server App</h2>
                            <p class="text-sm text-gray-500">Connect a Zoom app to automatically generate Zoom meeting links.</p>
                        </div>
                    </div>

                    <?php if ($zoomAccount && $zoomAccount['is_connected']): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-green-800">Status: Connected</h3>
                                    <p class="mt-1 text-sm text-green-700">Token is actively caching and valid.</p>
                                </div>
                                <form action="" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="disconnect_zoom">
                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500 bg-white border border-red-200 py-1.5 px-3 rounded-md shadow-sm">Disconnect</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                        <input type="hidden" name="action" value="save_zoom">
                        
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="zoom_account_id" class="block text-sm font-medium text-gray-700">Account ID</label>
                                <input type="text" name="zoom_account_id" id="zoom_account_id" value="<?= htmlspecialchars($zoomAccount['zoom_account_id'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="zoom_client_id" class="block text-sm font-medium text-gray-700">Client ID</label>
                                <input type="text" name="zoom_client_id" id="zoom_client_id" value="<?= htmlspecialchars($zoomAccount['client_id'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="zoom_client_secret" class="block text-sm font-medium text-gray-700">Client Secret</label>
                                <input type="password" name="zoom_client_secret" id="zoom_client_secret" value="<?= htmlspecialchars($zoomAccount['client_secret'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition-colors">
                                Save & Test Zoom Connection
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 3: Global Settings -->
                <div id="content-global" class="hidden space-y-6">
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                        <input type="hidden" name="action" value="save_settings">

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="default_provider" class="block text-sm font-medium text-gray-700">Default Meeting Provider</label>
                                <select id="default_provider" name="default_provider" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                    <option value="zoom" <?= ($settings['default_provider'] === 'zoom') ? 'selected' : '' ?>>Zoom</option>
                                    <option value="google_meet" <?= ($settings['default_provider'] === 'google_meet') ? 'selected' : '' ?>>Google Meet</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="default_timezone" class="block text-sm font-medium text-gray-700">System Default Timezone</label>
                                <select id="default_timezone" name="default_timezone" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                    <option value="Asia/Dhaka" <?= ($settings['default_timezone'] === 'Asia/Dhaka') ? 'selected' : '' ?>>Asia/Dhaka</option>
                                    <?php foreach (\DateTimeZone::listIdentifiers() as $tz): if ($tz === 'Asia/Dhaka') continue; ?>
                                        <option value="<?= $tz ?>" <?= ($settings['default_timezone'] === $tz) ? 'selected' : '' ?>><?= $tz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="join_open_minutes_before" class="block text-sm font-medium text-gray-700">Join Window (minutes before start)</label>
                                <input type="number" name="join_open_minutes_before" id="join_open_minutes_before" value="<?= htmlspecialchars($settings['join_open_minutes_before']) ?>" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                <p class="mt-1 text-xs text-gray-500">Students can click 'Join' this many minutes before the session starts.</p>
                            </div>

                            <div>
                                <label for="expose_direct_link" class="block text-sm font-medium text-gray-700">Expose Direct Join Link</label>
                                <select id="expose_direct_link" name="expose_direct_link" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                    <option value="0" <?= ($settings['expose_direct_link'] === '0') ? 'selected' : '' ?>>No (Use Secure Redirect Only)</option>
                                    <option value="1" <?= ($settings['expose_direct_link'] === '1') ? 'selected' : '' ?>>Yes (Show Raw Provider URL)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Secure redirect prevents link sharing.</p>
                            </div>

                            <div class="sm:col-span-2 border-t border-gray-200 pt-4 mt-2">
                                <h4 class="text-sm font-medium text-gray-900 mb-4">Future Readiness Features</h4>
                                
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="hidden" name="recording_sync_enabled" value="0">
                                            <input id="recording_sync_enabled" name="recording_sync_enabled" type="checkbox" value="1" <?= ($settings['recording_sync_enabled'] === '1') ? 'checked' : '' ?> class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="recording_sync_enabled" class="font-medium text-gray-700">Enable Cloud Recording Sync</label>
                                            <p class="text-gray-500">Allows the system to save recording URLs via provider webhook.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="hidden" name="attendance_sync_enabled" value="0">
                                            <input id="attendance_sync_enabled" name="attendance_sync_enabled" type="checkbox" value="1" <?= ($settings['attendance_sync_enabled'] === '1') ? 'checked' : '' ?> class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="attendance_sync_enabled" class="font-medium text-gray-700">Enable Automated Attendance</label>
                                            <p class="text-gray-500">Sync participant join/leave times automatically.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5 border-t border-gray-200 flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none transition-colors">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function switchTab(tabId) {
        // Reset all tabs
        ['google', 'zoom', 'global'].forEach(id => {
            const btn = document.getElementById('tab-' + id);
            const content = document.getElementById('content-' + id);
            
            btn.className = "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors";
            content.classList.add('hidden');
        });

        // Activate selected tab
        const activeBtn = document.getElementById('tab-' + tabId);
        activeBtn.className = "border-primary text-primary whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors";
        document.getElementById('content-' + tabId).classList.remove('hidden');
    }
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

