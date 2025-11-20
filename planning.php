<?php
/**
 * GRAFIK - Gestion du Planning
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Schedule.php';
require_once __DIR__ . '/../classes/Punch.php';
require_once __DIR__ . '/../classes/GoogleSheets.php';
require_once __DIR__ . '/../classes/SecuritySettings.php';

include 'header.php';

$employeeModel = new Employee();
$scheduleModel = new Schedule();
$punchModel = new Punch();

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

$settingsModel = new SecuritySettings();
$googleSheets = new GoogleSheets();

// Récupérer l'URL Google Sheets
$sheets_url = $settingsModel->get('google_sheets_url', '');

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_sheets_url') {
        $sheets_url = trim($_POST['sheets_url'] ?? '');
        $settingsModel->set('google_sheets_url', $sheets_url, 'string', 'URL Google Sheets pour import planning');
        $message = 'URL Google Sheets enregistrée';
    } elseif ($action === 'import_from_sheets') {
        try {
            if (empty($sheets_url)) {
                throw new Exception('Veuillez d\'abord configurer l\'URL Google Sheets');
            }
            $result = $googleSheets->importFromUrl($sheets_url, $current_year, $current_month);
            $message = $result['imported'] . ' planning(s) importé(s) depuis Google Sheets';
            if (!empty($result['errors'])) {
                $error = 'Erreurs: ' . implode(', ', array_slice($result['errors'], 0, 5));
                if (count($result['errors']) > 5) {
                    $error .= '... (' . count($result['errors']) . ' erreurs au total)';
                }
            }
            // Recharger les données
            $schedules = $scheduleModel->getForMonth($current_year, $current_month);
            $schedules_map = [];
            foreach ($schedules as $schedule) {
                $key = $schedule['employee_id'] . '_' . $schedule['schedule_date'];
                $schedules_map[$key] = $schedule;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'save_schedule') {
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

// Calculer les jours du mois d'abord
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);

$employees = $employeeModel->getAll(true);
$schedules = $scheduleModel->getForMonth($current_year, $current_month);
$stats = $scheduleModel->getMonthStats($current_year, $current_month);

// Créer un tableau associatif pour accès rapide
$schedules_map = [];
foreach ($schedules as $schedule) {
    $key = $schedule['employee_id'] . '_' . $schedule['schedule_date'];
    $schedules_map[$key] = $schedule;
}

// Récupérer les pointages du mois
$start_date = date('Y-m-d', mktime(0, 0, 0, $current_month, 1, $current_year));
$end_date = date('Y-m-d', mktime(0, 0, 0, $current_month, $days_in_month, $current_year));

$punches_map = [];
foreach ($employees as $employee) {
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = date('Y-m-d', mktime(0, 0, 0, $current_month, $day, $current_year));
        $punches = $punchModel->getByEmployeeAndDate($employee['id'], $date);
        
        $in_time = null;
        $out_time = null;
        $hours = 0;
        
        foreach ($punches as $punch) {
            if ($punch['punch_type'] === 'in') {
                $in_time = substr($punch['punch_datetime'], 11, 5);
            } elseif ($punch['punch_type'] === 'out') {
                $out_time = substr($punch['punch_datetime'], 11, 5);
            }
        }
        
        if ($in_time && $out_time) {
            $start = strtotime($date . ' ' . $in_time);
            $end = strtotime($date . ' ' . $out_time);
            $hours = round(($end - $start) / 3600, 1);
        }
        
        if ($in_time || $out_time) {
            $key = $employee['id'] . '_' . $date;
            $punches_map[$key] = [
                'in_time' => $in_time,
                'out_time' => $out_time,
                'hours' => $hours
            ];
        }
    }
}

// Le nom du mois pour l'affichage
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
            <!-- Google Sheets Import -->
            <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                <h3 style="margin: 0 0 10px 0; font-size: 16px;">📊 Import depuis Google Sheets</h3>
                <form method="POST" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="save_sheets_url">
                    <div style="flex: 1; min-width: 300px;">
                        <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #666;">URL Google Sheets (public):</label>
                        <input type="url" name="sheets_url" value="<?= htmlspecialchars($sheets_url) ?>" 
                               placeholder="https://docs.google.com/spreadsheets/d/ID/edit" 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small style="color: #666; font-size: 12px;">Format CSV: Date, Employé, Heure début, Heure fin</small>
                    </div>
                    <button type="submit" class="btn btn-secondary" style="padding: 8px 15px;">💾 Enregistrer URL</button>
                    <?php if (!empty($sheets_url)): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="import_from_sheets">
                        <button type="submit" class="btn btn-primary" style="padding: 8px 15px;" 
                                onclick="return confirm('Importer le planning depuis Google Sheets pour <?= ucfirst($month_name) ?> <?= $current_year ?>?')">
                            🔄 Importer maintenant
                        </button>
                    </form>
                    <?php endif; ?>
                </form>
            </div>
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
                        <?php 
                        $punch = $punches_map[$key] ?? null;
                        $has_data = $schedule || $punch;
                        ?>
                        <td class="schedule-cell <?= $is_weekend ? 'weekend' : '' ?> <?= $has_data ? 'has-schedule' : '' ?>"
                            onclick="openScheduleModal(<?= $employee['id'] ?>, '<?= $date ?>', <?= $schedule ? json_encode($schedule) : 'null' ?>)">
                            <?php if ($schedule): ?>
                                <div class="schedule-time planned">
                                    <small>Prévu:</small><br>
                                    <?= substr($schedule['start_time'], 0, 5) ?> - <?= substr($schedule['end_time'], 0, 5) ?>
                                </div>
                                <?php 
                                $start = strtotime($schedule['start_time']);
                                $end = strtotime($schedule['end_time']);
                                $hours = round(($end - $start) / 3600, 1);
                                ?>
                                <div class="schedule-hours planned-hours"><?= $hours ?>h</div>
                            <?php endif; ?>
                            
                            <?php if ($punch): ?>
                                <div class="schedule-time actual" style="margin-top: <?= $schedule ? '8px' : '0' ?>;">
                                    <small>Réel:</small><br>
                                    <?php if ($punch['in_time']): ?><?= $punch['in_time'] ?><?php endif; ?>
                                    <?php if ($punch['in_time'] && $punch['out_time']): ?> - <?php endif; ?>
                                    <?php if ($punch['out_time']): ?><?= $punch['out_time'] ?><?php endif; ?>
                                </div>
                                <?php if ($punch['hours'] > 0): ?>
                                <div class="schedule-hours actual-hours"><?= $punch['hours'] ?>h</div>
                                <?php endif; ?>
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

<!-- Modal Édition Rapide -->
<div class="modal" id="bulkEditModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>✏️ Édition rapide du planning</h2>
            <button class="modal-close" onclick="closeBulkEditModal()">&times;</button>
        </div>
        
        <p style="color: #7f8c8d; margin-bottom: 20px;">
            Définissez les horaires par défaut, puis sélectionnez les jours pour chaque employé
        </p>
        
        <div class="form-row" style="margin-bottom: 30px; padding: 20px; background: #ecf0f1; border-radius: 8px;">
            <div class="form-group">
                <label for="bulk_start_time">Heure de début par défaut</label>
                <input type="time" id="bulk_start_time" value="09:00">
            </div>
            
            <div class="form-group">
                <label for="bulk_end_time">Heure de fin par défaut</label>
                <input type="time" id="bulk_end_time" value="17:00">
            </div>
        </div>
        
        <div style="max-height: 400px; overflow-y: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Jours à planifier</th>
                        <th>Heures</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                        <td>
                            <input type="text" 
                                   id="bulk_days_<?= $emp['id'] ?>" 
                                   placeholder="Ex: 1,2,3,5,8-15,22"
                                   style="width: 100%;">
                            <small style="color: #7f8c8d;">Jours individuels ou plages (1-5)</small>
                        </td>
                        <td>
                            <input type="time" id="bulk_emp_start_<?= $emp['id'] ?>" style="width: 80px;">
                            -
                            <input type="time" id="bulk_emp_end_<?= $emp['id'] ?>" style="width: 80px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="modal-actions" style="margin-top: 20px;">
            <button type="button" class="btn btn-secondary" onclick="closeBulkEditModal()">
                Annuler
            </button>
            <button type="button" class="btn btn-primary" onclick="saveBulkSchedules()">
                💾 Enregistrer tout
            </button>
        </div>
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
    margin-bottom: 3px;
    font-size: 11px;
}

.schedule-time.planned {
    color: #27ae60;
    background: #d5f4e6;
    padding: 4px;
    border-radius: 4px;
}

.schedule-time.actual {
    color: #3498db;
    background: #d6eaf8;
    padding: 4px;
    border-radius: 4px;
}

.schedule-time small {
    font-size: 9px;
    font-weight: normal;
    text-transform: uppercase;
}

.schedule-hours {
    font-size: 10px;
    font-weight: bold;
    margin-top: 2px;
}

.schedule-hours.planned-hours {
    color: #27ae60;
}

.schedule-hours.actual-hours {
    color: #3498db;
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
    const defaultStart = document.getElementById('bulk_start_time').value;
    const defaultEnd = document.getElementById('bulk_end_time').value;
    
    // Remplir les champs avec les valeurs par défaut
    <?php foreach ($employees as $emp): ?>
    document.getElementById('bulk_emp_start_<?= $emp['id'] ?>').value = defaultStart;
    document.getElementById('bulk_emp_end_<?= $emp['id'] ?>').value = defaultEnd;
    <?php endforeach; ?>
    
    document.getElementById('bulkEditModal').classList.add('active');
}

function closeBulkEditModal() {
    document.getElementById('bulkEditModal').classList.remove('active');
}

function parseDays(daysStr) {
    const days = [];
    const parts = daysStr.split(',');
    
    parts.forEach(part => {
        part = part.trim();
        if (part.includes('-')) {
            const [start, end] = part.split('-').map(n => parseInt(n.trim()));
            for (let i = start; i <= end; i++) {
                days.push(i);
            }
        } else {
            const day = parseInt(part);
            if (!isNaN(day)) {
                days.push(day);
            }
        }
    });
    
    return days;
}

function saveBulkSchedules() {
    const schedules = [];
    const currentMonth = <?= $current_month ?>;
    const currentYear = <?= $current_year ?>;
    const daysInMonth = <?= $days_in_month ?>;
    
    <?php foreach ($employees as $emp): ?>
    {
        const daysStr = document.getElementById('bulk_days_<?= $emp['id'] ?>').value.trim();
        const startTime = document.getElementById('bulk_emp_start_<?= $emp['id'] ?>').value;
        const endTime = document.getElementById('bulk_emp_end_<?= $emp['id'] ?>').value;
        
        if (daysStr && startTime && endTime) {
            const days = parseDays(daysStr);
            days.forEach(day => {
                if (day >= 1 && day <= daysInMonth) {
                    const date = currentYear + '-' + String(currentMonth).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                    schedules.push({
                        employee_id: <?= $emp['id'] ?>,
                        date: date,
                        start_time: startTime,
                        end_time: endTime,
                        notes: ''
                    });
                }
            });
        }
    }
    <?php endforeach; ?>
    
    if (schedules.length === 0) {
        alert('Aucun horaire à enregistrer');
        return;
    }
    
    if (!confirm(`Enregistrer ${schedules.length} horaire(s) ?`)) {
        return;
    }
    
    // Envoyer au serveur
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="save_bulk">
        <input type="hidden" name="schedules" value='${JSON.stringify(schedules)}'>
    `;
    document.body.appendChild(form);
    form.submit();
}

function exportPlanning() {
    window.print();
}

window.onclick = function(event) {
    const scheduleModal = document.getElementById('scheduleModal');
    const bulkModal = document.getElementById('bulkEditModal');
    
    if (event.target === scheduleModal) {
        closeScheduleModal();
    }
    if (event.target === bulkModal) {
        closeBulkEditModal();
    }
}
</script>

<?php include 'footer.php'; ?>
