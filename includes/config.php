<?php
/**
 * GRAFIK - Configuration générale
 */

// Fuseau horaire
date_default_timezone_set('Europe/Riga');

// Configuration base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'napo_grafik');
define('DB_USER', 'napo_admin');
define('DB_PASS', 'Superman13**');
define('DB_CHARSET', 'utf8mb4');

// Configuration application
define('APP_NAME', 'Grafik');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', '/grafik');
define('ADMIN_USER', 'loran');
define('ADMIN_PASS', 'superman13*'); // À hasher en production

// Configuration session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_lifetime', 28800); // 8 heures

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Affichage des erreurs (désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

// Créer le dossier logs si nécessaire
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0755, true);
}

