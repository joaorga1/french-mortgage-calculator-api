<?php

namespace App\Services;

use App\Exports\AmortizationExport;
use App\Models\Simulation;
use Maatwebsite\Excel\Facades\Excel;

class SimulationExportService
{
    public function __construct(
        private AmortizationTableService $amortizationService
    ) {
    }

    /**
     * Export to CSV
     */
    public function toCsv(Simulation $simulation): string
    {
        $table = $this->amortizationService->generate($simulation);

        $csv = fopen('php://temp', 'r+');

        // Header
        fputcsv($csv, ['Month', 'Payment', 'Amortised', 'Interest', 'Balance']);

        // Data
        foreach ($table as $row) {
            fputcsv($csv, [
                $row['month'],
                $row['payment'],
                $row['amortised'],
                $row['interest'],
                $row['balance'],
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }

    /**
     * Export to Excel
     */
    public function toExcel(Simulation $simulation)
    {
        return Excel::download(
            new AmortizationExport($simulation, $this->amortizationService),
            "simulation_{$simulation->id}.xlsx"
        );
    }
}
