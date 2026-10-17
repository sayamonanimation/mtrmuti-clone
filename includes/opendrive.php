<?php
/**
 * OpenDrive storage for admin uploads.
 *
 * Enabled when config/config.local.php defines OD_USERNAME and OD_PASSWORD.
 * Files go to one OpenDrive folder per site section, e.g.
 *   mtrmuti_web_uploads/teachers, mtrmuti_web_uploads/sliders, ...
 * under the account root (or under OD_ROOT_FOLDER_ID if defined), and the
 * public direct link is what gets saved in the database.
 *
 * The transport (dev.opendrive.com host, browser-like headers, 8MB chunks)
 * follows the client already proven in production on rmutimt_gallery.
 *
 * @see https://dev.opendrive.com/api
 */

const OPENDRIVE_ROOT_FOLDER_NAME = 'mtrmuti_web_uploads';

/** Section folders used by the admin upload forms (handle_*_upload subdirs). */
const OPENDRIVE_SECTION_FOLDERS = [
    'teachers' => 'รูปอาจารย์',
    'teacher-portfolio' => 'รูปผลงานอาจารย์',
    'sliders' => 'สไลด์หน้าแรก',
    'posts' => 'ข่าวประชาสัมพันธ์/กิจกรรม',
    'gallery' => 'แกลอรี่ภาพ',
    'students' => 'รูปนักศึกษาปัจจุบัน',
    'alumni' => 'รูปศิษย์เก่า',
    'classrooms' => 'รูปห้องเรียน',
    'student-works' => 'ผลงานนักศึกษา',
    'fields' => 'รายวิชาที่เปิดสอน',
    'branding' => 'โลโก้และแบนเนอร์',
    'documents' => 'เอกสาร PDF',
    'general' => 'อื่น ๆ',
];

function opendrive_enabled() {
    return defined('OD_USERNAME') && defined('OD_PASSWORD') && OD_USERNAME !== '' && OD_PASSWORD !== '';
}

/**
 * Uploads a local file into the section folder $subdir and returns its public
 * direct link. Throws RuntimeException on any API failure.
 */
function opendrive_store_file($localPath, $ext, $subdir) {
    $client = new OpenDriveClient(OD_USERNAME, OD_PASSWORD);
    $folderId = opendrive_section_folder_id($client, $subdir !== '' ? $subdir : 'general');
    $fileName = bin2hex(random_bytes(8)) . '.' . $ext;
    return $client->uploadFile($localPath, $fileName, $folderId);
}

/**
 * Creates (or finds) the root folder and every section folder up front.
 * Returns [section => folder id].
 */
function opendrive_prepare_all_folders() {
    $client = new OpenDriveClient(OD_USERNAME, OD_PASSWORD);
    $ids = [];
    foreach (array_keys(OPENDRIVE_SECTION_FOLDERS) as $section) {
        $ids[$section] = opendrive_section_folder_id($client, $section);
    }
    return $ids;
}

/** [table, id column, path column] for every column that stores an upload path. */
const OPENDRIVE_PATH_COLUMNS = [
    ['sliders', 'id', 'image_path'],
    ['posts', 'id', 'image_path'],
    ['fields_of_study', 'id', 'icon_path'],
    ['teachers', 'id', 'photo_path'],
    ['teacher_portfolio', 'id', 'image_path'],
    ['gallery', 'id', 'image_path'],
    ['alumni_photos', 'id', 'image_path'],
    ['student_photos', 'id', 'image_path'],
    ['student_works', 'id', 'image_path'],
    ['classrooms', 'id', 'image_path'],
    ['site_settings', 'setting_key', 'setting_value'],
];

/** Uploads that still live on this server: list of [table, idCol, pathCol, id, path]. */
function opendrive_local_files(PDO $pdo) {
    $found = [];
    foreach (OPENDRIVE_PATH_COLUMNS as [$table, $idCol, $pathCol]) {
        $stmt = $pdo->prepare("SELECT $idCol AS id, $pathCol AS path FROM $table WHERE $pathCol LIKE ?");
        $stmt->execute([UPLOAD_URL . '/%']);
        foreach ($stmt->fetchAll() as $row) {
            $found[] = [$table, $idCol, $pathCol, $row['id'], $row['path']];
        }
    }
    return $found;
}

/**
 * Moves up to $limit server-stored uploads to OpenDrive and points the
 * database at the new links. The local file is kept as a backup.
 * Returns ['moved' => n, 'failed' => [messages], 'remaining' => n].
 */
