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
        // Vérifier si c'est une boisson gratuite sélectionnée
        $free_drink = $_POST['free_drink'] ?? '';
        $free_drinks = ['Tēja', 'Kafija', 'Kafija ar pienu'];
        
        if (!empty($free_drink) && in_array($free_drink, $free_drinks)) {
            // C'est une boisson gratuite
            $item_name = $free_drink;
            $free_drinks_count = $consumptionModel->countFreeDrinksToday($employee_id);
            
            // Si c'est le premier → gratuit, sinon demander le prix
            if ($free_drinks_count === 0) {
                $original_price = 0;
            } else {
                $original_price = floatval($_POST['original_price'] ?? 0);
                if ($original_price <= 0) {
                    $error = 'Lūdzu, ievadiet derīgu cenu (no otrās reizes jāmaksā)';
                }
            }
            
            if (empty($error)) {
                $consumptionModel->add($employee_id, $item_name, $original_price, 50);
                $message = 'Patēriņš pievienots!';
            }
        } else {
            // Consommation normale
            $item_name = trim($_POST['item_name'] ?? '');
            $original_price = floatval($_POST['original_price'] ?? 0);
            
            if (empty($item_name) && empty($free_drink)) {
                $error = 'Lūdzu, ievadiet produkta nosaukumu vai izvēlieties bezmaksas dzērienu';
            } elseif (!empty($item_name) && $original_price <= 0) {
                $error = 'Lūdzu, ievadiet derīgu cenu';
            } elseif (!empty($item_name)) {
                $consumptionModel->add($employee_id, $item_name, $original_price, 50);
                $message = 'Patēriņš pievienots!';
            }
        }
    }
}

// Récupérer les consommations
$consumptions_today = $consumptionModel->getTodayForEmployee($employee_id);
$consumptions_month = $consumptionModel->getMonthForEmployee($employee_id, date('Y'), date('n'));

// Calculer les totaux
$total_today = $consumptionModel->getTotalForPeriod($employee_id, date('Y-m-d'), date('Y-m-d'));
$total_month = $consumptionModel->getTotalForPeriod($employee_id, date('Y-m-01'), date('Y-m-t'));

