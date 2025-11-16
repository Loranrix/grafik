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
                Tableau de bord
            </a>
            <a href="employees.php" class="nav-item <?= $current_page === 'employees' ? 'active' : '' ?>">
                Employés
            </a>
            <a href="planning.php" class="nav-item <?= $current_page === 'planning' ? 'active' : '' ?>">
                Planning
            </a>
            <a href="punches.php" class="nav-item <?= $current_page === 'punches' ? 'active' : '' ?>">
                Pointages
            </a>
        </div>
    </nav>

