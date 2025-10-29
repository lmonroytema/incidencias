<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncidentExportService
{
    /**
     * Export incidents to Excel. If a macro-enabled template exists, it will be used (.xlsm).
     * @param \Illuminate\Support\Collection $incidents
     * @param array $filters
     * @return string Absolute file path ready for download
     */
    public function export($incidents, array $filters = []): string
    {
        $templatePath = null;
        if (Storage::exists('templates/incidencias_template.xlsm')) {
            $templatePath = Storage::path('templates/incidencias_template.xlsm');
        }

        if ($templatePath) {
            $spreadsheet = IOFactory::load($templatePath);
        } else {
            $spreadsheet = new Spreadsheet();
        }

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Headers
        $headers = [
            'ID', 'Fecha', 'DNI Tipo', 'DNI Número', 'Nombre Completo', 'Área', 'Correo',
            'Categoría', 'Apps', 'Descripción', 'Urgencia', 'Hostname', 'SO', 'Versión Office',
            'Primera vez', 'Inicio', 'Estado', 'Consultor', 'Notas', 'Fecha Resolución', 'Solución'
        ];
        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $h);
        }

        // Data
        $row = 2;
        foreach ($incidents as $incident) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(1) . $row, $incident->id);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(2) . $row, optional($incident->created_at)->format('Y-m-d H:i'));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(3) . $row, $incident->dni_type);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(4) . $row, $incident->dni_number);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(5) . $row, $incident->full_name);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(6) . $row, $incident->area_name);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(7) . $row, $incident->corporate_email);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(8) . $row, $incident->category);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(9) . $row, implode(',', (array) $incident->apps));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(10) . $row, $incident->description);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(11) . $row, $incident->urgency);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(12) . $row, $incident->hostname);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(13) . $row, $incident->os);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(14) . $row, $incident->office_version);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(15) . $row, $incident->first_time ? 'Sí' : 'No');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(16) . $row, optional($incident->started_at)->format('Y-m-d'));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(17) . $row, $incident->status);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(18) . $row, optional($incident->assignedTo)->name);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(19) . $row, $incident->consultant_notes);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(20) . $row, optional($incident->resolution_date)->format('Y-m-d H:i'));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(21) . $row, $incident->solution_applied);
            $row++;
        }

        // Auto-size columns
        foreach (range(1, count($headers)) as $colIdx) {
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to storage temporary path
        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $ext = $templatePath ? 'xlsm' : 'xlsx';
        $filename = 'incidencias_export_' . date('Ymd_His') . '.' . $ext;
        $fullPath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($fullPath);

        return $fullPath;
    }
}