<?php
require_once __DIR__ . '/utils.php';
$workshops = readJson(__DIR__ . '/../data/workshops.json');
if (!isset($workshops['all']) || !isset($workshops['schedule'])) {
    jsonResponse(['all' => [], 'schedule' => ['phase1Dates' => [], 'phase2Dates' => [], 'phase1Slot' => [], 'phase2Slots' => []]]);
}
jsonResponse($workshops);
?>
