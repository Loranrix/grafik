<?php
/**
 * GRAFIK - Tableau de bord admin
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';
require_once __DIR__ . '/../classes/Consumption.php';

include 'header.php';

$employeeModel = new Employee();
$punchModel = new Punch();
$consumptionModel = new Consumption();

// Statistiques
$total_employees = count($employeeModel->getAll(false));
$active_employees = count($employeeModel->getAll(true));
$today_punches = count($punchModel->getAllByDate(date('Y-m-d')));

// Derniers pointages
$recent_punches = $punchModel->getAllByDate(date('Y-m-d'));
$recent_punches = array_slice($recent_punches, 0, 10);

// Dernières consommations
$recent_consumptions = $consumptionModel->getRecent(10);
?>

<div class="container">
    <div class="page-header">
        <h1>Tableau de bord</h1>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Employés actifs</div>
            <div class="value"><?= $active_employees ?></div>
            <div class="sublabel">Sur <?= $total_employees ?> au total</div>
        </div>
        
        <div class="stat-card">
            <div class="label">Pointages aujourd'hui</div>
            <div class="value"><?= $today_punches ?></div>
            <div class="sublabel"><?= date('d/m/Y') ?></div>
        </div>
    </div>
    
    <div class="card">
        <h2>Derniers pointages</h2>
        
        <?php if (count($recent_punches) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Type</th>
                    <th>Date/Heure</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_punches as $punch): ?>
                <tr>
                    <td><?= htmlspecialchars($punch['first_name'] . ' ' . $punch['last_name']) ?></td>
                    <td>
                        <?php if ($punch['punch_type'] === 'in'): ?>
                            <span style="color: #27ae60; font-weight: bold;">✓ Arrivée</span>
                        <?php else: ?>
                            <span style="color: #e74c3c; font-weight: bold;">← Départ</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($punch['punch_datetime'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #999; text-align: center; padding: 20px;">Aucun pointage aujourd'hui</p>
        <?php endif; ?>
    </div>
    
    <div class="card" style="margin-top: 30px;">
        <h2>Dernières consommations</h2>
        
        <?php if (count($recent_consumptions) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Article</th>
                    <th>Prix original</th>
                    <th>Prix payé</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_consumptions as $cons): ?>
                <tr>
                    <td><?= htmlspecialchars($cons['first_name'] . ' ' . $cons['last_name']) ?></td>
                    <td><?= htmlspecialchars($cons['item_name']) ?></td>
                    <td><?= number_format($cons['original_price'], 2) ?> €</td>
                    <td><?= number_format($cons['paid_price'], 2) ?> €</td>
                    <td><?= date('d/m/Y H:i', strtotime($cons['consumption_datetime'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #999; text-align: center; padding: 20px;">Aucune consommation enregistrée</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

