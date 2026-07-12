<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../../src/Services/UserService.php';

// Only admins can access this script
requireRole(ROLE_ADMIN);

$repository = new \App\Repositories\UserRepository($db);
$service = new \App\Services\UserService($repository, $auth);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['count'])) {
    $count = (int)$_POST['count'];
    $role = (int)$_POST['role'];
    
    if ($count > 0 && $count <= 5000) {
        $successCount = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $randomString = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 5);
            $randomNum = rand(10000, 99999);
            
            $name = ucfirst($randomString) . ' ' . $randomNum;
            $email = "{$randomString}{$randomNum}@dummy.com";
            
            $data = [
                'name' => $name,
                'email' => $email,
                'password' => $email, // Using email as password
                'confirm_password' => $email,
                'status' => 0
            ];
            
            try {
                $service->createUser($data, $role);
                $successCount++;
            } catch (Exception $e) {
                // Skip if duplicate or error
                error_log("Dummy generation error: " . $e->getMessage());
            }
        }
        $message = "Successfully created {$successCount} dummy accounts!";
    } else {
        $error = "Please enter a valid number (1-5000).";
    }
}

$pageTitle = 'Generate Dummy Accounts';
require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="flex h-screen overflow-hidden">
    <?php require_once __DIR__ . '/../../views/layouts/components/admin_sidebar.php'; ?>
    
    <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden bg-bgBody">
        <main class="w-full grow p-6">
            <div class="max-w-3xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-brandText">Generate Dummy Accounts</h1>
                    <p class="text-sm text-gray-500">Quickly create random accounts for testing.</p>
                </div>

                <?php if ($message): ?>
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="bg-cardBg rounded-2xl shadow-sm border border-borderBase p-6">
                    <form method="POST" action="">
                        <div class="space-y-6">
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                                <select name="role" id="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                                    <option value="<?php echo ROLE_STUDENT; ?>">Student</option>
                                    <option value="<?php echo ROLE_TEACHER; ?>">Teacher</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="count" class="block text-sm font-medium text-gray-700 mb-1">Number of Accounts</label>
                                <input type="number" name="count" id="count" min="1" max="5000" value="500" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                                <p class="text-xs text-gray-500 mt-1">Maximum 5000 at a time.</p>
                            </div>
                            
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-sm text-yellow-800">
                                    <strong>Note:</strong> 
                                    - Names will be randomly generated (e.g., Abcde 12345).<br>
                                    - Emails will be randomly generated (e.g., abcde12345@dummy.com).<br>
                                    - <strong>Password will be the exact same as the email address.</strong>
                                </p>
                            </div>

                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-primaryHover transition-colors">
                                Generate Accounts
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layouts/footer.php'; ?>
