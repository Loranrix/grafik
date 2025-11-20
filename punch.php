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

// Vérifier la confirmation
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';
if (!$confirm) {
    // Rediriger vers la page de confirmation
    header('Location: confirm-punch.php?type=' . $type);
    exit;
}

$punchModel = new Punch();
$shiftModel = new Shift();

// Enregistrer le pointage
$error_message = null;
$punch_id = null;
try {
    $punch_id = $punchModel->record($employee_id, $type);
    $punch_datetime = date('Y-m-d H:i:s');
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $punch_datetime = date('Y-m-d H:i:s');
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Grafik - <?= $type_label ?></title>
    <link rel="stylesheet" href="../css/employee.css">
</head>
<body>
    <div class="container">
        <?php if ($error_message): ?>
        <div class="logo" style="font-size: 72px;">❌</div>
        <h1>Kļūda!</h1>
        
        <div class="message error" style="background: #e74c3c; color: white; padding: 20px; border-radius: 15px; margin: 20px 0;">
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php else: ?>
        <div class="logo" style="font-size: 72px;"><?= $type_icon ?></div>
        <h1><?= $type_label ?> reģistrēta!</h1>
        
        <div class="message success">
            Paldies, <?= htmlspecialchars($employee_name) ?>!
        </div>
        <?php endif; ?>
        
        <?php if (!$error_message): ?>
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
        <?php endif; ?>
        
        <div class="button-group" style="margin-top: 30px;">
            <a href="actions.php" class="btn btn-secondary">← Atpakaļ</a>
            <a href="logout.php" class="btn btn-exit">✕ Iziet</a>
        </div>
    </div>
</body>
</html>

