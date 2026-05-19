<?php
require_once __DIR__ . '/api/utils.php';

// Запустите этот файл один раз на хостинге для хеширования паролей
$usersFile = __DIR__ . '/data/users.json';
$users = readJson($usersFile);
$changed = false;
foreach ($users as &$u) {
    if (isset($u['password']) && !isset($u['password_hash'])) {
        $u['password_hash'] = password_hash($u['password'], PASSWORD_DEFAULT);
        unset($u['password']);
        $changed = true;
    }
}
if ($changed) {
    writeJson($usersFile, $users);
    echo "Пароли захешированы. Удалите install.php после выполнения.";
} else {
    echo "Пароли уже захешированы или не найдены.";
}
?>
