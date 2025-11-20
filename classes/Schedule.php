<?php
/**
 * GRAFIK - Classe Schedule
 * Gestion des plannings
 */

require_once __DIR__ . '/Firebase.php';
require_once __DIR__ . '/Employee.php';

class Schedule {
    private $firebase;
    private $employee;

    public function __construct() {
        $this->firebase = Firebase::getInstance();
        $this->employee = new Employee();
    }

    /**
     * Récupérer le planning pour un mois donné
     */
    public function getForMonth($year, $month) {
        $schedules = $this->firebase->getAllSchedulesByMonth($year, $month);
        
        // Enrichir avec les noms des employés
        $result = [];
        foreach ($schedules as $schedule) {
            $emp = $this->employee->getById($schedule['employee_id']);
            if ($emp) {
                $schedule['first_name'] = $emp['first_name'] ?? '';
                $schedule['last_name'] = $emp['last_name'] ?? '';
            }
            $result[] = $schedule;
        }
        
        return $result;
    }

    /**
     * Récupérer le planning d'un employé pour une période
     */
    public function getForEmployee($employee_id, $start_date, $end_date) {
        $start_year = date('Y', strtotime($start_date));
        $start_month = date('n', strtotime($start_date));
        $end_year = date('Y', strtotime($end_date));
        $end_month = date('n', strtotime($end_date));
        
        $result = [];
        
        // Parcourir tous les mois entre start et end
        $current_year = $start_year;
        $current_month = $start_month;
        
        while ($current_year < $end_year || ($current_year == $end_year && $current_month <= $end_month)) {
            $schedules = $this->firebase->getSchedulesByEmployeeMonth($employee_id, $current_year, $current_month);
            
            foreach ($schedules as $schedule) {
                $schedule_date = $schedule['schedule_date'] ?? '';
                if ($schedule_date >= $start_date && $schedule_date <= $end_date) {
                    $result[] = $schedule;
                }
            }
            
            $current_month++;
            if ($current_month > 12) {
                $current_month = 1;
                $current_year++;
            }
        }
        
        usort($result, function($a, $b) {
            return strcmp($a['schedule_date'] ?? '', $b['schedule_date'] ?? '');
        });
        
        return $result;
    }

    /**
     * Récupérer le planning d'un employé pour un jour spécifique
     */
    public function getForEmployeeDate($employee_id, $date) {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        $schedules = $this->firebase->getSchedulesByEmployeeMonth($employee_id, $year, $month);
        
        foreach ($schedules as $schedule) {
            if (isset($schedule['schedule_date']) && $schedule['schedule_date'] === $date) {
                return $schedule;
            }
        }
        return null;
    }

    /**
     * Ajouter ou mettre à jour un planning
     */
    public function saveSchedule($employee_id, $date, $start_time, $end_time, $notes = null) {
        // Vérifier si un planning existe déjà
        $existing = $this->getForEmployeeDate($employee_id, $date);
        
        $data = [
            'employee_id' => $employee_id,
            'schedule_date' => $date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'notes' => $notes,
            'updated_at' => date('Y-m-d\TH:i:s')
        ];
        
        if ($existing) {
            // Mettre à jour
            $schedule_id = $existing['id'];
            $data['created_at'] = $existing['created_at'] ?? date('Y-m-d\TH:i:s');
            $this->firebase->saveSchedule($schedule_id, $data);
            return $schedule_id;
        } else {
            // Insérer
            $schedule_id = $this->firebase->generateScheduleId();
            $data['created_at'] = date('Y-m-d\TH:i:s');
            $this->firebase->saveSchedule($schedule_id, $data);
            return $schedule_id;
        }
    }

    /**
     * Supprimer un planning
     */
    public function delete($id) {
        return $this->firebase->deleteSchedule($id);
    }

    /**
     * Supprimer tous les plannings d'un employé pour une date
     */
    public function deleteForEmployeeDate($employee_id, $date) {
        $schedule = $this->getForEmployeeDate($employee_id, $date);
        if ($schedule && isset($schedule['id'])) {
            return $this->firebase->deleteSchedule($schedule['id']);
        }
        return false;
    }

    /**
     * Dupliquer le planning d'une semaine vers une autre
     */
    public function duplicateWeek($source_start_date, $target_start_date) {
        // Récupérer tous les plannings de la semaine source
        $year = date('Y', strtotime($source_start_date));
        $month = date('n', strtotime($source_start_date));
        $schedules = $this->firebase->getAllSchedulesByMonth($year, $month);
        
        // Filtrer pour la semaine source
        $week_schedules = [];
        $week_end = date('Y-m-d', strtotime($source_start_date . ' +6 days'));
        foreach ($schedules as $schedule) {
            $schedule_date = $schedule['schedule_date'] ?? '';
            if ($schedule_date >= $source_start_date && $schedule_date <= $week_end) {
                $week_schedules[] = $schedule;
            }
        }
        
        foreach ($week_schedules as $schedule) {
            $source_date = new DateTime($schedule['schedule_date']);
            $source_week_start = new DateTime($source_start_date);
            $day_diff = $source_date->diff($source_week_start)->days;
            
            $target_date = new DateTime($target_start_date);
            $target_date->modify("+{$day_diff} days");
            
            $this->saveSchedule(
                $schedule['employee_id'],
                $target_date->format('Y-m-d'),
                $schedule['start_time'],
                $schedule['end_time'],
                $schedule['notes'] ?? null
            );
        }
    }

    /**
     * Obtenir les statistiques du planning pour un mois
     */
    public function getMonthStats($year, $month) {
        $schedules = $this->firebase->getAllSchedulesByMonth($year, $month);
        $employees = $this->employee->getAll(true);
        
        $stats = [];
        foreach ($employees as $emp) {
            $employee_id = $emp['id'];
            $days_scheduled = 0;
            $total_hours = 0;
            
            foreach ($schedules as $schedule) {
                if (isset($schedule['employee_id']) && $schedule['employee_id'] == $employee_id) {
                    $days_scheduled++;
                    if (isset($schedule['start_time']) && isset($schedule['end_time'])) {
                        $start = strtotime($schedule['start_time']);
                        $end = strtotime($schedule['end_time']);
                        $hours = ($end - $start) / 3600;
                        $total_hours += $hours;
                    }
                }
            }
            
            $stats[] = [
                'id' => $employee_id,
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'days_scheduled' => $days_scheduled,
                'total_hours' => round($total_hours, 2)
            ];
        }
        
        return $stats;
    }
}
