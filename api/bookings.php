<?php
require_once __DIR__ . '/utils.php';
session_start();

if (!isset($_SESSION['user'])) {
    jsonResponse(['error' => 'Не авторизован'], 401);
}

$bookingsFile = __DIR__ . '/../data/bookings.json';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Файл-блокировка для предотвращения race condition
$lockFile = __DIR__ . '/../data/.bookings.lock';
$lockFp = fopen($lockFile, 'c');
if ($lockFp) {
    flock($lockFp, LOCK_EX);
}

$bookings = readJson($bookingsFile);

if ($method === 'GET') {
    if ($lockFp) {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
    jsonResponse($bookings);
}

if ($method === 'POST') {
    // Только школы могут создавать записи
    if ($_SESSION['user']['role'] !== 'school') {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'Только школы могут записываться на мастерклассы'], 403);
    }

    $day = isset($input['day']) ? (int)$input['day'] : null;
    $time = $input['time'] ?? null;
    $workshopId = $input['workshopId'] ?? null;

    if (!$day || !$time || !$workshopId) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'Не все поля заполнены'], 400);
    }

    // Загружаем расписание для валидации
    $workshops = readJson(__DIR__ . '/../data/workshops.json');
    $schedule = $workshops['schedule'] ?? null;
    if (!$schedule || !isset($workshops['all'])) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'Ошибка загрузки расписания'], 500);
    }

    $phase1Dates = $schedule['phase1Dates'] ?? [];
    $phase2Dates = $schedule['phase2Dates'] ?? [];
    $phase1Slot = $schedule['phase1Slot'] ?? [];
    $phase2Slots = $schedule['phase2Slots'] ?? [];

    $isPhase1 = in_array($day, $phase1Dates, true);
    $isPhase2 = in_array($day, $phase2Dates, true);
    if (!$isPhase1 && !$isPhase2) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'Недопустимая дата'], 400);
    }

    $validTimes = $isPhase1 ? $phase1Slot : $phase2Slots;
    if (!in_array($time, $validTimes, true)) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'Недопустимое время'], 400);
    }

    // Проверка: одна запись на слот
    foreach ($bookings as $b) {
        if ($b['school'] === $_SESSION['user']['login'] && (int)$b['day'] === $day && $b['time'] === $time) {
            if ($lockFp) {
                flock($lockFp, LOCK_UN);
                fclose($lockFp);
            }
            jsonResponse(['error' => 'Вы уже записаны на этот слот'], 400);
        }
    }

    // Получаем информацию о мастерклассе
    $ws = null;
    foreach ($workshops['all'] as $w) {
        if ($w['id'] === $workshopId) {
            $ws = $w;
            break;
        }
    }
    if (!$ws) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
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
            if ($lockFp) {
                flock($lockFp, LOCK_UN);
                fclose($lockFp);
            }
            jsonResponse(['error' => 'Мест нет'], 400);
        }
    }

    $booking = [
        'id' => bin2hex(random_bytes(8)),
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

    if ($lockFp) {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
    jsonResponse(['success' => true, 'booking' => $booking]);
}

if ($method === 'DELETE') {
    $id = $input['id'] ?? null;
    if (!$id) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'ID не указан'], 400);
    }

    $target = null;
    foreach ($bookings as $b) {
        if ($b['id'] === $id) {
            $target = $b;
            break;
        }
    }

    if (!$target) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'Запись не найдена'], 404);
    }

    $userRole = $_SESSION['user']['role'];
    $userLogin = $_SESSION['user']['login'];
    if ($userRole !== 'admin' && $target['school'] !== $userLogin) {
        if ($lockFp) {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
        jsonResponse(['error' => 'Нет прав для удаления этой записи'], 403);
    }

    $bookings = array_values(array_filter($bookings, function($b) use ($id) {
        return $b['id'] !== $id;
    }));
    writeJson($bookingsFile, $bookings);

    if ($lockFp) {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
    jsonResponse(['success' => true]);
}

if ($lockFp) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
}
jsonResponse(['error' => 'Method not allowed'], 405);
?>
