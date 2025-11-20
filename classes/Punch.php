<?php
/**
 * GRAFIK - Classe Punch
 * Gestion des pointages
 */

require_once __DIR__ . '/Firebase.php';
require_once __DIR__ . '/Shift.php';

class Punch {
    private $firebase;
    private $shift;

    public function __construct() {
        $this->firebase = Firebase::getInstance();
        $this->shift = new Shift();
    }

    /**
     * Enregistrer un pointage (arrivée ou départ)
     */
    public function record($employee_id, $type, $datetime = null) {
        if ($datetime === null) {
            $datetime = date('Y-m-d H:i:s');
        }

        // Vérifier le dernier pointage pour éviter les doublons
        $lastPunch = $this->getLastPunch($employee_id);
        if ($lastPunch && $lastPunch['punch_type'] === $type) {
            // Si le dernier pointage est du même type, on refuse
            $type_label = $type === 'in' ? 'ierašanās' : 'aiziešana';
            throw new Exception("Nevar reģistrēt divreiz {$type_label}. Lūdzu, reģistrējiet pretējo darbību.");
        }

        // Trouver le shift correspondant si existe
        $shift_id = $this->findShiftForPunch($employee_id, $datetime);

        $punch_data = [
            'punch_type' => $type,
            'punch_datetime' => $datetime,
            'shift_id' => $shift_id
        ];

        $punch_id = $this->firebase->savePunch($employee_id, $punch_data);
        return $punch_id;
    }

    /**
     * Trouver le shift correspondant pour un pointage
     */
    private function findShiftForPunch($employee_id, $datetime) {
        $date = date('Y-m-d', strtotime($datetime));
        $schedules = $this->firebase->getSchedulesByEmployeeMonth($employee_id, date('Y', strtotime($date)), date('n', strtotime($date)));
        
        foreach ($schedules as $schedule) {
            if (isset($schedule['schedule_date']) && $schedule['schedule_date'] === $date) {
                return $schedule['id'] ?? null;
            }
        }
        return null;
    }

    /**
     * Récupérer le dernier pointage d'un employé
     */
    public function getLastPunch($employee_id) {
        $punches = $this->firebase->getPunches($employee_id);
        if (empty($punches)) {
            return null;
        }
        return $punches[0]; // Déjà trié par date décroissante
    }

    /**
     * Récupérer tous les pointages d'un employé pour une date
     */
    public function getByEmployeeAndDate($employee_id, $date) {
        $punches = $this->firebase->getPunches($employee_id, $date, $date);
        // Trier par heure croissante
        usort($punches, function($a, $b) {
            return strcmp($a['punch_datetime'], $b['punch_datetime']);
        });
        return $punches;
    }

    /**
     * Récupérer tous les pointages d'un employé pour une période
     */
    public function getByEmployeeDateRange($employee_id, $start_date, $end_date) {
        $punches = $this->firebase->getPunches($employee_id, $start_date, $end_date);
        // Trier par heure croissante
        usort($punches, function($a, $b) {
            return strcmp($a['punch_datetime'], $b['punch_datetime']);
        });
        return $punches;
    }

    /**
     * Calculer les heures travaillées pour un employé pour une date
     */
    public function calculateHours($employee_id, $date) {
        $punches = $this->getByEmployeeAndDate($employee_id, $date);
        
        $total_hours = 0;
        $in_time = null;

        foreach ($punches as $punch) {
            if ($punch['punch_type'] === 'in') {
                $in_time = strtotime($punch['punch_datetime']);
            } elseif ($punch['punch_type'] === 'out' && $in_time !== null) {
                $out_time = strtotime($punch['punch_datetime']);
                $hours = ($out_time - $in_time) / 3600; // Convertir en heures
                $total_hours += $hours;
                $in_time = null;
            }
        }

        return round($total_hours, 2);
    }

    /**
     * Calculer les heures travaillées pour une période
     */
    public function calculateHoursRange($employee_id, $start_date, $end_date) {
        $total = 0;
        $current_date = $start_date;
        
        while (strtotime($current_date) <= strtotime($end_date)) {
            $total += $this->calculateHours($employee_id, $current_date);
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        
        return round($total, 2);
    }

    /**
     * Ajouter manuellement un pointage (admin)
     */
    public function addManual($employee_id, $type, $datetime) {
        return $this->record($employee_id, $type, $datetime);
    }

    /**
     * Supprimer un pointage
     */
    public function delete($id) {
        // Pour supprimer un pointage, on doit trouver l'employé et le pointage
        // On doit parcourir tous les employés pour trouver le pointage
        $allEmployees = $this->firebase->getAllEmployees();
        foreach ($allEmployees as $employee_id => $employee) {
            $punches = $this->firebase->getPunches($employee_id);
            foreach ($punches as $punch) {
                if (isset($punch['id']) && $punch['id'] === $id) {
                    // Supprimer le pointage de Firebase
                    try {
                        $ref = $this->firebase->getDatabase()->getReference('grafik/punches/' . $employee_id . '/' . $id);
                        $ref->remove();
                        return true;
                    } catch (Exception $e) {
                        error_log("Erreur suppression pointage: " . $e->getMessage());
                        return false;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Récupérer tous les pointages pour une date (admin)
     */
    public function getAllByDate($date) {
        return $this->firebase->getAllPunchesByDate($date);
    }
}
