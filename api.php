<?php
/**
 * REST API for PT KTU Logistik LPG Application
 * Uses JSON file as database (NO MySQL needed!)
 * Ready for Railway deployment
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// JSON file path - use /data volume on Railway for persistence
$dataDir = getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: __DIR__;
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
$jsonFile = $dataDir . '/data.json';

// Helper function: Load JSON data
function loadJSON($filePath) {
    if (!file_exists($filePath)) {
        return ['users' => [], 'sessions' => [], 'data' => []];
    }
    $content = file_get_contents($filePath);
    return $content ? json_decode($content, true) : ['users' => [], 'sessions' => [], 'data' => []];
}

// Helper function: Save JSON data
function saveJSON($filePath, $data) {
    // Use LOCK_EX to prevent concurrent write issues
    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

$response = ['ok' => false, 'msg' => ''];
$action = $_GET['action'] ?? '';

try {
    switch($action) {
        
        case 'direct_save':
            // Save data without session check (for bridge/API access)
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? 'admin');
            $jsonData = $input['data'] ?? [];
            $settingsData = $input['settings'] ?? [];
            $masterLokasiData = $input['masterLokasi'] ?? [];
            
            // Load and update users data in JSON file
            $store = loadJSON($jsonFile);
            if (!isset($store['users'][$username])) {
                $store['users'][$username] = ['created_at' => date('Y-m-d H:i:s')];
            }

            // Pastikan data_json selalu tersimpan
            $store['users'][$username]['data_json'] = $jsonData;
            if (!empty($settingsData)) {
                $store['users'][$username]['settings_json'] = $settingsData;
            }
            // Support both 'masterLokasi' and 'locations'
            $locs = !empty($masterLokasiData) ? $masterLokasiData : ($input['locations'] ?? []);
            if (!empty($locs)) {
                $store['users'][$username]['master_lokasi_json'] = $locs;
            }
            $isoNow = gmdate('Y-m-d\TH:i:s\Z');
            $store['users'][$username]['updated_at'] = $isoNow;
            
            if (saveJSON($jsonFile, $store)) {
                $response['ok'] = true;
                $response['saved_at'] = $isoNow;
            } else {
                throw new Exception('Failed to save JSON file');
            }
            break;
            
        case 'login':
            // Login user and set session (check from JSON file)
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            
            if (!$username || !$password) {
                $response['msg'] = 'Username dan password wajib diisi';
                break;
            }
            
            $store = loadJSON($jsonFile);
            
            if (!isset($store['users'][$username])) {
                // Auto-create admin user on first login with default password
                $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $store['users'][$username] = [
                    'password_hash' => $defaultPassword,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                saveJSON($jsonFile, $store);
            }
            
            // Verify password
            $storedHash = $store['users'][$username]['password_hash'] ?? '';
            if ($storedHash && !password_verify($password, $storedHash)) {
                $response['msg'] = 'Username atau password salah';
            } else {
                // Set session
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = $username;
                
                $response['ok'] = true;
                $response['data'] = ['id' => 1, 'username' => $username];
            }
            break;
            
        case 'logout':
            // Destroy session
            session_destroy();
            $response['ok'] = true;
            break;
            
        case 'me':
            // Get current logged-in user
            if (isset($_SESSION['user_id'])) {
                $response['ok'] = true;
                $response['data'] = [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username']
                ];
            }
            break;
            
        case 'load':
            // Load data for current user from JSON file
            if (!isset($_SESSION['user_id'])) {
                $response['msg'] = 'Unauthorized';
                http_response_code(401);
                break;
            }
            
            $store = loadJSON($jsonFile);
            $username = $_SESSION['username'];
            
            if (isset($store['users'][$username])) {
                $userData = $store['users'][$username];
                $response['ok'] = true;
                $response['data'] = [
                    'data' => $userData['data_json'] ?? [],
                    'settings' => $userData['settings_json'] ?? [],
                    'masterLokasi' => $userData['master_lokasi_json'] ?? []
                ];
            } else {
                // No data yet, return empty
                $response['ok'] = true;
                $response['data'] = [
                    'data' => [],
                    'settings' => [],
                    'masterLokasi' => []
                ];
            }
            break;
            
        case 'direct_load':
            // Load data without session check (for bridge/API access)
            $input = json_decode(file_get_contents('php://input'), true);
            $username = trim($input['username'] ?? 'admin');
            
            $store = loadJSON($jsonFile);
            
            if (isset($store['users'][$username])) {
                $userData = $store['users'][$username];
                $response['ok'] = true;
                $response['data'] = [
                    'data' => $userData['data_json'] ?? [],
                    'settings' => $userData['settings_json'] ?? [],
                    'masterLokasi' => $userData['master_lokasi_json'] ?? [],
                    'updated_at' => $userData['updated_at'] ?? null
                ];
            } else {
                $response['ok'] = true;
                $response['data'] = [
                    'data' => [],
                    'settings' => [],
                    'masterLokasi' => [],
                    'updated_at' => null
                ];
            }
            break;
            
        case 'create_user':
            // Create new user (admin only)
            if (!isset($_SESSION['user_id'])) {
                $response['msg'] = 'Unauthorized';
                http_response_code(401);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $newUsername = trim($input['username'] ?? '');
            $newPassword = $input['password'] ?? '';
            
            if (!$newUsername || !$newPassword) {
                $response['msg'] = 'Username dan password wajib diisi';
                break;
            }
            
            $store = loadJSON($jsonFile);
            
            if (isset($store['users'][$newUsername])) {
                $response['msg'] = 'Username sudah digunakan';
                http_response_code(409);
                break;
            }
            
            $store['users'][$newUsername] = [
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (saveJSON($jsonFile, $store)) {
                $response['ok'] = true;
                $response['data'] = [
                    'id' => count($store['users']),
                    'username' => $newUsername
                ];
            } else {
                $response['msg'] = 'Failed to create user';
                http_response_code(500);
            }
            break;
            
        case 'get_users':
            // Get list of all users
            if (!isset($_SESSION['user_id'])) {
                $response['msg'] = 'Unauthorized';
                http_response_code(401);
                break;
            }
            
            $store = loadJSON($jsonFile);
            $usersList = array_keys($store['users']);
            
            $response['ok'] = true;
            $response['data'] = $usersList;
            break;
            
        case 'backup_data':
            // Export all data for backup
            if (!isset($_SESSION['user_id'])) {
                $response['msg'] = 'Unauthorized';
                http_response_code(401);
                break;
            }
            
            $store = loadJSON($jsonFile);
            $response['ok'] = true;
            $response['data'] = $store;
            $response['backup_at'] = date('Y-m-d H:i:s');
            break;
            
        case 'restore_data':
            // Restore data from backup
            if (!isset($_SESSION['user_id'])) {
                $response['msg'] = 'Unauthorized';
                http_response_code(401);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $backupData = $input['backup'] ?? null;
            
            if (!$backupData) {
                $response['msg'] = 'No backup data provided';
                http_response_code(400);
                break;
            }
            
            if (saveJSON($jsonFile, $backupData)) {
                $response['ok'] = true;
                $response['restored_at'] = date('Y-m-d H:i:s');
            } else {
                $response['msg'] = 'Failed to restore backup';
                http_response_code(500);
            }
            break;
            
        default:
            $response['msg'] = 'Invalid action: ' . $action;
            http_response_code(400);
            break;
    }
    
} catch (Exception $e) {
    $response['msg'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
