<?php
require_once __DIR__ . '/utils.php';
$workshops = readJson(__DIR__ . '/../data/workshops.json');
jsonResponse($workshops);
?>
