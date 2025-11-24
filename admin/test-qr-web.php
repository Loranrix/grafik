<?php
/**
 * GRAFIK - Test web pour vérifier que qr-codes.php fonctionne
 * Accessible via https://grafik.napopizza.lv/admin/test-qr-web.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';

// Vérifier l'authentification
if (!Admin::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Fonctions de test (copiées de qr-codes.php)
function getSetting($db, $key) {
    try {
        $result = $db->fetchOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
        if ($result) return $result['value'];
    } catch (Exception $e) {
        try {
            $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
            if ($result) return $result['setting_value'];
        } catch (Exception $e2) {
            return null;
        }
    }
    return null;
}

function setSetting($db, $key, $value) {
    try {
        $db->query(
            "INSERT INTO settings (`key`, value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE value = ?",
            [$key, $value, $value]
        );
        return true;
    } catch (Exception $e) {
        try {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
            return true;
        } catch (Exception $e2) {
            try {
                $db->query("
                    CREATE TABLE IF NOT EXISTS settings (
                        `key` VARCHAR(255) PRIMARY KEY,
                        value TEXT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                $db->query(
                    "INSERT INTO settings (`key`, value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE value = ?",
                    [$key, $value, $value]
                );
                return true;
            } catch (Exception $e3) {
                throw $e3;
            }
        }
    }
}

include 'header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🧪 Test QR Codes</h1>
    </div>
    
    <div class="card">
        <div style="padding: 30px;">
            <h2>1. Test de connexion à la base de données</h2>
            <?php
            try {
                $db->getConnection();
                echo '<div style="background: #d5f4e6; color: #27ae60; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '✅ Connexion à la base de données OK';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div style="background: #fadbd8; color: #e74c3c; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '❌ Erreur: ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            ?>
            
            <h2 style="margin-top: 30px;">2. Vérification de la table settings</h2>
            <?php
            try {
                $columns = $db->fetchAll("SHOW COLUMNS FROM settings");
                echo '<div style="background: #d5f4e6; color: #27ae60; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '✅ Table settings existe<br>';
                echo 'Colonnes: ';
                $col_names = [];
                foreach ($columns as $col) {
                    $col_names[] = $col['Field'];
                }
                echo implode(', ', $col_names);
                echo '</div>';
            } catch (Exception $e) {
                echo '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '⚠️ Table settings n\'existe pas: ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            ?>
            
            <h2 style="margin-top: 30px;">3. Test getSetting()</h2>
            <?php
            $test_url = getSetting($db, 'general_qr_url');
            if ($test_url !== null) {
                echo '<div style="background: #d5f4e6; color: #27ae60; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '✅ getSetting() fonctionne<br>';
                echo 'URL actuelle: <code>' . htmlspecialchars($test_url) . '</code>';
                echo '</div>';
            } else {
                echo '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '⚠️ Aucune URL configurée (normal si c\'est la première fois)';
                echo '</div>';
            }
            ?>
            
            <h2 style="margin-top: 30px;">4. Test setSetting()</h2>
            <?php
            try {
                $test_value = 'https://grafik.napopizza.lv/employee/';
                setSetting($db, 'test_qr_url', $test_value);
                $retrieved = getSetting($db, 'test_qr_url');
                
                if ($retrieved === $test_value) {
                    echo '<div style="background: #d5f4e6; color: #27ae60; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                    echo '✅ setSetting() fonctionne<br>';
                    echo 'Valeur sauvegardée et récupérée: <code>' . htmlspecialchars($retrieved) . '</code>';
                    echo '</div>';
                } else {
                    echo '<div style="background: #fadbd8; color: #e74c3c; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                    echo '❌ setSetting() ne fonctionne pas correctement<br>';
                    echo 'Attendu: ' . htmlspecialchars($test_value) . '<br>';
                    echo 'Reçu: ' . htmlspecialchars($retrieved ?: 'NULL');
                    echo '</div>';
                }
                
                // Nettoyer
                try {
                    $db->query("DELETE FROM settings WHERE `key` = 'test_qr_url'");
                } catch (Exception $e) {
                    try {
                        $db->query("DELETE FROM settings WHERE setting_key = 'test_qr_url'");
                    } catch (Exception $e2) {}
                }
            } catch (Exception $e) {
                echo '<div style="background: #fadbd8; color: #e74c3c; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '❌ Erreur lors du test: ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            ?>
            
            <h2 style="margin-top: 30px;">5. Test de l'URL du QR code</h2>
            <?php
            $current_url = getSetting($db, 'general_qr_url');
            $correct_url = 'https://grafik.napopizza.lv/employee/';
            
            if ($current_url === $correct_url) {
                echo '<div style="background: #d5f4e6; color: #27ae60; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '✅ L\'URL est correcte: <code>' . htmlspecialchars($current_url) . '</code>';
                echo '</div>';
            } elseif ($current_url) {
                echo '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '⚠️ L\'URL n\'est pas la bonne<br>';
                echo 'Actuelle: <code>' . htmlspecialchars($current_url) . '</code><br>';
                echo 'Attendue: <code>' . htmlspecialchars($correct_url) . '</code>';
                echo '</div>';
            } else {
                echo '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0;">';
                echo '⚠️ Aucune URL configurée. Elle sera créée automatiquement.';
                echo '</div>';
            }
            ?>
            
            <div style="margin-top: 30px;">
                <a href="qr-codes.php" class="btn btn-primary">← Retour à la gestion du QR code</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

