<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ticket->ticket_no }} — Maintenance Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 1100px">
    <a href="{{ route('maintenance-tickets.index') }}" class="btn btn-link px-0">← All tickets</a>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    <div class="card mb-3"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2"><div><h2>{{ $ticket->ticket_no }}</h2><div class="text-muted">{{ $ticket->machine_name }} ({{ $ticket->equipment_code }}) · {{ $ticket->department_name ?: 'No department recorded' }}</div></div><span class="badge text-bg-primary align-self-start fs-6">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span></div>
        <hr>
        <div class="row g-3">
            <div class="col-md-3"><strong>Raised</strong><br>{{ $ticket->raised_at?->format('d-m-Y') }}</div>
            <div class="col-md-3"><strong>Requester</strong><br>{{ $ticket->requester_name }}<br>{{ $ticket->requester_contact }}</div>
            <div class="col-md-3"><strong>Category / priority</strong><br>{{ ucwords($ticket->problem_category) }} / {{ strtoupper($ticket->priority) }}</div>
            <div class="col-md-3"><strong>Production affected</strong><br>{{ $ticket->production_affected ? 'Yes' : 'No' }}</div>
            <div class="col-12"><strong>Problem description</strong><p class="mb-1">{{ $ticket->problem_description }}</p></div>
            @if($ticket->request_remarks)<div class="col-12"><strong>Initial remarks</strong><p>{{ $ticket->request_remarks }}</p></div>@endif
        </div>
    </div></div>

    @if($ticket->status === 'pending_inspection')
    <div class="card mb-3"><div class="card-header fw-bold">Stage 1 — Inspection</div><div class="card-body">
        <form method="POST" action="{{ route('maintenance-tickets.inspection',$ticket) }}">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Inspector / operator name *</label><input name="inspector_name" class="form-control" required value="{{ old('inspector_name') }}"></div>
                <div class="col-md-6"><label class="form-label">Inspection date/time *</label><input type="datetime-local" name="inspected_at" class="form-control" required value="{{ old('inspected_at', now()->format('Y-m-d\TH:i')) }}"></div>
                <div class="col-12"><label class="form-label">Inspection findings *</label><textarea name="inspection_findings" class="form-control" rows="3" required>{{ old('inspection_findings') }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Suspected cause</label><textarea name="suspected_cause" class="form-control" rows="2">{{ old('suspected_cause') }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Recommended action *</label><textarea name="inspection_recommendation" class="form-control" rows="2" required>{{ old('inspection_recommendation') }}</textarea></div>
                <div class="col-md-4"><label class="form-label">Downtime (hours)</label><input type="number" step="0.01" min="0" name="downtime_hours" class="form-control" value="{{ old('downtime_hours') }}"></div>
                <div class="col-md-8"><label class="form-label">Remarks</label><input name="inspection_remarks" class="form-control" value="{{ old('inspection_remarks') }}"></div>
                <div class="col-12"><button class="btn btn-primary">Complete Inspection → Expert Assessment</button></div>
            </div>
        </form>
    </div></div>
    @endif

    @if($ticket->status === 'pending_expert')
    <div class="card mb-3"><div class="card-header fw-bold">Stage 2 — Expert assessment and spare parts</div><div class="card-body">
        <div class="alert alert-secondary"><strong>Inspection:</strong> {{ $ticket->inspection_findings }}<br><strong>Recommendation:</strong> {{ $ticket->inspection_recommendation }}</div>
        <form method="POST" action="{{ route('maintenance-tickets.expert',$ticket) }}">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Expert name *</label><input name="expert_name" class="form-control" required value="{{ old('expert_name') }}"></div>
                <div class="col-md-6"><label class="form-label">Assessment date/time *</label><input type="datetime-local" name="assessed_at" class="form-control" required value="{{ old('assessed_at', now()->format('Y-m-d\TH:i')) }}"></div>
                <div class="col-12"><label class="form-label">Confirmed root cause *</label><textarea name="root_cause" class="form-control" required rows="2">{{ old('root_cause') }}</textarea></div>
                <div class="col-12"><label class="form-label">Corrective action *</label><textarea name="corrective_action" class="form-control" required rows="2">{{ old('corrective_action') }}</textarea></div>
                <div class="col-12"><label class="form-label">Preventive action to avoid repeat failure</label><textarea name="preventive_action" class="form-control" rows="2">{{ old('preventive_action') }}</textarea></div>
                <div class="col-md-4"><label class="form-label">Total estimated cost *</label><input type="number" min="0" step="0.01" name="estimated_cost" class="form-control" required value="{{ old('estimated_cost',0) }}"></div>
                <div class="col-md-8"><label class="form-label">Expert remarks</label><input name="expert_remarks" class="form-control" value="{{ old('expert_remarks') }}"></div>
            </div>
            <hr><h6>Spare parts required (optional)</h6>
            <div id="spares">
                <div class="row g-2 mb-2 spare-row">
                    <div class="col-md-4"><input class="form-control" name="spares[0][spare_name]" placeholder="Spare part name"></div>
                    <div class="col-md-2"><input class="form-control" name="spares[0][part_number]" placeholder="Part number"></div>
                    <div class="col-md-2"><input class="form-control" type="number" min="0.01" step="0.01" name="spares[0][quantity_required]" placeholder="Qty required"></div>
                    <div class="col-md-2"><input class="form-control" type="number" min="0" step="0.01" name="spares[0][estimated_unit_cost]" placeholder="Unit cost"></div>
                    <div class="col-md-2"><input class="form-control" name="spares[0][remarks]" placeholder="Remarks"></div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addSpare()">+ Add spare row</button><br>
            <button class="btn btn-primary">Submit Assessment → Budget/Admin</button>
        </form>
    </div></div>
    <script>
    let spareIndex = 1;
    function addSpare() {
        const html = `<div class="row g-2 mb-2 spare-row">
          <div class="col-md-4"><input class="form-control" name="spares[${spareIndex}][spare_name]" placeholder="Spare part name" required></div>
          <div class="col-md-2"><input class="form-control" name="spares[${spareIndex}][part_number]" placeholder="Part number"></div>
          <div class="col-md-2"><input class="form-control" type="number" min="0.01" step="0.01" name="spares[${spareIndex}][quantity_required]" placeholder="Qty required" required></div>
          <div class="col-md-2"><input class="form-control" type="number" min="0" step="0.01" name="spares[${spareIndex}][estimated_unit_cost]" placeholder="Unit cost"></div>
          <div class="col-md-2"><input class="form-control" name="spares[${spareIndex}][remarks]" placeholder="Remarks"></div>
        </div>`;
        document.getElementById('spares').insertAdjacentHTML('beforeend', html);
        spareIndex++;
    }
    </script>
    @endif

    @if($ticket->status === 'pending_approval')
    <div class="card mb-3"><div class="card-header fw-bold">Stage 3 — Budget / administration decision</div><div class="card-body">
        <p><strong>Root cause:</strong> {{ $ticket->root_cause }}</p><p><strong>Corrective action:</strong> {{ $ticket->corrective_action }}</p><p><strong>Estimated cost:</strong> ₹{{ number_format((float)$ticket->estimated_cost,2) }}</p>
        @if($ticket->spares->count())
            <h6>Requested spare parts</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Part</th><th>Part no.</th><th>Qty</th><th>Est. unit cost</th></tr></thead><tbody>
            @foreach($ticket->spares as $spare)<tr><td>{{ $spare->spare_name }}</td><td>{{ $spare->part_number }}</td><td>{{ $spare->quantity_required }}</td><td>{{ $spare->estimated_unit_cost }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
        <form method="POST" action="{{ route('maintenance-tickets.approval',$ticket) }}">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Approver name *</label><input name="approver_name" class="form-control" required value="{{ old('approver_name') }}"></div>
                <div class="col-md-6"><label class="form-label">Decision *</label><select name="approval_decision" class="form-select" required><option value="approved">Approve</option><option value="clarification">Return for clarification</option><option value="rejected">Reject</option></select></div>
                <div class="col-md-6"><label class="form-label">Approved budget (₹)</label><input type="number" min="0" step="0.01" name="approved_budget" class="form-control" value="{{ old('approved_budget',$ticket->estimated_cost) }}"></div>
                <div class="col-12"><label class="form-label">Decision remarks (required) *</label><textarea name="approval_remarks" class="form-control" rows="2" required>{{ old('approval_remarks') }}</textarea></div>
                <div class="col-12"><button class="btn btn-primary">Record Decision</button></div>
            </div>
        </form>
    </div></div>
    @endif

    @if($ticket->status === 'approved_for_repair')
    <div class="card mb-3"><div class="card-header fw-bold">Stage 4 — Start repair</div><div class="card-body">
        <p>Approved budget: ₹{{ number_format((float)$ticket->approved_budget,2) }}</p>
        <form method="POST" action="{{ route('maintenance-tickets.start-repair',$ticket) }}">@csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Technician / assigned person *</label><input name="technician_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Start date/time *</label><input type="datetime-local" name="repair_started_at" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                <div class="col-12"><label class="form-label">Initial repair notes</label><textarea name="repair_work_done" class="form-control" rows="2"></textarea></div>
                <div class="col-12"><button class="btn btn-primary">Start Repair</button></div>
            </div>
        </form>
    </div></div>
    @endif

    @if($ticket->status === 'in_repair')
    <div class="card mb-3"><div class="card-header fw-bold">Repair completion</div><div class="card-body">
        <form method="POST" action="{{ route('maintenance-tickets.complete-repair',$ticket) }}">@csrf
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Technician name *</label><input name="technician_name" class="form-control" required value="{{ old('technician_name',$ticket->technician_name) }}"></div>
                <div class="col-md-4"><label class="form-label">Completion date/time *</label><input type="datetime-local" name="repair_completed_at" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                <div class="col-md-4"><label class="form-label">Actual cost (₹) *</label><input type="number" min="0" step="0.01" name="actual_cost" class="form-control" required value="{{ old('actual_cost',0) }}"></div>
                <div class="col-12"><label class="form-label">Work performed / parts replaced *</label><textarea name="repair_work_done" class="form-control" rows="3" required>{{ old('repair_work_done',$ticket->repair_work_done) }}</textarea></div>
                <div class="col-md-4"><label class="form-label">Actual downtime (hours)</label><input type="number" min="0" step="0.01" name="actual_downtime_hours" class="form-control" value="{{ old('actual_downtime_hours',$ticket->downtime_hours) }}"></div>
                <div class="col-12"><button class="btn btn-primary">Complete Repair → Verification</button></div>
            </div>
        </form>
    </div></div>
    @endif

    @if($ticket->status === 'pending_verification')
    <div class="card mb-3"><div class="card-header fw-bold">Verification and closure</div><div class="card-body">
        <p><strong>Repair performed:</strong> {{ $ticket->repair_work_done }}</p><p><strong>Actual cost:</strong> ₹{{ number_format((float)$ticket->actual_cost,2) }}</p>
        <form method="POST" action="{{ route('maintenance-tickets.verify',$ticket) }}">@csrf
            <div class="row g-3">
                <div class="col-md-5"><label class="form-label">Verifier name *</label><input name="verifier_name" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Result *</label><select name="verification_result" class="form-select" required><option value="passed">Passed — close ticket</option><option value="failed">Failed — return to repair</option></select></div>
                <div class="col-md-12"><label class="form-label">Trial / verification remarks *</label><textarea name="verification_remarks" class="form-control" rows="2" required></textarea></div>
                <div class="col-12"><button class="btn btn-primary">Save Verification</button></div>
            </div>
        </form>
    </div></div>
    @endif

    <div class="card mb-3"><div class="card-header fw-bold">Workflow details</div><div class="card-body">
        <div class="row g-3">
            @if($ticket->inspection_findings)<div class="col-md-6"><strong>Inspection findings</strong><p>{{ $ticket->inspection_findings }}</p></div>@endif
            @if($ticket->suspected_cause)<div class="col-md-6"><strong>Suspected cause</strong><p>{{ $ticket->suspected_cause }}</p></div>@endif
            @if($ticket->root_cause)<div class="col-md-6"><strong>Confirmed root cause</strong><p>{{ $ticket->root_cause }}</p></div>@endif
            @if($ticket->corrective_action)<div class="col-md-6"><strong>Corrective action</strong><p>{{ $ticket->corrective_action }}</p></div>@endif
            @if($ticket->preventive_action)<div class="col-md-6"><strong>Preventive action</strong><p>{{ $ticket->preventive_action }}</p></div>@endif
            @if($ticket->approval_decision)<div class="col-md-6"><strong>Approval</strong><p>{{ ucfirst($ticket->approval_decision) }} — {{ $ticket->approval_remarks }}</p></div>@endif
            @if($ticket->verification_remarks)<div class="col-md-6"><strong>Verification</strong><p>{{ $ticket->verification_result }} — {{ $ticket->verification_remarks }}</p></div>@endif
        </div>
    </div></div>

    <div class="card"><div class="card-header fw-bold">Ticket history</div><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Date/time</th><th>Action</th><th>By</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
        @forelse($ticket->histories as $history)
            <tr><td>{{ $history->created_at?->format('d-m-Y H:i') }}</td><td>{{ $history->action }}</td><td>{{ $history->performed_by }}</td><td>{{ $history->from_status ? str_replace('_',' ',$history->from_status).' → ' : '' }}{{ str_replace('_',' ',$history->to_status) }}</td><td>{{ $history->remarks }}</td></tr>
        @empty <tr><td colspan="5" class="text-muted text-center">No history yet.</td></tr> @endforelse
        </tbody></table></div></div>
</div>
</body>
</html>
