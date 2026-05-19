<?php
require_once __DIR__ . '/utils.php';
session_start();

if (!isset($_SESSION['user'])) {
    jsonResponse(['error' => 'Не авторизован'], 401);
}

$bookingsFile = __DIR__ . '/../data/bookings.json';
$bookings = readJson($bookingsFile);
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    jsonResponse($bookings);
}

if ($method === 'POST') {
    $day = isset($input['day']) ? (int)$input['day'] : null;
    $time = $input['time'] ?? null;
    $workshopId = $input['workshopId'] ?? null;

    if (!$day || !$time || !$workshopId) {
        jsonResponse(['error' => 'Не все поля заполнены'], 400);
    }

    // Проверка: одна запись на слот
    foreach ($bookings as $b) {
        if ($b['school'] === $_SESSION['user']['login'] && (int)$b['day'] === $day && $b['time'] === $time) {
            jsonResponse(['error' => 'Вы уже записаны на этот слот'], 400);
        }
    }

    // Получаем информацию о мастерклассе
    $workshops = readJson(__DIR__ . '/../data/workshops.json');
    $ws = null;
    foreach ($workshops['all'] as $w) {
        if ($w['id'] === $workshopId) {
            $ws = $w;
            break;
        }
    }
    if (!$ws) {
        jsonResponse(['error' => 'Мастеркласс не найден'], 404);
    }

    // Проверка лимита
    if ($ws['type'] === 'studio') {
        $count = 0;
        foreach ($bookings as $b) {
            if ((int)$b['day'] === $day && $b['time'] === $time && $b['workshopId'] === $workshopId) {
                $count++;
            }
        }
        if ($count >= $ws['maxSlots']) {
            jsonResponse(['error' => 'Мест нет'], 400);
        }
    }

    $booking = [
        'id' => uniqid(),
        'school' => $_SESSION['user']['login'],
        'schoolName' => $_SESSION['user']['name'],
        'day' => $day,
        'time' => $time,
        'workshopId' => $workshopId,
        'workshopName' => $ws['title'],
        'type' => $ws['type'],
        'bookedAt' => date('c')
    ];

    $bookings[] = $booking;
    writeJson($bookingsFile, $bookings);
    jsonResponse(['success' => true, 'booking' => $booking]);
}

if ($method === 'DELETE') {
    $id = $input['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'ID не указан'], 400);
    }

    $found = false;
    $bookings = array_filter($bookings, function($b) use ($id, &$found) {
        if ($b['id'] === $id) {
            $found = true;
            return false;
        }
        return true;
    });
    $bookings = array_values($bookings);

    if ($found) {
        writeJson($bookingsFile, $bookings);
        jsonResponse(['success' => true]);
    } else {
        jsonResponse(['error' => 'Запись не найдена'], 404);
    }
}

jsonResponse(['error' => 'Method not allowed'], 405);
?>
