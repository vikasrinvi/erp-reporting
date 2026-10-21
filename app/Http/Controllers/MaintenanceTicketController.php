<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Exports\MaintenanceTicketReportQuery;
use App\Exports\MaintenanceTicketsExport;
use Maatwebsite\Excel\Facades\Excel;

class MaintenanceTicketController extends Controller
{
    public function index122(Request $request)
    {
        $query = MaintenanceTicket::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department')) {
            $query->where('department_name', 'like', '%' . $request->department . '%');
        }
        if ($request->filled('machine')) {
            $machine = $request->machine;
            $query->where(fn ($q) => $q->where('machine_name', 'like', "%{$machine}%")
                ->orWhere('equipment_code', 'like', "%{$machine}%"));
        }
        if ($request->filled('ticket_no')) {
            $query->where('ticket_no', 'like', '%' . $request->ticket_no . '%');
        }

        $tickets = $query->latest('id')->paginate(20)->withQueryString();

        return view('maintenance-tickets.index', compact('tickets'));
    }

    
    public function index35345(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'ticket_no' => ['nullable', 'string', 'max:255'],
            'machine' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                'pending_inspection',
                'pending_expert',
                'pending_approval',
                'approved_for_repair',
                'in_repair',
                'pending_verification',
                'closed',
                'rejected',
            ])],
            'priority' => ['nullable', Rule::in([
                'low', 'normal', 'high', 'critical',
            ])],
            'category' => ['nullable', Rule::in([
                'breakdown', 'spare', 'adjustment', 'inspection', 'other',
            ])],
            'requester' => ['nullable', 'string', 'max:255'],
        ]);

        $tickets = $this->filteredTickets($request)
            ->orderByDesc('raised_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('maintenance-tickets.index', compact('tickets'));
    }

    public function index(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'ticket_no' => ['nullable', 'string', 'max:100'],
            'machine' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'requester' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,normal,high,critical'],
            'category' => ['nullable', 'in:breakdown,spare,adjustment,inspection,other'],
            'status' => [
                'nullable',
                'in:pending_inspection,pending_expert,pending_approval,approved_for_repair,in_repair,pending_verification,closed,rejected',
            ],
        ]);

        $tickets = MaintenanceTicketReportQuery::build($request)
            ->orderByDesc('raised_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('maintenance-tickets.index', compact('tickets'));
    }

    /**
     * Shared report query. The same filters are used by the screen
     * and the export, so the results remain consistent.
     */
    private function filteredTickets(Request $request)
    {
        $query = MaintenanceTicket::query();

        if ($request->filled('from_date')) {
            $query->whereDate('raised_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('raised_at', '<=', $request->input('to_date'));
        }

        if ($request->filled('ticket_no')) {
            $query->where('ticket_no', 'like', '%' . $request->input('ticket_no') . '%');
        }

        if ($request->filled('machine')) {
            $machine = $request->input('machine');

            $query->where(function ($q) use ($machine) {
                $q->where('machine_name', 'like', '%' . $machine . '%')
                ->orWhere('equipment_code', 'like', '%' . $machine . '%');
            });
        }

        if ($request->filled('department')) {
            $query->where('department_name', 'like', '%' . $request->input('department') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('category')) {
            $query->where('problem_category', $request->input('category'));
        }

        if ($request->filled('requester')) {
            $query->where('requester_name', 'like', '%' . $request->input('requester') . '%');
        }

        return $query;
    }

    public function export3234(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'ticket_no' => ['nullable', 'string', 'max:255'],
            'machine' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                'pending_inspection',
                'pending_expert',
                'pending_approval',
                'approved_for_repair',
                'in_repair',
                'pending_verification',
                'closed',
                'rejected',
            ])],
            'priority' => ['nullable', Rule::in([
                'low', 'normal', 'high', 'critical',
            ])],
            'category' => ['nullable', Rule::in([
                'breakdown', 'spare', 'adjustment', 'inspection', 'other',
            ])],
            'requester' => ['nullable', 'string', 'max:255'],
        ]);

        // Do not paginate: export every record matching the filters.
        $tickets = $this->filteredTickets($request)
            ->with([
                'spares' => fn ($q) => $q->orderBy('id'),
                'histories' => fn ($q) => $q->orderBy('created_at'),
            ])
            ->orderBy('raised_at')
            ->orderBy('id')
            ->get();

        $filename = 'maintenance-tickets-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tickets, $request) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM helps Excel display text correctly.
            fwrite($file, "\xEF\xBB\xBF");

            // Reduce the risk of spreadsheet formula injection.
            $safe = function ($value) {
                if ($value === null) {
                    return '';
                }

                $value = (string) $value;

                if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
                    return "'" . $value;
                }

                return $value;
            };

            $write = function (array $row) use ($file, $safe) {
                fputcsv($file, array_map($safe, $row));
            };

            $write(['MACHINE MAINTENANCE TICKET REPORT']);
            $write(['Report generated', now()->format('d-m-Y H:i:s')]);
            $write(['From date', $request->input('from_date', 'All dates')]);
            $write(['To date', $request->input('to_date', 'All dates')]);
            $write(['Ticket number filter', $request->input('ticket_no', 'All')]);
            $write(['Machine filter', $request->input('machine', 'All')]);
            $write(['Department filter', $request->input('department', 'All')]);
            $write(['Status filter', $request->input('status', 'All')]);
            $write(['Priority filter', $request->input('priority', 'All')]);
            $write(['Category filter', $request->input('category', 'All')]);
            $write(['Requester filter', $request->input('requester', 'All')]);
            $write(['Total matching tickets', $tickets->count()]);
            $write([]);

            $write([
                'Ticket No', 'Raised Date', 'Status', 'Priority', 'Category',
                'Machine Code', 'Machine Name', 'Department',
                'Requester', 'Requester Contact', 'Production Affected',
                'Problem Description', 'Request Remarks',

                'Inspector', 'Inspection Date', 'Inspection Findings',
                'Suspected Cause', 'Inspection Downtime Hours',
                'Inspection Recommendation', 'Inspection Remarks',

                'Expert', 'Assessment Date', 'Confirmed Root Cause',
                'Corrective Action', 'Preventive Action', 'Estimated Total Cost',
                'Expert Remarks',

                'Approver', 'Approval Date', 'Approval Decision',
                'Approved Budget', 'Approval Remarks',

                'Technician', 'Repair Started', 'Repair Completed',
                'Repair Work Done', 'Actual Total Cost', 'Actual Downtime Hours',

                'Verifier', 'Verified At', 'Verification Result',
                'Verification Remarks', 'Closed Date',

                
                
                'Created At', 'Last Updated At',
            ]);

            foreach ($tickets as $ticket) {
                $spareLines = [];

                foreach ($ticket->spares as $spare) {
                    $spareLines[] = implode(' | ', [
                        'Part: ' . $spare->spare_name,
                        'Part No: ' . ($spare->part_number ?: '-'),
                        'Required: ' . ($spare->quantity_required ?? ''),
                        'Approved: ' . ($spare->quantity_approved ?? ''),
                        'Used: ' . ($spare->quantity_used ?? ''),
                        'Estimated Unit Cost: ' . ($spare->estimated_unit_cost ?? ''),
                        'Actual Unit Cost: ' . ($spare->actual_unit_cost ?? ''),
                        'Remarks: ' . ($spare->remarks ?: '-'),
                    ]);
                }

                $historyLines = [];

                foreach ($ticket->histories as $history) {
                    $historyLines[] = implode(' | ', array_filter([
                        optional($history->created_at)->format('d-m-Y H:i'),
                        $history->action,
                        'By: ' . $history->performed_by,
                        'From: ' . ($history->from_status ?: '-'),
                        'To: ' . $history->to_status,
                        'Remarks: ' . ($history->remarks ?: '-'),
                    ]));
                }

                $write([
                    $ticket->ticket_no,
                    optional($ticket->raised_at)->format('d-m-Y'),
                    $ticket->status,
                    $ticket->priority,
                    $ticket->problem_category,
                    $ticket->equipment_code,
                    $ticket->machine_name,
                    $ticket->department_name,
                    $ticket->requester_name,
                    $ticket->requester_contact,
                    $ticket->production_affected ? 'Yes' : 'No',
                    $ticket->problem_description,
                    $ticket->request_remarks,

                    $ticket->inspector_name,
                    optional($ticket->inspected_at)->format('d-m-Y H:i'),
                    $ticket->inspection_findings,
                    $ticket->suspected_cause,
                    $ticket->downtime_hours,
                    $ticket->inspection_recommendation,
                    $ticket->inspection_remarks,

                    $ticket->expert_name,
                    optional($ticket->assessed_at)->format('d-m-Y H:i'),
                    $ticket->root_cause,
                    $ticket->corrective_action,
                    $ticket->preventive_action,
                    $ticket->estimated_cost,
                    $ticket->expert_remarks,

                    $ticket->approver_name,
                    optional($ticket->approval_decided_at)->format('d-m-Y H:i'),
                    $ticket->approval_decision,
                    $ticket->approved_budget,
                    $ticket->approval_remarks,

                    $ticket->technician_name,
                    optional($ticket->repair_started_at)->format('d-m-Y H:i'),
                    optional($ticket->repair_completed_at)->format('d-m-Y H:i'),
                    $ticket->repair_work_done,
                    $ticket->actual_cost,
                    $ticket->actual_downtime_hours,

                    $ticket->verifier_name,
                    optional($ticket->verified_at)->format('d-m-Y H:i'),
                    $ticket->verification_result,
                    $ticket->verification_remarks,
                    $ticket->status === 'closed'
                        ? optional($ticket->updated_at)->format('d-m-Y H:i')
                        : '',

                    // implode("\n", $spareLines),
                    
                    optional($ticket->created_at)->format('d-m-Y H:i'),
                    optional($ticket->updated_at)->format('d-m-Y H:i'),
                ]);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'ticket_no' => ['nullable', 'string', 'max:100'],
            'machine' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'requester' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'in:low,normal,high,critical'],
            'category' => ['nullable', 'in:breakdown,spare,adjustment,inspection,other'],
            'status' => [
                'nullable',
                'in:pending_inspection,pending_expert,pending_approval,approved_for_repair,in_repair,pending_verification,closed,rejected',
            ],
        ]);

        return Excel::download(
            new MaintenanceTicketsExport($request),
            'maintenance-tickets-' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }


    public function create()
    {
        $machines = DB::table('equipment_masters')
            ->select('id', 'eqpt_code', 'eqpt_name', 'dept_id', 'capacity')
            ->where('status', 1)
            ->orderBy('eqpt_name')
            ->get();

        return view('maintenance-tickets.create', compact('machines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'equipment_code' => ['required', 'string', 'max:255'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_contact' => ['nullable', 'string', 'max:255'],
            'raised_at' => ['required', 'date'],
            'problem_category' => ['required', Rule::in(['breakdown', 'spare', 'adjustment', 'inspection', 'other'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'critical'])],
            'problem_description' => ['required', 'string', 'max:10000'],
            'production_affected' => ['nullable', 'boolean'],
            'request_remarks' => ['nullable', 'string', 'max:5000'],
            'performed_by' => ['required', 'string', 'max:255'],
        ]);

        $machine = DB::table('equipment_masters')
            ->where('eqpt_code', $data['equipment_code'])
            ->first();

        if (!$machine) {
            return back()->withInput()->withErrors([
                'equipment_code' => 'Selected machine was not found. Refresh and select it again.',
            ]);
        }

        $ticket = DB::transaction(function () use ($data, $machine, $request) {
            $lastId = (int) MaintenanceTicket::max('id') + 1;
            $ticketNo = 'MT-' . now()->format('ym') . '-' . $machine->dept_id . '-' . str_pad((string) $lastId, 6, '0', STR_PAD_LEFT);

            $ticket = MaintenanceTicket::create([
                'ticket_no' => $ticketNo,
                'equipment_code' => $machine->eqpt_code,
                'machine_name' => $machine->eqpt_name,
                'department_name' => $machine->dept_id ?? null,
                'requester_name' => $data['requester_name'],
                'requester_contact' => $data['requester_contact'] ?? null,
                'raised_at' => $data['raised_at'],
                'problem_category' => $data['problem_category'],
                'priority' => $data['priority'],
                'problem_description' => $data['problem_description'],
                'production_affected' => $request->boolean('production_affected'),
                'request_remarks' => $data['request_remarks'] ?? null,
                'status' => 'pending_inspection',
            ]);

            $this->history($ticket, 'Ticket raised', null, 'pending_inspection',
                $data['performed_by'], $data['request_remarks'] ?? null);

            return $ticket;
        });

        return redirect()->route('maintenance-tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_no} raised successfully.");
    }

    public function show(MaintenanceTicket $maintenanceTicket)
    {
        $maintenanceTicket->load(['spares', 'histories']);
        return view('maintenance-tickets.show', ['ticket' => $maintenanceTicket]);
    }

    public function inspection(Request $request, MaintenanceTicket $maintenanceTicket)
    {
        $this->requireStatus($maintenanceTicket, 'pending_inspection');

        $data = $request->validate([
            'inspector_name' => ['required', 'string', 'max:255'],
            'inspected_at' => ['required', 'date'],
            'inspection_findings' => ['required', 'string', 'max:10000'],
            'suspected_cause' => ['nullable', 'string', 'max:5000'],
            'downtime_hours' => ['nullable', 'numeric', 'min:0'],
            'inspection_recommendation' => ['required', 'string', 'max:5000'],
            'inspection_remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->transition($maintenanceTicket, $data, 'inspection completed',
            'pending_expert', $data['inspector_name'], $data['inspection_remarks'] ?? null);

        return back()->with('success', 'Inspection saved. Ticket moved to expert assessment.');
    }

    public function expert(Request $request, MaintenanceTicket $maintenanceTicket)
    {
        $this->requireStatus($maintenanceTicket, 'pending_expert');

        $data = $request->validate([
            'expert_name' => ['required', 'string', 'max:255'],
            'assessed_at' => ['required', 'date'],
            'root_cause' => ['required', 'string', 'max:10000'],
            'corrective_action' => ['required', 'string', 'max:10000'],
            'preventive_action' => ['nullable', 'string', 'max:10000'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'expert_remarks' => ['nullable', 'string', 'max:5000'],
            'spares' => ['nullable', 'array', 'max:50'],
            'spares.*.spare_name' => ['required_with:spares', 'string', 'max:255'],
            'spares.*.part_number' => ['nullable', 'string', 'max:255'],
            'spares.*.quantity_required' => ['required_with:spares', 'numeric', 'gt:0'],
            'spares.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'spares.*.remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($maintenanceTicket, $data) {
            $spares = $data['spares'] ?? [];
            unset($data['spares']);
            $this->transition($maintenanceTicket, $data, 'expert assessment completed',
                'pending_approval', $data['expert_name'], $data['expert_remarks'] ?? null);

            $maintenanceTicket->spares()->delete();
            foreach ($spares as $spare) {
                $maintenanceTicket->spares()->create($spare);
            }
        });

        return back()->with('success', 'Expert assessment saved. Ticket moved to budget/admin approval.');
    }

    public function approval(Request $request, MaintenanceTicket $maintenanceTicket)
    {
        $this->requireStatus($maintenanceTicket, 'pending_approval');

        $data = $request->validate([
            'approver_name' => ['required', 'string', 'max:255'],
            'approval_decision' => ['required', Rule::in(['approved', 'rejected', 'clarification'])],
            'approved_budget' => ['nullable', 'numeric', 'min:0'],
            'approval_remarks' => ['required', 'string', 'max:5000'],
        ]);

        if ($data['approval_decision'] === 'approved' && !isset($data['approved_budget'])) {
            return back()->withInput()->withErrors(['approved_budget' => 'Enter the approved budget.']);
        }

        $next = match ($data['approval_decision']) {
            'approved' => 'approved_for_repair',
            'rejected' => 'rejected',
            default => 'pending_expert',
        };

        $data['approval_decided_at'] = now();
        $this->transition($maintenanceTicket, $data, 'budget/admin decision',
            $next, $data['approver_name'], $data['approval_remarks']);

        return back()->with('success', 'Approval decision recorded.');
    }

    public function startRepair(Request $request, MaintenanceTicket $maintenanceTicket)
    {
        $this->requireStatus($maintenanceTicket, 'approved_for_repair');

        $data = $request->validate([
            'technician_name' => ['required', 'string', 'max:255'],
            'repair_started_at' => ['required', 'date'],
            'repair_work_done' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->transition($maintenanceTicket, $data, 'repair started',
            'in_repair', $data['technician_name'], $data['repair_work_done'] ?? null);

        return back()->with('success', 'Repair started.');
    }

    public function completeRepair(Request $request, MaintenanceTicket $maintenanceTicket)
    {
        $this->requireStatus($maintenanceTicket, 'in_repair');

        $data = $request->validate([
            'technician_name' => ['required', 'string', 'max:255'],
            'repair_completed_at' => ['required', 'date'],
            'repair_work_done' => ['required', 'string', 'max:10000'],
            'actual_cost' => ['required', 'numeric', 'min:0'],
            'actual_downtime_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->transition($maintenanceTicket, $data, 'repair completed; awaiting verification',
            'pending_verification', $data['technician_name'], $data['repair_work_done']);

        return back()->with('success', 'Repair details saved. Ticket is awaiting verification.');
    }

    public function verify(Request $request, MaintenanceTicket $maintenanceTicket)
    {
        $this->requireStatus($maintenanceTicket, 'pending_verification');

        $data = $request->validate([
            'verifier_name' => ['required', 'string', 'max:255'],
            'verification_result' => ['required', Rule::in(['passed', 'failed'])],
            'verification_remarks' => ['required', 'string', 'max:5000'],
        ]);

        $data['verified_at'] = now();
        $next = $data['verification_result'] === 'passed' ? 'closed' : 'in_repair';

        $this->transition($maintenanceTicket, $data, 'repair verification',
            $next, $data['verifier_name'], $data['verification_remarks']);

        return back()->with('success', $next === 'closed'
            ? 'Ticket verified and closed.'
            : 'Verification failed. Ticket returned to repair.');
    }

    private function requireStatus(MaintenanceTicket $ticket, string $expected): void
    {
        abort_unless($ticket->status === $expected, 422,
            'This action is not allowed at the current workflow stage.');
    }

    private function transition(
        MaintenanceTicket $ticket,
        array $data,
        string $action,
        string $toStatus,
        string $performedBy,
        ?string $remarks = null
    ): void {
        DB::transaction(function () use ($ticket, $data, $action, $toStatus, $performedBy, $remarks) {
            $from = $ticket->status;
            $ticket->update(array_merge($data, ['status' => $toStatus]));
            $this->history($ticket, $action, $from, $toStatus, $performedBy, $remarks);
        });
    }

    private function history(
        MaintenanceTicket $ticket,
        string $action,
        ?string $from,
        string $to,
        string $performedBy,
        ?string $remarks = null
    ): void {
        MaintenanceTicketHistory::create([
            'maintenance_ticket_id' => $ticket->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'performed_by' => $performedBy,
            'remarks' => $remarks,
        ]);
    }
}
