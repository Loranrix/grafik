<?php
/**
 * GRAFIK - Page employé - Actions (Arrivée/Départ/Dashboard)
 * Interface en letton
 */

// Charger la configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';

// Vérifier qu'un employé est connecté
if (!isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik - Izvēlne</title>
    <link rel="stylesheet" href="../css/employee.css">
</head>
<body>
    <div class="container">
        <div class="logo">👤</div>
        <h1>Sveiki, <?= htmlspecialchars($employee_name) ?>!</h1>
        
        <div class="action-buttons">
            <a href="punch.php?type=in" class="btn btn-in">Ierašanās</a>
            <a href="punch.php?type=out" class="btn btn-out">Aiziešana</a>
            <a href="consumption.php" class="btn btn-consumption">Patēriņš</a>
            <a href="dashboard.php" class="btn btn-dashboard">Mana statistika</a>
        </div>
        
        <a href="logout.php" class="back-link">← Iziet</a>
    </div>
</body>
</html>