function opendrive_migrate_local_files(PDO $pdo, $limit = 15) {
    $client = new OpenDriveClient(OD_USERNAME, OD_PASSWORD);
    $files = opendrive_local_files($pdo);
    $moved = 0;
    $failed = [];
    foreach (array_slice($files, 0, $limit) as [$table, $idCol, $pathCol, $id, $path]) {
        $relative = substr($path, strlen(UPLOAD_URL) + 1);
        $localPath = UPLOAD_DIR . '/' . $relative;
        if (strpos($relative, '..') !== false || !is_file($localPath)) {
            $failed[] = $path . ' (ไม่พบไฟล์บนเซิร์ฟเวอร์)';
            continue;
        }
        $section = strpos($relative, '/') !== false ? strstr($relative, '/', true) : 'general';
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        try {
            $folderId = opendrive_section_folder_id($client, $section);
            $link = $client->uploadFile($localPath, bin2hex(random_bytes(8)) . '.' . $ext, $folderId);
            $stmt = $pdo->prepare("UPDATE $table SET $pathCol = ? WHERE $idCol = ? AND $pathCol = ?");
            $stmt->execute([$link, $id, $path]);
            $moved++;
        } catch (Throwable $e) {
            $failed[] = $path . ' (' . $e->getMessage() . ')';
        }
    }
    return ['moved' => $moved, 'failed' => $failed, 'remaining' => count(opendrive_local_files($pdo))];
}

function opendrive_cached_folders() {
    $cacheFile = SITE_ROOT . '/storage/opendrive_folders.json';
    return is_file($cacheFile) ? (json_decode((string) file_get_contents($cacheFile), true) ?: []) : [];
}

/**
 * Folder IDs are cached in storage/opendrive_folders.json so each upload
 * doesn't have to list folders on OpenDrive first.
 */
function opendrive_section_folder_id(OpenDriveClient $client, $subdir) {
    $cacheFile = SITE_ROOT . '/storage/opendrive_folders.json';
    $cache = is_file($cacheFile) ? (json_decode((string) file_get_contents($cacheFile), true) ?: []) : [];

    if (empty($cache['_root'])) {
        $parent = defined('OD_ROOT_FOLDER_ID') && OD_ROOT_FOLDER_ID !== '' ? OD_ROOT_FOLDER_ID : '0';
        $cache['_root'] = $client->getOrCreateFolder(OPENDRIVE_ROOT_FOLDER_NAME, $parent);
    }
    $key = trim(str_replace(['/', '\\'], '-', $subdir), '-');
    if (empty($cache[$key])) {
        $cache[$key] = $client->getOrCreateFolder($key, $cache['_root']);
    }
    @file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT), LOCK_EX);
    return $cache[$key];
}

final class OpenDriveClient
{
    private const API_BASE = 'https://dev.opendrive.com/api/v1';
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    private const CHUNK_SIZE = 8 * 1024 * 1024;
    private const TIMEOUT = 600;

    private $username;
    private $password;
    private $sessionId = '';

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function login() {
        if ($this->sessionId !== '') {
            return $this->sessionId;
        }
        $data = $this->request('POST', '/session/login.json', json_encode([
            'username' => $this->username,
            'passwd' => $this->password,
            'password' => $this->password,
        ]), 'json');
        $this->sessionId = (string) ($data['SessionID'] ?? '');
        if ($this->sessionId === '') {
            throw new RuntimeException('OpenDrive: login failed');
        }
        return $this->sessionId;
    }

    public function getOrCreateFolder($name, $parentId) {
        $sid = $this->login();
        $list = $this->request('GET', '/folder/list.json/' . rawurlencode($sid) . '/' . rawurlencode($parentId));
        foreach (($list['Folders'] ?? []) as $folder) {
            if (strcasecmp((string) ($folder['Name'] ?? ''), $name) === 0 && !empty($folder['FolderID'])) {
                return (string) $folder['FolderID'];
            }
        }
        $created = $this->request('POST', '/folder.json', http_build_query([
            'session_id' => $sid,
            'folder_name' => $name,
            'folder_sub_parent' => $parentId,
            'folder_is_public' => '1',
        ]));
        $id = (string) ($created['FolderID'] ?? '');
        if ($id === '') {
            throw new RuntimeException('OpenDrive: could not create folder ' . $name);
        }
        return $id;
    }

