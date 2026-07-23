<?php
/**
 * ============================================================
 * debug_check.php — "File not found" Root Cause Finder
 * URL: https://portal.rahenazatinstitute.com/debug_check.php
 * ⚠️ সমস্যা সমাধানের পর DELETE করুন!
 * ============================================================
 */
error_reporting(E_ALL);
ini_set('display_errors', 0); // HTML-এ দেখাবো নিজেই

$checks = [];

function ok($label, $detail)  { global $checks; $checks[] = ['s'=>'ok',   'label'=>$label, 'detail'=>$detail]; }
function fail($label, $detail){ global $checks; $checks[] = ['s'=>'fail', 'label'=>$label, 'detail'=>$detail]; }
function warn($label, $detail){ global $checks; $checks[] = ['s'=>'warn', 'label'=>$label, 'detail'=>$detail]; }
function info($label, $detail){ global $checks; $checks[] = ['s'=>'info', 'label'=>$label, 'detail'=>$detail]; }

// ============================================================
// ১. NGINX ROOT — সবচেয়ে বড় সমস্যা কোথায়?
// ============================================================
$docRoot      = $_SERVER['DOCUMENT_ROOT']   ?? 'N/A';
$scriptFile   = $_SERVER['SCRIPT_FILENAME'] ?? 'N/A';
$requestUri   = $_SERVER['REQUEST_URI']     ?? 'N/A';

info('DOCUMENT_ROOT (Nginx root)',    $docRoot);
info('SCRIPT_FILENAME (PHP file)',    $scriptFile);
info('REQUEST_URI',                   $requestUri);

// ফাইলটা কোথায় আছে সেটা চেক
$expectedAdminDash1 = $docRoot . '/admin/dashboard.php';           // যদি root=public/
$expectedAdminDash2 = $docRoot . '/public/admin/dashboard.php';    // যদি root=/var/www/html

if (file_exists($expectedAdminDash1)) {
    ok('admin/dashboard.php পাওয়া গেছে', $expectedAdminDash1 . ' ✓ EXISTS');
} else {
    fail('admin/dashboard.php পাওয়া যায়নি', $expectedAdminDash1 . ' — MISSING!');
}

if (file_exists($expectedAdminDash2)) {
    warn('public/ prefixed path এও আছে', $expectedAdminDash2 . ' (Root হয়তো /var/www/html, public/ নয়)');
}

// আসল project root কোথায়?
$possibleRoots = [
    '/var/www/html/public',
    '/var/www/html',
    '/app/public',
    '/app',
];
foreach ($possibleRoots as $r) {
    $exists = is_dir($r);
    $hasAdmin = file_exists("$r/admin/dashboard.php");
    if ($exists) {
        info("Dir exists: $r", ($hasAdmin ? '✅ admin/dashboard.php আছে' : '❌ admin/dashboard.php নেই'));
    }
}

// ============================================================
// ২. HTTPS & PROXY HEADERS
// ============================================================
$https    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$fwdProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'NOT SET';
$fwdHost  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? 'NOT SET';
$port     = $_SERVER['SERVER_PORT'] ?? '?';

info('HTTPS Status', ($https ? 'HTTPS=on' : 'HTTPS=off') . " | X-Forwarded-Proto=$fwdProto | Port=$port");

// Dokploy/Traefik সাধারণত X-Forwarded-Proto পাঠায়
$isRealHttps = $https || $fwdProto === 'https';
if ($isRealHttps) {
    ok('Site is HTTPS', "Protocol: " . ($https ? 'Direct HTTPS' : "Via proxy ($fwdProto)"));
} else {
    warn('Site NOT HTTPS', "cookie_secure=1 থাকলে session কাজ করবে না — http দিয়ে চলছে");
}

// ============================================================
// ৩. SESSION TEST
// ============================================================
// security.php load না করে raw session test
$cookieParams = session_get_cookie_params();
info('Session Cookie Params (current php.ini)',
    "secure=" . ($cookieParams['secure'] ? 'true' : 'false') .
    " | httponly=" . ($cookieParams['httponly'] ? 'true' : 'false') .
    " | samesite=" . ($cookieParams['samesite'] ?? 'N/A') .
    " | path=" . $cookieParams['path']
);

