<?php
require_once __DIR__ . '/utils.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    jsonResponse(['error' => 'Forbidden'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
$bookings = $input['bookings'] ?? null;

if (!is_array($bookings)) {
    jsonResponse(['error' => 'Неверный формат: ожидается массив bookings'], 400);
}

// Базовая валидация каждой записи
$requiredFields = ['id', 'school', 'schoolName', 'day', 'time', 'workshopId', 'workshopName', 'type', 'bookedAt'];
foreach ($bookings as $index => $b) {
    foreach ($requiredFields as $field) {
        if (!isset($b[$field])) {
            jsonResponse(['error' => "Ошибка в записи #{$index}: отсутствует поле {$field}"], 400);
        }
    }
    if (!is_int($b['day']) && !ctype_digit((string)$b['day'])) {
        jsonResponse(['error' => "Ошибка в записи #{$index}: поле day должно быть числом"], 400);
    }
}

$bookingsFile = __DIR__ . '/../data/bookings.json';
writeJson($bookingsFile, $bookings);
jsonResponse(['success' => true, 'count' => count($bookings)]);
?>
