<?php
/**
 * GRAFIK - Test de connexion à la base de données
 * À exécuter via curl pour vérifier le déploiement
 */

header('Content-Type: application/json');

$result = [
    'status' => 'error',
    'message' => '',
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Connexion à la base de données
    $dsn = "mysql:host=localhost;dbname=napo_grafik;charset=utf8mb4";
    $pdo = new PDO($dsn, 'napo_admin', 'Superman13**', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Vérifier les tables
    $tables = ['admins', 'employees', 'shifts', 'punches', 'settings'];
    $existing_tables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existing_tables[] = $table;
        }
    }
    
    // Compter les enregistrements
    $counts = [];
    foreach ($existing_tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $row = $stmt->fetch();
        $counts[$table] = $row['count'];
    }
    
    $result['status'] = 'success';
    $result['message'] = 'Base de données connectée avec succès';
    $result['tables'] = $existing_tables;
    $result['counts'] = $counts;
    
} catch (PDOException $e) {
    $result['message'] = 'Erreur de connexion: ' . $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);

