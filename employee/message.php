<?php
/**
 * GRAFIK - Page employé - Envoyer un message
 * Interface en letton
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Message.php';

// Vérifier qu'un employé est connecté
if (!isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'];
$messageModel = new Message();
$success = false;
$error = '';

// Vérifier si c'est un message d'oubli de pointage
$is_missing_punch = isset($_GET['type']) && $_GET['type'] === 'missing_punch';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message_text = trim($_POST['message']);
    
    if (empty($message_text)) {
        $error = 'Lūdzu, ievadiet ziņojumu!';
    } else {
        try {
            // Créer le message
            $message_id = $messageModel->create($employee_id, $message_text);
            
            // Envoyer l'email
            $employee = (new Employee())->getById($employee_id);
            $employee_full_name = $employee['first_name'] . ' ' . $employee['last_name'];
            
            // Récupérer l'email admin depuis les paramètres de sécurité
            require_once __DIR__ . '/../classes/SecuritySettings.php';
            $securitySettings = new SecuritySettings();
            $admin_email = $securitySettings->getAdminNotificationEmail();
            if (empty($admin_email)) {
                $admin_email = 'info@napopizza.lv'; // Email par défaut
            }
            
            $to = $admin_email;
            
            // Adapter le sujet selon le type de message
            if ($is_missing_punch) {
                $subject = 'Grafik - URGENT: Aizmirsts punktējums - ' . $employee_full_name;
                $body = "⚠️ URGENT: Aizmirsts punktējums!\n\n";
            } else {
                $subject = 'Grafik - Jauns ziņojums no ' . $employee_full_name;
                $body = "Jauns ziņojums no darbinieka:\n\n";
            }
            
            $body .= "Darbinieks: " . $employee_full_name . "\n";
            $body .= "Datums/Laiks: " . date('d/m/Y H:i') . "\n";
            if ($is_missing_punch) {
                $body .= "Tips: Aizmirsts punktējums (nepilna diena)\n";
            }
            $body .= "Ziņojums:\n" . $message_text . "\n";
            
            // Configuration email avec SMTP
            $headers = "From: grafik@napopizza.lv\r\n";
            $headers .= "Reply-To: grafik@napopizza.lv\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            // Tentative d'envoi avec ini_set pour SMTP si disponible
            $old_smtp = ini_get('SMTP');
            $old_smtp_port = ini_get('smtp_port');
            
            ini_set('SMTP', 'napopizza.lv');
            ini_set('smtp_port', '587');
            
            @mail($to, $subject, $body, $headers);
            
            // Restaurer les paramètres
            if ($old_smtp !== false) ini_set('SMTP', $old_smtp);
            if ($old_smtp_port !== false) ini_set('smtp_port', $old_smtp_port);
            
            $success = true;
        } catch (Exception $e) {
            $error = 'Kļūda! Lūdzu, mēģiniet vēlreiz.';
            error_log("Message creation error: " . $e->getMessage());
        }
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
    <title>Grafik - Ziņojums</title>
    <link rel="stylesheet" href="../css/employee.css">
    <style>
        .message-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-submit:hover {
            transform: scale(1.02);
        }
        .btn-submit:active {
            transform: scale(0.98);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #27ae60;
            color: white;
        }
        .alert-error {
            background-color: #e74c3c;
            color: white;
        }
        .info-text {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">💬</div>
        <h1>Nosūtīt ziņojumu</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ Ziņojums nosūtīts veiksmīgi!
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="actions.php" class="btn btn-dashboard" style="display: inline-block; text-decoration: none;">
                    Atgriezties
                </a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($is_missing_punch): ?>
            <div class="alert alert-error" style="background: #f39c12; color: white;">
                ⚠️ <strong>Brīdinājums:</strong><br>
                Pēdējo reizi bija aizmirsts punktējums. Lūdzu, sazinieties ar hierarhiju.
            </div>
            <?php endif; ?>
            
            <div class="info-text">
                <strong>💡 Informācija:</strong><br>
                <?php if ($is_missing_punch): ?>
                    Lūdzu, informējiet administratoru par aizmirsto punktējumu. Jūsu ziņojums tiks nosūtīts administratoram.
                <?php else: ?>
                    Izmantojiet šo formu, lai paziņotu, ka esat kļūdījies, nospiežot "Ierašanās" vai "Aiziešana". 
                    Jūsu ziņojums tiks nosūtīts administratoram.
                <?php endif; ?>
            </div>
            
            <form method="POST" class="message-form">
                <div class="form-group">
                    <label for="message">Jūsu ziņojums:</label>
                    <textarea 
                        id="message" 
                        name="message" 
                        placeholder="Piemēram: Es nejauši nospiedu 'Ierašanās' divas reizes. Lūdzu, dzēsiet otro ierakstu."
                        required
                    ></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    Nosūtīt ziņojumu
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="actions.php" class="btn btn-exit" style="display: inline-block; text-decoration: none;">
                    ✕ Atcelt
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

