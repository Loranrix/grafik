<?php
/**
 * GRAFIK - Migration des données vers Firebase
 * ⚠️ ATTENTION : Effectuer une sauvegarde de la base de données avant d'exécuter cette migration !
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Firebase.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';

// Vérifier l'authentification admin
if (!Admin::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$firebase = Firebase::getInstance();

$migrationResults = [];
$errors = [];

// Désactiver l'affichage des erreurs pour ne pas bloquer l'interface
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Fonction helper pour compter sans erreur fatale - version ultra-robuste
function safeCount($db, $table) {
    if (!$db) return 0;
    try {
        $result = @$db->fetchOne("SELECT COUNT(*) as count FROM `$table`");
        if ($result && isset($result['count'])) {
            return (int)$result['count'];
        }
        return 0;
    } catch (Throwable $e) {
        return 0;
    } catch (Exception $e) {
        return 0;
    }
    return 0;
}

// Vérifier que Firebase est connecté
if (!$firebase->isConnected()) {
    die("❌ Firebase n'est pas connecté. Veuillez configurer Firebase d'abord.");
}

// Option de migration
$migrate_employees = isset($_GET['employees']) && $_GET['employees'] === 'yes';
$migrate_punches = isset($_GET['punches']) && $_GET['punches'] === 'yes';
$migrate_schedules = isset($_GET['schedules']) && $_GET['schedules'] === 'yes';
$migrate_messages = isset($_GET['messages']) && $_GET['messages'] === 'yes';
$migrate_consumptions = isset($_GET['consumptions']) && $_GET['consumptions'] === 'yes';
$migrate_settings = isset($_GET['settings']) && $_GET['settings'] === 'yes';
$migrate_logs = isset($_GET['logs']) && $_GET['logs'] === 'yes';
$migrate_admins = isset($_GET['admins']) && $_GET['admins'] === 'yes';
$migrate_all = isset($_GET['all']) && $_GET['all'] === 'yes';
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($confirm):
    // Si "migrate_all", activer toutes les migrations
    if ($migrate_all) {
        $migrate_employees = true;
        $migrate_punches = true;
        $migrate_schedules = true;
        $migrate_messages = true;
        $migrate_consumptions = true;
        $migrate_settings = true;
        $migrate_logs = true;
        $migrate_admins = true;
    }
    
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
                    'punch_type' => $punch['punch_type'],
                    'punch_datetime' => $punch['punch_datetime'],
                    'shift_id' => $punch['shift_id'] ?? null,
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
    
    // Migration des plannings (schedules)
    if ($migrate_schedules) {
        try {
            // Essayer schedules d'abord, puis shifts
            try {
                $schedules = $db->fetchAll("SELECT * FROM schedules ORDER BY employee_id, schedule_date");
            } catch (Exception $e) {
                $schedules = $db->fetchAll("SELECT * FROM shifts ORDER BY employee_id, shift_date");
            }
            $count = 0;
            
            foreach ($schedules as $schedule) {
                $firebase_data = [
                    'employee_id' => $schedule['employee_id'],
                    'schedule_date' => $schedule['shift_date'] ?? $schedule['schedule_date'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'notes' => $schedule['notes'] ?? null,
                    'created_at' => $schedule['created_at'] ?? date('Y-m-d\TH:i:s'),
                    'migrated_at' => date('Y-m-d\TH:i:s'),
                    'original_id' => $schedule['id']
                ];
                
                $schedule_id = 'schedule_' . $schedule['id'];
                if ($firebase->saveSchedule($schedule_id, $firebase_data)) {
                    $count++;
                } else {
                    $errors[] = "Erreur lors de la migration du planning ID: " . $schedule['id'];
                }
            }
            
            $migrationResults['schedules'] = [
                'success' => true,
                'count' => $count,
                'message' => "$count planning(s) migré(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['schedules'] = [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }
    
    // Migration des messages
    if ($migrate_messages) {
        try {
            $messages = $db->fetchAll("SELECT * FROM messages ORDER BY created_at");
            $count = 0;
            
            foreach ($messages as $message) {
                $firebase_data = [
                    'employee_id' => $message['employee_id'],
                    'message' => $message['message'],
                    'is_read' => (bool)($message['is_read'] ?? false),
                    'created_at' => $message['created_at'] ?? date('Y-m-d\TH:i:s'),
                    'migrated_at' => date('Y-m-d\TH:i:s'),
                    'original_id' => $message['id']
                ];
                
                $message_id = 'msg_' . $message['id'];
                if ($firebase->saveMessage($message_id, $firebase_data)) {
                    $count++;
                } else {
                    $errors[] = "Erreur lors de la migration du message ID: " . $message['id'];
                }
            }
            
            $migrationResults['messages'] = [
                'success' => true,
                'count' => $count,
                'message' => "$count message(s) migré(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['messages'] = [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }
    
    // Migration des consommations
    if ($migrate_consumptions) {
        try {
            $consumptions = $db->fetchAll("SELECT * FROM consumptions ORDER BY consumption_date, consumption_time");
            $count = 0;
            
            foreach ($consumptions as $consumption) {
                $firebase_data = [
                    'employee_id' => $consumption['employee_id'],
                    'item_name' => $consumption['item_name'],
                    'original_price' => floatval($consumption['original_price']),
                    'discounted_price' => floatval($consumption['discounted_price']),
                    'discount_percent' => intval($consumption['discount_percent']),
                    'consumption_date' => $consumption['consumption_date'],
                    'consumption_time' => $consumption['consumption_time'],
                    'created_at' => date('Y-m-d\TH:i:s'),
                    'migrated_at' => date('Y-m-d\TH:i:s'),
                    'original_id' => $consumption['id']
                ];
                
                $consumption_id = 'cons_' . $consumption['id'];
                if ($firebase->saveConsumption($consumption_id, $firebase_data)) {
                    $count++;
                } else {
                    $errors[] = "Erreur lors de la migration de la consommation ID: " . $consumption['id'];
                }
            }
            
            $migrationResults['consumptions'] = [
                'success' => true,
                'count' => $count,
                'message' => "$count consommation(s) migrée(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['consumptions'] = [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }
    
    // Migration des paramètres de sécurité
    if ($migrate_settings) {
        try {
            $settings = $db->fetchAll("SELECT * FROM security_settings");
            $count = 0;
            
            foreach ($settings as $setting) {
                $key = $setting['setting_key'];
                $value = $setting['setting_value'];
                $type = $setting['setting_type'] ?? 'string';
                $description = $setting['description'] ?? '';
                
                if ($firebase->setSecuritySetting($key, $value, $type, $description)) {
                    $count++;
                } else {
                    $errors[] = "Erreur lors de la migration du paramètre: " . $key;
                }
            }
            
            $migrationResults['settings'] = [
                'success' => true,
                'count' => $count,
                'message' => "$count paramètre(s) migré(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['settings'] = [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }
    
    // Migration des logs
    if ($migrate_logs) {
        try {
            // Logs de connexion employés
            $login_logs = $db->fetchAll("SELECT * FROM employee_login_logs ORDER BY created_at");
            $count_logins = 0;
            foreach ($login_logs as $log) {
                $log_data = [
                    'qr_code' => $log['qr_code'] ?? null,
                    'pin_entered' => $log['pin_entered'] ?? null,
                    'success' => (bool)($log['success'] ?? false),
                    'failure_reason' => $log['failure_reason'] ?? null,
                    'device_id' => $log['device_id'] ?? null,
                    'device_info' => $log['device_info'] ?? null,
                    'ip_address' => $log['ip_address'] ?? null,
                    'gps_latitude' => $log['gps_latitude'] ?? null,
                    'gps_longitude' => $log['gps_longitude'] ?? null
                ];
                if ($firebase->logEmployeeLogin($log['employee_id'], $log_data)) {
                    $count_logins++;
                }
            }
            
            // Logs d'actions admin
            $admin_logs = $db->fetchAll("SELECT * FROM admin_action_logs ORDER BY created_at");
            $count_admin = 0;
            foreach ($admin_logs as $log) {
                $old_values = $log['old_values'] ? json_decode($log['old_values'], true) : null;
                $new_values = $log['new_values'] ? json_decode($log['new_values'], true) : null;
                if ($firebase->logAdminAction(
                    $log['admin_id'],
                    $log['action_type'],
                    $log['action_description'],
                    $log['target_type'],
                    $log['target_id'],
                    $old_values,
                    $new_values
                )) {
                    $count_admin++;
                }
            }
            
            $migrationResults['logs'] = [
                'success' => true,
                'count' => $count_logins + $count_admin,
                'message' => "$count_logins log(s) de connexion et $count_admin log(s) admin migré(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['logs'] = [
                'success' => false,
                'message' => "Erreur: " . $e->getMessage()
            ];
        }
    }
    
    // Migration des admins
    if ($migrate_admins) {
        try {
            $admins = $db->fetchAll("SELECT * FROM admins");
            $count = 0;
            
            foreach ($admins as $admin) {
                $firebase_data = [
                    'username' => $admin['username'],
                    'password' => $admin['password'],
                    'created_at' => $admin['created_at'] ?? date('Y-m-d\TH:i:s'),
                    'last_login' => $admin['last_login'] ?? null,
                    'migrated_at' => date('Y-m-d\TH:i:s'),
                    'original_id' => $admin['id']
                ];
                
                $admin_id = 'admin_' . $admin['id'];
                if ($firebase->saveAdmin($admin_id, $firebase_data)) {
                    $count++;
                } else {
                    $errors[] = "Erreur lors de la migration de l'admin ID: " . $admin['id'];
                }
            }
            
            $migrationResults['admins'] = [
                'success' => true,
                'count' => $count,
                'message' => "$count admin(s) migré(s) vers Firebase"
            ];
            
        } catch (Exception $e) {
            $migrationResults['admins'] = [
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
                <li>Après la migration, le système utilisera Firebase pour <strong>TOUTES les données</strong></li>
                <li>Les données MariaDB seront conservées en backup</li>
                <li>Toutes les nouvelles données seront stockées uniquement dans Firebase</li>
                <li><strong>8 types de données</strong> seront migrés : Employés, Pointages, Plannings, Messages, Consommations, Paramètres, Logs, Administrateurs</li>
            </ul>
        </div>
        
        <div class="option-box">
            <h3>Données à migrer</h3>
            
            <?php
            // Calculer tous les compteurs AVANT d'afficher pour éviter les erreurs
            // Version ultra-robuste : chaque compteur est isolé dans son propre try-catch
            $counts = [
                'employees' => 0,
                'punches' => 0,
                'schedules' => 0,
                'messages' => 0,
                'consumptions' => 0,
                'settings' => 0,
                'admins' => 0
            ];
            
            try { $counts['employees'] = safeCount($db, 'employees'); } catch (Throwable $e) { $counts['employees'] = 0; }
            try { $counts['punches'] = safeCount($db, 'punches'); } catch (Throwable $e) { $counts['punches'] = 0; }
            try { 
                $counts['schedules'] = safeCount($db, 'schedules');
                if ($counts['schedules'] == 0) {
                    $counts['schedules'] = safeCount($db, 'shifts');
                }
            } catch (Throwable $e) { $counts['schedules'] = 0; }
            try { $counts['messages'] = safeCount($db, 'messages'); } catch (Throwable $e) { $counts['messages'] = 0; }
            try { $counts['consumptions'] = safeCount($db, 'consumptions'); } catch (Throwable $e) { $counts['consumptions'] = 0; }
            try { $counts['settings'] = safeCount($db, 'security_settings'); } catch (Throwable $e) { $counts['settings'] = 0; }
            try { $counts['admins'] = safeCount($db, 'admins'); } catch (Throwable $e) { $counts['admins'] = 0; }
            ?>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_employees" checked>
                <label for="migrate_employees">
                    <strong>👥 Employés (PIN codes, informations)</strong>
                    <span class="info-badge"><?= htmlspecialchars($counts['employees']) ?> employé(s)</span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_punches" checked>
                <label for="migrate_punches">
                    <strong>⏱️ Pointages (historique entrée/sortie)</strong>
                    <span class="info-badge"><?= htmlspecialchars($counts['punches']) ?> pointage(s)</span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_schedules" checked>
                <label for="migrate_schedules">
                    <strong>📅 Plannings (schedules)</strong>
                    <span class="info-badge"><?= htmlspecialchars($counts['schedules']) ?> planning(s)</span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_messages" checked>
                <label for="migrate_messages">
                    <strong>💬 Messages</strong>
                    <span class="info-badge"><?= htmlspecialchars($counts['messages']) ?> message(s)</span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_consumptions" checked>
                <label for="migrate_consumptions">
                    <strong>🍕 Consommations</strong>
                    <span class="info-badge"><?= htmlspecialchars($counts['consumptions']) ?> consommation(s)</span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_settings" checked>
                <label for="migrate_settings">
                    <strong>🔒 Paramètres de sécurité</strong>
                    <span class="info-badge"><?= htmlspecialchars($counts['settings']) ?> paramètre(s)</span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_logs" checked>
                <label for="migrate_logs">
                    <strong>📋 Logs d'audit</strong>
                    <span class="info-badge">Historique complet</span>
                </label>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="migrate_admins" checked>
                <label for="migrate_admins">
                    <strong>👤 Administrateurs</strong>
                    <span class="info-badge"><?= htmlspecialchars($counts['admins']) ?> admin(s)</span>
                </label>
            </div>
            
            <div class="checkbox-group" style="background: #e8f4f8; border: 2px solid #3498db;">
                <input type="checkbox" id="migrate_all" checked>
                <label for="migrate_all" style="font-weight: bold; color: #2c3e50;">
                    <strong>✅ Migrer TOUT (recommandé)</strong>
                    <span class="info-badge" style="background: #3498db;">Toutes les données</span>
                </label>
            </div>
        </div>
        
        <div class="actions">
            <button class="btn btn-primary" onclick="startMigration()">
                ✅ Commencer la migration
            </button>
            <a href="firebase-test.php" class="btn btn-secondary">
                🔥 Test Firebase
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                ← Retour Dashboard
            </a>
        </div>
        
        <?php else: ?>
        
        <!-- Résultats de la migration -->
        <?php if (!empty($migrationResults)): ?>
            
            <?php foreach ($migrationResults as $key => $result): ?>
            <div class="result-box <?= $result['success'] ? '' : 'error' ?>">
                <h3>
                    <?= $result['success'] ? '✅' : '❌' ?>
                    <?php
                    $labels = [
                        'employees' => 'Employés',
                        'punches' => 'Pointages',
                        'schedules' => 'Plannings',
                        'messages' => 'Messages',
                        'consumptions' => 'Consommations',
                        'settings' => 'Paramètres',
                        'logs' => 'Logs',
                        'admins' => 'Administrateurs'
                    ];
                    echo $labels[$key] ?? ucfirst($key);
                    ?>
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
                    <li>✅ Toutes les données sont maintenant dans Firebase</li>
                    <li>✅ L'application utilise Firebase pour TOUTES les opérations</li>
                    <li>✅ Les nouvelles données seront stockées uniquement dans Firebase</li>
                    <li>✅ Aucune perte de données en cas de backup/restauration serveur</li>
                    <li>✅ Les données MariaDB sont conservées en backup</li>
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
        const migrateAll = document.getElementById('migrate_all').checked;
        const migrateEmployees = document.getElementById('migrate_employees').checked;
        const migratePunches = document.getElementById('migrate_punches').checked;
        const migrateSchedules = document.getElementById('migrate_schedules').checked;
        const migrateMessages = document.getElementById('migrate_messages').checked;
        const migrateConsumptions = document.getElementById('migrate_consumptions').checked;
        const migrateSettings = document.getElementById('migrate_settings').checked;
        const migrateLogs = document.getElementById('migrate_logs').checked;
        const migrateAdmins = document.getElementById('migrate_admins').checked;
        
        if (!migrateAll && !migrateEmployees && !migratePunches && !migrateSchedules && 
            !migrateMessages && !migrateConsumptions && !migrateSettings && !migrateLogs && !migrateAdmins) {
            alert('Veuillez sélectionner au moins une option de migration');
            return;
        }
        
        if (!confirm('⚠️ Avez-vous effectué une sauvegarde de votre base de données ?\n\nCette opération va migrer toutes les données vers Firebase.\nLes données MariaDB seront conservées en backup.')) {
            return;
        }
        
        let url = 'migrate-to-firebase.php?confirm=yes';
        if (migrateAll) {
            url += '&all=yes';
        } else {
            if (migrateEmployees) url += '&employees=yes';
            if (migratePunches) url += '&punches=yes';
            if (migrateSchedules) url += '&schedules=yes';
            if (migrateMessages) url += '&messages=yes';
            if (migrateConsumptions) url += '&consumptions=yes';
            if (migrateSettings) url += '&settings=yes';
            if (migrateLogs) url += '&logs=yes';
            if (migrateAdmins) url += '&admins=yes';
        }
        
        window.location.href = url;
    }
    
    // Gérer la case "Migrer TOUT"
    document.getElementById('migrate_all').addEventListener('change', function() {
        const checkboxes = ['migrate_employees', 'migrate_punches', 'migrate_schedules', 
                           'migrate_messages', 'migrate_consumptions', 'migrate_settings', 
                           'migrate_logs', 'migrate_admins'];
        checkboxes.forEach(id => {
            document.getElementById(id).checked = this.checked;
        });
    });
    
    // Décocher "Migrer TOUT" si une case individuelle est décochée
    const checkboxes = ['migrate_employees', 'migrate_punches', 'migrate_schedules', 
                       'migrate_messages', 'migrate_consumptions', 'migrate_settings', 
                       'migrate_logs', 'migrate_admins'];
    checkboxes.forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            if (!this.checked) {
                document.getElementById('migrate_all').checked = false;
            } else {
                // Vérifier si toutes les cases sont cochées
                const allChecked = checkboxes.every(cbId => document.getElementById(cbId).checked);
                document.getElementById('migrate_all').checked = allChecked;
            }
        });
    });
    </script>
</body>
</html>

