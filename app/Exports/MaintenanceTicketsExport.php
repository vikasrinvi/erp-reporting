<?php

namespace App\Exports;

use App\Exports\Sheets\MaintenanceTicketSummarySheet;
use App\Exports\Sheets\MaintenanceTicketSparesSheet;
use App\Exports\Sheets\MaintenanceTicketHistorySheet;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MaintenanceTicketsExport implements WithMultipleSheets
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        return [
            new MaintenanceTicketSummarySheet($this->request),
            new MaintenanceTicketSparesSheet($this->request),
            new MaintenanceTicketHistorySheet($this->request),
        ];
    }
}