    /** Returns the public direct link of the uploaded file. */
    public function uploadFile($path, $fileName, $folderId) {
        @set_time_limit(self::TIMEOUT);
        $sid = $this->login();
        $size = filesize($path);
        $hash = md5_file($path);

        $created = $this->request('POST', '/upload/create_file.json', http_build_query([
            'session_id' => $sid,
            'folder_id' => $folderId,
            'file_name' => $fileName,
            'file_size' => (string) $size,
            'file_hash' => $hash,
        ]));
        $fileId = (string) ($created['FileId'] ?? '');
        if ($fileId === '') {
            throw new RuntimeException('OpenDrive: create_file failed');
        }

        $tempLocation = '';
        if ((int) ($created['RequireHashOnly'] ?? 0) !== 1) {
            $opened = $this->request('POST', '/upload/open_file_upload.json', http_build_query([
                'session_id' => $sid,
                'file_id' => $fileId,
                'file_size' => (string) $size,
                'file_hash' => $hash,
            ]));
            $tempLocation = (string) ($opened['TempLocation'] ?? '');
            if ($tempLocation === '') {
                throw new RuntimeException('OpenDrive: open_file_upload failed');
            }
            $this->sendChunks($path, $sid, $fileId, $tempLocation);
        }

        $closed = $this->request('POST', '/upload/close_file_upload.json', http_build_query([
            'session_id' => $sid,
            'file_id' => $fileId,
            'file_size' => (string) $size,
            'temp_location' => $tempLocation,
            'file_hash' => $hash,
        ]));
        $finalId = (string) ($closed['FileId'] ?? $fileId);
        $this->makePublic($sid, $finalId);

        foreach (['DirectLinkPublick', 'DirectLinkPublic', 'DownloadLink', 'Link'] as $key) {
            $link = trim((string) ($closed[$key] ?? ''));
            if (preg_match('#^https?://#i', $link)) {
                return $link;
            }
        }
        throw new RuntimeException('OpenDrive: no public link returned');
    }

    private function sendChunks($path, $sid, $fileId, $tempLocation) {
        $in = fopen($path, 'rb');
        $offset = 0;
        try {
            while (!feof($in)) {
                $piece = fread($in, self::CHUNK_SIZE);
                if ($piece === false || $piece === '') {
                    break;
                }
                $chunkPath = opendrive_temp_dir() . '/od_chunk_' . bin2hex(random_bytes(6));
                file_put_contents($chunkPath, $piece);
                try {
                    $this->request('POST', '/upload/upload_file_chunk.json', [
                        'session_id' => $sid,
                        'file_id' => $fileId,
                        'temp_location' => $tempLocation,
                        'chunk_offset' => (string) $offset,
                        'chunk_size' => (string) strlen($piece),
                        'file_data' => new CURLFile($chunkPath, 'application/octet-stream', 'chunk.bin'),
                    ], 'multipart');
                } finally {
                    @unlink($chunkPath);
                }
                $offset += strlen($piece);
            }
        } finally {
            fclose($in);
        }
    }

    private function makePublic($sid, $fileId) {
        try {
            $this->request('POST', '/permissions/set_file_access.json', http_build_query([
                'session_id' => $sid,
                'file_id' => $fileId,
                'access_file' => '1',
                'public_access' => '1',
            ]));
        } catch (RuntimeException $e) {
            // Files in a public folder are already reachable; this call is best effort.
        }
    }

    /** @param string|array $body string for form/json, array for multipart */
    private function request($method, $path, $body = null, $mode = 'form') {
        // Same browser-like headers as rmutimt_gallery: the API sits behind
        // Cloudflare, which is stricter with requests that look like bots.
        $headers = [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: th-TH,th;q=0.9,en-US;q=0.8,en;q=0.7',
            'Origin: https://www.opendrive.com',
            'Referer: https://www.opendrive.com/',
            'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile: ?0',
            'Sec-Ch-Ua-Platform: "Windows"',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-site',
        ];
        if ($mode === 'json') {
            $headers[] = 'Content-Type: application/json';
        } elseif ($mode === 'form' && $body !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $base = defined('OD_API_BASE') && OD_API_BASE !== '' ? rtrim(OD_API_BASE, '/') : self::API_BASE;
        $ch = curl_init($base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_ENCODING => '',
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $out = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($out === false) {
            throw new RuntimeException('OpenDrive: ' . $err);
        }
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('OpenDrive HTTP ' . $code . ': ' . substr((string) $out, 0, 300));
        }
        $data = json_decode((string) $out, true);
        return is_array($data) ? $data : [];
    }
}

function opendrive_temp_dir() {
    $dir = SITE_ROOT . '/storage/temp';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return is_dir($dir) && is_writable($dir) ? $dir : sys_get_temp_dir();
}
