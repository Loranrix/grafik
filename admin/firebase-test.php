<?php
/**
 * GRAFIK - Test de connexion Firebase
 */

require_once __DIR__ . '/../includes/config.php';

$results = [];

// Test 1: Composer autoload
$results['composer'] = [
    'label' => 'Composer autoload',
    'success' => file_exists(__DIR__ . '/../vendor/autoload.php'),
    'message' => file_exists(__DIR__ . '/../vendor/autoload.php') 
        ? 'Fichier autoload trouvé' 
        : 'Composer non installé - Exécutez: composer install'
];

// Test 2: Firebase config
$results['config'] = [
    'label' => 'Configuration Firebase',
    'success' => file_exists(__DIR__ . '/../firebase-config.json'),
    'message' => file_exists(__DIR__ . '/../firebase-config.json')
        ? 'Fichier de configuration trouvé'
        : 'Fichier firebase-config.json manquant - Voir FIREBASE-SETUP.md'
];

// Test 3: Chargement de Firebase
if ($results['composer']['success']) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/../classes/Firebase.php';
        
        $results['firebase_class'] = [
            'label' => 'Classe Firebase',
            'success' => true,
            'message' => 'Classe Firebase chargée avec succès'
        ];
    } catch (Exception $e) {
        $results['firebase_class'] = [
            'label' => 'Classe Firebase',
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ];
    }
}

// Test 4: Connexion Firebase
if ($results['composer']['success'] && $results['config']['success']) {
    try {
        $firebase = Firebase::getInstance();
        $results['connection'] = [
            'label' => 'Connexion Firebase',
            'success' => $firebase->isConnected(),
            'message' => $firebase->isConnected()
                ? 'Connexion réussie'
                : 'Échec de connexion - Vérifiez firebase-config.json'
        ];
        
        // Test 5: Lecture/Écriture
        if ($firebase->isConnected()) {
            try {
                // Test write
                $testData = [
                    'test' => true,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $db = $firebase->getDatabase();
                $ref = $db->getReference('grafik/test');
                $ref->set($testData);
                
                // Test read
                $readData = $ref->getValue();
                
                $results['read_write'] = [
                    'label' => 'Lecture/Écriture',
                    'success' => isset($readData['test']) && $readData['test'] === true,
                    'message' => 'Test de lecture/écriture réussi'
                ];
                
                // Cleanup
                $ref->remove();
                
            } catch (Exception $e) {
                $results['read_write'] = [
                    'label' => 'Lecture/Écriture',
                    'success' => false,
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
            }
        }
    } catch (Exception $e) {
        $results['connection'] = [
            'label' => 'Connexion Firebase',
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Firebase - GRAFIK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .test-item {
            background: #f7f7f7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .test-icon {
            font-size: 32px;
            width: 50px;
            text-align: center;
        }
        
        .test-content {
            flex: 1;
        }
        
        .test-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .test-message {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .success {
            background: #d4edda;
            border-left: 5px solid #27ae60;
        }
        
        .error {
            background: #f8d7da;
            border-left: 5px solid #e74c3c;
        }
        
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.2s;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .info-box {
            background: #e8f4f8;
            border-left: 5px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .info-box h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #555;
        }
        
        .info-box li {
            margin-bottom: 8px;
        }
        
        code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 Test de connexion Firebase</h1>
        
        <?php foreach ($results as $key => $test): ?>
        <div class="test-item <?= $test['success'] ? 'success' : 'error' ?>">
            <div class="test-icon">
                <?= $test['success'] ? '✅' : '❌' ?>
            </div>
            <div class="test-content">
                <div class="test-label"><?= htmlspecialchars($test['label']) ?></div>
                <div class="test-message"><?= htmlspecialchars($test['message']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (count(array_filter($results, fn($r) => $r['success'])) === count($results)): ?>
        <div class="info-box" style="background: #d4edda; border-left-color: #27ae60;">
            <h3>✅ Firebase est correctement configuré !</h3>
            <p style="margin-top: 10px; color: #155724;">
                Vous pouvez maintenant procéder à la migration des données.
            </p>
            <a href="migrate-to-firebase.php" class="btn" style="background: #27ae60;">
                Commencer la migration
            </a>
        </div>
        <?php else: ?>
        <div class="info-box">
            <h3>ℹ️ Prochaines étapes</h3>
            <ul>
                <?php if (!$results['composer']['success']): ?>
                <li>Installer Composer : <code>composer install</code></li>
                <?php endif; ?>
                
                <?php if (!$results['config']['success']): ?>
                <li>Créer le fichier <code>firebase-config.json</code> avec vos clés Firebase</li>
                <li>Consultez le guide : <code>FIREBASE-SETUP.md</code></li>
                <?php endif; ?>
                
                <?php if (isset($results['connection']) && !$results['connection']['success']): ?>
                <li>Vérifier les clés Firebase dans <code>firebase-config.json</code></li>
                <li>Vérifier que Realtime Database est activé dans Firebase Console</li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="btn" style="background: #95a5a6;">← Retour au Dashboard</a>
            <a href="firebase-test.php" class="btn">🔄 Rafraîchir le test</a>
        </div>
    </div>
</body>
</html>

