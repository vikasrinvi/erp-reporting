<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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

    public function headings(): array
    {
        $headings = ['Item Code', 'Item Name'];

        // 1. Add JH Monthly Headings
        foreach ($this->months as $month) {
            $headings[] = 'Dispatch ' . $month['label'] . ' JH';
        }

        // 2. Add JH Totals & Stock Headings
        $headings = array_merge($headings, [
            'TOTAL_JH',
            'Stock JH',
            'REQUIRED_JH',
        ]);

        // 3. Add BP Monthly Headings
        foreach ($this->months as $month) {
            $headings[] = 'Dispatch ' . $month['label'] . ' BPJH';
        }

        // 4. Add BP Totals & Grand Totals Headings
        return array_merge($headings, [
            'TOTAL_BPJH',
            'Stock BPJH',
            'REQUIRED_BPJH',
            'TOTAL_DIS',
            'TOTAL_REQUIRED',
        ]);
    }

    public function map($row): array
    {
        $values = [];

        foreach ($this->columns as $column) {
            $value = $row[$column] ?? null;

            // Keep item code and item name as strings/blanks if empty
            if (in_array($column, ['item_code', 'item_name'])) {
                $values[] = $value ?? '';
            } else {
                // dd(4534, is_numeric($value), $value, $value == 0);
                // Default numeric/calculation columns to 0
                // $values[] = is_numeric($value) ? (float) $value : 0;
                $values[] = $value == 0 ?  0 : (float) $value;
            }
        }

        return $values;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->freezePane('C2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [];
    }
}