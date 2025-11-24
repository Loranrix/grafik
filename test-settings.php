<?php
require_once 'includes/config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

try {
    $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", ['general_qr_url']);
    echo "URL actuelle: " . ($result ? $result['setting_value'] : 'AUCUNE') . "\n";
    
    // Tester setSetting
    $db->query(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = ?",
        ['general_qr_url', 'https://grafik.napopizza.lv/employee/', 'https://grafik.napopizza.lv/employee/']
    );
    echo "URL mise à jour avec succès\n";
    
    $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", ['general_qr_url']);
    echo "URL après mise à jour: " . ($result ? $result['setting_value'] : 'AUCUNE') . "\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

