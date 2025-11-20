<?php
/**
 * GRAFIK - Page employé - Confirmation pointage
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
    header('Location: actions.php');
    exit;
}

$type_label = $type === 'in' ? 'Ierašanās' : 'Aiziešana';
$type_icon = $type === 'in' ? '✓' : '👋';
$type_color = $type === 'in' ? '#27ae60' : '#e74c3c';

// Récupérer l'heure prévue si un shift existe
$shiftModel = new Shift();
$today = date('Y-m-d');
$shifts_today = $shiftModel->getByEmployeeMonth($employee_id, date('Y'), date('n'));
$shift = null;
foreach ($shifts_today as $s) {
    if ($s['shift_date'] === $today) {
        $shift = $s;
        break;
    }
}

$current_time = date('H:i');
$current_date = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Grafik - <?= $type_label ?> apstiprināšana</title>
    <link rel="stylesheet" href="../css/employee.css">
    <style>
        .confirmation-box {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .confirmation-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .confirmation-title {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .confirmation-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #7f8c8d;
            font-size: 16px;
        }
        .info-value {
            color: #2c3e50;
            font-weight: bold;
            font-size: 18px;
        }
        .confirm-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-confirm {
            flex: 1;
            background: <?= $type_color ?>;
            color: white;
            border: none;
            padding: 20px;
            border-radius: 15px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-confirm:active {
            transform: scale(0.98);
        }
        .btn-cancel {
            flex: 1;
            background: #95a5a6;
            color: white;
            border: none;
            padding: 20px;
            border-radius: 15px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: transform 0.2s;
        }
        .btn-cancel:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-box">
            <div class="confirmation-icon" style="color: <?= $type_color ?>;"><?= $type_icon ?></div>
            <div class="confirmation-title"><?= $type_label ?></div>
            
            <div class="confirmation-info">
                <div class="info-row">
                    <span class="info-label">Datums:</span>
                    <span class="info-value"><?= $current_date ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Laiks:</span>
                    <span class="info-value"><?= $current_time ?></span>
                </div>
                <?php if ($shift): ?>
                <div class="info-row">
                    <span class="info-label">Plānotais laiks:</span>
                    <span class="info-value">
                        <?php if ($type === 'in'): ?>
                            <?= date('H:i', strtotime($shift['start_time'])) ?>
                        <?php else: ?>
                            <?= date('H:i', strtotime($shift['end_time'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <p style="color: #7f8c8d; margin: 20px 0; font-size: 16px;">
                Vai vēlaties reģistrēt <?= strtolower($type_label) ?>?
            </p>
            
            <div class="confirm-buttons">
                <a href="punch.php?type=<?= $type ?>&confirm=yes" class="btn-confirm">
                    OK
                </a>
                <a href="actions.php" class="btn-cancel">
                    ATCEĻT
                </a>
            </div>
        </div>
    </div>
</body>
</html>

