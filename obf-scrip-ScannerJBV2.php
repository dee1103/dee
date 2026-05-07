<?php
/**
 * PHP Malware Scanner V2 - Premium Edition
 * Features: High-performance scanning, Gold/Black Premium UI, Bulk Delete, Enhanced Patterns
 */

error_reporting(0);
set_time_limit(0);

$base = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();

// --- VIEW CODE ROUTER ---
if (isset($_GET['view_code'])) {
    $fileToView = realpath($_GET['view_code']);
    if ($fileToView && strpos($fileToView, $base) === 0 && is_file($fileToView)) {
        echo "<!DOCTYPE html><html style='background:#000;color:#fff;font-family:monospace;padding:20px;'><head><title>Viewing: ".htmlspecialchars(basename($fileToView))."</title></head><body>";
        echo "<h2 style='color:#FFD700;border-bottom:1px solid #333;padding-bottom:10px;'>Source Code: ".htmlspecialchars($fileToView)."</h2>";
        highlight_file($fileToView);
        echo "</body></html>";
        exit;
    } else {
        die("Unauthorized or invalid file path.");
    }
}

// Handle Selective Deletion
$deleteStatus = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_files']) && is_array($_POST['delete_files'])) {
    $deletedCount = 0;
    foreach ($_POST['delete_files'] as $file) {
        $realFile = realpath($file);
        // Security check: ensure file is within or below the base directory
        if ($realFile && strpos($realFile, $base) === 0 && is_file($realFile)) {
            if (@unlink($realFile)) {
                $deletedCount++;
            }
        }
    }
    $deleteStatus = "Successfully deleted $deletedCount file(s).";
}

$patterns = [
    '/eval\s*\(/i',
    '/base64_decode\s*\(/i',
    '/gzinflate\s*\(/i',
    '/assert\s*\(/i',
    '/preg_replace\s*\(.*\/e/i',
    '/(system|shell_exec|passthru|exec)\s*\(/i',
    '/php:\/\/input/i',
    '/curl_(init|exec|setopt)/i',
    '/file_get_contents\s*\(/i',
    '/fopen\s*\(/i',
    '/stream_get_contents/i',
    '/include\s*\(\s*\$[a-z0-9_]+\s*\)/i',
    '/require\s*\(\s*\$[a-z0-9_]+\s*\)/i',
    '/["\']\s*\.\s*["\']/i',
    '/md5\s*\(\s*\$_SERVER\s*\[\s*[\'"]HTTP_HOST[\'"]\s*\]/i',
    '/\/dev\/shm\//i',
    '/raw\.githubusercontent\.com/i',
    '/goto\s+[a-z0-9_]+/i',
    '/str_rot13/i',
    '/strrev/i',
];

function is_shell($code, $patterns) {
    $score = 0;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $code)) {
            $score++;
        }
    }
    return $score >= 3;
}

function snippet_preview($code, $match) {
    $pos = stripos($code, $match);
    return $pos === false ? '' : substr($code, max(0, $pos - 50), 300);
}

function scan_folder($dir, $patterns, &$results, $base) {
    $items = @scandir($dir);
    if (!$items) return;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            scan_folder($path, $patterns, $results, $base);
        } elseif (preg_match('/\.(php|phtml)$/i', $item) && filesize($path) < 2000000) {
            $code = @file_get_contents($path);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $code, $match) && is_shell($code, $patterns)) {
                    $results[] = [
                        'file'    => $path,
                        'match'   => $match[0],
                        'snippet' => snippet_preview($code, $match[0]),
                    ];
                    break;
                }
            }
        }
    }
}

