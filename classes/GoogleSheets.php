<?php
/**
 * GRAFIK - Classe GoogleSheets
 * Import de planning depuis Google Sheets
 */

require_once __DIR__ . '/Firebase.php';
require_once __DIR__ . '/Employee.php';

class GoogleSheets {
    private $firebase;
    private $employee;
    
    public function __construct() {
        $this->firebase = Firebase::getInstance();
        $this->employee = new Employee();
    }
    
    /**
     * Importer le planning depuis Google Sheets (CSV)
     * Format attendu: Date, Employé (Prénom Nom), Heure début, Heure fin
     */
    public function importFromUrl($url, $year = null, $month = null) {
        if ($year === null) $year = date('Y');
        if ($month === null) $month = date('n');
        
        // Convertir l'URL Google Sheets en URL CSV
        $csv_url = $this->convertToCsvUrl($url);
        
        // Télécharger le CSV
        $csv_data = @file_get_contents($csv_url);
        if ($csv_data === false) {
            throw new Exception("Impossible de télécharger le fichier Google Sheets. Vérifiez que le document est public.");
        }
        
        // Parser le CSV
        $lines = str_getcsv($csv_data, "\n");
        $imported = 0;
        $errors = [];
        
        // Ignorer la première ligne (en-têtes)
        array_shift($lines);
        
        foreach ($lines as $line_num => $line) {
            if (empty(trim($line))) continue;
            
            $fields = str_getcsv($line);
            if (count($fields) < 4) continue;
            
            try {
                $date = trim($fields[0]);
                $employee_name = trim($fields[1]);
                $start_time = trim($fields[2]);
                $end_time = trim($fields[3]);
                $notes = isset($fields[4]) ? trim($fields[4]) : '';
                
                // Valider la date
                $date_obj = DateTime::createFromFormat('Y-m-d', $date);
                if (!$date_obj) {
                    $date_obj = DateTime::createFromFormat('d/m/Y', $date);
                }
                if (!$date_obj) {
                    $date_obj = DateTime::createFromFormat('d.m.Y', $date);
                }
                if (!$date_obj) {
                    throw new Exception("Format de date invalide: $date");
                }
                
                $schedule_date = $date_obj->format('Y-m-d');
                
                // Vérifier que c'est le bon mois
                if (date('Y', strtotime($schedule_date)) != $year || date('n', strtotime($schedule_date)) != $month) {
                    continue; // Ignorer les dates hors du mois cible
                }
                
                // Trouver l'employé par nom
                $employee = $this->findEmployeeByName($employee_name);
                if (!$employee) {
                    $errors[] = "Ligne " . ($line_num + 2) . ": Employé non trouvé: $employee_name";
                    continue;
                }
                
                // Valider les heures
                if (!preg_match('/^\d{2}:\d{2}$/', $start_time) && !preg_match('/^\d{1,2}:\d{2}$/', $start_time)) {
                    throw new Exception("Format d'heure invalide: $start_time");
                }
                if (!preg_match('/^\d{2}:\d{2}$/', $end_time) && !preg_match('/^\d{1,2}:\d{2}$/', $end_time)) {
                    throw new Exception("Format d'heure invalide: $end_time");
                }
                
                // Normaliser les heures (ajouter 0 devant si nécessaire)
                $start_time = $this->normalizeTime($start_time);
                $end_time = $this->normalizeTime($end_time);
                
                // Sauvegarder dans Firebase
                $schedule_data = [
                    'employee_id' => $employee['id'],
                    'schedule_date' => $schedule_date,
                    'start_time' => $schedule_date . ' ' . $start_time . ':00',
                    'end_time' => $schedule_date . ' ' . $end_time . ':00',
                    'notes' => $notes,
                    'imported_from_sheets' => true,
                    'imported_at' => date('Y-m-d\TH:i:s')
                ];
                
                $schedule_id = 'schedule_' . $employee['id'] . '_' . $schedule_date;
                if ($this->firebase->saveSchedule($schedule_id, $schedule_data)) {
                    $imported++;
                }
                
            } catch (Exception $e) {
                $errors[] = "Ligne " . ($line_num + 2) . ": " . $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }
    
    /**
     * Convertir l'URL Google Sheets en URL CSV
     */
    private function convertToCsvUrl($url) {
        // Si c'est déjà une URL CSV, la retourner
        if (strpos($url, '/export?format=csv') !== false) {
            return $url;
        }
        
        // Extraire l'ID du document depuis l'URL
        // Format: https://docs.google.com/spreadsheets/d/ID/edit
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheet_id = $matches[1];
            return "https://docs.google.com/spreadsheets/d/$sheet_id/export?format=csv&gid=0";
        }
        
        throw new Exception("URL Google Sheets invalide. Format attendu: https://docs.google.com/spreadsheets/d/ID/edit");
    }
    
    /**
     * Trouver un employé par nom (prénom nom)
     */
    private function findEmployeeByName($name) {
        $all_employees = $this->employee->getAll(false);
        
        // Essayer correspondance exacte
        foreach ($all_employees as $emp) {
            $full_name = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
            if (strcasecmp(trim($full_name), trim($name)) === 0) {
                return $emp;
            }
        }
        
        // Essayer correspondance partielle
        $name_parts = explode(' ', trim($name));
        foreach ($all_employees as $emp) {
            $first_match = isset($name_parts[0]) && strcasecmp($emp['first_name'] ?? '', $name_parts[0]) === 0;
            $last_match = isset($name_parts[1]) && strcasecmp($emp['last_name'] ?? '', $name_parts[1]) === 0;
            
            if ($first_match && $last_match) {
                return $emp;
            }
        }
        
        return null;
    }
    
    /**
     * Normaliser le format d'heure (ajouter 0 devant si nécessaire)
     */
    private function normalizeTime($time) {
        if (preg_match('/^(\d{1}):(\d{2})$/', $time, $matches)) {
            return '0' . $matches[1] . ':' . $matches[2];
        }
        return $time;
    }
}

