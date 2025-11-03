<?php

namespace App\Exports;

use App\Models\Simulation;
use App\Services\AmortizationTableService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AmortizationExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private Simulation $simulation,
        private AmortizationTableService $service
    ) {
    }

    public function array(): array
    {
        $table = $this->service->generate($this->simulation);

        // Convert to Excel format
        return array_map(function ($row) {
            return [
                $row['month'],
                number_format($row['payment'], 2, '.', ''),
                number_format($row['amortised'], 2, '.', ''),
                number_format($row['interest'], 2, '.', ''),
                number_format($row['balance'], 2, '.', ''),
            ];
        }, $table);
    }

    public function headings(): array
    {
        return ['Month', 'Payment', 'Amortised', 'Interest', 'Balance'];
    }

    public function title(): string
    {
        return 'Amortization Schedule';
    }
}
