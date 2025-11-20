<?php
/**
 * GRAFIK - Paramètres de sécurité
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/SecuritySettings.php';
require_once __DIR__ . '/../classes/AuditLog.php';

$current_page = 'security-settings';
include 'header.php';

$settingsModel = new SecuritySettings();
$auditLog = new AuditLog();

$message = '';
$error = '';

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        try {
            $updates = [];
            
            // Restriction par appareil
            $updates['device_restriction_enabled'] = [
                'value' => isset($_POST['device_restriction_enabled']),
                'type' => 'boolean'
            ];
            
            // Vérification GPS
            $updates['gps_verification_enabled'] = [
                'value' => isset($_POST['gps_verification_enabled']),
                'type' => 'boolean'
            ];
            $updates['gps_latitude'] = [
                'value' => trim($_POST['gps_latitude']),
                'type' => 'string'
            ];
            $updates['gps_longitude'] = [
                'value' => trim($_POST['gps_longitude']),
                'type' => 'string'
            ];
            $updates['gps_radius_meters'] = [
                'value' => intval($_POST['gps_radius_meters']),
                'type' => 'integer'
            ];
            
            // Multi-appareils
            $updates['multi_device_enabled'] = [
                'value' => isset($_POST['multi_device_enabled']),
                'type' => 'boolean'
            ];
            
            // Tentatives PIN
            $updates['max_pin_attempts'] = [
                'value' => intval($_POST['max_pin_attempts']),
                'type' => 'integer'
            ];
            $updates['pin_attempt_lockout_minutes'] = [
                'value' => intval($_POST['pin_attempt_lockout_minutes']),
                'type' => 'integer'
            ];
            
            // Tolérances de pointage
            $updates['early_punch_tolerance_minutes'] = [
                'value' => intval($_POST['early_punch_tolerance_minutes']),
                'type' => 'integer'
            ];
            $updates['late_punch_tolerance_minutes'] = [
                'value' => intval($_POST['late_punch_tolerance_minutes']),
                'type' => 'integer'
            ];
            
            // Notifications
            $updates['notifications_enabled'] = [
                'value' => isset($_POST['notifications_enabled']),
                'type' => 'boolean'
            ];
            $updates['admin_notification_email'] = [
                'value' => trim($_POST['admin_notification_email']),
                'type' => 'string'
            ];
            
            $settingsModel->updateMultiple($updates);
            
            // Logger l'action
            $auditLog->logAdminAction(
                $_SESSION['admin_id'],
                'update_security_settings',
                'Mise à jour des paramètres de sécurité',
                'security_settings',
                null,
                null,
                $updates
            );
            
            $message = 'Paramètres de sécurité mis à jour avec succès';
            
        } catch (Exception $e) {
            $error = 'Erreur lors de la mise à jour : ' . $e->getMessage();
        }
    }
}

// Récupérer les paramètres actuels
$settings = $settingsModel->getAll();

// Statistiques de sécurité
$securityStats = $auditLog->getSecurityStats();
$recentFailed = $auditLog->getRecentFailedAttempts(24);
?>

<div class="container">
    <div class="page-header">
        <h1>🔒 Paramètres de sécurité</h1>
        <p class="subtitle">Configurer les options de sécurité et contrôle d'accès</p>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-label">Tentatives 24h</div>
            <div class="stat-value"><?= $securityStats['login_attempts_24h']['total'] ?? 0 ?></div>
            <div class="stat-detail">
                ✅ <?= $securityStats['login_attempts_24h']['successful'] ?? 0 ?> réussies | 
                ❌ <?= $securityStats['login_attempts_24h']['failed'] ?? 0 ?> échouées
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Appareils verrouillés</div>
            <div class="stat-value"><?= $securityStats['locked_devices'] ?? 0 ?></div>
            <div class="stat-detail">En cours</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Actions admin</div>
            <div class="stat-value"><?= $securityStats['admin_actions_24h'] ?? 0 ?></div>
            <div class="stat-detail">Dernières 24h</div>
        </div>
    </div>
    
    <!-- Formulaire de configuration -->
    <form method="POST" class="security-form">
        <input type="hidden" name="action" value="update_settings">
        
        <!-- Section : Appareil -->
        <div class="card">
            <h2>📱 Restriction par appareil</h2>
            <p class="section-description">
                Limiter l'accès à un ou plusieurs appareils enregistrés par employé
            </p>
            
            <div class="form-check">
                <input type="checkbox" 
                       id="device_restriction_enabled" 
                       name="device_restriction_enabled" 
                       <?= $settings['device_restriction_enabled']['value'] ? 'checked' : '' ?>>
                <label for="device_restriction_enabled">
                    Activer la restriction par appareil
                </label>
            </div>
            
            <div class="form-check">
                <input type="checkbox" 
                       id="multi_device_enabled" 
                       name="multi_device_enabled" 
                       <?= $settings['multi_device_enabled']['value'] ? 'checked' : '' ?>>
                <label for="multi_device_enabled">
                    Autoriser plusieurs appareils par employé
                </label>
            </div>
        </div>
        
        <!-- Section : GPS -->
        <div class="card">
            <h2>📍 Vérification GPS</h2>
            <p class="section-description">
                Vérifier la localisation lors du scan du QR code
            </p>
            
            <div class="form-check">
                <input type="checkbox" 
                       id="gps_verification_enabled" 
                       name="gps_verification_enabled" 
                       <?= $settings['gps_verification_enabled']['value'] ? 'checked' : '' ?>>
                <label for="gps_verification_enabled">
                    Activer la vérification GPS
                </label>
            </div>
            
            <div class="form-row" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="gps_latitude">Latitude du restaurant</label>
                    <input type="text" 
                           id="gps_latitude" 
                           name="gps_latitude" 
                           value="<?= htmlspecialchars($settings['gps_latitude']['value']) ?>" 
                           placeholder="56.9496">
                    <small>Format : 56.9496</small>
                </div>
                
                <div class="form-group">
                    <label for="gps_longitude">Longitude du restaurant</label>
                    <input type="text" 
                           id="gps_longitude" 
                           name="gps_longitude" 
                           value="<?= htmlspecialchars($settings['gps_longitude']['value']) ?>" 
                           placeholder="24.1052">
                    <small>Format : 24.1052</small>
                </div>
                
                <div class="form-group">
                    <label for="gps_radius_meters">Rayon autorisé (mètres)</label>
                    <input type="number" 
                           id="gps_radius_meters" 
                           name="gps_radius_meters" 
                           value="<?= htmlspecialchars($settings['gps_radius_meters']['value']) ?>" 
                           min="10" 
                           max="1000">
                    <small>Distance maximale depuis le restaurant</small>
                </div>
            </div>
        </div>
        
        <!-- Section : PIN -->
        <div class="card">
            <h2>🔐 Sécurité PIN</h2>
            <p class="section-description">
                Contrôler les tentatives de connexion et verrouillage
            </p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="max_pin_attempts">Nombre maximum de tentatives</label>
                    <input type="number" 
                           id="max_pin_attempts" 
                           name="max_pin_attempts" 
                           value="<?= htmlspecialchars($settings['max_pin_attempts']['value']) ?>" 
                           min="1" 
                           max="10">
                    <small>Après ce nombre, l'appareil sera verrouillé</small>
                </div>
                
                <div class="form-group">
                    <label for="pin_attempt_lockout_minutes">Durée du verrouillage (minutes)</label>
                    <input type="number" 
                           id="pin_attempt_lockout_minutes" 
                           name="pin_attempt_lockout_minutes" 
                           value="<?= htmlspecialchars($settings['pin_attempt_lockout_minutes']['value']) ?>" 
                           min="1" 
                           max="1440">
                    <small>Temps avant nouvelle tentative autorisée</small>
                </div>
            </div>
        </div>
        
        <!-- Section : Pointage -->
        <div class="card">
            <h2>⏰ Validation des pointages</h2>
            <p class="section-description">
                Tolérance pour les arrivées anticipées ou retards
            </p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="early_punch_tolerance_minutes">Tolérance arrivée anticipée (minutes)</label>
                    <input type="number" 
                           id="early_punch_tolerance_minutes" 
                           name="early_punch_tolerance_minutes" 
                           value="<?= htmlspecialchars($settings['early_punch_tolerance_minutes']['value']) ?>" 
                           min="0" 
                           max="120">
                    <small>Arrivée avant l'heure prévue acceptée</small>
                </div>
                
                <div class="form-group">
                    <label for="late_punch_tolerance_minutes">Tolérance retard (minutes)</label>
                    <input type="number" 
                           id="late_punch_tolerance_minutes" 
                           name="late_punch_tolerance_minutes" 
                           value="<?= htmlspecialchars($settings['late_punch_tolerance_minutes']['value']) ?>" 
                           min="0" 
                           max="120">
                    <small>Retard accepté sans alerte</small>
                </div>
            </div>
        </div>
        
        <!-- Section : Notifications -->
        <div class="card">
            <h2>🔔 Notifications</h2>
            <p class="section-description">
                Alertes en cas d'anomalie ou tentatives suspectes
            </p>
            
            <div class="form-check">
                <input type="checkbox" 
                       id="notifications_enabled" 
                       name="notifications_enabled" 
                       <?= $settings['notifications_enabled']['value'] ? 'checked' : '' ?>>
                <label for="notifications_enabled">
                    Activer les notifications par email
                </label>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="admin_notification_email">Email administrateur</label>
                <input type="email" 
                       id="admin_notification_email" 
                       name="admin_notification_email" 
                       value="<?= htmlspecialchars($settings['admin_notification_email']['value']) ?>" 
                       placeholder="admin@restaurant.com">
                <small>Recevoir les alertes de sécurité</small>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-large">
                ✅ Enregistrer les paramètres
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                Annuler
            </a>
        </div>
    </form>
    
    <!-- Tentatives échouées récentes -->
    <?php if (!empty($recentFailed)): ?>
    <div class="card" style="margin-top: 30px;">
        <h2>⚠️ Tentatives échouées récentes (24h)</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Employé</th>
                        <th>PIN saisi</th>
                        <th>Appareil</th>
                        <th>Tentatives</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentFailed as $attempt): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($attempt['created_at'])) ?></td>
                        <td>
                            <?php if ($attempt['first_name']): ?>
                                <?= htmlspecialchars($attempt['first_name'] . ' ' . $attempt['last_name']) ?>
                            <?php else: ?>
                                <span style="color: #e74c3c;">Inconnu</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars($attempt['pin_entered']) ?></code></td>
                        <td><small><?= htmlspecialchars(substr($attempt['device_id'], 0, 20)) ?>...</small></td>
                        <td>
                            <span class="badge <?= $attempt['attempts_count'] >= 3 ? 'badge-danger' : 'badge-warning' ?>">
                                <?= $attempt['attempts_count'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($attempt['locked_until'] && strtotime($attempt['locked_until']) > time()): ?>
                                <span class="badge badge-danger">
                                    🔒 Verrouillé jusqu'à <?= date('H:i', strtotime($attempt['locked_until'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-success">Déverrouillé</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.security-form .card {
    margin-bottom: 20px;
}

.security-form h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.section-description {
    color: #7f8c8d;
    margin-bottom: 20px;
    font-size: 14px;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
}

.form-check input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.form-check label {
    cursor: pointer;
    font-weight: 500;
    margin: 0;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group small {
    display: block;
    color: #7f8c8d;
    margin-top: 5px;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.stat-label {
    font-size: 14px;
    color: #7f8c8d;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 36px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-detail {
    font-size: 12px;
    color: #95a5a6;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}
</style>

<?php include 'footer.php'; ?>

