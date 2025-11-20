<?php
/**
 * GRAFIK - Header pour les pages admin
 */

// Vérifier l'authentification
if (!Admin::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Charger Message pour le compteur de messages non lus
require_once __DIR__ . '/../classes/Message.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik - Administration</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-text">📊 Grafik</div>
            <div class="user-info">
                <span>Bonjour, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                <a href="logout.php" class="btn btn-secondary btn-sm">Déconnexion</a>
            </div>
        </div>
    </header>
    
        <nav class="nav-menu">
            <div class="nav-content">
                <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    📊 Tableau de bord
                </a>
                <a href="employees.php" class="nav-item <?= $current_page === 'employees' ? 'active' : '' ?>">
                    👥 Employés
                </a>
                <a href="qr-codes.php" class="nav-item <?= $current_page === 'qr-codes' ? 'active' : '' ?>">
                    🔲 QR Code
                </a>
                <a href="planning.php" class="nav-item <?= $current_page === 'planning' ? 'active' : '' ?>">
                    📅 Planning
                </a>
                <a href="punches.php" class="nav-item <?= $current_page === 'punches' ? 'active' : '' ?>">
                    ⏱️ Pointages
                </a>
                <a href="consumption.php" class="nav-item <?= $current_page === 'consumption' ? 'active' : '' ?>">
                    🍕 Consommations
                </a>
                <a href="boxes.php" class="nav-item <?= $current_page === 'boxes' ? 'active' : '' ?>">
                    📦 Boîtes vides
                </a>
                <a href="security-settings.php" class="nav-item <?= $current_page === 'security-settings' ? 'active' : '' ?>">
                    🔒 Sécurité
                </a>
                <a href="logs.php" class="nav-item <?= $current_page === 'logs' ? 'active' : '' ?>">
                    📋 Logs
                </a>
                <a href="export.php" class="nav-item <?= $current_page === 'export' ? 'active' : '' ?>">
                    📥 Export
                </a>
                <a href="messages.php" class="nav-item <?= $current_page === 'messages' ? 'active' : '' ?>">
                    💬 Messages
                    <?php
                    if ($current_page !== 'messages') {
                        try {
                            $messageModel = new Message();
                            $unread = $messageModel->countUnread();
                            if ($unread > 0) {
                                echo '<span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">' . $unread . '</span>';
                            }
                        } catch (Exception $e) {
                            // Ignorer les erreurs pour ne pas bloquer l'affichage
                        }
                    }
                    ?>
                </a>
                <a href="firebase-test.php" class="nav-item <?= $current_page === 'firebase-test' ? 'active' : '' ?>" style="color: #f39c12;">
                    🔥 Firebase
                </a>
            </div>
        </nav>

