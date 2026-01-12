<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelService
{
    private string $templatePath;
    private string $outputDir;

    public function __construct(string $templatePath, string $outputDir)
    {
        $this->templatePath = $templatePath;
        $this->outputDir = rtrim($outputDir, '/');
    }

    public function generateReport(array $installation, array $processes, array $summary): string
    {
        $spreadsheet = $this->loadTemplate();

        $this->fillInstallation($spreadsheet, $installation);
        $this->fillProcesses($spreadsheet, $processes, $summary);
        $this->fillSummary($spreadsheet, $summary);

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0775, true);
        }

        $filename = sprintf('CBAM_Report_%d_%s.xlsx', $installation['id'], date('Ymd_His'));
        $path = $this->outputDir . '/' . $filename;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);

        return $path;
    }

    private function loadTemplate(): Spreadsheet
    {
        if (!file_exists($this->templatePath)) {
            throw new RuntimeException('Template not found: ' . $this->templatePath);
        }

        return IOFactory::load($this->templatePath);
    }

    private function fillInstallation(Spreadsheet $spreadsheet, array $installation): void
    {
        $sheet = $spreadsheet->getSheetByName('A_InstData');
        if (!$sheet) {
            return;
        }

        $sheet->setCellValue('C8', $installation['name']);
        $sheet->setCellValue('C12', $installation['unlocode']);
        $sheet->setCellValue('D9', $installation['period_start']);
        $sheet->setCellValue('F9', $installation['period_end']);
    }

    private function fillProcesses(Spreadsheet $spreadsheet, array $processes, array $summary): void
    {
        $sheet = $spreadsheet->getSheetByName('D_Processes');
        if (!$sheet) {
            return;
        }

        $startRow = 10;
        foreach ($processes as $index => $process) {
            $row = $startRow + $index;
            $sheet->setCellValue('B' . $row, $process['cn_code']);
            $sheet->setCellValue('C' . $row, $process['production_amount']);
            $sheet->setCellValue('D' . $row, $summary['see']);
        }
    }

    private function fillSummary(Spreadsheet $spreadsheet, array $summary): void
    {
        $sheet = $spreadsheet->getSheetByName('Summary_Processes');
        if (!$sheet) {
            return;
        }

        $sheet->setCellValue('C6', $summary['total_production']);
        $sheet->setCellValue('C7', $summary['direct']);
        $sheet->setCellValue('C8', $summary['indirect']);
        $sheet->setCellValue('C9', $summary['see']);
    }
}
