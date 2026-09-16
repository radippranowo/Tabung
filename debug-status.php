<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataDir = getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: __DIR__;
$jsonFile = $dataDir . '/data.json';

echo json_encode([
    'dataDir' => $dataDir,
    'jsonFile' => $jsonFile,
    'dirExists' => is_dir($dataDir),
    'isWritable' => is_writable($dataDir),
    'fileExists' => file_exists($jsonFile),
    'fileSize' => file_exists($jsonFile) ? filesize($jsonFile) : 0,
    'env_volume' => getenv('RAILWAY_VOLUME_MOUNT_PATH'),
    'server_cwd' => getcwd(),
    'all_env' => array_keys($_ENV)
], JSON_PRETTY_PRINT);
