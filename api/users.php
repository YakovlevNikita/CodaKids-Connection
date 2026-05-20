<?php
require_once __DIR__ . '/utils.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    jsonResponse(['error' => 'Доступ запрещён'], 403);
}

$usersFile = __DIR__ . '/../data/users.json';
$users = readJson($usersFile);
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    $safeUsers = array_map(function($u) {
        return [
            'login' => $u['login'],
            'name' => $u['name'],
            'role' => $u['role']
        ];
    }, $users);
    jsonResponse($safeUsers);
}

if ($method === 'POST') {
    $login = $input['login'] ?? '';
    $password = $input['password'] ?? '';

    if (!$login || !$password) {
        jsonResponse(['error' => 'Логин и пароль обязательны'], 400);
    }

    $found = false;
    foreach ($users as &$u) {
        if ($u['login'] === $login) {
            $u['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            unset($u['password']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        jsonResponse(['error' => 'Пользователь не найден'], 404);
    }

    writeJson($usersFile, $users);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
?>
