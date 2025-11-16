<?php
/**
 * GRAFIK - Gestion du planning
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Shift.php';

include 'header.php';

$employeeModel = new Employee();
$shiftModel = new Shift();
$message = '';
$error = '';

// Mois et année courants
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Validation
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_shift') {
        $employee_id = intval($_POST['employee_id']);
        $shift_date = $_POST['shift_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        
        $shiftModel->create($employee_id, $shift_date, $start_time, $end_time);
        $message = 'Shift ajouté avec succès';
    } elseif ($action === 'delete_shift') {
        $shift_id = intval($_POST['shift_id']);
        $shiftModel->delete($shift_id);
        $message = 'Shift supprimé avec succès';
    }
}

$employees = $employeeModel->getAll(true);
$shifts = $shiftModel->getAllByMonth($year, $month);

// Organiser les shifts par date et employé
$shifts_by_date = [];
foreach ($shifts as $shift) {
    $date = $shift['shift_date'];
    if (!isset($shifts_by_date[$date])) {
        $shifts_by_date[$date] = [];
    }
    $shifts_by_date[$date][] = $shift;
}

// Calendrier
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$first_weekday = date('N', $first_day); // 1 = Lundi, 7 = Dimanche
?>

<div class="container">
    <div class="page-header">
        <h1>Planning</h1>
        <button class="btn btn-success" onclick="openAddShiftModal()">+ Ajouter un shift</button>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="calendar-header">
            <div class="calendar-nav">
                <a href="?year=<?= $year ?>&month=<?= $month - 1 ?>" class="btn btn-secondary btn-sm">← Mois précédent</a>
                <div class="calendar-month">
                    <?= strftime('%B %Y', $first_day) ?>
                </div>
                <a href="?year=<?= $year ?>&month=<?= $month + 1 ?>" class="btn btn-secondary btn-sm">Mois suivant →</a>
            </div>
        </div>
        
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Lun</th>
                    <th>Mar</th>
                    <th>Mer</th>
                    <th>Jeu</th>
                    <th>Ven</th>
                    <th>Sam</th>
                    <th>Dim</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $day = 1;
                $today = date('Y-m-d');
                
                for ($week = 0; $week < 6; $week++):
                    if ($day > $days_in_month) break;
                ?>
                <tr>
                    <?php for ($weekday = 1; $weekday <= 7; $weekday++): ?>
                        <?php if (($week == 0 && $weekday < $first_weekday) || $day > $days_in_month): ?>
                            <td></td>
                        <?php else:
                            $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $is_today = $current_date === $today;
                            $day_shifts = $shifts_by_date[$current_date] ?? [];
                            $has_shift = count($day_shifts) > 0;
                        ?>
                            <td>
                                <div class="calendar-day <?= $is_today ? 'today' : '' ?> <?= $has_shift ? 'has-shift' : '' ?>" 
                                     onclick="showDayShifts('<?= $current_date ?>')">
                                    <div class="date-number"><?= $day ?></div>
                                    <div class="shift-info">
                                        <?php foreach (array_slice($day_shifts, 0, 2) as $shift): ?>
                                            <div>
                                                <?= htmlspecialchars(substr($shift['first_name'], 0, 1) . '. ' . $shift['last_name']) ?>: 
                                                <?= date('H:i', strtotime($shift['start_time'])) ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($day_shifts) > 2): ?>
                                            <div>+<?= count($day_shifts) - 2 ?> autre(s)</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        <?php
                            $day++;
                        endif;
                        ?>
                    <?php endfor; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajouter Shift -->
<div class="modal" id="addShiftModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Ajouter un shift</h2>
            <button class="modal-close" onclick="closeAddShiftModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_shift">
            
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
                <label for="shift_date">Date</label>
                <input type="date" id="shift_date" name="shift_date" required>
            </div>
            
            <div class="form-group">
                <label for="start_time">Heure de début</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>
            
            <div class="form-group">
                <label for="end_time">Heure de fin</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</div>

<!-- Modal Détails Jour -->
<div class="modal" id="dayShiftsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="dayShiftsTitle">Shifts du jour</h2>
            <button class="modal-close" onclick="closeDayShiftsModal()">&times;</button>
        </div>
        <div id="dayShiftsContent">
            <!-- Rempli dynamiquement -->
        </div>
    </div>
</div>

<script>
function openAddShiftModal() {
    document.getElementById('addShiftModal').classList.add('active');
    // Définir la date d'aujourd'hui par défaut
    document.getElementById('shift_date').value = new Date().toISOString().split('T')[0];
}

function closeAddShiftModal() {
    document.getElementById('addShiftModal').classList.remove('active');
}

function showDayShifts(date) {
    const shifts = <?= json_encode($shifts_by_date) ?>;
    const dayShifts = shifts[date] || [];
    
    document.getElementById('dayShiftsTitle').textContent = 'Shifts du ' + new Date(date).toLocaleDateString('fr-FR');
    
    let html = '';
    if (dayShifts.length === 0) {
        html = '<p style="color: #999; text-align: center; padding: 20px;">Aucun shift ce jour</p>';
    } else {
        html = '<table class="table"><thead><tr><th>Employé</th><th>Horaires</th><th>Action</th></tr></thead><tbody>';
        dayShifts.forEach(shift => {
            html += '<tr>';
            html += '<td>' + shift.first_name + ' ' + shift.last_name + '</td>';
            html += '<td>' + shift.start_time.substring(0, 5) + ' - ' + shift.end_time.substring(0, 5) + '</td>';
            html += '<td>';
            html += '<form method="POST" style="display: inline;">';
            html += '<input type="hidden" name="action" value="delete_shift">';
            html += '<input type="hidden" name="shift_id" value="' + shift.id + '">';
            html += '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Supprimer ce shift ?\')">Supprimer</button>';
            html += '</form>';
            html += '</td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
    }
    
    document.getElementById('dayShiftsContent').innerHTML = html;
    document.getElementById('dayShiftsModal').classList.add('active');
}

function closeDayShiftsModal() {
    document.getElementById('dayShiftsModal').classList.remove('active');
}

// Fermer modals en cliquant à l'extérieur
window.onclick = function(event) {
    const addShiftModal = document.getElementById('addShiftModal');
    const dayShiftsModal = document.getElementById('dayShiftsModal');
    if (event.target === addShiftModal) {
        closeAddShiftModal();
    }
    if (event.target === dayShiftsModal) {
        closeDayShiftsModal();
    }
}
</script>

<?php include 'footer.php'; ?>

