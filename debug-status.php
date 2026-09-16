<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataDir = getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: __DIR__;
$jsonFile = $dataDir . '/data.json';
$seedFile = __DIR__ . '/data.json';

$store = [];
$adminInfo = null;
if (file_exists($jsonFile)) {
    $raw = file_get_contents($jsonFile);
    $store = $raw ? json_decode($raw, true) : [];
    if (isset($store['users']['admin'])) {
        $u = $store['users']['admin'];
        $dj = $u['data_json'] ?? [];
        $adminInfo = [
            'updated_at' => $u['updated_at'] ?? null,
            'kembali_count' => count($dj['kembali'] ?? []),
            'keluar_count'  => count($dj['keluar']  ?? []),
            'tabung_count'  => count($dj['tabung']  ?? []),
            'refill_count'  => count($dj['refill']  ?? []),
            'aset_count'    => count($dj['aset']    ?? []),
        ];
    }
}

echo json_encode([
    'volume_env'  => getenv('RAILWAY_VOLUME_MOUNT_PATH'),
    'dataDir'     => $dataDir,
    'jsonFile'    => $jsonFile,
    'dirExists'   => is_dir($dataDir),
    'isWritable'  => is_writable($dataDir),
    'fileExists'  => file_exists($jsonFile),
    'fileSize'    => file_exists($jsonFile) ? filesize($jsonFile) : 0,
    'seedExists'  => file_exists($seedFile),
    'usingVolume' => ($dataDir !== __DIR__),
    'admin'       => $adminInfo,
    'server_cwd'  => getcwd(),
], JSON_PRETTY_PRINT);
