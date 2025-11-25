<?php
/**
 * GRAFIK - Vérification PIN code (AJAX)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Employee.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exists' => false]);
    exit;
}

$pin = trim($_POST['pin'] ?? '');
$exclude_id = trim($_POST['exclude_id'] ?? '');

if (empty($pin) || strlen($pin) !== 4 || !ctype_digit($pin)) {
    echo json_encode(['exists' => false]);
    exit;
}

$employeeModel = new Employee();
$exists = $employeeModel->pinExists($pin, $exclude_id ?: null);

echo json_encode(['exists' => $exists]);

