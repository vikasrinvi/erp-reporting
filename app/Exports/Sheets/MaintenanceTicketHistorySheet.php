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

class MaintenanceTicketHistorySheet implements
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
        return 'Workflow History';
    }

    public function collection()
    {
        return MaintenanceTicketReportQuery::build($this->request)
            ->with('histories')
            ->orderByDesc('raised_at')
            ->get()
            ->flatMap(function ($ticket) {
                return $ticket->histories
                    ->sortBy('created_at')
                    ->map(function ($history) use ($ticket) {
                        return [
                            $ticket->ticket_no,
                            $this->formatDate($ticket->raised_at),
                            $ticket->equipment_code,
                            $ticket->machine_name,
                            $this->formatDate($history->created_at, true),
                            $history->action,
                            $history->from_status,
                            $history->to_status,
                            $history->performed_by,
                            $history->remarks,
                            is_array($history->details)
                                ? json_encode($history->details, JSON_UNESCAPED_UNICODE)
                                : (string) ($history->details ?? ''),
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
            'Event Date & Time',
            'Action',
            'Previous Status',
            'New Status',
            'Performed By',
            'Remarks',
            'Additional Details',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Freeze header row (A1)
        $sheet->freezePane('A2');

        $sheet->getStyle('A:K')->getAlignment()->setVertical('top');
        $sheet->getStyle('A:K')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(30);

        return $this->reportStyles();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, 'B' => 14, 'C' => 18, 'D' => 28, 'E' => 22,
            'F' => 34, 'G' => 22, 'H' => 22, 'I' => 24, 'J' => 40,
            'K' => 45,
        ];
    }

    /**
     * Register events to apply the AutoFilter on range A1:K.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                $event->sheet->getDelegate()->setAutoFilter("A1:K{$highestRow}");
            },
        ];
    }
}