$results = [];
scan_folder($base, $patterns, $results, $base);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner JBV2 | Premium Security</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #FFD700;
            --dark-gold: #B8860B;
            --black: #000000;
            --charcoal: #121212;
            --glass: rgba(20, 20, 20, 0.9);
            --red: #ff5252;
            --green: #00ff00;
        }

        body {
            background: radial-gradient(circle at center, #1a1a1a 0%, #000000 100%);
            color: #eee;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 0 20px;
        }

        header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 4px;
            font-size: 2.5em;
            margin: 0;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
        }

        .subtitle {
            font-size: 0.9em;
            color: #888;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .status-bar {
            background: var(--glass);
            border: 1px solid rgba(255, 215, 0, 0.2);
            padding: 15px 25px;
            border-radius: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .path-info {
            font-family: monospace;
            font-size: 0.9em;
            color: var(--gold);
        }

        .actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 50px;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8em;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            border: none;
            text-transform: uppercase;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--gold), var(--dark-gold));
            color: black;
            box-shadow: 0 5px 15px rgba(218, 165, 32, 0.4);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(218, 165, 32, 0.6);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
        }

        .btn-outline:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .btn-red {
            background: linear-gradient(135deg, #ff5252, #b71c1c);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 82, 82, 0.3);
        }

        .btn-red:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 82, 82, 0.5);
        }

        .btn-coding {
            background: #1a1a1a;
            border: 1px solid #444;
            color: #0f0;
            padding: 5px 12px;
            font-size: 10px;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-coding:hover {
            background: #333;
            border-color: #0f0;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.2);
        }

        .result-card {
            background: var(--glass);
            border: 1px solid rgba(255, 215, 0, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            position: relative;
            transition: 0.3s;
            overflow: hidden;
        }

        .result-card:hover {
            border-color: rgba(255,215, 0, 0.3);
            background: rgba(30, 30, 30, 0.95);
        }

        .result-card.viewed {
            border-color: var(--green);
            background: rgba(0, 255, 0, 0.08);
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.2);
        }

        .viewed-badge {
            display: none;
            position: absolute;
            top: 15px;
            right: 180px;
            background: var(--green);
            color: black;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 10px;
            border-radius: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 0 10px var(--green);
            animation: pulse-green 2s infinite;
        }

        .result-card.viewed .viewed-badge {
            display: block;
        }

        @keyframes pulse-green {
            0% { opacity: 0.7; }
            50% { opacity: 1; }
            100% { opacity: 0.7; }
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .file-info {
            flex-grow: 1;
        }

        .file-path {
            display: block;
            color: var(--gold);
            font-weight: bold;
            font-size: 1em;
            margin-bottom: 5px;
            word-break: break-all;
            text-decoration: none;
            transition: 0.3s;
        }

        .file-path:hover {
            color: white;
            text-shadow: 0 0 10px var(--gold);
        }

        .pattern-match {
            font-size: 0.8em;
            color: #888;
        }

        .pattern-match span {
            color: var(--red);
            font-family: monospace;
            font-weight: bold;
        }

        pre {
            background: #000;
            padding: 15px;
            border-radius: 10px;
            font-size: 0.85em;
            color: #0f0;
            overflow-x: auto;
            border: 1px solid #333;
            margin: 15px 0 0 0;
        }

        .checkbox-container {
            margin-left: 20px;
        }

        input[type="checkbox"] {
            accent-color: var(--gold);
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .no-results {
            text-align: center;
            padding: 100px 0;
            color: var(--green);
            font-size: 1.2em;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--gold);
            color: black;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: bold;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            z-index: 1000;
            display: <?= $deleteStatus ? 'block' : 'none' ?>;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #555;
            font-size: 0.8em;
            padding-bottom: 50px;
        }

        /* Responsive */
        @media (max-width: 600px) {
            h1 { font-size: 1.5em; }
            .status-bar { flex-direction: column; border-radius: 20px; gap: 15px; }
            .actions { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="notification" id="notif">
        <?= htmlspecialchars($deleteStatus) ?>
    </div>

    <div class="container">
        <header>
            <h1>Scanner <span style="color:#fff">V2</span></h1>
            <div class="subtitle">Premium Script Malware Detection</div>
        </header>

        <form method="POST" id="scan-form">
            <div class="status-bar">
                <div class="path-info">
                     DIRECTORY: <?= htmlspecialchars($base) ?>
                </div>
                <div class="actions">
                    <button type="button" class="btn btn-outline" id="select-all">Select All</button>
                    <button type="submit" name="bulk_delete" class="btn btn-red" onclick="return confirmDeletes()">Delete Selected</button>
                    <a href="?path=<?= urlencode($base) ?>" class="btn btn-gold">Refresh Scan</a>
                </div>
            </div>

            <?php if (empty($results)): ?>
                <div class="no-results">
                    🛡️ Clean! No suspicious patterns detected in this directory.
                </div>
            <?php else: ?>
                <div class="results-list">
                    <?php foreach ($results as $index => $r): ?>
                        <div class="result-card" data-path="<?= htmlspecialchars($r['file']) ?>">
                            <div class="viewed-badge">CHECKED</div>
                            <div class="card-header">
                                <div class="file-info">
                                     <a href="<?= htmlspecialchars(str_replace($_SERVER['DOCUMENT_ROOT'], '', $r['file'])) ?>" 
                                        class="file-path" target="_blank" title="Klik untuk membuka file di latar belakang"
                                        onclick="markAsViewed(this.closest('.result-card'), this.href, event)">
                                        <?= htmlspecialchars($r['file']) ?>
                                    </a>
                                    <button type="button" class="btn-coding" onclick="viewSourceCode('<?= addslashes($r['file']) ?>')">
                                        📄 CODING
                                    </button>
                                    <div class="pattern-match">
                                        Matched: <span><?= htmlspecialchars($r['match']) ?></span>
                                    </div>
                                </div>
                                <div class="checkbox-container" style="display: flex; align-items: center; gap: 12px; min-width: 140px; justify-content: flex-end;">
                                    <button type="submit" name="delete_files[]" value="<?= htmlspecialchars($r['file']) ?>" 
                                            class="btn btn-red" style="padding: 8px 18px; font-size: 11px; white-space: nowrap;"
                                            onclick="return confirm('Hapus file ini permanently?')">
                                        🗑️ HAPUS
                                    </button>
                                    <input type="checkbox" name="delete_files[]" value="<?= htmlspecialchars($r['file']) ?>" class="file-cb">
                                </div>
                            </div>
                            <pre><code><?= htmlspecialchars(trim($r['snippet'])) ?></code></pre>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </form>

        <div class="footer">
            &copy; 2026 Premium Scanner V2 &bull; Professional Security Tools
        </div>
    </div>

    <script>
        // Select All Handler
        document.getElementById('select-all').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.file-cb');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            this.textContent = allChecked ? 'Select All' : 'Deselect All';
        });

        function markAsViewed(card, url, event) {
            if (event) event.preventDefault();
            
            const path = card.getAttribute('data-path');
            let viewed = JSON.parse(localStorage.getItem('scanned_viewed_v2') || '[]');
            if (!viewed.includes(path)) {
                viewed.push(path);
                localStorage.setItem('scanned_viewed_v2', JSON.stringify(viewed));
            }
            card.classList.add('viewed');

            // Open as a separate Popup Window at the LEFT SIDE
            const popupWidth = 1000;
            const popupHeight = 800;
            const left = 20; // Position at the left edge
            const top = 50;
            
            const newWin = window.open(url, 'ScannerViewer', 
                `width=${popupWidth},height=${popupHeight},left=${left},top=${top},scrollbars=yes,resizable=yes`
            );

            if (newWin) {
                // Focus the popup but allow the parent to stay active
                newWin.focus();
                // We keep the scanner focused by slightly delaying refocusing if needed, 
                // but usually a popup is what users want for "outside" feel.
                setTimeout(() => {
                    window.focus(); 
                }, 200);
            }
        }

        function viewSourceCode(filePath) {
            const url = '?view_code=' + encodeURIComponent(filePath);
            const popupWidth = 1100;
            const popupHeight = 900;
            const left = 20; // Position at the left edge
            const top = 50;
            
            window.open(url, 'CodeViewer_' + btoa(filePath).substring(0,10), 
                `width=${popupWidth},height=${popupHeight},left=${left},top=${top},scrollbars=yes,resizable=yes`
            );
        }

        // Apply viewed status on load
        document.addEventListener('DOMContentLoaded', () => {
            const viewed = JSON.parse(localStorage.getItem('scanned_viewed_v2') || '[]');
            document.querySelectorAll('.result-card').forEach(card => {
                const path = card.getAttribute('data-path');
                if (viewed.includes(path)) {
                    card.classList.add('viewed');
                }
            });
        });

        // Confirmation Modal (Simple)
        function confirmDeletes() {
            const checked = document.querySelectorAll('.file-cb:checked').length;
            if (checked === 0) {
                alert('Please select at least one file to delete.');
                return false;
            }
            return confirm(`Are you sure you want to permanently delete ${checked} suspicious file(s)?`);
        }

        // Auto hide notification
        setTimeout(() => {
            const notif = document.getElementById('notif');
            if (notif) notif.style.display = 'none';
        }, 5000);
    </script>
</body>
</html>
