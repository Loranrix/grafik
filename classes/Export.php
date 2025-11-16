<?php
/**
 * GRAFIK - Classe Export
 * Export des données en PDF et Excel
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use TCPDF;

class Export {
    /**
     * Exporter les pointages en Excel
     */
    public static function exportPunchesToExcel($punches, $employee = null, $start_date = null, $end_date = null) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Titre
        $sheet->setCellValue('A1', 'RAPPORT DE POINTAGES');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Informations
        $row = 2;
        if ($employee) {
            $sheet->setCellValue('A' . $row, 'Employé: ' . $employee['first_name'] . ' ' . $employee['last_name']);
            $row++;
        }
        if ($start_date && $end_date) {
            $sheet->setCellValue('A' . $row, 'Période: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
            $row++;
        }
        $sheet->setCellValue('A' . $row, 'Généré le: ' . date('d/m/Y H:i'));
        $row += 2;
        
        // En-têtes
        $headers = ['Date', 'Employé', 'Arrivée', 'Départ', 'Pause', 'Total', 'Notes'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }
        $row++;
        
        // Données groupées par jour
        $grouped = self::groupPunchesByDay($punches);
        
        foreach ($grouped as $date => $data) {
            $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($date)));
            $sheet->setCellValue('B' . $row, $data['employee_name'] ?? '');
            $sheet->setCellValue('C' . $row, $data['in_time'] ?? '-');
            $sheet->setCellValue('D' . $row, $data['out_time'] ?? '-');
            $sheet->setCellValue('E' . $row, $data['break_duration'] ?? '0h');
            $sheet->setCellValue('F' . $row, $data['total_hours'] ?? '0h');
            $sheet->setCellValue('G' . $row, $data['notes'] ?? '');
            $row++;
        }
        
        // Ajuster les largeurs
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A' . ($row - count($grouped) - 1) . ':G' . ($row - 1))->applyFromArray($styleArray);
        
        // Générer le fichier
        $filename = 'pointages_' . date('Y-m-d_His') . '.xlsx';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        
        return ['filepath' => $filepath, 'filename' => $filename];
    }
    
    /**
     * Exporter les pointages en PDF
     */
    public static function exportPunchesToPDF($punches, $employee = null, $start_date = null, $end_date = null) {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        // Métadonnées
        $pdf->SetCreator('GRAFIK');
        $pdf->SetAuthor('NapoPizza');
        $pdf->SetTitle('Rapport de pointages');
        
        // Marges
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Logo/Titre
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'RAPPORT DE POINTAGES', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Informations
        $pdf->SetFont('helvetica', '', 10);
        if ($employee) {
            $pdf->Cell(0, 6, 'Employé: ' . $employee['first_name'] . ' ' . $employee['last_name'], 0, 1);
        }
        if ($start_date && $end_date) {
            $pdf->Cell(0, 6, 'Période: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)), 0, 1);
        }
        $pdf->Cell(0, 6, 'Généré le: ' . date('d/m/Y H:i'), 0, 1);
        $pdf->Ln(5);
        
        // Table
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(68, 114, 196);
        $pdf->SetTextColor(255, 255, 255);
        
        $pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
        $pdf->Cell(50, 7, 'Employé', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Arrivée', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Départ', 1, 0, 'C', true);
        $pdf->Cell(15, 7, 'Pause', 1, 0, 'C', true);
        $pdf->Cell(15, 7, 'Total', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Notes', 1, 1, 'C', true);
        
        // Données
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        
        $grouped = self::groupPunchesByDay($punches);
        $fill = false;
        
        foreach ($grouped as $date => $data) {
            $pdf->SetFillColor($fill ? 245 : 255);
            
            $pdf->Cell(25, 6, date('d/m/Y', strtotime($date)), 1, 0, 'C', $fill);
            $pdf->Cell(50, 6, substr($data['employee_name'] ?? '', 0, 25), 1, 0, 'L', $fill);
            $pdf->Cell(20, 6, $data['in_time'] ?? '-', 1, 0, 'C', $fill);
            $pdf->Cell(20, 6, $data['out_time'] ?? '-', 1, 0, 'C', $fill);
            $pdf->Cell(15, 6, $data['break_duration'] ?? '0h', 1, 0, 'C', $fill);
            $pdf->Cell(15, 6, $data['total_hours'] ?? '0h', 1, 0, 'C', $fill);
            $pdf->Cell(35, 6, substr($data['notes'] ?? '', 0, 20), 1, 1, 'L', $fill);
            
            $fill = !$fill;
        }
        
        // Totaux
        $totalHours = array_sum(array_map(function($d) {
            return floatval(str_replace('h', '', $d['total_hours'] ?? 0));
        }, $grouped));
        
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'Total des heures: ' . number_format($totalHours, 1) . 'h', 0, 1, 'R');
        
        // Générer le fichier
        $filename = 'pointages_' . date('Y-m-d_His') . '.pdf';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $pdf->Output($filepath, 'F');
        
        return ['filepath' => $filepath, 'filename' => $filename];
    }
    
    /**
     * Grouper les pointages par jour
     */
    private static function groupPunchesByDay($punches) {
        $grouped = [];
        
        foreach ($punches as $punch) {
            $date = substr($punch['punch_datetime'], 0, 10);
            
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'employee_name' => ($punch['first_name'] ?? '') . ' ' . ($punch['last_name'] ?? ''),
                    'in_time' => null,
                    'out_time' => null,
                    'break_duration' => '0h',
                    'total_hours' => '0h',
                    'notes' => ''
                ];
            }
            
            $time = date('H:i', strtotime($punch['punch_datetime']));
            
            if ($punch['type'] === 'in') {
                if (!$grouped[$date]['in_time']) {
                    $grouped[$date]['in_time'] = $time;
                }
            } else {
                $grouped[$date]['out_time'] = $time;
            }
        }
        
        // Calculer les heures totales
        foreach ($grouped as $date => &$data) {
            if ($data['in_time'] && $data['out_time']) {
                $in = strtotime($date . ' ' . $data['in_time']);
                $out = strtotime($date . ' ' . $data['out_time']);
                $hours = ($out - $in) / 3600;
                $data['total_hours'] = number_format($hours, 1) . 'h';
            }
        }
        
        return $grouped;
    }
    
    /**
     * Télécharger un fichier
     */
    public static function downloadFile($filepath, $filename) {
        if (!file_exists($filepath)) {
            return false;
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        
        readfile($filepath);
        unlink($filepath);
        exit;
    }
}

