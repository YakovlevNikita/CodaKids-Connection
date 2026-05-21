<?php
require_once __DIR__ . '/utils.php';
session_start();
$_SESSION = [];
session_destroy();
jsonResponse(['success' => true]);
?>
