<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\FormatsMaintenanceTicketReport;
use App\Exports\MaintenanceTicketReportQuery;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceTicketSparesSheet implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithEvents
{
    use FormatsMaintenanceTicketReport;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function title(): string
    {
        return 'Spare Parts';
    }

    public function collection()
    {
        return MaintenanceTicketReportQuery::build($this->request)
            ->with('spares')
            ->orderByDesc('raised_at')
            ->get()
            ->flatMap(function ($ticket) {
                return $ticket->spares->map(function ($spare) use ($ticket) {
                    return [
                        $ticket->ticket_no,
                        $this->formatDate($ticket->raised_at),
                        $ticket->equipment_code,
                        $ticket->machine_name,
                        $ticket->department_name,
                        $spare->spare_name,
                        $spare->part_number,
                        $spare->quantity_required,
                        $spare->quantity_approved,
                        $spare->quantity_used,
                        $spare->estimated_unit_cost,
                        $spare->actual_unit_cost,
                        $spare->remarks,
                    ];
                });
            })
            ->values();
    }

    public function headings(): array
    {
        return [
            'Ticket No.',
            'Raised Date',
            'Machine Code',
            'Machine Name',
            'Department',
            'Spare Part',
            'Part Number',
            'Quantity Required',
            'Quantity Approved',
            'Quantity Used',
            'Estimated Unit Cost (₹)',
            'Actual Unit Cost (₹)',
            'Remarks',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Freeze header row (A1)
        $sheet->freezePane('A2');

        $sheet->getStyle('A:M')->getAlignment()->setVertical('top');
        $sheet->getStyle('A:M')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(30);

        return $this->reportStyles();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, 'B' => 14, 'C' => 18, 'D' => 28, 'E' => 20,
            'F' => 28, 'G' => 20, 'H' => 18, 'I' => 18, 'J' => 16,
            'K' => 22, 'L' => 22, 'M' => 35,
        ];
    }

    /**
     * Register events to apply the AutoFilter on range A1:M.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                $event->sheet->getDelegate()->setAutoFilter("A1:M{$highestRow}");
            },
        ];
    }
}