<?php
require_once __DIR__ . '/utils.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    jsonResponse(['error' => 'Forbidden'], 403);
}

$workshopsFile = __DIR__ . '/../data/workshops.json';
$workshops = readJson($workshopsFile);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = $workshops['settings'] ?? [
        'studioEnabled' => true,
        'outboundItEnabled' => true,
        'outboundArtEnabled' => true
    ];
    jsonResponse($settings);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $settings = [
        'studioEnabled' => $input['studioEnabled'] ?? ($workshops['settings']['studioEnabled'] ?? true),
        'outboundItEnabled' => $input['outboundItEnabled'] ?? ($workshops['settings']['outboundItEnabled'] ?? true),
        'outboundArtEnabled' => $input['outboundArtEnabled'] ?? ($workshops['settings']['outboundArtEnabled'] ?? true)
    ];
    $workshops['settings'] = $settings;
    writeJson($workshopsFile, $workshops);
    jsonResponse(['success' => true, 'settings' => $settings]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
?>
