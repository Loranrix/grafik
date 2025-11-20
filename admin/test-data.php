<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Admin.php';

if (!Admin::isLoggedIn()) {
    die('Non autorisé');
}

require_once __DIR__ . '/../classes/Firebase.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Punch.php';

$firebase = Firebase::getInstance();
$employee = new Employee();
$punch = new Punch();

echo "<h1>Test des données Firebase</h1>";

echo "<h2>1. Connexion Firebase</h2>";
echo $firebase->isConnected() ? "✅ Connecté" : "❌ Non connecté";
echo "<br>";

echo "<h2>2. Employés</h2>";
$employees = $firebase->getAllEmployees();
echo "Total dans Firebase: " . count($employees) . "<br>";
$allEmps = $employee->getAll(false);
echo "Via Employee->getAll(): " . count($allEmps) . "<br>";
if (count($allEmps) > 0) {
    echo "<pre>";
    print_r(array_slice($allEmps, 0, 2));
    echo "</pre>";
}

echo "<h2>3. Pointages</h2>";
$today = date('Y-m-d');
$punches = $punch->getAllByDate($today);
echo "Pointages aujourd'hui: " . count($punches) . "<br>";
if (count($punches) > 0) {
    echo "<pre>";
    print_r(array_slice($punches, 0, 2));
    echo "</pre>";
}

echo "<h2>4. Test direct Firebase</h2>";
try {
    $ref = $firebase->getDatabase()->getReference('grafik/employees');
    $data = $ref->getValue();
    echo "Données brutes Firebase: " . (is_array($data) ? count($data) : 0) . " employé(s)<br>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

