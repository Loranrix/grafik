<?php
/**
 * GRAFIK - Page employé - Enregistrement pointage
 * Interface en letton
 */

// Charger la configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';
require_once __DIR__ . '/../classes/Shift.php';

// Vérifier qu'un employé est connecté
if (!isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'];

// Vérifier le type de pointage
$type = isset($_GET['type']) ? $_GET['type'] : 'in';
if (!in_array($type, ['in', 'out'])) {
    $type = 'in';
}

$punchModel = new Punch();
$shiftModel = new Shift();

// Enregistrer le pointage
$punch_id = $punchModel->record($employee_id, $type);
$punch_datetime = date('Y-m-d H:i:s');

// Récupérer l'heure prévue si un shift existe
$shift = null;
$today = date('Y-m-d');
$shifts_today = $shiftModel->getByEmployeeMonth($employee_id, date('Y'), date('n'));
foreach ($shifts_today as $s) {
    if ($s['shift_date'] === $today) {
        $shift = $s;
        break;
    }
}

$type_label = $type === 'in' ? 'Ierašanās' : 'Aiziešana';
$type_icon = $type === 'in' ? '✓' : '👋';
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik - <?= $type_label ?></title>
    <link rel="stylesheet" href="../css/employee.css">
</head>
<body>
    <div class="container">
        <div class="logo" style="font-size: 72px;"><?= $type_icon ?></div>
        <h1><?= $type_label ?> reģistrēta!</h1>
        
        <div class="message success">
            Paldies, <?= htmlspecialchars($employee_name) ?>!
        </div>
        
        <div class="punch-info">
            <div class="label">Datums:</div>
            <div class="value"><?= date('d.m.Y', strtotime($punch_datetime)) ?></div>
            
            <div class="label">Laiks:</div>
            <div class="value"><?= date('H:i', strtotime($punch_datetime)) ?></div>
            
            <?php if ($shift && $type === 'in'): ?>
            <div class="label">Plānotais laiks:</div>
            <div class="value"><?= date('H:i', strtotime($shift['start_time'])) ?></div>
            <?php endif; ?>
            
            <?php if ($shift && $type === 'out'): ?>
            <div class="label">Plānotais laiks:</div>
            <div class="value"><?= date('H:i', strtotime($shift['end_time'])) ?></div>
            <?php endif; ?>
        </div>
        
        <a href="actions.php" class="btn btn-dashboard">← Atpakaļ</a>
    </div>
</body>
</html>

