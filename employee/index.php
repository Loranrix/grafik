<?php
/**
 * GRAFIK - Page employé - Clavier PIN
 * Interface en letton
 */

// Charger la configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Employee.php';

$error = '';

// Traiter le PIN si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    $pin = trim($_POST['pin']);
    
    if (strlen($pin) === 4) {
        $employeeModel = new Employee();
        $employee = $employeeModel->getByPin($pin);
        
        if ($employee) {
            // Stocker l'ID employé en session
            $_SESSION['employee_id'] = $employee['id'];
            $_SESSION['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            header('Location: actions.php');
            exit;
        } else {
            $error = 'Nepareizs PIN kods'; // PIN incorrect
        }
    } else {
        $error = 'PIN kodam jābūt 4 cipariem'; // Le PIN doit contenir 4 chiffres
    }
}

// Gérer l'accès par QR code
if (isset($_GET['qr'])) {
    $qr_code = trim($_GET['qr']);
    $employeeModel = new Employee();
    $employee = $employeeModel->getByQr($qr_code);
    
    if ($employee) {
        $_SESSION['employee_id'] = $employee['id'];
        $_SESSION['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
        header('Location: actions.php');
        exit;
    } else {
        $error = 'Nederīgs QR kods'; // QR code invalide
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
    <title>Grafik - Darbinieku punkts</title>
    <link rel="stylesheet" href="../css/employee.css">
</head>
<body>
    <div class="container">
        <div class="logo">🕐</div>
        <h1>Laipni lūdzam</h1>
        
        <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" id="pinForm">
            <div class="pin-display" id="pinDisplay"></div>
            
            <div class="keypad">
                <button type="button" class="key" data-key="1">1</button>
                <button type="button" class="key" data-key="2">2</button>
                <button type="button" class="key" data-key="3">3</button>
                <button type="button" class="key" data-key="4">4</button>
                <button type="button" class="key" data-key="5">5</button>
                <button type="button" class="key" data-key="6">6</button>
                <button type="button" class="key" data-key="7">7</button>
                <button type="button" class="key" data-key="8">8</button>
                <button type="button" class="key" data-key="9">9</button>
                <button type="button" class="key cancel" id="cancelKey">✕</button>
                <button type="button" class="key zero" data-key="0">0</button>
                <button type="button" class="key ok" id="okKey">✓</button>
            </div>
            
            <input type="hidden" name="pin" id="pinInput" value="">
        </form>
    </div>
    
    <script>
        let pin = '';
        const pinDisplay = document.getElementById('pinDisplay');
        const pinInput = document.getElementById('pinInput');
        const pinForm = document.getElementById('pinForm');
        
        // Fonction pour gérer le clic avec feedback visuel
        function handleKeyPress(element, callback, highlightColor) {
            const handler = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Effet visuel de clic
                element.style.transform = 'scale(0.95)';
                if (highlightColor) {
                    element.style.background = highlightColor;
                }
                
                setTimeout(() => {
                    element.style.transform = '';
                    element.style.background = '';
                }, 150);
                
                // Exécuter le callback
                callback();
            };
            
            // Support mobile et desktop
            element.addEventListener('touchstart', handler, { passive: false });
            element.addEventListener('click', handler);
        }
        
        // Gérer les touches numériques
        document.querySelectorAll('.key[data-key]').forEach(key => {
            handleKeyPress(key, function() {
                if (pin.length < 4) {
                    pin += key.dataset.key;
                    updateDisplay();
                    
                    if (pin.length === 4) {
                        setTimeout(() => submitPin(), 300);
                    }
                }
            }, '#5a67d8');
        });
        
        // Touche Cancel
        handleKeyPress(document.getElementById('cancelKey'), function() {
            pin = '';
            updateDisplay();
        }, '#c0392b');
        
        // Touche OK
        handleKeyPress(document.getElementById('okKey'), function() {
            if (pin.length === 4) {
                submitPin();
            }
        }, '#229954');
        
        function updateDisplay() {
            if (pin.length === 0) {
                pinDisplay.textContent = '';
                pinDisplay.style.minHeight = '60px'; // Garde la hauteur même vide
            } else {
                pinDisplay.textContent = '•'.repeat(pin.length);
            }
        }
        
        function submitPin() {
            pinInput.value = pin;
            pinForm.submit();
        }
        
        // Support clavier physique
        document.addEventListener('keydown', function(e) {
            if (e.key >= '0' && e.key <= '9') {
                if (pin.length < 4) {
                    pin += e.key;
                    updateDisplay();
                    
                    if (pin.length === 4) {
                        submitPin();
                    }
                }
            } else if (e.key === 'Backspace' || e.key === 'Escape') {
                pin = '';
                updateDisplay();
            } else if (e.key === 'Enter') {
                if (pin.length === 4) {
                    submitPin();
                }
            }
        });
    </script>
</body>
</html>

