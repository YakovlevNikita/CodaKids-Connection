<?php
require_once __DIR__ . '/utils.php';

$input = json_decode(file_get_contents('php://input'), true);
$login = $input['login'] ?? '';
$password = $input['password'] ?? '';

$users = readJson(__DIR__ . '/../data/users.json');
$user = null;
foreach ($users as $u) {
    $passOk = false;
    if (isset($u['password_hash'])) {
        $passOk = password_verify($password, $u['password_hash']);
    } elseif (isset($u['password'])) {
        $passOk = $u['password'] === $password;
    }
    if ($u['login'] === $login && $passOk) {
        $user = $u;
        break;
    }
}

if ($user) {
    session_start();
    $_SESSION['user'] = ['login' => $user['login'], 'name' => $user['name'], 'role' => $user['role']];
    jsonResponse(['success' => true, 'user' => $_SESSION['user']]);
} else {
    jsonResponse(['success' => false, 'error' => 'Неверный логин или пароль'], 401);
}
?>