// আমরা session start করবো নিজেরা
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$_SESSION['__diag'] = 'test_' . time();

if (!empty(session_id())) {
    ok('Session start', 'ID=' . session_id() . ' | __diag=' . $_SESSION['__diag']);
} else {
    fail('Session start', 'Session ID নেই — session_start() ব্যর্থ হয়েছে');
}

// Session save path writable?
$savePath = session_save_path() ?: '/tmp';
if (is_writable($savePath)) {
    ok('Session save path writable', $savePath);
} else {
    fail('Session save path NOT writable', $savePath . ' — sessions save হচ্ছে না');
}

// ============================================================
// ৪. DATABASE & AUTH
// ============================================================
$base = dirname(__FILE__, 2); // /var/www/html (public এর parent)

$autoload = $base . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    fail('vendor/autoload.php', $autoload . ' — MISSING! composer install হয়নি');
} else {
    ok('vendor/autoload.php', $autoload);
    
    try {
        require_once $autoload;

        // .env load
        $envFile = $base . '/.env';
        if (file_exists($envFile)) {
            ok('.env file', $envFile);
            $dotenv = Dotenv\Dotenv::createImmutable($base);
            $dotenv->safeLoad();
        } else {
            fail('.env file', $envFile . ' — MISSING! Dokploy environment variables ব্যবহার হচ্ছে?');
        }

        $dbHost = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?? '127.0.0.1';
        $dbPort = $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?? '3306';
        $dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? '';
        $dbUser = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? '';
        $dbPass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';

        info('DB Config', "Host=$dbHost:$dbPort | DB=$dbName | User=$dbUser | Pass=" . (empty($dbPass) ? '❌ EMPTY' : '✓ SET'));

        if (empty($dbName) || empty($dbUser)) {
            fail('DB Credentials missing', 'DB_DATABASE বা DB_USERNAME খালি — Dokploy-এ env variable সেট করুন');
        } else {
            $db = new PDO(
                "mysql:dbname={$dbName};host={$dbHost};port={$dbPort};charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            ok('Database connected', "MySQL $dbHost:$dbPort/$dbName");

            // Admin user আছে?
            // Delight Auth role_id 64 = ADMIN
            $stmt = $db->query(
                "SELECT u.id, u.email FROM users u 
                 JOIN users_roles ur ON u.id = ur.user_id 
                 WHERE ur.role_id = 64 LIMIT 3"
            );
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($admins)) {
                fail('Admin user নেই', 'users_roles তে role_id=64 কোনো user নেই — /setup_admin.php চালান');
            } else {
                ok('Admin users found', implode(', ', array_column($admins, 'email')));
            }

            // Auth state check
            $auth = new \Delight\Auth\Auth($db);
            if ($auth->isLoggedIn()) {
                $email   = $auth->getEmail();
                $uid     = $auth->getUserId();
                $isAdmin = $auth->hasRole(\Delight\Auth\Role::ADMIN);
                ok('Currently logged in as', "$email (ID=$uid)");
                if ($isAdmin) {
                    ok('Admin role', "✅ ROLE_ADMIN আছে — redirect /admin/dashboard.php হবে");
                } else {
                    fail('Admin role নেই', "$email এর ADMIN role নেই — dashboard দেখতে পাবে না");
                }
            } else {
                warn('Not logged in', 'এই URL-এ visit করলে session থাকে না — login করে তারপর এই page visit করুন');
            }

        }
    } catch (PDOException $e) {
        fail('DB Connection Error', $e->getMessage());
    } catch (Exception $e) {
        fail('PHP Error', $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine());
    }
}

// ============================================================
// ৫. REDIRECT TARGET FILE EXISTS?
// ============================================================
// AuthMiddleware.php এ redirect করে: header("Location: /admin/dashboard.php")
// Nginx root যদি /var/www/html/public হয়, তাহলে খুঁজবে /var/www/html/public/admin/dashboard.php
// Nginx root যদি /var/www/html হয়, তাহলে খুঁজবে /var/www/html/admin/dashboard.php → FILE NOT FOUND!

