<?php
require_once __DIR__ . '/utils.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    jsonResponse(['error' => 'Forbidden'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
$bookings = $input['bookings'] ?? [];

$bookingsFile = __DIR__ . '/../data/bookings.json';
writeJson($bookingsFile, $bookings);
jsonResponse(['success' => true, 'count' => count($bookings)]);
?>
