<?php
/**
 * GRAFIK - Page employé - Déconnexion
 */

session_start();
unset($_SESSION['employee_id']);
unset($_SESSION['employee_name']);
header('Location: index.php');
exit;

