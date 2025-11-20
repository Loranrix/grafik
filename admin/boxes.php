<?php
/**
 * GRAFIK - Gestion des boîtes vides
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';

include 'header.php';

$employeeModel = new Employee();
$punchModel = new Punch();
$message = '';
$error = '';

// Date sélectionnée
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Récupérer tous les pointages pour la date sélectionnée
$allPunches = $punchModel->getAllByDate($selected_date);

// Filtrer les pointages avec boîtes
$boxesPunches = [];
foreach ($allPunches as $punch) {
    if (isset($punch['boxes_count']) && $punch['boxes_count'] !== null && intval($punch['boxes_count']) > 0) {
        $boxesPunches[] = $punch;
    }
}

// Trier par date/heure décroissante
usort($boxesPunches, function($a, $b) {
    return strcmp($b['punch_datetime'], $a['punch_datetime']);
});

// Statistiques pour la date sélectionnée
$total_boxes_today = 0;
foreach ($boxesPunches as $punch) {
    $total_boxes_today += intval($punch['boxes_count']);
}
?>

<div class="container">
    <div class="page-header">
        <h1>📦 Gestion des boîtes vides</h1>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Sélecteur de date -->
    <div class="card">
        <form method="GET" style="display: flex; gap: 15px; align-items: center;">
            <label for="date" style="font-weight: 600;">Date :</label>
            <input type="date" id="date" name="date" value="<?= $selected_date ?>" style="padding: 10px; border: 2px solid #ddd; border-radius: 8px;">
            <button type="submit" class="btn btn-primary btn-sm">Afficher</button>
            <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-secondary btn-sm">Aujourd'hui</a>
        </form>
    </div>
    
    <!-- Statistiques -->
    <?php if ($selected_date === date('Y-m-d')): ?>
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-top: 20px;">
        <h2 style="color: white; margin: 0 0 10px 0;">📊 Statistiques du jour</h2>
        <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">
            <?= $total_boxes_today ?> boîtes
        </div>
        <div style="opacity: 0.9;">
            <?= count($boxesPunches) ?> saisie(s) aujourd'hui
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Liste des saisies -->
    <div class="card" style="margin-top: 20px;">
        <h2>Historique des boîtes vides - <?= date('d/m/Y', strtotime($selected_date)) ?></h2>
        
        <?php if (count($boxesPunches) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Date/Heure</th>
                    <th>Employé</th>
                    <th>Type</th>
                    <th>Nombre de boîtes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($boxesPunches as $punch): ?>
                <tr>
                    <td><?= date('d/m/Y H:i:s', strtotime($punch['punch_datetime'])) ?></td>
                    <td><?= htmlspecialchars($punch['first_name'] . ' ' . $punch['last_name']) ?></td>
                    <td>
                        <?php if ($punch['punch_type'] === 'in'): ?>
                            <span style="color: #27ae60; font-weight: bold;">✓ Arrivée</span>
                        <?php else: ?>
                            <span style="color: #e74c3c; font-weight: bold;">← Départ</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background: #e74c3c; color: white; padding: 6px 12px; border-radius: 12px; font-weight: bold; font-size: 16px;">
                            <?= intval($punch['boxes_count']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #999; text-align: center; padding: 20px;">Aucune saisie de boîtes pour cette date</p>
        <?php endif; ?>
    </div>
    
    <!-- Résumé par employé -->
    <?php if (count($boxesPunches) > 0): ?>
    <div class="card" style="margin-top: 20px;">
        <h2>Résumé par employé</h2>
        <?php
        $summary = [];
        foreach ($boxesPunches as $punch) {
            $emp_id = $punch['employee_id'] ?? ($punch['first_name'] . ' ' . $punch['last_name']);
            if (!isset($summary[$emp_id])) {
                $summary[$emp_id] = [
                    'name' => $punch['first_name'] . ' ' . $punch['last_name'],
                    'total' => 0,
                    'count' => 0
                ];
            }
            $summary[$emp_id]['total'] += intval($punch['boxes_count']);
            $summary[$emp_id]['count']++;
        }
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Nombre de saisies</th>
                    <th>Total boîtes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary as $emp_summary): ?>
                <tr>
                    <td><?= htmlspecialchars($emp_summary['name']) ?></td>
                    <td><?= $emp_summary['count'] ?></td>
                    <td><strong><?= $emp_summary['total'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

