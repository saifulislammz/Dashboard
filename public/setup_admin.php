<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/config/roles.php';

try {
    $email = 'admin@example.com';
    $password = 'password';
    $username = 'Super Admin';

    // Create user without email verification
    $userId = $auth->admin()->createUser($email, $password, $username);

    // Assign ADMIN role
    $auth->admin()->addRoleForUserById($userId, \App\Config\Roles::ADMIN);

    echo "<h3>Admin created successfully!</h3>";
    echo "Email: <strong>" . htmlspecialchars($email) . "</strong><br>";
    echo "Password: <strong>" . htmlspecialchars($password) . "</strong><br>";
    echo "<br><p style='color:red;'>⚠️ Please DELETE this file (setup_admin.php) after you login successfully for security reasons.</p>";
    echo "<a href='index.php'>Go to Login</a>";

} catch (\Delight\Auth\UserAlreadyExistsException $e) {
    echo "User already exists! Please go to <a href='index.php'>Login</a>.";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