$nginxRoot = $docRoot; // PHP কে nginx যা বলেছে
$redirectTarget = $nginxRoot . '/admin/dashboard.php';

if (file_exists($redirectTarget)) {
    ok('Redirect target exists', "header(Location: /admin/dashboard.php) → $redirectTarget ✓");
} else {
    fail(
        '❌ ROOT CAUSE: Redirect target নেই',
        "header(Location: /admin/dashboard.php) → $nginxRoot/admin/dashboard.php → FILE NOT FOUND!\n" .
        "Nginx root হওয়া উচিত: /var/www/html/public\n" .
        "কিন্তু এখন: $nginxRoot"
    );
    // সঠিক path কোথায়?
    $correctPath = '/var/www/html/public/admin/dashboard.php';
    if (file_exists($correctPath)) {
        warn('সঠিক file এখানে আছে', $correctPath . ' — Nginx-এর root ঠিক করতে হবে');
    }
}

// ============================================================
// HTML OUTPUT
// ============================================================
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>🔍 Debug — File not found কেন?</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Consolas,'Courier New',monospace;background:#0d1117;color:#c9d1d9;padding:20px;font-size:14px}
h1{color:#58a6ff;margin-bottom:4px;font-size:20px}
.sub{color:#6e7681;margin-bottom:20px;font-size:12px}
.row{display:flex;gap:10px;align-items:flex-start;padding:8px 12px;border-radius:6px;margin-bottom:6px;border-left:4px solid transparent}
.row.ok  {background:#0d2218;border-color:#3fb950}
.row.fail{background:#2d0f0f;border-color:#f85149}
.row.warn{background:#2d1f00;border-color:#e3b341}
.row.info{background:#0d1b2e;border-color:#388bfd}
.icon{font-size:16px;flex-shrink:0;width:22px;text-align:center}
.label{color:#e6edf3;font-weight:bold;flex-shrink:0;min-width:260px}
.detail{color:#8b949e;word-break:break-all;white-space:pre-wrap}
.row.fail .detail{color:#ffa198}
.row.warn .detail{color:#d29922}
.rootcause{background:#3d0000;border:2px solid #f85149;border-radius:8px;padding:16px;margin-bottom:20px}
.rootcause h2{color:#f85149;margin-bottom:8px}
.rootcause p{color:#ffa198;line-height:1.7;font-size:13px}
.footer{margin-top:20px;padding:12px;background:#161b22;border-radius:6px;color:#6e7681;font-size:12px}
</style>
</head>
<body>
<h1>🔍 "File not found" Root Cause Diagnostic</h1>
<p class="sub">Generated: <?= date('Y-m-d H:i:s') ?> | <?= php_uname('n') ?> | PHP <?= PHP_VERSION ?></p>

<?php
$fails = array_filter($checks, fn($c) => $c['s'] === 'fail');
if (!empty($fails)):
?>
<div class="rootcause">
  <h2>❌ সমস্যা পাওয়া গেছে:</h2>
  <?php foreach ($fails as $f): ?>
  <p><b>• <?= htmlspecialchars($f['label']) ?>:</b><br><?= nl2br(htmlspecialchars($f['detail'])) ?></p><br>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php foreach ($checks as $c): ?>
<div class="row <?= $c['s'] ?>">
  <span class="icon"><?= ['ok'=>'✅','fail'=>'❌','warn'=>'⚠️','info'=>'ℹ️'][$c['s']] ?></span>
  <span class="label"><?= htmlspecialchars($c['label']) ?></span>
  <span class="detail"><?= nl2br(htmlspecialchars($c['detail'])) ?></span>
</div>
<?php endforeach; ?>

<div class="footer">
  ⚠️ <b>Security:</b> এই ফাইলটি সমস্যা সমাধানের পর DELETE করুন →
  <code>rm /var/www/html/public/debug_check.php</code>
</div>
</body>
</html>
