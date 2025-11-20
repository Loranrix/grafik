<?php
/**
 * GRAFIK - Gestion des pointages
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

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_punch') {
        $employee_id = intval($_POST['employee_id']);
        $punch_type = $_POST['punch_type'];
        $punch_date = $_POST['punch_date'];
        $punch_time = $_POST['punch_time'];
        $punch_datetime = $punch_date . ' ' . $punch_time;
        
        $punchModel->addManual($employee_id, $punch_type, $punch_datetime);
        $message = 'Pointage ajouté avec succès';
    } elseif ($action === 'update_punch') {
        $punch_id = intval($_POST['punch_id']);
        $punch_date = $_POST['punch_date'];
        $punch_time = $_POST['punch_time'];
        $punch_datetime = $punch_date . ' ' . $punch_time;
        $boxes_count = isset($_POST['boxes_count']) && $_POST['boxes_count'] !== '' ? intval($_POST['boxes_count']) : null;
        
        $punchModel->update($punch_id, $punch_datetime, $boxes_count);
        $message = 'Pointage modifié avec succès';
    } elseif ($action === 'delete_punch') {
        $punch_id = intval($_POST['punch_id']);
        $punchModel->delete($punch_id);
        $message = 'Pointage supprimé avec succès';
    }
}

$employees = $employeeModel->getAll(true);
$punches = $punchModel->getAllByDate($selected_date);

// Calculer les heures travaillées par employé pour la date sélectionnée
$hours_by_employee = [];
foreach ($employees as $emp) {
    $hours = $punchModel->calculateHours($emp['id'], $selected_date);
    if ($hours > 0) {
        $hours_by_employee[$emp['id']] = $hours;
    }
}
?>

<div class="container">
    <div class="page-header">
        <h1>Pointages</h1>
        <button class="btn btn-success" onclick="openAddPunchModal()">+ Ajouter un pointage</button>
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
    
    <?php if (count($hours_by_employee) > 0): ?>
    <div class="card">
        <h2>Heures travaillées le <?= date('d/m/Y', strtotime($selected_date)) ?></h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Heures travaillées</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hours_by_employee as $emp_id => $hours): 
                    $emp = $employeeModel->getById($emp_id);
                ?>
                <tr>
                    <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                    <td><strong><?= number_format($hours, 2) ?> h</strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>Liste des pointages - <?= date('d/m/Y', strtotime($selected_date)) ?></h2>
        
        <?php if (count($punches) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Type</th>
                    <th>Heure</th>
                    <th>Boîtes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($punches as $punch): ?>
                <tr>
                    <td><?= htmlspecialchars($punch['first_name'] . ' ' . $punch['last_name']) ?></td>
                    <td>
                        <?php if ($punch['punch_type'] === 'in'): ?>
                            <span style="color: #27ae60; font-weight: bold;">✓ Arrivée</span>
                        <?php else: ?>
                            <span style="color: #e74c3c; font-weight: bold;">← Départ</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('H:i:s', strtotime($punch['punch_datetime'])) ?></td>
                    <td>
                        <?php if (isset($punch['boxes_count']) && $punch['boxes_count'] !== null): ?>
                            <span style="color: #e74c3c; font-weight: bold;"><?= intval($punch['boxes_count']) ?></span>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick='editPunch(<?= json_encode($punch) ?>)'>
                            Modifier
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_punch">
                            <input type="hidden" name="punch_id" value="<?= $punch['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce pointage ?')">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #999; text-align: center; padding: 20px;">Aucun pointage pour cette date</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Modifier Pointage -->
<div class="modal" id="editPunchModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Modifier un pointage</h2>
            <button class="modal-close" onclick="closeEditPunchModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_punch">
            <input type="hidden" name="punch_id" id="edit_punch_id">
            
            <div class="form-group">
                <label for="edit_punch_date">Date</label>
                <input type="date" id="edit_punch_date" name="punch_date" required>
            </div>
            
            <div class="form-group">
                <label for="edit_punch_time">Heure</label>
                <input type="time" id="edit_punch_time" name="punch_time" step="1" required>
            </div>
            
            <div class="form-group">
                <label for="edit_boxes_count">Nombre de boîtes (optionnel)</label>
                <input type="number" id="edit_boxes_count" name="boxes_count" min="0" placeholder="Laisser vide si non applicable">
            </div>
            
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</div>

<!-- Modal Ajouter Pointage -->
<div class="modal" id="addPunchModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Ajouter un pointage manuel</h2>
            <button class="modal-close" onclick="closeAddPunchModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_punch">
            
            <div class="form-group">
                <label for="employee_id">Employé</label>
                <select id="employee_id" name="employee_id" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>">
                        <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="punch_type">Type</label>
                <select id="punch_type" name="punch_type" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                    <option value="in">Arrivée</option>
                    <option value="out">Départ</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="punch_date">Date</label>
                <input type="date" id="punch_date" name="punch_date" required>
            </div>
            
            <div class="form-group">
                <label for="punch_time">Heure</label>
                <input type="time" id="punch_time" name="punch_time" step="1" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</div>

<script>
function openAddPunchModal() {
    document.getElementById('addPunchModal').classList.add('active');
    // Définir la date d'aujourd'hui par défaut
    document.getElementById('punch_date').value = '<?= $selected_date ?>';
    document.getElementById('punch_time').value = new Date().toTimeString().slice(0, 5);
}

function closeAddPunchModal() {
    document.getElementById('addPunchModal').classList.remove('active');
}

function editPunch(punch) {
    // Utiliser l'ID du pointage (clé Firebase)
    document.getElementById('edit_punch_id').value = punch.id;
    
    // Parser la date/heure du pointage
    const punchDateTime = punch.punch_datetime || punch.datetime;
    let dateStr, timeStr;
    
    if (punchDateTime) {
        // Gérer le format "YYYY-MM-DD HH:MM:SS" ou "YYYY-MM-DDTHH:MM:SS"
        const dateTimeStr = punchDateTime.replace('T', ' ');
        const parts = dateTimeStr.split(' ');
        if (parts.length >= 1) {
            dateStr = parts[0];
        }
        if (parts.length >= 2) {
            timeStr = parts[1].substring(0, 5); // HH:MM
        }
    }
    
    // Fallback si pas de date trouvée
    if (!dateStr) {
        const now = new Date();
        dateStr = now.toISOString().split('T')[0];
        timeStr = now.toTimeString().slice(0, 5);
    }
    
    document.getElementById('edit_punch_date').value = dateStr;
    document.getElementById('edit_punch_time').value = timeStr || '';
    document.getElementById('edit_boxes_count').value = punch.boxes_count || '';
    document.getElementById('editPunchModal').classList.add('active');
}

function closeEditPunchModal() {
    document.getElementById('editPunchModal').classList.remove('active');
}

// Fermer modal en cliquant à l'extérieur
window.onclick = function(event) {
    const addPunchModal = document.getElementById('addPunchModal');
    const editPunchModal = document.getElementById('editPunchModal');
    if (event.target === addPunchModal) {
        closeAddPunchModal();
    }
    if (event.target === editPunchModal) {
        closeEditPunchModal();
    }
}
</script>

<?php include 'footer.php'; ?>

