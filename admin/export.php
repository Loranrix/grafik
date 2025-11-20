<?php
/**
 * GRAFIK - Export de données
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';
require_once __DIR__ . '/../classes/Export.php';

$employeeModel = new Employee();
$punchModel = new Punch();

// Traiter l'export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $format = $_GET['format'] ?? 'excel';
    $employee_id = $_GET['employee_id'] ?? 'all';
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');
    
    try {
        // Récupérer les données
        if ($employee_id === 'all') {
            $punches = $punchModel->getAllByDateRange($start_date, $end_date);
            $employee = null;
        } else {
            $punches = $punchModel->getByEmployeeDateRange($employee_id, $start_date, $end_date);
            $employee = $employeeModel->getById($employee_id);
        }
        
        // Exporter
        if ($format === 'pdf') {
            $result = Export::exportPunchesToPDF($punches, $employee, $start_date, $end_date);
        } else {
            $result = Export::exportPunchesToExcel($punches, $employee, $start_date, $end_date);
        }
        
        // Télécharger
        Export::downloadFile($result['filepath'], $result['filename']);
        
    } catch (Exception $e) {
        die('Erreur lors de l\'export: ' . $e->getMessage());
    }
}

$current_page = 'export';
include 'header.php';

$employees = $employeeModel->getAll(true);
?>

<div class="container">
    <div class="page-header">
        <h1>📥 Export de données</h1>
        <p class="subtitle">Exporter les pointages en PDF ou Excel</p>
    </div>
    
    <div class="card">
        <h2>Paramètres d'export</h2>
        
        <form method="GET" action="export.php" id="exportForm">
            <input type="hidden" name="action" value="export">
            
            <div class="form-group">
                <label for="format">Format d'export</label>
                <select id="format" name="format" required class="form-control">
                    <option value="excel">Excel (.xlsx)</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="employee_id">Employé</label>
                <select id="employee_id" name="employee_id" class="form-control">
                    <option value="all">Tous les employés</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>">
                        <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Date de début</label>
                    <input type="date" 
                           id="start_date" 
                           name="start_date" 
                           value="<?= date('Y-m-01') ?>" 
                           required 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="end_date">Date de fin</label>
                    <input type="date" 
                           id="end_date" 
                           name="end_date" 
                           value="<?= date('Y-m-t') ?>" 
                           required 
                           class="form-control">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    📥 Générer et télécharger
                </button>
            </div>
        </form>
    </div>
    
    <!-- Préconfigurations rapides -->
    <div class="card">
        <h2>Exports rapides</h2>
        <div class="quick-exports">
            <button class="quick-export-btn" onclick="quickExport('excel', 'all', 'current_month')">
                📊 Ce mois - Tous (Excel)
            </button>
            <button class="quick-export-btn" onclick="quickExport('pdf', 'all', 'current_month')">
                📄 Ce mois - Tous (PDF)
            </button>
            <button class="quick-export-btn" onclick="quickExport('excel', 'all', 'last_month')">
                📊 Mois dernier - Tous (Excel)
            </button>
            <button class="quick-export-btn" onclick="quickExport('pdf', 'all', 'last_month')">
                📄 Mois dernier - Tous (PDF)
            </button>
        </div>
    </div>
    
    <!-- Informations -->
    <div class="card info-card">
        <h3>ℹ️ Contenu de l'export</h3>
        <ul>
            <li>Date de chaque pointage</li>
            <li>Heures d'arrivée et de départ</li>
            <li>Durée des pauses</li>
            <li>Total des heures travaillées par jour</li>
            <li>Total général de la période</li>
        </ul>
        
        <h3 style="margin-top: 20px;">📋 Formats disponibles</h3>
        <ul>
            <li><strong>Excel (.xlsx)</strong> : Tableau modifiable, idéal pour analyse</li>
            <li><strong>PDF</strong> : Document imprimable, idéal pour archivage</li>
        </ul>
    </div>
</div>

<style>
.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #2c3e50;
}

.form-actions {
    margin-top: 30px;
}

.quick-exports {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.quick-export-btn {
    padding: 15px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-export-btn:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.info-card {
    background: #e8f4f8;
}

.info-card h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.info-card ul {
    margin-left: 20px;
    color: #555;
}

.info-card li {
    margin-bottom: 8px;
}
</style>

<script>
function quickExport(format, employee_id, period) {
    const today = new Date();
    let start_date, end_date;
    
    if (period === 'current_month') {
        start_date = new Date(today.getFullYear(), today.getMonth(), 1);
        end_date = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (period === 'last_month') {
        start_date = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        end_date = new Date(today.getFullYear(), today.getMonth(), 0);
    }
    
    const formatDate = (d) => d.toISOString().split('T')[0];
    
    const url = `export.php?action=export&format=${format}&employee_id=${employee_id}&start_date=${formatDate(start_date)}&end_date=${formatDate(end_date)}`;
    window.location.href = url;
}
</script>

<?php include 'footer.php'; ?>

