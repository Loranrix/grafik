<?php
/**
 * GRAFIK - Déconnexion admin
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Admin.php';

Admin::logout();
header('Location: index.php');
exit;

