<?php
/**
 * GRAFIK - Migration des données vers Firebase
 * ⚠️ ATTENTION : Effectuer une sauvegarde de la base de données avant d'exécuter cette migration !
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Firebase.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';

$db = Database::getInstance();
$firebase = Firebase::getInstance();

$migrationResults = [];
$errors = [];

// Vérifier que Firebase est connecté
if (!$firebase->isConnected()) {
    die("❌ Firebase n'est pas connecté. Veuillez configurer Firebase d'abord.");
}

// Option de migration
$migrate_employees = isset($_GET['employees']) && $_GET['employees'] === 'yes';
$migrate_punches = isset($_GET['punches']) && $_GET['punches'] === 'yes';
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($confirm):
    // Migration des employés
    if ($migrate_employees) {
        try {
            $employees = $db->fetchAll("SELECT * FROM employees");
            $count = 0;
            
            foreach ($employees as $employee) {
                $firebase_data = [
                    'first_name' => $employee['first_name'],
                    'last_name' => $employee['last_name'],
                    'phone' => $employee['phone'] ?? '',
                    'pin' => $employee['pin'],
                    'qr_code' => $employee['qr_code'],
                    'is_active' => (bool) $employee['is_active'],
                    'created_at' => $employee['created_at'] ?? date('Y-m-d\TH:i:s'),
                    'migrated_at' => date('Y-m-d\TH:i:s')
                ];
                
                if ($firebase->saveEmployee($employee['id'], $firebase_data)) {
                    $count++;
                } else {
                    $errors[] = "Erreur lors de la migration de l'employé ID: " . $employee['id'];
                }
            }
            
            $migrationResults['employees'] = [
                'success' => true,
                'count' => $count,
                'message' => "$count employé(s) migré(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['employees'] = [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }
    
    // Migration des pointages
    if ($migrate_punches) {
        try {
            $punches = $db->fetchAll("SELECT * FROM punches ORDER BY employee_id, punch_datetime");
            $count = 0;
            
            foreach ($punches as $punch) {
                $firebase_data = [
                    'type' => $punch['type'],
                    'datetime' => str_replace(' ', 'T', $punch['punch_datetime']),
                    'device_id' => 'migrated',
                    'location' => null,
                    'verified' => true,
                    'migrated_at' => date('Y-m-d\TH:i:s'),
                    'original_id' => $punch['id']
                ];
                
                if ($firebase->savePunch($punch['employee_id'], $firebase_data)) {
                    $count++;
                } else {
                    $errors[] = "Erreur lors de la migration du pointage ID: " . $punch['id'];
                }
            }
            
            $migrationResults['punches'] = [
                'success' => true,
                'count' => $count,
                'message' => "$count pointage(s) migré(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['punches'] = [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }
endif;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Firebase - GRAFIK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .warning-box ul {
            margin-left: 20px;
            color: #856404;
        }
        
        .warning-box li {
            margin-bottom: 8px;
        }
        
        .option-box {
            background: #f7f7f7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .option-box h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            cursor: pointer;
            flex: 1;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-primary {
            background: #27ae60;
            color: white;
        }
        
        .btn-primary:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .result-box {
            background: #d4edda;
            border-left: 5px solid #27ae60;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .result-box.error {
            background: #f8d7da;
            border-left-color: #e74c3c;
        }
        
        .result-box h3 {
            color: #155724;
            margin-bottom: 10px;
        }
        
        .result-box.error h3 {
            color: #721c24;
        }
        
        .error-list {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .error-list li {
            color: #e74c3c;
            margin-bottom: 5px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .info-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Migration des données vers Firebase</h1>
        <p class="subtitle">Migrer les données existantes de MariaDB vers Firebase</p>
        
        <?php if (!$confirm): ?>
        
        <div class="warning-box">
            <h3>⚠️ ATTENTION - Sauvegarde obligatoire</h3>
            <ul>
                <li><strong>Effectuez une sauvegarde complète de votre base de données avant de continuer</strong></li>
                <li>Cette migration va copier les données vers Firebase</li>
                <li>Les données MariaDB existantes seront conservées</li>
                <li>Après la migration, le système utilisera Firebase pour les PIN et les pointages</li>
                <li>Le dashboard et les rapports continueront d'utiliser MariaDB</li>
            </ul>
        </div>
        
        <div class="option-box">
            <h3>Données à migrer</h3>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_employees" checked>
                <label for="migrate_employees">
                    <strong>Employés (PIN codes, informations)</strong>
                    <span class="info-badge">
                        <?php 
                        $emp_count = $db->fetchOne("SELECT COUNT(*) as count FROM employees");
                        echo $emp_count['count'];
                        ?> employé(s)
                    </span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_punches" checked>
                <label for="migrate_punches">
                    <strong>Pointages (historique entrée/sortie)</strong>
                    <span class="info-badge">
                        <?php 
                        $punch_count = $db->fetchOne("SELECT COUNT(*) as count FROM punches");
                        echo $punch_count['count'];
                        ?> pointage(s)
                    </span>
                </label>
            </div>
        </div>
        
        <div class="actions">
            <button class="btn btn-primary" onclick="startMigration()">
                ✅ Commencer la migration
            </button>
            <a href="firebase-test.php" class="btn btn-secondary">
                ← Retour au test
            </a>
        </div>
        
        <?php else: ?>
        
        <!-- Résultats de la migration -->
        <?php if (!empty($migrationResults)): ?>
            
            <?php foreach ($migrationResults as $key => $result): ?>
            <div class="result-box <?= $result['success'] ? '' : 'error' ?>">
                <h3>
                    <?= $result['success'] ? '✅' : '❌' ?>
                    <?= $key === 'employees' ? 'Employés' : 'Pointages' ?>
                </h3>
                <p><?= htmlspecialchars($result['message']) ?></p>
                <?php if (isset($result['count'])): ?>
                <p style="margin-top: 10px; font-size: 24px; font-weight: bold; color: #27ae60;">
                    <?= $result['count'] ?> élément(s) migrés
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="result-box error">
                <h3>⚠️ Erreurs rencontrées</h3>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="result-box" style="background: #e8f4f8; border-left-color: #3498db;">
                <h3>ℹ️ Prochaines étapes</h3>
                <ul style="margin-left: 20px; color: #2c3e50;">
                    <li>Les données sont maintenant dans Firebase</li>
                    <li>L'application utilisera Firebase pour l'authentification PIN</li>
                    <li>Les nouveaux pointages seront enregistrés dans Firebase</li>
                    <li>Le dashboard continuera d'afficher toutes les données</li>
                </ul>
            </div>
            
            <div class="actions">
                <a href="dashboard.php" class="btn btn-primary">
                    ✅ Aller au Dashboard
                </a>
                <a href="firebase-test.php" class="btn btn-secondary">
                    🔥 Test Firebase
                </a>
            </div>
            
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
    
    <script>
    function startMigration() {
        const migrateEmployees = document.getElementById('migrate_employees').checked;
        const migratePunches = document.getElementById('migrate_punches').checked;
        
        if (!migrateEmployees && !migratePunches) {
            alert('Veuillez sélectionner au moins une option de migration');
            return;
        }
        
        if (!confirm('⚠️ Avez-vous effectué une sauvegarde de votre base de données ?\n\nCette opération est irréversible !')) {
            return;
        }
        
        let url = 'migrate-to-firebase.php?confirm=yes';
        if (migrateEmployees) url += '&employees=yes';
        if (migratePunches) url += '&punches=yes';
        
        window.location.href = url;
    }
    </script>
</body>
</html>

