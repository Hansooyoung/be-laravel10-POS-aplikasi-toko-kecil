<?php

namespace App\Services;

use App\Exports\ExportArray;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    public function exportToExcel($data, $filename, $headers)
    {
        $exportData = collect($data)->map(fn($item) => (array) $item);
        $spreadsheetData = array_merge([$headers], $exportData->toArray());

        return Excel::download(new ExportArray($spreadsheetData), "{$filename}.xlsx");
    }

    public function exportToPDF($data, $filename, $headers, $title)
    {
        $pdf = Pdf::loadView('exports.generic_pdf', compact('data', 'headers', 'title'));
        return $pdf->download("{$filename}.pdf");
    }

}
