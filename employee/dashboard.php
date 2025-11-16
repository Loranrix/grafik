<?php
/**
 * GRAFIK - Page employé - Dashboard / Statistiques
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

$punchModel = new Punch();
$shiftModel = new Shift();

// Calculer les heures
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$hours_today = $punchModel->calculateHours($employee_id, $today);
$hours_yesterday = $punchModel->calculateHours($employee_id, $yesterday);
$hours_week = $punchModel->calculateHoursRange($employee_id, $week_start, $week_end);
$hours_month = $punchModel->calculateHoursRange($employee_id, $month_start, $month_end);

// Récupérer le planning du mois
$shifts = $shiftModel->getByEmployeeMonth($employee_id, date('Y'), date('n'));
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik - Mana statistika</title>
    <link rel="stylesheet" href="../css/employee.css">
</head>
<body>
    <div class="container">
        <div class="logo">📊</div>
        <h1>Mana statistika</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Šodien</div>
                <div class="value"><?= number_format($hours_today, 1) ?></div>
                <div class="unit">stundas</div>
            </div>
            
            <div class="stat-card">
                <div class="label">Vakar</div>
                <div class="value"><?= number_format($hours_yesterday, 1) ?></div>
                <div class="unit">stundas</div>
            </div>
            
            <div class="stat-card">
                <div class="label">Šonedēļ</div>
                <div class="value"><?= number_format($hours_week, 1) ?></div>
                <div class="unit">stundas</div>
            </div>
            
            <div class="stat-card">
                <div class="label">Šomēnes</div>
                <div class="value"><?= number_format($hours_month, 1) ?></div>
                <div class="unit">stundas</div>
            </div>
        </div>
        
        <?php if (count($shifts) > 0): ?>
        <div class="calendar">
            <div class="calendar-header">Mans grafiks (<?= date('F Y') ?>)</div>
            
            <?php foreach ($shifts as $shift): ?>
                <?php 
                $shift_date = $shift['shift_date'];
                $is_today = $shift_date === $today;
                ?>
                <div class="calendar-day <?= $is_today ? 'today' : '' ?>">
                    <div class="date">
                        <?= date('d.m.Y (l)', strtotime($shift_date)) ?>
                    </div>
                    <div class="time">
                        <?= date('H:i', strtotime($shift['start_time'])) ?> - 
                        <?= date('H:i', strtotime($shift['end_time'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <a href="actions.php" class="back-link">← Atpakaļ</a>
    </div>
</body>
</html>

