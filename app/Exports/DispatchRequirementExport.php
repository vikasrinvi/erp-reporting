<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DispatchRequirementExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    protected Collection $rows;
    protected array $months;
    protected array $columns = [];

    public function __construct(Collection $rows, array $months)
    {
        $this->rows = $rows;
        $this->months = $months;

        $this->columns = ['item_code', 'item_name'];

        // 1. Add JH Monthly Dispatch Columns
        foreach ($months as $month) {
            $key = str_replace('-', '_', $month['key']);
            $this->columns[] = 'dispatch_' . $key . '_jh';
        }

        // 2. Add JH Totals & Stock Columns
        $this->columns = array_merge($this->columns, [
            'total_jh',
            'stock_jh',
            'required_jh',
        ]);

        // 3. Add BP Monthly Dispatch Columns
        foreach ($months as $month) {
            $key = str_replace('-', '_', $month['key']);
            $this->columns[] = 'dispatch_' . $key . '_bp';
        }

        // 4. Add BP Totals, Stock, and Grand Totals
        $this->columns = array_merge($this->columns, [
            'total_bp',
            'stock_bp',
            'required_bp',
            'total_dis',
            'total_required',
        ]);
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * Two-tier grouped headers matching the Blade table structure.
     */
    public function headings(): array
    {
        $monthCount = count($this->months);

        // Row 1: Division Group Headers
        $row1 = ['Item Code', 'Item Name'];
        $row1[] = 'JH Division';
        for ($i = 1; $i < $monthCount + 3; $i++) {
            $row1[] = ''; // Empty cells to allow merging
        }

        $row1[] = 'BPJH Division';
        for ($i = 1; $i < $monthCount + 3; $i++) {
            $row1[] = ''; // Empty cells to allow merging
        }

        $row1[] = 'Summary';
        $row1[] = '';

        // Row 2: Sub-headers
        $row2 = ['', ''];

        // JH Sub-headers
        foreach ($this->months as $month) {
            $row2[] = $month['label'];
        }
        $row2[] = 'TOTAL_JH';
        $row2[] = 'Stock JH';
        $row2[] = 'REQUIRED_JH';

        // BPJH Sub-headers
        foreach ($this->months as $month) {
            $row2[] = $month['label'];
        }
        $row2[] = 'TOTAL_BPJH';
        $row2[] = 'Stock BPJH';
        $row2[] = 'REQUIRED_BPJH';

        // Summary Sub-headers
        $row2[] = 'TOTAL_DIS';
        $row2[] = 'TOTAL_REQUIRED';

        return [$row1, $row2];
    }

    
    public function map($row): array
    {
        $values = [];

        foreach ($this->columns as $column) {
            $value = $row[$column] ?? null;

            if (in_array($column, ['item_code', 'item_name'], true)) {
                $values[] = (string) ($value ?? '');
                continue;
            }

            // Preserve zero values and convert missing values to numeric zero.
            if ($value === null || $value === '') {
                $values[] = 0;
            } elseif (is_numeric($value)) {
                $values[] = (float) $value;
            } else {
                $values[] = 0;
            }
        }

        return $values;
    }


    
public function styles(Worksheet $sheet): array
{
    $monthCount = count($this->months);
    $lastColumnIndex = count($this->columns);
    $lastColumnLetter = Coordinate::stringFromColumnIndex($lastColumnIndex);
    $totalRows = $sheet->getHighestRow();

    // Merge Item Code and Item Name headers vertically.
    $sheet->mergeCells('A1:A2');
    $sheet->mergeCells('B1:B2');

    // JH Division group.
    $jhStartCol = 3;
    $jhEndCol = $jhStartCol + $monthCount + 2;

    $jhStartLetter = Coordinate::stringFromColumnIndex($jhStartCol);
    $jhEndLetter = Coordinate::stringFromColumnIndex($jhEndCol);

    $sheet->mergeCells("{$jhStartLetter}1:{$jhEndLetter}1");

    // BPJH Division group.
    $bpStartCol = $jhEndCol + 1;
    $bpEndCol = $bpStartCol + $monthCount + 2;

    $bpStartLetter = Coordinate::stringFromColumnIndex($bpStartCol);
    $bpEndLetter = Coordinate::stringFromColumnIndex($bpEndCol);

    $sheet->mergeCells("{$bpStartLetter}1:{$bpEndLetter}1");

    // Summary group.
    $summaryStartCol = $bpEndCol + 1;
    $summaryEndCol = $summaryStartCol + 1;

    $summaryStartLetter = Coordinate::stringFromColumnIndex($summaryStartCol);
    $summaryEndLetter = Coordinate::stringFromColumnIndex($summaryEndCol);

    $sheet->mergeCells("{$summaryStartLetter}1:{$summaryEndLetter}1");

    // Header formatting.
    $sheet->getStyle("A1:{$lastColumnLetter}2")->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '000000'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F8F9FA'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ]);

    // Apply borders to the worksheet.
    if ($totalRows >= 1) {
        $fullRange = "A1:{$lastColumnLetter}{$totalRows}";

        $sheet->getStyle($fullRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DEE2E6'],
                ],
            ],
        ]);
    }

    // Explicitly display zero values as 0.000.
    if ($totalRows >= 3 && $lastColumnIndex >= 3) {
        $numericRange = "C3:{$lastColumnLetter}{$totalRows}";

        $sheet->getStyle($numericRange)
            ->getNumberFormat()
            ->setFormatCode('0.000;-0.000;0.000');

        $sheet->getStyle($numericRange)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    // Center-align item codes.
    if ($totalRows >= 3) {
        $sheet->getStyle("A3:A{$totalRows}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Freeze headers and item-identification columns.
    $sheet->freezePane('C3');

    // Keep the existing filter behavior.
    // Note: With two header rows, this filters by the second row.
    if ($totalRows >= 3) {
        $sheet->setAutoFilter("A2:{$lastColumnLetter}{$totalRows}");
    }

    return [];
}

}