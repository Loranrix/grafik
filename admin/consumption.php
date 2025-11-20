<?php
/**
 * GRAFIK - Gestion des consommations employés
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Consumption.php';

include 'header.php';

$employeeModel = new Employee();
$consumptionModel = new Consumption();

// Récupérer toutes les consommations
$all_consumptions = $consumptionModel->getRecent(100);

// Calculer les totaux par employé
$totals_by_employee = [];
foreach ($all_consumptions as $cons) {
    $emp_id = $cons['employee_id'];
    if (!isset($totals_by_employee[$emp_id])) {
        $totals_by_employee[$emp_id] = [
            'name' => $cons['first_name'] . ' ' . $cons['last_name'],
            'count' => 0,
            'total_original' => 0,
            'total_paid' => 0
        ];
    }
    $totals_by_employee[$emp_id]['count']++;
    $totals_by_employee[$emp_id]['total_original'] += $cons['original_price'];
    $totals_by_employee[$emp_id]['total_paid'] += $cons['paid_price'];
}
?>

<div class="container">
    <div class="page-header">
        <h1>Consommations Employés</h1>
    </div>
    
    <div class="card" style="margin-bottom: 30px;">
        <h2>Résumé par employé</h2>
        
        <?php if (count($totals_by_employee) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Nombre d'articles</th>
                    <th>Prix total original</th>
                    <th>Prix total payé (-50%)</th>
                    <th>À déduire du salaire</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($totals_by_employee as $total): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($total['name']) ?></strong></td>
                    <td><?= $total['count'] ?></td>
                    <td><?= number_format($total['total_original'], 2) ?> €</td>
                    <td><?= number_format($total['total_paid'], 2) ?> €</td>
                    <td style="color: #e74c3c; font-weight: bold;">-<?= number_format($total['total_paid'], 2) ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #999; text-align: center; padding: 20px;">Aucune consommation enregistrée</p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Historique détaillé</h2>
        
        <?php if (count($all_consumptions) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Article</th>
                    <th>Prix original</th>
                    <th>Réduction</th>
                    <th>Prix payé</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_consumptions as $cons): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($cons['consumption_datetime'])) ?></td>
                    <td><?= htmlspecialchars($cons['first_name'] . ' ' . $cons['last_name']) ?></td>
                    <td><?= htmlspecialchars($cons['item_name']) ?></td>
                    <td><?= number_format($cons['original_price'], 2) ?> €</td>
                    <td style="color: #27ae60;">-<?= $cons['discount_percent'] ?>%</td>
                    <td style="font-weight: bold;"><?= number_format($cons['paid_price'], 2) ?> €</td>
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

