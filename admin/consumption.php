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
$all_consumptions = $consumptionModel->getRecent(1000);

// Organiser les consommations par employé avec détails
$consumptions_by_employee = [];
$totals_by_employee = [];
$grand_total_original = 0;
$grand_total_paid = 0;
$grand_total_count = 0;

foreach ($all_consumptions as $cons) {
    $emp_id = $cons['employee_id'];
    
    // Initialiser si premier passage
    if (!isset($consumptions_by_employee[$emp_id])) {
        $employee = $employeeModel->getById($emp_id);
        $employee_name = $employee ? ($employee['first_name'] . ' ' . $employee['last_name']) : 'Employé #' . $emp_id;
        
        $consumptions_by_employee[$emp_id] = [
            'name' => $employee_name,
            'consumptions' => []
        ];
        
        $totals_by_employee[$emp_id] = [
            'name' => $employee_name,
            'count' => 0,
            'total_original' => 0,
            'total_paid' => 0
        ];
    }
    
    // Ajouter la consommation
    $consumptions_by_employee[$emp_id]['consumptions'][] = $cons;
    
    // Calculer les totaux
    $original = floatval($cons['original_price'] ?? 0);
    $paid = floatval($cons['paid_price'] ?? $cons['discounted_price'] ?? 0);
    
    $totals_by_employee[$emp_id]['count']++;
    $totals_by_employee[$emp_id]['total_original'] += $original;
    $totals_by_employee[$emp_id]['total_paid'] += $paid;
    
    $grand_total_original += $original;
    $grand_total_paid += $paid;
    $grand_total_count++;
}
?>

<div class="container">
    <div class="page-header">
        <h1>Consommations Employés</h1>
    </div>
    
    <!-- Résumé global -->
    <div class="card" style="margin-bottom: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h2 style="color: white; margin-bottom: 20px;">📊 Résumé Global</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center;">
                <div style="font-size: 32px; font-weight: bold;"><?= $grand_total_count ?></div>
                <div style="opacity: 0.9;">Total consommations</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 32px; font-weight: bold;"><?= number_format($grand_total_original, 2) ?> €</div>
                <div style="opacity: 0.9;">Prix total original</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 32px; font-weight: bold;"><?= number_format($grand_total_paid, 2) ?> €</div>
                <div style="opacity: 0.9;">Prix total payé</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 32px; font-weight: bold;"><?= number_format($grand_total_original - $grand_total_paid, 2) ?> €</div>
                <div style="opacity: 0.9;">Économie totale</div>
            </div>
        </div>
    </div>
    
    <!-- Détails par employé -->
    <?php if (count($consumptions_by_employee) > 0): ?>
        <?php foreach ($consumptions_by_employee as $emp_id => $emp_data): ?>
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="border-bottom: 3px solid #667eea; padding-bottom: 10px; margin-bottom: 20px;">
                👤 <?= htmlspecialchars($emp_data['name']) ?>
            </h2>
            
            <!-- Totaux de l'employé -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: center;">
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #667eea;"><?= $totals_by_employee[$emp_id]['count'] ?></div>
                        <div style="color: #666; font-size: 14px;">Articles</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #333;"><?= number_format($totals_by_employee[$emp_id]['total_original'], 2) ?> €</div>
                        <div style="color: #666; font-size: 14px;">Prix total original</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #27ae60;"><?= number_format($totals_by_employee[$emp_id]['total_paid'], 2) ?> €</div>
                        <div style="color: #666; font-size: 14px;">Prix total payé</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: bold; color: #e74c3c;">-<?= number_format($totals_by_employee[$emp_id]['total_paid'], 2) ?> €</div>
                        <div style="color: #666; font-size: 14px;">À déduire</div>
                    </div>
                </div>
            </div>
            
            <!-- Liste détaillée des produits -->
            <h3 style="margin-bottom: 15px; color: #666;">Détail des produits</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Produit</th>
                        <th style="text-align: right;">Prix original</th>
                        <th style="text-align: center;">Réduction</th>
                        <th style="text-align: right;">Prix payé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emp_data['consumptions'] as $cons): 
                        $original = floatval($cons['original_price'] ?? 0);
                        $discounted = floatval($cons['discounted_price'] ?? 0);
                        $discount_percent = floatval($cons['discount_percent'] ?? 0);
                        $datetime = $cons['consumption_datetime'] ?? ($cons['consumption_date'] . ' ' . $cons['consumption_time']);
                    ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($datetime)) ?></td>
                        <td><strong><?= htmlspecialchars($cons['item_name']) ?></strong></td>
                        <td style="text-align: right;"><?= number_format($original, 2) ?> €</td>
                        <td style="text-align: center;">
                            <?php if ($discount_percent > 0): ?>
                                <span style="color: #27ae60; font-weight: bold;">-<?= number_format($discount_percent, 0) ?>%</span>
                            <?php else: ?>
                                <span style="color: #999;">Gratuit</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; font-weight: bold;">
                            <?= number_format($discounted, 2) ?> €
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Ligne total -->
                    <tr style="background: #f8f9fa; font-weight: bold; border-top: 2px solid #667eea;">
                        <td colspan="2" style="text-align: right;">TOTAL :</td>
                        <td style="text-align: right;"><?= number_format($totals_by_employee[$emp_id]['total_original'], 2) ?> €</td>
                        <td></td>
                        <td style="text-align: right; color: #e74c3c;">
                            -<?= number_format($totals_by_employee[$emp_id]['total_paid'], 2) ?> €
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <p style="color: #999; text-align: center; padding: 40px;">Aucune consommation enregistrée</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

