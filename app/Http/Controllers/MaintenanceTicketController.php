<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MaintenanceTicketController extends Controller
{
    public function index(Request $request)
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
            $ticketNo = 'MT-' . now()->format('ym') . '-' . str_pad((string) $lastId, 6, '0', STR_PAD_LEFT);

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
