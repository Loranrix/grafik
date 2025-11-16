<?php
/**
 * GRAFIK - Gestion des employés
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';

include 'header.php';

$employeeModel = new Employee();
$message = '';
$error = '';

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $pin = trim($_POST['pin']);
        
        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            $error = 'Le PIN doit contenir exactement 4 chiffres';
        } elseif ($employeeModel->pinExists($pin)) {
            $error = 'Ce PIN est déjà utilisé';
        } else {
            $employeeModel->create($first_name, $last_name, $phone, $pin);
            $message = 'Employé créé avec succès';
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $pin = trim($_POST['pin']);
        
        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            $error = 'Le PIN doit contenir exactement 4 chiffres';
        } elseif ($employeeModel->pinExists($pin, $id)) {
            $error = 'Ce PIN est déjà utilisé';
        } else {
            $employeeModel->update($id, $first_name, $last_name, $phone, $pin);
            $message = 'Employé modifié avec succès';
        }
    } elseif ($action === 'toggle_active') {
        $id = intval($_POST['id']);
        $is_active = intval($_POST['is_active']);
        $employeeModel->setActive($id, $is_active);
        $message = 'Statut modifié avec succès';
    }
}

$employees = $employeeModel->getAll(false);
?>

<div class="container">
    <div class="page-header">
        <h1>Gestion des employés</h1>
        <button class="btn btn-success" onclick="openCreateModal()">+ Nouvel employé</button>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Téléphone</th>
                    <th>PIN</th>
                    <th>QR Code</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?= htmlspecialchars($emp['last_name']) ?></td>
                    <td><?= htmlspecialchars($emp['first_name']) ?></td>
                    <td><?= htmlspecialchars($emp['phone'] ?? '-') ?></td>
                    <td><code><?= htmlspecialchars($emp['pin']) ?></code></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="showQR('<?= $emp['qr_code'] ?>', '<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>')">
                            Afficher QR
                        </button>
                    </td>
                    <td>
                        <?php if ($emp['is_active']): ?>
                            <span style="color: #27ae60; font-weight: bold;">● Actif</span>
                        <?php else: ?>
                            <span style="color: #e74c3c; font-weight: bold;">● Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <button class="btn btn-secondary btn-sm" onclick='editEmployee(<?= json_encode($emp) ?>)'>
                            Modifier
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                            <input type="hidden" name="is_active" value="<?= $emp['is_active'] ? 0 : 1 ?>">
                            <button type="submit" class="btn <?= $emp['is_active'] ? 'btn-danger' : 'btn-success' ?> btn-sm">
                                <?= $emp['is_active'] ? 'Désactiver' : 'Activer' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal" id="employeeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Nouvel employé</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="employeeForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="employeeId">
            
            <div class="form-group">
                <label for="last_name">Nom</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label for="first_name">Prénom</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" placeholder="+371 12345678">
            </div>
            
            <div class="form-group">
                <label for="pin">PIN (4 chiffres)</label>
                <input type="text" id="pin" name="pin" pattern="[0-9]{4}" maxlength="4" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal" id="qrModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="qrEmployeeName">QR Code</h2>
            <button class="modal-close" onclick="closeQRModal()">&times;</button>
        </div>
        <div class="qr-code-display">
            <div id="qrCodeImage"></div>
            <p style="margin-top: 20px; color: #666;">
                L'employé peut scanner ce QR code pour se connecter
            </p>
            <p style="font-size: 12px; color: #999; word-break: break-all;" id="qrCodeValue"></p>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nouvel employé';
    document.getElementById('formAction').value = 'create';
    document.getElementById('employeeId').value = '';
    document.getElementById('first_name').value = '';
    document.getElementById('last_name').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('pin').value = '';
    document.getElementById('employeeModal').classList.add('active');
}

function editEmployee(emp) {
    document.getElementById('modalTitle').textContent = 'Modifier l\'employé';
    document.getElementById('formAction').value = 'update';
    document.getElementById('employeeId').value = emp.id;
    document.getElementById('first_name').value = emp.first_name;
    document.getElementById('last_name').value = emp.last_name;
    document.getElementById('phone').value = emp.phone || '';
    document.getElementById('pin').value = emp.pin;
    document.getElementById('employeeModal').classList.add('active');
}

function closeModal() {
    document.getElementById('employeeModal').classList.remove('active');
}

function showQR(qrCode, employeeName) {
    document.getElementById('qrEmployeeName').textContent = 'QR Code - ' + employeeName;
    document.getElementById('qrCodeValue').textContent = qrCode;
    
    // Générer QR code avec une API externe simple
    const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(window.location.origin + '/grafik/employee/?qr=' + qrCode);
    document.getElementById('qrCodeImage').innerHTML = '<img src="' + qrUrl + '" alt="QR Code" style="max-width: 100%;">';
    
    document.getElementById('qrModal').classList.add('active');
}

function closeQRModal() {
    document.getElementById('qrModal').classList.remove('active');
}

// Fermer modal en cliquant à l'extérieur
window.onclick = function(event) {
    const employeeModal = document.getElementById('employeeModal');
    const qrModal = document.getElementById('qrModal');
    if (event.target === employeeModal) {
        closeModal();
    }
    if (event.target === qrModal) {
        closeQRModal();
    }
}
</script>

<?php include 'footer.php'; ?>

