<?php
/**
 * GRAFIK - Gestion des QR Codes
 * Génération et téléchargement des QR codes pour les employés
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';

include 'header.php';

$employeeModel = new Employee();
$employees = $employeeModel->getAll(false); // Tous les employés (actifs + inactifs)
?>

<div class="container">
    <div class="page-header">
        <h1>QR Codes Employés</h1>
        <p style="color: #7f8c8d; margin-top: 10px;">
            Générez et téléchargez les QR codes pour vos employés
        </p>
    </div>
    
    <div class="card">
        <div style="padding: 20px;">
            <h3 style="margin-bottom: 20px;">Liste des employés</h3>
            
            <div class="qr-grid">
                <?php foreach ($employees as $emp): ?>
                <div class="qr-card <?= $emp['is_active'] ? '' : 'inactive' ?>">
                    <div class="qr-card-header">
                        <h4><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></h4>
                        <?php if (!$emp['is_active']): ?>
                            <span class="badge-inactive">Inactif</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="qr-code-container">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($emp['qr_code']) ?>" 
                             alt="QR Code <?= htmlspecialchars($emp['first_name']) ?>"
                             class="qr-code-img">
                    </div>
                    
                    <div class="qr-card-info">
                        <p><strong>PIN:</strong> <code><?= htmlspecialchars($emp['pin']) ?></code></p>
                        <?php if ($emp['phone']): ?>
                        <p><strong>Tél:</strong> <?= htmlspecialchars($emp['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="qr-card-actions">
                        <button class="btn btn-primary btn-sm" onclick="downloadQR('<?= $emp['qr_code'] ?>', '<?= htmlspecialchars($emp['first_name'] . '_' . $emp['last_name']) ?>')">
                            📥 Télécharger PNG
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="printQR('<?= $emp['id'] ?>')">
                            🖨️ Imprimer
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($employees)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <p>Aucun employé enregistré</p>
                <a href="employees.php" class="btn btn-primary" style="margin-top: 10px;">
                    + Créer un employé
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.qr-card {
    background: white;
    border: 2px solid #3498db;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.qr-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.qr-card.inactive {
    border-color: #e74c3c;
    opacity: 0.7;
}

.qr-card-header {
    margin-bottom: 15px;
}

.qr-card-header h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
}

.badge-inactive {
    display: inline-block;
    background: #e74c3c;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    margin-top: 5px;
}

.qr-code-container {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.qr-code-img {
    max-width: 100%;
    height: auto;
    border: 1px solid #ecf0f1;
    border-radius: 4px;
}

.qr-card-info {
    margin: 15px 0;
    text-align: left;
}

.qr-card-info p {
    margin: 5px 0;
    font-size: 14px;
    color: #34495e;
}

.qr-card-info code {
    background: #ecf0f1;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: bold;
}

.qr-card-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.qr-card-actions .btn {
    flex: 1;
    font-size: 13px;
}

@media print {
    body * {
        visibility: hidden;
    }
    .print-area, .print-area * {
        visibility: visible;
    }
    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>

<script>
function downloadQR(qrCode, employeeName) {
    const size = 500;
    const url = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(qrCode)}`;
    
    // Créer un lien temporaire pour télécharger
    const link = document.createElement('a');
    link.href = url;
    link.download = `QR_${employeeName}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printQR(employeeId) {
    window.print();
}

// Télécharger tous les QR codes
function downloadAllQR() {
    const employees = <?= json_encode($employees) ?>;
    employees.forEach((emp, index) => {
        setTimeout(() => {
            downloadQR(emp.qr_code, emp.first_name + '_' + emp.last_name);
        }, index * 500); // Délai pour éviter de bloquer le navigateur
    });
}
</script>

<?php include 'footer.php'; ?>

