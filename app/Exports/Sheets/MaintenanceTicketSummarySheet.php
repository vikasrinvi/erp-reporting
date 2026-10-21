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

class MaintenanceTicketSummarySheet implements
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
        return 'Maintenance Summary';
    }

    public function collection()
    {
        return MaintenanceTicketReportQuery::build($this->request)
            ->orderByDesc('raised_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($t) {
                return [
                    $t->ticket_no,
                    $this->formatDate($t->raised_at),
                    ucfirst(str_replace('_', ' ', $t->status)),
                    ucfirst($t->priority ?? ''),
                    ucfirst($t->problem_category ?? ''),
                    $t->equipment_code,
                    $t->machine_name,
                    $t->department_name,
                    $t->requester_name,
                    $t->requester_contact,
                    $this->formatYesNo($t->production_affected),
                    $t->problem_description,
                    $t->request_remarks,

                    $t->inspector_name,
                    $this->formatDate($t->inspected_at, true),
                    $t->inspection_findings,
                    $t->suspected_cause,
                    $t->downtime_hours,
                    $t->inspection_recommendation,
                    $t->inspection_remarks,

                    $t->expert_name,
                    $this->formatDate($t->assessed_at, true),
                    $t->root_cause,
                    $t->corrective_action,
                    $t->preventive_action,
                    $t->estimated_cost,
                    $t->expert_remarks,

                    $t->approver_name,
                    $this->formatDate($t->approval_decided_at, true),
                    $t->approval_decision,
                    $t->approved_budget,
                    $t->approval_remarks,

                    $t->technician_name,
                    $this->formatDate($t->repair_started_at, true),
                    $this->formatDate($t->repair_completed_at, true),
                    $t->repair_work_done,
                    $t->actual_cost,
                    $t->actual_downtime_hours,

                    $t->verifier_name,
                    $this->formatDate($t->verified_at, true),
                    $t->verification_result,
                    $t->verification_remarks,

                    $this->formatDate($t->created_at, true),
                    $this->formatDate($t->updated_at, true),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Ticket No.',
            'Raised Date',
            'Current Status',
            'Priority',
            'Problem Category',
            'Machine Code',
            'Machine Name',
            'Department',
            'Requested By',
            'Contact',
            'Production Affected',
            'Problem Description',
            'Request Remarks',

            'Inspector',
            'Inspection Date',
            'Inspection Findings',
            'Suspected Cause',
            'Downtime Hours',
            'Inspection Recommendation',
            'Inspection Remarks',

            'Expert',
            'Assessment Date',
            'Root Cause',
            'Corrective Action',
            'Preventive Action',
            'Estimated Cost (₹)',
            'Expert Remarks',

            'Approver',
            'Decision Date',
            'Approval Decision',
            'Approved Budget (₹)',
            'Approval Remarks',

            'Technician',
            'Repair Started',
            'Repair Completed',
            'Repair Work Done',
            'Actual Cost (₹)',
            'Actual Downtime Hours',

            'Verifier',
            'Verification Date',
            'Verification Result',
            'Verification Remarks',

            'Created At',
            'Last Updated At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Freeze pane at cell A2 (keeps row 1 header frozen while scrolling)
        $sheet->freezePane('A2');

        $sheet->getStyle('A:AR')->getAlignment()->setVertical('top');
        $sheet->getStyle('A:AR')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(35);

        return $this->reportStyles();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, 'B' => 14, 'C' => 22, 'D' => 12, 'E' => 18,
            'F' => 16, 'G' => 25, 'H' => 18, 'I' => 22, 'J' => 18,
            'K' => 18, 'L' => 45, 'M' => 35, 'N' => 22, 'O' => 20,
            'P' => 40, 'Q' => 35, 'R' => 15, 'S' => 35, 'T' => 35,
            'U' => 22, 'V' => 20, 'W' => 40, 'X' => 40, 'Y' => 40,
            'Z' => 18, 'AA' => 35, 'AB' => 22, 'AC' => 20, 'AD' => 20,
            'AE' => 18, 'AF' => 35, 'AG' => 22, 'AH' => 20, 'AI' => 20,
            'AJ' => 40, 'AK' => 18, 'AL' => 18, 'AM' => 22, 'AN' => 20,
            'AO' => 22, 'AP' => 35, 'AQ' => 20, 'AR' => 20,
        ];
    }

    /**
     * Register events to apply the AutoFilter on range A1:AR.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                $event->sheet->getDelegate()->setAutoFilter("A1:AR{$highestRow}");
            },
        ];
    }
}