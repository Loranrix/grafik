<?php
/**
 * GRAFIK - Logs et Audit
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/AuditLog.php';

$current_page = 'logs';
include 'header.php';

$auditLog = new AuditLog();

// Filtres
$log_type = $_GET['type'] ?? 'login';
$hours = intval($_GET['hours'] ?? 24);

// Récupérer les logs selon le type
switch ($log_type) {
    case 'admin':
        $logs = $auditLog->getAdminActionLogs(null, 200);
        break;
    case 'failed':
        $logs = $auditLog->getRecentFailedAttempts($hours);
        break;
    case 'login':
    default:
        $logs = $auditLog->getRecentLoginLogs($hours, 200);
        break;
}

$stats = $auditLog->getSecurityStats();
?>

<div class="container">
    <div class="page-header">
        <h1>📋 Logs et Audit</h1>
        <p class="subtitle">Historique des connexions et actions</p>
    </div>
    
    <!-- Statistiques -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-label">Tentatives de connexion (24h)</div>
            <div class="stat-value"><?= $stats['login_attempts_24h']['total'] ?? 0 ?></div>
            <div class="stat-detail">
                ✅ <?= $stats['login_attempts_24h']['successful'] ?? 0 ?> | 
                ❌ <?= $stats['login_attempts_24h']['failed'] ?? 0 ?>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Appareils verrouillés</div>
            <div class="stat-value"><?= $stats['locked_devices'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Actions admin (24h)</div>
            <div class="stat-value"><?= $stats['admin_actions_24h'] ?? 0 ?></div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="card filters-card">
        <div class="filters-row">
            <div class="filter-group">
                <label>Type de logs :</label>
                <select id="log_type" onchange="updateFilters()">
                    <option value="login" <?= $log_type === 'login' ? 'selected' : '' ?>>Connexions employés</option>
                    <option value="failed" <?= $log_type === 'failed' ? 'selected' : '' ?>>Tentatives échouées</option>
                    <option value="admin" <?= $log_type === 'admin' ? 'selected' : '' ?>>Actions admin</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Période :</label>
                <select id="hours" onchange="updateFilters()">
                    <option value="6" <?= $hours === 6 ? 'selected' : '' ?>>6 dernières heures</option>
                    <option value="24" <?= $hours === 24 ? 'selected' : '' ?>>24 dernières heures</option>
                    <option value="72" <?= $hours === 72 ? 'selected' : '' ?>>3 derniers jours</option>
                    <option value="168" <?= $hours === 168 ? 'selected' : '' ?>>7 derniers jours</option>
                </select>
            </div>
            
            <button class="btn btn-primary" onclick="exportLogs()">
                📥 Exporter
            </button>
        </div>
    </div>
    
    <!-- Logs -->
    <div class="card">
        <h2>
            <?php
            switch ($log_type) {
                case 'admin': echo '⚙️ Actions administrateur'; break;
                case 'failed': echo '❌ Tentatives échouées'; break;
                default: echo '📝 Connexions employés'; break;
            }
            ?>
        </h2>
        
        <div class="table-responsive">
            <?php if ($log_type === 'login'): ?>
            <!-- Table connexions -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Employé</th>
                        <th>QR Code</th>
                        <th>PIN</th>
                        <th>Appareil</th>
                        <th>IP</th>
                        <th>GPS</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="<?= $log['success'] ? 'log-success' : 'log-error' ?>">
                        <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></strong>
                        </td>
                        <td><code><?= htmlspecialchars(substr($log['qr_code'], 0, 10)) ?>...</code></td>
                        <td><code><?= htmlspecialchars($log['pin_entered']) ?></code></td>
                        <td><small><?= htmlspecialchars(substr($log['device_id'] ?? 'N/A', 0, 15)) ?></small></td>
                        <td><small><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></small></td>
                        <td>
                            <?php if ($log['gps_latitude'] && $log['gps_longitude']): ?>
                                <a href="https://maps.google.com/?q=<?= $log['gps_latitude'] ?>,<?= $log['gps_longitude'] ?>" 
                                   target="_blank" 
                                   title="Voir sur Google Maps">
                                    📍
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['success']): ?>
                                <span class="badge badge-success">✓ Réussi</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ Échoué</span>
                                <small style="display: block; color: #e74c3c; margin-top: 3px;">
                                    <?= htmlspecialchars($log['failure_reason']) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php elseif ($log_type === 'failed'): ?>
            <!-- Table tentatives échouées -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Employé</th>
                        <th>QR Code</th>
                        <th>PIN saisi</th>
                        <th>Appareil</th>
                        <th>IP</th>
                        <th>Tentatives</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="log-error">
                        <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <?php if ($log['first_name']): ?>
                                <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?>
                            <?php else: ?>
                                <span style="color: #e74c3c;">Inconnu</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars(substr($log['qr_code'], 0, 10)) ?>...</code></td>
                        <td><code><?= htmlspecialchars($log['pin_entered']) ?></code></td>
                        <td><small><?= htmlspecialchars(substr($log['device_id'], 0, 15)) ?></small></td>
                        <td><small><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></small></td>
                        <td>
                            <span class="badge <?= $log['attempts_count'] >= 3 ? 'badge-danger' : 'badge-warning' ?>">
                                <?= $log['attempts_count'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($log['locked_until'] && strtotime($log['locked_until']) > time()): ?>
                                <span class="badge badge-danger">
                                    🔒 Verrouillé jusqu'à <?= date('H:i', strtotime($log['locked_until'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-success">Déverrouillé</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php else: ?>
            <!-- Table actions admin -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Cible</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                        <td><code><?= htmlspecialchars($log['action_type']) ?></code></td>
                        <td><?= htmlspecialchars($log['action_description']) ?></td>
                        <td>
                            <?php if ($log['target_type']): ?>
                                <?= htmlspecialchars($log['target_type']) ?> 
                                <?php if ($log['target_id']): ?>
                                    #<?= $log['target_id'] ?>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><small><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (empty($logs)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <p>Aucun log trouvé pour cette période</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.filters-card {
    padding: 20px;
    margin-bottom: 20px;
}

.filters-row {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 14px;
    font-weight: bold;
    color: #2c3e50;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.log-success {
    background: #f8fff8;
}

.log-error {
    background: #fff8f8;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
}

.data-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.data-table tr:hover {
    background: #f8f9fa;
}

code {
    background: #ecf0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
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
</style>

<script>
function updateFilters() {
    const logType = document.getElementById('log_type').value;
    const hours = document.getElementById('hours').value;
    window.location.href = `logs.php?type=${logType}&hours=${hours}`;
}

function exportLogs() {
    alert('Fonction d\'export en développement');
    // TODO: Implémenter l'export CSV/Excel
}
</script>

<?php include 'footer.php'; ?>

