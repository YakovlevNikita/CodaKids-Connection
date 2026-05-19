<?php
require_once __DIR__ . '/utils.php';
session_start();

if (isset($_SESSION['user'])) {
    jsonResponse(['loggedIn' => true, 'user' => $_SESSION['user']]);
} else {
    jsonResponse(['loggedIn' => false]);
}
?>
