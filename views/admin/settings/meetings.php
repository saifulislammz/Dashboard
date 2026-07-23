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

                    <?php if (!empty($googleAccounts)): ?>
                        <div class="space-y-4">
                            <?php foreach ($googleAccounts as $account): ?>
                                <div class="bg-<?= $account['is_connected'] ? 'green' : 'yellow' ?>-50 border border-<?= $account['is_connected'] ? 'green' : 'yellow' ?>-200 rounded-lg p-5 relative">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($account['nickname'] ?? 'Unnamed Account') ?></h3>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Status: <strong class="text-<?= $account['is_connected'] ? 'green' : 'yellow' ?>-700"><?= $account['is_connected'] ? 'Connected' : 'Not Connected' ?></strong>
                                            </p>
                                            <?php if ($account['is_connected']): ?>
                                                <p class="text-sm text-gray-500">Email: <strong><?= htmlspecialchars($account['account_email'] ?? '') ?></strong></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex gap-2">
                                            <?php if ($account['is_connected']): ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="disconnect_account">
                                                    <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                                                    <button type="submit" onclick="return confirm('Disconnect this account?')" class="text-sm font-medium text-yellow-600 hover:text-yellow-500 bg-white border border-yellow-200 py-1.5 px-3 rounded-md shadow-sm">Disconnect</button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" onclick="document.getElementById('edit-google-<?= $account['id'] ?>').classList.toggle('hidden')" class="text-sm font-medium text-primary hover:text-primary/80 bg-white border border-gray-200 py-1.5 px-3 rounded-md shadow-sm">Edit</button>
                                            <form action="" method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete_account">
                                                <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                                                <button type="submit" onclick="return confirm('Permanently delete this account?')" class="text-sm font-medium text-red-600 hover:text-red-500 bg-white border border-red-200 py-1.5 px-3 rounded-md shadow-sm">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div id="edit-google-<?= $account['id'] ?>" class="hidden mt-4 pt-4 border-t border-gray-200">
                                        <form action="" method="POST" class="space-y-4">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                            <input type="hidden" name="action" value="update_google">
                                            <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                                            
                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700">Nickname</label>
                                                    <input type="text" name="nickname" value="<?= htmlspecialchars($account['nickname'] ?? '') ?>" placeholder="e.g. Science Dept" class="mt-1.5 px-3 py-2 border-2 border-gray-300 focus:border-primary block w-full sm:text-sm rounded-md">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700">Client ID</label>
                                                    <input type="text" name="google_client_id" value="<?= htmlspecialchars($account['client_id'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-gray-300 focus:border-primary block w-full sm:text-sm rounded-md">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700">Client Secret</label>
                                                    <input type="password" name="google_client_secret" value="<?= htmlspecialchars($account['client_secret'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-gray-300 focus:border-primary block w-full sm:text-sm rounded-md">
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <button type="submit" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Save</button>
                                                <?php if (!empty($account['client_id']) && isset($googleAuthUrls[$account['id']])): ?>
                                                    <a href="<?= htmlspecialchars($googleAuthUrls[$account['id']]) ?>" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                                        <?= $account['is_connected'] ? 'Reconnect' : 'Connect Account' ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-6">
                        <button type="button" onclick="document.getElementById('add-google-form').classList.toggle('hidden')" class="inline-flex items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
                            + Add Google Account
                        </button>
                    </div>

                    <div id="add-google-form" class="hidden mt-6 bg-white border border-gray-200 rounded-lg p-5">
                        <h3 class="text-md font-medium text-gray-900 mb-4">Add New Google Account</h3>
                        <form action="" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                            <input type="hidden" name="action" value="add_google">
                            
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Nickname</label>
                                    <input type="text" name="nickname" placeholder="e.g. Main School Account" required class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Client ID</label>
                                    <input type="text" name="google_client_id" required class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Client Secret</label>
                                    <input type="password" name="google_client_secret" required class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                            </div>

                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-4">
                                <p class="text-sm text-blue-700"><strong>Redirect URI:</strong> Ensure this is added in Google Cloud Console:</p>
                                <code class="mt-2 block bg-white p-2 rounded text-xs"><?= htmlspecialchars($googleRedirectUri) ?></code>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Create Slot
                                </button>
                            </div>
                        </form>
                    </div>
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

                    <?php if (!empty($zoomAccounts)): ?>
                        <div class="space-y-4">
                            <?php foreach ($zoomAccounts as $account): ?>
                                <div class="bg-<?= $account['is_connected'] ? 'green' : 'yellow' ?>-50 border border-<?= $account['is_connected'] ? 'green' : 'yellow' ?>-200 rounded-lg p-5 relative">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($account['nickname'] ?? 'Unnamed Account') ?></h3>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Status: <strong class="text-<?= $account['is_connected'] ? 'green' : 'yellow' ?>-700"><?= $account['is_connected'] ? 'Connected' : 'Not Connected' ?></strong>
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <?php if ($account['is_connected']): ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                    <input type="hidden" name="action" value="disconnect_account">
                                                    <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                                                    <button type="submit" onclick="return confirm('Disconnect this account?')" class="text-sm font-medium text-yellow-600 hover:text-yellow-500 bg-white border border-yellow-200 py-1.5 px-3 rounded-md shadow-sm">Disconnect</button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" onclick="document.getElementById('edit-zoom-<?= $account['id'] ?>').classList.toggle('hidden')" class="text-sm font-medium text-primary hover:text-primary/80 bg-white border border-gray-200 py-1.5 px-3 rounded-md shadow-sm">Edit</button>
                                            <form action="" method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete_account">
                                                <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                                                <button type="submit" onclick="return confirm('Permanently delete this account?')" class="text-sm font-medium text-red-600 hover:text-red-500 bg-white border border-red-200 py-1.5 px-3 rounded-md shadow-sm">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div id="edit-zoom-<?= $account['id'] ?>" class="hidden mt-4 pt-4 border-t border-gray-200">
                                        <form action="" method="POST" class="space-y-4">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                            <input type="hidden" name="action" value="update_zoom">
                                            <input type="hidden" name="account_id" value="<?= $account['id'] ?>">
                                            
                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700">Nickname</label>
                                                    <input type="text" name="nickname" value="<?= htmlspecialchars($account['nickname'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-gray-300 focus:border-primary block w-full sm:text-sm rounded-md">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700">Account ID</label>
                                                    <input type="text" name="zoom_account_id" value="<?= htmlspecialchars($account['zoom_account_id'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-gray-300 focus:border-primary block w-full sm:text-sm rounded-md">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700">Client ID</label>
                                                    <input type="text" name="zoom_client_id" value="<?= htmlspecialchars($account['client_id'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-gray-300 focus:border-primary block w-full sm:text-sm rounded-md">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700">Client Secret</label>
                                                    <input type="password" name="zoom_client_secret" value="<?= htmlspecialchars($account['client_secret'] ?? '') ?>" class="mt-1.5 px-3 py-2 border-2 border-gray-300 focus:border-primary block w-full sm:text-sm rounded-md">
                                                </div>
                                            </div>

                                            <div>
                                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                                                    Save & Test Zoom Connection
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-6">
                        <button type="button" onclick="document.getElementById('add-zoom-form').classList.toggle('hidden')" class="inline-flex items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
                            + Add Zoom Account
                        </button>
                    </div>

                    <div id="add-zoom-form" class="hidden mt-6 bg-white border border-gray-200 rounded-lg p-5">
                        <h3 class="text-md font-medium text-gray-900 mb-4">Add New Zoom Account</h3>
                        <form action="" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                            <input type="hidden" name="action" value="add_zoom">
                            
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Nickname</label>
                                    <input type="text" name="nickname" required placeholder="e.g. Finance Zoom" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Account ID</label>
                                    <input type="text" name="zoom_account_id" required class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Client ID</label>
                                    <input type="text" name="zoom_client_id" required class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Client Secret</label>
                                    <input type="password" name="zoom_client_secret" required class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none">
                                    Save & Connect
                                </button>
                            </div>
                        </form>
                    </div>
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

                            <div class="sm:col-span-2">
                                <label for="join_open_minutes_before" class="block text-sm font-medium text-gray-700">Join Window (minutes before start)</label>
                                <input type="number" name="join_open_minutes_before" id="join_open_minutes_before" value="<?= htmlspecialchars($settings['join_open_minutes_before']) ?>" class="mt-1.5 px-3 py-2 border-2 border-primary shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm rounded-md">
                                <p class="mt-1 text-xs text-gray-500">Students can click 'Join' this many minutes before the session starts.</p>
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

