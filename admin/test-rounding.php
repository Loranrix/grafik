<?php
/**
 * Test des fonctions d'arrondi
 */

// Fonction pour arrondir au 1/4 d'heure supérieur (pour arrivée)
function roundUpQuarter($datetime) {
    $timestamp = strtotime($datetime);
    $minutes = (int)date('i', $timestamp);
    $hours = (int)date('H', $timestamp);
    
    // Arrondir au quart supérieur
    $rounded_minutes = ceil($minutes / 15) * 15;
    if ($rounded_minutes >= 60) {
        $hours++;
        $rounded_minutes = 0;
    }
    
    return date('Y-m-d H:i:s', mktime($hours, $rounded_minutes, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)));
}

// Fonction pour arrondir au 1/4 d'heure inférieur (pour départ)
function roundDownQuarter($datetime) {
    $timestamp = strtotime($datetime);
    $minutes = (int)date('i', $timestamp);
    $hours = (int)date('H', $timestamp);
    
    // Arrondir au quart inférieur
    $rounded_minutes = floor($minutes / 15) * 15;
    
    return date('Y-m-d H:i:s', mktime($hours, $rounded_minutes, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp)));
}

// Tests
echo "<h2>Tests d'arrondi</h2>";

$test_cases = [
    '2025-01-15 11:10:00' => ['up' => '11:15', 'down' => '11:00'],
    '2025-01-15 11:20:00' => ['up' => '11:30', 'down' => '11:15'],
    '2025-01-15 11:30:00' => ['up' => '11:30', 'down' => '11:30'],
    '2025-01-15 11:45:00' => ['up' => '11:45', 'down' => '11:45'],
    '2025-01-15 11:50:00' => ['up' => '12:00', 'down' => '11:45'],
    '2025-01-15 11:05:00' => ['up' => '11:15', 'down' => '11:00'],
    '2025-01-15 11:59:00' => ['up' => '12:00', 'down' => '11:45'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Heure originale</th><th>Arrondi supérieur (arrivée)</th><th>Arrondi inférieur (départ)</th><th>Résultat</th></tr>";

foreach ($test_cases as $original => $expected) {
    $rounded_up = roundUpQuarter($original);
    $rounded_down = roundDownQuarter($original);
    $up_time = date('H:i', strtotime($rounded_up));
    $down_time = date('H:i', strtotime($rounded_down));
    
    $up_ok = $up_time === $expected['up'];
    $down_ok = $down_time === $expected['down'];
    $result = ($up_ok && $down_ok) ? '✓ OK' : '✗ ERREUR';
    
    echo "<tr>";
    echo "<td>" . date('H:i', strtotime($original)) . "</td>";
    echo "<td style='color: " . ($up_ok ? 'green' : 'red') . "'>$up_time (attendu: {$expected['up']})</td>";
    echo "<td style='color: " . ($down_ok ? 'green' : 'red') . "'>$down_time (attendu: {$expected['down']})</td>";
    echo "<td><strong>$result</strong></td>";
    echo "</tr>";
}

echo "</table>";

// Test de calcul d'heures
echo "<h2>Test de calcul d'heures</h2>";

$test_punches = [
    ['type' => 'in', 'datetime' => '2025-01-15 11:10:00'],
    ['type' => 'out', 'datetime' => '2025-01-15 17:20:00'],
];

$in_time = strtotime($test_punches[0]['datetime']);
$out_time = strtotime($test_punches[1]['datetime']);
$real_hours = ($out_time - $in_time) / 3600;

$in_time_rounded = strtotime(roundUpQuarter($test_punches[0]['datetime']));
$out_time_rounded = strtotime(roundDownQuarter($test_punches[1]['datetime']));
$rounded_hours = ($out_time_rounded - $in_time_rounded) / 3600;

echo "<p>Arrivée réelle: " . date('H:i', $in_time) . " → Arrondie: " . date('H:i', $in_time_rounded) . "</p>";
echo "<p>Départ réel: " . date('H:i', $out_time) . " → Arrondi: " . date('H:i', $out_time_rounded) . "</p>";
echo "<p>Heures réelles: " . number_format($real_hours, 2) . " h</p>";
echo "<p>Heures arrondies: " . number_format($rounded_hours, 2) . " h</p>";

?>




