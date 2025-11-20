<?php
/**
 * GRAFIK - Page employé - Saisie nombre de boîtes vides
 * Interface en letton
 */

// Charger la configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';

// Vérifier qu'un employé est connecté
if (!isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'];

// Récupérer les informations de l'employé
$employeeModel = new Employee();
$employee = $employeeModel->getById($employee_id);

// Vérifier que c'est un employé de type Cuisine
if (!isset($employee['employee_type']) || $employee['employee_type'] !== 'Cuisine') {
    header('Location: punch.php?type=out&confirm=yes');
    exit;
}

// Traiter la soumission
$boxes_count = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $boxes_count = intval($_POST['boxes_count'] ?? 0);
    
    if ($boxes_count < 0) {
        $error = 'Skaits nevar būt negatīvs';
    } else {
        // Rediriger vers le pointage avec le nombre de boîtes
        header('Location: punch.php?type=out&confirm=yes&boxes=' . $boxes_count);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Grafik - Metāla kastītes</title>
    <link rel="stylesheet" href="../css/employee.css">
    <style>
        .boxes-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .boxes-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .boxes-display {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            font-size: 48px;
            font-weight: bold;
            color: #e74c3c;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .keypad-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 25px;
            border-radius: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.1s, background 0.1s;
        }
        .keypad-btn:active {
            transform: scale(0.95);
            background: #2980b9;
        }
        .keypad-btn.clear {
            background: #e74c3c;
            grid-column: span 2;
        }
        .keypad-btn.clear:active {
            background: #c0392b;
        }
        .keypad-btn.ok {
            background: #27ae60;
            grid-column: span 1;
        }
        .keypad-btn.ok:active {
            background: #229954;
        }
        .keypad-btn.cancel {
            background: #95a5a6;
            grid-column: span 3;
        }
        .keypad-btn.cancel:active {
            background: #7f8c8d;
        }
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="boxes-container">
            <div class="boxes-title">
                Metāla kastīšu skaits picas kastītēm
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <div class="boxes-display" id="boxesDisplay">0</div>
            
            <form method="POST" id="boxesForm">
                <input type="hidden" name="boxes_count" id="boxesCount" value="0">
                
                <div class="keypad">
                    <button type="button" class="keypad-btn" onclick="addDigit(1)">1</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(2)">2</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(3)">3</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(4)">4</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(5)">5</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(6)">6</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(7)">7</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(8)">8</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(9)">9</button>
                    <button type="button" class="keypad-btn clear" onclick="clearDisplay()">ATCEĻT</button>
                    <button type="button" class="keypad-btn" onclick="addDigit(0)">0</button>
                    <button type="submit" class="keypad-btn ok">OK</button>
                    <button type="button" class="keypad-btn cancel" onclick="window.location.href='actions.php'">ATCEĻT VISU</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function addDigit(digit) {
            const display = document.getElementById('boxesDisplay');
            const input = document.getElementById('boxesCount');
            let current = parseInt(display.textContent) || 0;
            current = current * 10 + digit;
            display.textContent = current;
            input.value = current;
        }
        
        function clearDisplay() {
            document.getElementById('boxesDisplay').textContent = '0';
            document.getElementById('boxesCount').value = '0';
        }
    </script>
</body>
</html>

