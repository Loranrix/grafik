<?php
/**
 * GRAFIK - Page employé - Consommation
 * Interface en letton
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Consumption.php';

// Vérifier qu'un employé est connecté
if (!isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'];

$consumptionModel = new Consumption();

$message = '';
$error = '';

// Traiter l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $item_name = trim($_POST['item_name']);
        $original_price = floatval($_POST['original_price']);
        
        if (empty($item_name)) {
            $error = 'Lūdzu, ievadiet produkta nosaukumu';
        } elseif ($original_price <= 0) {
            $error = 'Lūdzu, ievadiet derīgu cenu';
        } else {
            $consumptionModel->add($employee_id, $item_name, $original_price, 50);
            $message = 'Patēriņš pievienots!';
        }
    }
}

// Récupérer les consommations
$consumptions_today = $consumptionModel->getTodayForEmployee($employee_id);
$consumptions_month = $consumptionModel->getMonthForEmployee($employee_id, date('Y'), date('n'));

// Calculer les totaux
$total_today = $consumptionModel->getTotalForPeriod($employee_id, date('Y-m-d'), date('Y-m-d'));
$total_month = $consumptionModel->getTotalForPeriod($employee_id, date('Y-m-01'), date('Y-m-t'));
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Grafik - Patēriņš</title>
    <link rel="stylesheet" href="../css/employee.css">
</head>
<body>
    <div class="container">
        <div class="logo">🍽️</div>
        <h1>Mans patēriņš</h1>
        <p class="subtitle"><?= htmlspecialchars($employee_name) ?></p>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Formulaire d'ajout -->
        <div class="card" style="margin-bottom: 20px;">
            <h2>Pievienot patēriņu</h2>
            <form method="POST" class="consumption-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="item_name">Produkta nosaukums</label>
                    <input type="text" 
                           id="item_name" 
                           name="item_name" 
                           placeholder="Piemēram: Kafija, Sendviča, Sula"
                           required
                           autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label for="original_price">Pilna cena (€)</label>
                    <input type="number" 
                           id="original_price" 
                           name="original_price" 
                           step="0.01"
                           min="0.01"
                           placeholder="Piemēram: 5.00"
                           inputmode="decimal"
                           pattern="[0-9]*\.?[0-9]*"
                           required>
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                        50% atlaide tiks piemērota automātiski
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large">
                    ✓ Pievienot
                </button>
            </form>
        </div>
        
        <!-- Statistiques du jour -->
        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-card">
                <div class="label">Šodien</div>
                <div class="value"><?= $total_today['count'] ?></div>
                <div class="unit">patēriņi</div>
            </div>
            
            <div class="stat-card">
                <div class="label">Šodienas summa</div>
                <div class="value"><?= number_format($total_today['total_discounted'], 2) ?>€</div>
                <div class="unit">(-50%)</div>
            </div>
        </div>
        
        <!-- Consommations du jour -->
        <?php if (!empty($consumptions_today)): ?>
        <div class="card">
            <h2>Šodienas patēriņš</h2>
            <div class="consumption-list">
                <?php foreach ($consumptions_today as $c): ?>
                <div class="consumption-item">
                    <div class="consumption-info">
                        <div class="consumption-name"><?= htmlspecialchars($c['item_name']) ?></div>
                        <div class="consumption-time"><?= substr($c['consumption_time'], 0, 5) ?></div>
                    </div>
                    <div class="consumption-price">
                        <div class="original-price"><?= number_format($c['original_price'], 2) ?>€</div>
                        <div class="discounted-price"><?= number_format($c['discounted_price'], 2) ?>€</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Résumé mensuel -->
        <div class="card" style="margin-top: 20px;">
            <h2>Mēneša kopsavilkums</h2>
            <div class="month-summary">
                <div class="summary-row">
                    <span>Kopējais patēriņu skaits:</span>
                    <strong><?= $total_month['count'] ?></strong>
                </div>
                <div class="summary-row">
                    <span>Pilna cena:</span>
                    <strong><?= number_format($total_month['total_original'], 2) ?>€</strong>
                </div>
                <div class="summary-row">
                    <span>Ar 50% atlaidi:</span>
                    <strong class="discounted"><?= number_format($total_month['total_discounted'], 2) ?>€</strong>
                </div>
                <div class="summary-row savings">
                    <span>Ietaupījums:</span>
                    <strong><?= number_format($total_month['total_original'] - $total_month['total_discounted'], 2) ?>€</strong>
                </div>
            </div>
        </div>
        
        <!-- Navigation buttons -->
        <div class="button-group" style="margin-top: 30px;">
            <a href="actions.php" class="btn btn-secondary">← Atpakaļ</a>
            <a href="dashboard.php" class="btn btn-dashboard">📊 Statistika</a>
            <a href="logout.php" class="btn btn-exit">✕ Iziet</a>
        </div>
    </div>
</body>
</html>

