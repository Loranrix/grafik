<?php
/**
 * GRAFIK - Gestion du Planning
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Schedule.php';

include 'header.php';

$employeeModel = new Employee();
$scheduleModel = new Schedule();

// Récupérer le mois et l'année (par défaut : mois actuel)
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validation
if ($current_month < 1) $current_month = 1;
if ($current_month > 12) $current_month = 12;
if ($current_year < 2020) $current_year = 2020;
if ($current_year > 2100) $current_year = 2100;

$message = '';
$error = '';

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_schedule') {
        $employee_id = intval($_POST['employee_id']);
        $date = $_POST['date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $notes = trim($_POST['notes'] ?? '');
        
        // Validation
        if (strtotime($start_time) >= strtotime($end_time)) {
            $error = 'L\'heure de fin doit être après l\'heure de début';
        } else {
            $scheduleModel->saveSchedule($employee_id, $date, $start_time, $end_time, $notes);
            $message = 'Planning enregistré avec succès';
        }
    } elseif ($action === 'delete_schedule') {
        $employee_id = intval($_POST['employee_id']);
        $date = $_POST['date'];
        $scheduleModel->deleteForEmployeeDate($employee_id, $date);
        $message = 'Planning supprimé';
    } elseif ($action === 'save_bulk') {
        $schedules = json_decode($_POST['schedules'], true);
        foreach ($schedules as $schedule) {
            if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                $scheduleModel->saveSchedule(
                    $schedule['employee_id'],
                    $schedule['date'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                    $schedule['notes'] ?? null
                );
            }
        }
        $message = 'Planning enregistré avec succès';
    }
}

$employees = $employeeModel->getAll(true);
$schedules = $scheduleModel->getForMonth($current_year, $current_month);
$stats = $scheduleModel->getMonthStats($current_year, $current_year);

// Créer un tableau associatif pour accès rapide
$schedules_map = [];
foreach ($schedules as $schedule) {
    $key = $schedule['employee_id'] . '_' . $schedule['schedule_date'];
    $schedules_map[$key] = $schedule;
}

// Calculer les jours du mois
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$month_name = strftime('%B %Y', $first_day);

// Navigation mois précédent/suivant
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
?>

<div class="container">
    <div class="page-header">
        <h1>Planning - <?= ucfirst($month_name) ?></h1>
        <div class="month-navigation">
            <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-secondary">
                ← Mois précédent
            </a>
            <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-secondary">
                Aujourd'hui
            </a>
            <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-secondary">
                Mois suivant →
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="planning-toolbar">
            <button class="btn btn-primary" onclick="openBulkEditModal()">
                ✏️ Édition rapide
            </button>
            <button class="btn btn-secondary" onclick="exportPlanning()">
                📥 Exporter
            </button>
        </div>
        
        <div class="planning-table-wrapper">
            <table class="planning-table">
                <thead>
                    <tr>
                        <th class="employee-col">Employé</th>
                        <?php for ($day = 1; $day <= $days_in_month; $day++): 
                            $date = date('Y-m-d', mktime(0, 0, 0, $current_month, $day, $current_year));
                            $day_name = strftime('%a', strtotime($date));
                            $is_weekend = in_array(date('N', strtotime($date)), [6, 7]);
                        ?>
                        <th class="day-col <?= $is_weekend ? 'weekend' : '' ?>">
                            <div class="day-number"><?= $day ?></div>
                            <div class="day-name"><?= $day_name ?></div>
                        </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td class="employee-name">
                            <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                        </td>
                        <?php for ($day = 1; $day <= $days_in_month; $day++): 
                            $date = date('Y-m-d', mktime(0, 0, 0, $current_month, $day, $current_year));
                            $key = $employee['id'] . '_' . $date;
                            $schedule = $schedules_map[$key] ?? null;
                            $is_weekend = in_array(date('N', strtotime($date)), [6, 7]);
                        ?>
                        <td class="schedule-cell <?= $is_weekend ? 'weekend' : '' ?> <?= $schedule ? 'has-schedule' : '' ?>"
                            onclick="openScheduleModal(<?= $employee['id'] ?>, '<?= $date ?>', <?= $schedule ? json_encode($schedule) : 'null' ?>)">
                            <?php if ($schedule): ?>
                                <div class="schedule-time">
                                    <?= substr($schedule['start_time'], 0, 5) ?> - <?= substr($schedule['end_time'], 0, 5) ?>
                                </div>
                                <?php 
                                $start = strtotime($schedule['start_time']);
                                $end = strtotime($schedule['end_time']);
                                $hours = round(($end - $start) / 3600, 1);
                                ?>
                                <div class="schedule-hours"><?= $hours ?>h</div>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Édition Planning -->
<div class="modal" id="scheduleModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="scheduleModalTitle">Ajouter un horaire</h2>
            <button class="modal-close" onclick="closeScheduleModal()">&times;</button>
        </div>
        <form method="POST" id="scheduleForm">
            <input type="hidden" name="action" value="save_schedule">
            <input type="hidden" name="employee_id" id="schedule_employee_id">
            <input type="hidden" name="date" id="schedule_date">
            
            <div class="form-group">
                <label>Employé</label>
                <input type="text" id="schedule_employee_name" readonly class="readonly">
            </div>
            
            <div class="form-group">
                <label>Date</label>
                <input type="text" id="schedule_date_display" readonly class="readonly">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Heure de début</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time">Heure de fin</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes (optionnel)</label>
                <textarea id="notes" name="notes" rows="2"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="deleteSchedule()" id="deleteBtn" style="display:none;">
                    Supprimer
                </button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<style>
.month-navigation {
    display: flex;
    gap: 10px;
}

.planning-toolbar {
    padding: 15px;
    display: flex;
    gap: 10px;
    border-bottom: 1px solid #ecf0f1;
}

.planning-table-wrapper {
    overflow-x: auto;
}

.planning-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.planning-table th,
.planning-table td {
    border: 1px solid #ddd;
    padding: 5px;
    text-align: center;
}

.employee-col {
    position: sticky;
    left: 0;
    background: #2c3e50;
    color: white;
    z-index: 10;
    min-width: 150px;
    text-align: left;
}

.employee-name {
    position: sticky;
    left: 0;
    background: #ecf0f1;
    font-weight: bold;
    text-align: left;
    padding-left: 10px;
    z-index: 5;
}

.day-col {
    min-width: 80px;
    background: #34495e;
    color: white;
}

.day-col.weekend {
    background: #e74c3c;
}

.day-number {
    font-weight: bold;
    font-size: 14px;
}

.day-name {
    font-size: 10px;
    text-transform: uppercase;
}

.schedule-cell {
    cursor: pointer;
    transition: background 0.2s;
    min-height: 50px;
    vertical-align: middle;
}

.schedule-cell:hover {
    background: #ecf0f1;
}

.schedule-cell.weekend {
    background: #fde8e8;
}

.schedule-cell.has-schedule {
    background: #d4edda;
}

.schedule-cell.has-schedule.weekend {
    background: #f8d7da;
}

.schedule-time {
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 3px;
}

.schedule-hours {
    font-size: 10px;
    color: #7f8c8d;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.readonly {
    background: #ecf0f1;
    border: 1px solid #bdc3c7;
}

.modal-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}
</style>

<script>
let currentScheduleId = null;

function openScheduleModal(employeeId, date, schedule) {
    const employee = <?= json_encode($employees) ?>.find(e => e.id == employeeId);
    
    document.getElementById('schedule_employee_id').value = employeeId;
    document.getElementById('schedule_date').value = date;
    document.getElementById('schedule_employee_name').value = employee.first_name + ' ' + employee.last_name;
    document.getElementById('schedule_date_display').value = formatDate(date);
    
    if (schedule) {
        document.getElementById('scheduleModalTitle').textContent = 'Modifier l\'horaire';
        document.getElementById('start_time').value = schedule.start_time.substring(0, 5);
        document.getElementById('end_time').value = schedule.end_time.substring(0, 5);
        document.getElementById('notes').value = schedule.notes || '';
        document.getElementById('deleteBtn').style.display = 'inline-block';
        currentScheduleId = schedule.id;
    } else {
        document.getElementById('scheduleModalTitle').textContent = 'Ajouter un horaire';
        document.getElementById('start_time').value = '09:00';
        document.getElementById('end_time').value = '17:00';
        document.getElementById('notes').value = '';
        document.getElementById('deleteBtn').style.display = 'none';
        currentScheduleId = null;
    }
    
    document.getElementById('scheduleModal').classList.add('active');
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('active');
}

function deleteSchedule() {
    if (!confirm('Supprimer cet horaire ?')) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete_schedule">
        <input type="hidden" name="employee_id" value="${document.getElementById('schedule_employee_id').value}">
        <input type="hidden" name="date" value="${document.getElementById('schedule_date').value}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function formatDate(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
}

function openBulkEditModal() {
    alert('Fonction d\'édition rapide en développement');
}

function exportPlanning() {
    window.print();
}

window.onclick = function(event) {
    const modal = document.getElementById('scheduleModal');
    if (event.target === modal) {
        closeScheduleModal();
    }
}
</script>

<?php include 'footer.php'; ?>