// Compter les boissons gratuites consommées aujourd'hui
$free_drinks_count_today = $consumptionModel->countFreeDrinksToday($employee_id);
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
    <style>
        /* Permettre le scroll sur la page consommation */
        body {
            position: relative !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            min-height: 100vh;
            height: auto;
        }
        .container {
            margin: 20px auto;
        }
    </style>
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
            <form method="POST" class="consumption-form" id="consumptionForm">
                <input type="hidden" name="action" value="add">
                
                <!-- Consommation normale (formulaire original) -->
                <div class="form-group">
                    <label for="item_name">Produkta nosaukums</label>
                    <input type="text" 
                           id="item_name" 
                           name="item_name" 
                           placeholder="Piemēram: Kafija, Sendviča, Sula"
                           autocomplete="off">
                </div>
                
                <div class="form-group" id="priceGroup">
                    <label for="original_price">Pilna cena (€)</label>
                    <input type="number" 
                           id="original_price" 
                           name="original_price" 
                           step="0.01"
                           min="0.01"
                           placeholder="Piemēram: 5.00"
                           inputmode="decimal"
                           pattern="[0-9]*\.?[0-9]*">
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;" id="priceHelp">
                        50% atlaide tiks piemērota automātiski
                    </small>
                </div>
                
                <div style="margin: 20px 0; border-top: 2px solid #ddd; padding-top: 20px;">
                    <label style="font-weight: 600; margin-bottom: 15px; display: block;">Bezmaksas dzērieni (pirmā reize dienā - bez maksas):</label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                            <input type="radio" name="free_drink" value="Tēja" onchange="handleFreeDrinkChange()">
                            <span style="font-size: 16px;">☕ Tēja</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                            <input type="radio" name="free_drink" value="Kafija" onchange="handleFreeDrinkChange()">
                            <span style="font-size: 16px;">☕ Kafija</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9;">
                            <input type="radio" name="free_drink" value="Kafija ar pienu" onchange="handleFreeDrinkChange()">
                            <span style="font-size: 16px;">☕ Kafija ar pienu</span>
                        </label>
                    </div>
                    <small style="color: #7f8c8d; display: block; margin-top: 10px;">
                        Pirmā reize dienā - bez maksas. No otrās reizes - jāmaksā ar 50% atlaidi.
                    </small>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary btn-large" style="flex: 1;">
                        ✓ OK
                    </button>
                    <a href="actions.php" class="btn btn-secondary btn-large" style="flex: 1; text-align: center; text-decoration: none;">
                        ← Atpakaļ
                    </a>
                </div>
            </form>
        </div>
        
        <script>
        function handleFreeDrinkChange() {
            const freeDrinkSelected = document.querySelector('input[name="free_drink"]:checked');
            const itemNameInput = document.getElementById('item_name');
            const priceInput = document.getElementById('original_price');
            
            // Si les champs sont déjà remplis, désélectionner la boisson
            if (freeDrinkSelected && (itemNameInput.value.trim() !== '' || (priceInput.value.trim() !== '' && parseFloat(priceInput.value) > 0))) {
                freeDrinkSelected.checked = false;
                return;
            }
            
            if (freeDrinkSelected) {
                // Une boisson gratuite est sélectionnée (et les champs sont vides)
                itemNameInput.required = false;
                
                // Vérifier combien de boissons gratuites ont déjà été consommées aujourd'hui
                const freeDrinksCount = <?= $free_drinks_count_today ?>;
                const priceGroup = document.getElementById('priceGroup');
                const priceHelp = document.getElementById('priceHelp');
                
                if (freeDrinksCount >= 1) {
                    // C'est la deuxième fois ou plus, demander le prix
                    priceGroup.style.display = 'block';
                    priceInput.required = true;
                    priceInput.min = "0.01";
                    priceInput.value = '';
                    priceInput.style.border = '2px solid #e74c3c';
                    priceHelp.textContent = '⚠️ No otrās reizes jāmaksā! Lūdzu, ievadiet cenu.';
                    priceHelp.style.color = '#e74c3c';
                } else {
                    // Première fois, gratuit
                    priceGroup.style.display = 'none';
                    priceInput.required = false;
                    priceInput.value = '0';
                    priceInput.min = "0";
                    priceInput.style.border = '';
                }
            } else {
                // Aucune boisson gratuite sélectionnée, formulaire normal
                itemNameInput.required = false;
                const priceGroup = document.getElementById('priceGroup');
                priceGroup.style.display = 'block';
                priceInput.required = false;
                priceInput.min = "0.01";
                priceInput.style.border = '';
                const priceHelp = document.getElementById('priceHelp');
                priceHelp.textContent = '50% atlaide tiks piemērota automātiski';
                priceHelp.style.color = '#7f8c8d';
            }
        }
        
        // Désélectionner la boisson si on tape dans le champ item_name
        document.getElementById('item_name').addEventListener('input', function() {
            if (this.value.trim() !== '') {
                document.querySelectorAll('input[name="free_drink"]').forEach(radio => {
                    radio.checked = false;
                });
                handleFreeDrinkChange();
            }
        });
        
        // Désélectionner la boisson si on tape dans le champ prix
        document.getElementById('original_price').addEventListener('input', function() {
            if (this.value.trim() !== '' && parseFloat(this.value) > 0) {
                document.querySelectorAll('input[name="free_drink"]').forEach(radio => {
                    radio.checked = false;
                });
                handleFreeDrinkChange();
            }
        });
        
        // Désélectionner la boisson si on clique sur les champs
        document.getElementById('item_name').addEventListener('focus', function() {
            if (this.value.trim() === '') {
                document.querySelectorAll('input[name="free_drink"]').forEach(radio => {
                    radio.checked = false;
                });
                handleFreeDrinkChange();
            }
        });
        
        document.getElementById('original_price').addEventListener('focus', function() {
            if (this.value.trim() === '' || parseFloat(this.value) === 0) {
                document.querySelectorAll('input[name="free_drink"]').forEach(radio => {
                    radio.checked = false;
                });
                handleFreeDrinkChange();
            }
        });
        </script>
        
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

