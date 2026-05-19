<?php
require_once __DIR__ . '/utils.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    jsonResponse(['error' => 'Forbidden'], 403);
}

$bookingsFile = __DIR__ . '/../data/bookings.json';
writeJson($bookingsFile, []);
jsonResponse(['success' => true]);
?>
