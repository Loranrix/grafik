<?php
/**
 * GRAFIK - QR Code General
 * Un seul QR code pour tous les employes
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';

include 'header.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Fonction pour obtenir la valeur d'un setting (compatible avec les deux structures)
function getSetting($db, $key) {
    try {
        // Essayer d'abord avec la structure simple (key, value)
        $result = $db->fetchOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
        if ($result) {
            return $result['value'];
        }
    } catch (Exception $e) {
        // Si ça échoue, essayer avec la structure complète (setting_key, setting_value)
        try {
            $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
            if ($result) {
                return $result['setting_value'];
            }
        } catch (Exception $e2) {
            error_log("Erreur récupération setting: " . $e2->getMessage());
        }
    }
    return null;
}

// Fonction pour sauvegarder un setting (compatible avec les deux structures)
function setSetting($db, $key, $value) {
    try {
        // Essayer d'abord avec la structure simple
        $db->query(
            "INSERT INTO settings (`key`, value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE value = ?",
            [$key, $value, $value]
        );
        return true;
    } catch (Exception $e) {
        // Si ça échoue, essayer avec la structure complète
        try {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
            return true;
        } catch (Exception $e2) {
            // Si les deux échouent, créer la table avec la structure simple
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
                error_log("Erreur création/sauvegarde setting: " . $e3->getMessage());
                throw $e3;
            }
        }
    }
}

// Fonction pour supprimer un setting
function deleteSetting($db, $key) {
    try {
        $db->query("DELETE FROM settings WHERE `key` = ?", [$key]);
        return true;
    } catch (Exception $e) {
        try {
            $db->query("DELETE FROM settings WHERE setting_key = ?", [$key]);
            return true;
        } catch (Exception $e2) {
            error_log("Erreur suppression setting: " . $e2->getMessage());
            throw $e2;
        }
    }
}

// Gerer les actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_qr') {
        $url = $_POST['qr_url'] ?? '';
        if (empty($url)) {
            $error = 'Veuillez entrer une URL valide';
        } else {
            try {
                setSetting($db, 'general_qr_url', $url);
                // Redirection pour éviter le re-soumission du formulaire
                header('Location: qr-codes.php?success=1');
                exit;
            } catch (Exception $e) {
                error_log("Erreur génération QR code: " . $e->getMessage());
                $error = 'Erreur lors de la génération : ' . htmlspecialchars($e->getMessage());
            }
        }
    } elseif ($action === 'delete_qr') {
        try {
            deleteSetting($db, 'general_qr_url');
            header('Location: qr-codes.php?deleted=1');
            exit;
        } catch (Exception $e) {
            error_log("Erreur suppression QR code: " . $e->getMessage());
            $error = 'Erreur lors de la suppression : ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Gérer les messages de succès depuis l'URL
if (isset($_GET['success'])) {
    $message = 'QR Code general genere avec succes !';
}
if (isset($_GET['deleted'])) {
    $message = 'QR Code general supprime';
}

// URL correcte par défaut
$correct_url = 'https://grafik.napopizza.lv/employee/';

// Recuperer l'URL du QR code si elle existe
$qr_url = getSetting($db, 'general_qr_url');
if ($qr_url === null) {
    $qr_url = '';
}

// Corriger automatiquement l'URL si elle est incorrecte ou vide
if (empty($qr_url) || $qr_url !== $correct_url) {
    try {
        $old_url = $qr_url;
        setSetting($db, 'general_qr_url', $correct_url);
        $qr_url = $correct_url;
        // Afficher un message si l'URL a été corrigée (seulement si pas de message déjà)
        if ($old_url !== $correct_url && empty($message)) {
            $message = '✅ URL du QR code corrigée automatiquement : ' . htmlspecialchars($old_url ?: 'vide') . ' → ' . htmlspecialchars($correct_url);
        }
    } catch (Exception $e) {
        error_log("Erreur correction URL QR code: " . $e->getMessage());
        if (empty($error)) {
            $error = 'Erreur lors de la correction automatique de l\'URL : ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<div class="container">
    <div class="page-header">
        <h1>QR Code General</h1>
        <p style="color: #7f8c8d; margin-top: 10px;">
            Un seul QR code pour l'acces au pointage de tous les employes
        </p>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div style="padding: 30px;">
            <?php if (empty($qr_url)): ?>
            <!-- Formulaire de generation -->
            <div style="text-align: center; padding: 40px;">
                <h2 style="color: #2c3e50; margin-bottom: 20px;">🔲 Generer un QR Code General</h2>
                <p style="color: #7f8c8d; margin-bottom: 30px;">
                    Ce QR code dirigera tous les employes vers la page de pointage principale
                </p>
                
                <form method="POST" style="max-width: 600px; margin: 0 auto;">
                    <input type="hidden" name="action" value="generate_qr">
                    
                    <div class="form-group" style="text-align: left;">
                        <label for="qr_url">URL de destination *</label>
                        <input type="url" 
                               id="qr_url" 
                               name="qr_url" 
                               value="https://grafik.napopizza.lv/employee/" 
                               required 
                               style="font-size: 16px; padding: 15px;">
                        <small style="color: #7f8c8d; display: block; margin-top: 8px;">
                            ⚠️ <strong>Important :</strong> Utilisez l'URL directe (sans service de redirection comme mrqrcode.mobi, bit.ly, etc.)
                            <br>Cette URL sera encodée directement dans le QR code, sans passer par un service externe.
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large" style="margin-top: 20px;">
                        🔲 Generer le QR Code
                    </button>
                </form>
            </div>
            <?php else: ?>
            <!-- Affichage du QR Code -->
            <div style="text-align: center;">
                <h2 style="color: #27ae60; margin-bottom: 20px;">✅ QR Code General Actif</h2>
                
                <div style="background: white; padding: 30px; border-radius: 12px; display: inline-block; box-shadow: 0 4px 16px rgba(0,0,0,0.1);">
                    <!-- QR Code généré directement avec l'URL, sans service de redirection -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= urlencode($qr_url) ?>" 
                         alt="QR Code General"
                         style="max-width: 100%; border: 2px solid #27ae60; border-radius: 8px;"
                         onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=400x400&data='+encodeURIComponent('<?= addslashes($qr_url) ?>');">
                    <p style="margin-top: 15px; font-size: 12px; color: #7f8c8d; text-align: center;">
                        ✅ Ce QR code pointe directement vers l'URL, sans service de redirection externe
                    </p>
                </div>
                
                <div style="margin: 30px 0; padding: 20px; background: #ecf0f1; border-radius: 8px; max-width: 600px; margin: 30px auto;">
                    <p style="margin: 0;"><strong>URL:</strong></p>
                    <code style="font-size: 14px; word-break: break-all;"><?= htmlspecialchars($qr_url) ?></code>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
                    <button onclick="downloadQR()" class="btn btn-primary">
                        📥 Telecharger (PNG)
                    </button>
                    <button onclick="printQR()" class="btn btn-secondary">
                        🖨️ Imprimer
                    </button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_qr">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer ce QR code ?')">
                            🗑️ Supprimer
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card" style="margin-top: 30px;">
        <div style="padding: 20px;">
            <h3 style="color: #2c3e50; margin-bottom: 15px;">💡 Comment l'utiliser ?</h3>
            <ol style="color: #34495e; line-height: 1.8;">
                <li>Imprimez ce QR code et affichez-le a l'entree de votre etablissement</li>
                <li>Les employes scannent le QR code avec leur smartphone</li>
                <li>Ils arrivent sur la page de pointage et entrent leur PIN</li>
                <li>Ils peuvent enregistrer leur arrivee ou depart</li>
            </ol>
        </div>
    </div>
</div>

<style>
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d5f4e6;
    color: #27ae60;
    border: 1px solid #27ae60;
}

.alert-error {
    background: #fadbd8;
    color: #e74c3c;
    border: 1px solid #e74c3c;
}
</style>

<script>
function downloadQR() {
    const url = '<?= addslashes($qr_url) ?>';
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=${encodeURIComponent(url)}`;
    
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = 'QR_Code_General_Grafik.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printQR() {
    const printWindow = window.open('', '_blank');
    const url = '<?= addslashes($qr_url) ?>';
    printWindow.document.write(`
        <html>
        <head>
            <title>QR Code General - Grafik</title>
            <style>
                body {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    font-family: Arial, sans-serif;
                }
                h1 {
                    color: #2c3e50;
                    margin-bottom: 20px;
                }
                img {
                    border: 3px solid #27ae60;
                    border-radius: 12px;
                    max-width: 500px;
                }
                p {
                    margin-top: 20px;
                    color: #7f8c8d;
                }
            </style>
        </head>
        <body>
            <h1>📊 Grafik - Pointage Employes</h1>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(url)}">
            <p>Scannez ce QR code pour pointer votre arrivee/depart</p>
        </body>
        </html>
    `);
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
    }, 500);
}
</script>

<?php include 'footer.php'; ?>
