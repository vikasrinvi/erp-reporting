@extends('layouts.app')

@section('content')
<div class="bg-light min-vh-100 py-4">
    <div class="container" style="max-width: 1100px;">
        
        <!-- Navigation & Alerts -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('maintenance-tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to All Tickets
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Main Ticket Overview Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="fw-bold text-dark mb-1">{{ $ticket->ticket_no }}</h2>
                        <div class="text-muted">
                            <i class="bi bi-gear"></i> {{ $ticket->machine_name }} 
                            <span class="badge bg-light text-dark border ms-1">{{ $ticket->equipment_code }}</span> 
                            <span class="mx-1">&middot;</span> 
                            <i class="bi bi-building"></i> {{ $ticket->department_name ?: 'No department recorded' }}
                        </div>
                    </div>
                    @php
                        $statusBadgeClass = match($ticket->status) {
                            'pending_inspection' => 'bg-warning text-dark',
                            'pending_expert' => 'bg-info text-dark',
                            'pending_approval' => 'bg-primary',
                            'approved_for_repair', 'in_repair' => 'bg-orange text-white',
                            'pending_verification' => 'bg-secondary',
                            'closed', 'completed' => 'bg-success',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="badge {{ $statusBadgeClass }} fs-6 px-3 py-2 shadow-sm">
                        {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <div class="row g-4">
                    <div class="col-md-3">
                        <span class="text-muted small d-block uppercase fw-semibold">Raised Date</span>
                        <span class="fw-medium">{{ $ticket->raised_at?->format('d-m-Y H:i') ?: '—' }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block uppercase fw-semibold">Requester</span>
                        <span class="fw-medium text-dark d-block">{{ $ticket->requester_name }}</span>
                        <small class="text-muted">{{ $ticket->requester_contact }}</small>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block uppercase fw-semibold">Category / Priority</span>
                        <span class="fw-medium">{{ ucwords($ticket->problem_category) }} / 
                            <span class="badge {{ $ticket->priority == 'high' || $ticket->priority == 'urgent' ? 'bg-danger' : 'bg-secondary' }}">
                                {{ strtoupper($ticket->priority) }}
                            </span>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block uppercase fw-semibold">Production Affected</span>
                        <span class="badge {{ $ticket->production_affected ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }}">
                            {{ $ticket->production_affected ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="col-12">
                        <span class="text-muted small d-block uppercase fw-semibold mb-1">Problem Description</span>
                        <div class="p-3 bg-light rounded border-0 text-dark">{{ $ticket->problem_description }}</div>
                    </div>
                    @if($ticket->request_remarks)
                        <div class="col-12">
                            <span class="text-muted small d-block uppercase fw-semibold mb-1">Initial Remarks</span>
                            <div class="p-3 bg-light rounded text-muted">{{ $ticket->request_remarks }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stage 1 — Inspection -->
        @if($ticket->status === 'pending_inspection')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Stage 1 — Inspection</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('maintenance-tickets.inspection', $ticket) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Inspector / operator name <span class="text-danger">*</span></label>
                            <input name="inspector_name" class="form-control" required value="{{ old('inspector_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Inspection date/time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="inspected_at" class="form-control" required value="{{ old('inspected_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Inspection findings <span class="text-danger">*</span></label>
                            <textarea name="inspection_findings" class="form-control" rows="3" required>{{ old('inspection_findings') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Suspected cause</label>
                            <textarea name="suspected_cause" class="form-control" rows="2">{{ old('suspected_cause') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Recommended action <span class="text-danger">*</span></label>
                            <textarea name="inspection_recommendation" class="form-control" rows="2" required>{{ old('inspection_recommendation') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Downtime (hours)</label>
                            <input type="number" step="0.01" min="0" name="downtime_hours" class="form-control" value="{{ old('downtime_hours') }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Remarks</label>
                            <input name="inspection_remarks" class="form-control" value="{{ old('inspection_remarks') }}">
                        </div>
                        <div class="col-12 mt-4">
                            <button class="btn btn-primary px-4">Complete Inspection &rarr; Expert Assessment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Stage 2 — Expert Assessment & Spares -->
        @if($ticket->status === 'pending_expert')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Stage 2 — Expert Assessment & Spare Parts</div>
            <div class="card-body p-4">
                <div class="alert alert-light border mb-4">
                    <strong>Inspection:</strong> {{ $ticket->inspection_findings }}<br>
                    <strong>Recommendation:</strong> {{ $ticket->inspection_recommendation }}
                </div>
                <form method="POST" action="{{ route('maintenance-tickets.expert', $ticket) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expert name <span class="text-danger">*</span></label>
                            <input name="expert_name" class="form-control" required value="{{ old('expert_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assessment date/time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="assessed_at" class="form-control" required value="{{ old('assessed_at', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Confirmed root cause <span class="text-danger">*</span></label>
                            <textarea name="root_cause" class="form-control" required rows="2">{{ old('root_cause') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Corrective action <span class="text-danger">*</span></label>
                            <textarea name="corrective_action" class="form-control" required rows="2">{{ old('corrective_action') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Preventive action to avoid repeat failure</label>
                            <textarea name="preventive_action" class="form-control" rows="2">{{ old('preventive_action') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total estimated cost (₹) <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="0.01" name="estimated_cost" class="form-control" required value="{{ old('estimated_cost', 0) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Expert remarks</label>
                            <input name="expert_remarks" class="form-control" value="{{ old('expert_remarks') }}">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Spare parts required (optional)</h6>
                    <div id="spares" class="vstack gap-2">
                        <div class="row g-2 spare-row align-items-center">
                            <div class="col-md-4"><input class="form-control" name="spares[0][spare_name]" placeholder="Spare part name"></div>
                            <div class="col-md-2"><input class="form-control" name="spares[0][part_number]" placeholder="Part number"></div>
                            <div class="col-md-2"><input class="form-control" type="number" min="0.01" step="0.01" name="spares[0][quantity_required]" placeholder="Qty required"></div>
                            <div class="col-md-2"><input class="form-control" type="number" min="0" step="0.01" name="spares[0][estimated_unit_cost]" placeholder="Unit cost"></div>
                            <div class="col-md-2"><input class="form-control" name="spares[0][remarks]" placeholder="Remarks"></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-3 mb-4" onclick="addSpare()">+ Add spare row</button>
                    <div>
                        <button class="btn btn-primary px-4">Submit Assessment &rarr; Budget/Admin</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        let spareIndex = 1;
        function addSpare() {
            const html = `<div class="row g-2 spare-row align-items-center">
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

        <!-- Stage 3 — Budget / Administration Decision -->
        @if($ticket->status === 'pending_approval')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Stage 3 — Budget / Administration Decision</div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <p class="mb-1"><strong>Root cause:</strong> {{ $ticket->root_cause }}</p>
                    <p class="mb-1"><strong>Corrective action:</strong> {{ $ticket->corrective_action }}</p>
                    <p class="mb-3"><strong>Estimated cost:</strong> ₹{{ number_format((float)$ticket->estimated_cost, 2) }}</p>
                </div>

                @if($ticket->spares->count())
                    <h6 class="fw-bold mb-2">Requested Spare Parts</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Part</th>
                                    <th>Part no.</th>
                                    <th>Qty</th>
                                    <th>Est. Unit Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ticket->spares as $spare)
                                <tr>
                                    <td>{{ $spare->spare_name }}</td>
                                    <td>{{ $spare->part_number ?: '—' }}</td>
                                    <td>{{ $spare->quantity_required }}</td>
                                    <td>₹{{ number_format((float)$spare->estimated_unit_cost, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form method="POST" action="{{ route('maintenance-tickets.approval', $ticket) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Approver name <span class="text-danger">*</span></label>
                            <input name="approver_name" class="form-control" required value="{{ old('approver_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Decision <span class="text-danger">*</span></label>
                            <select name="approval_decision" class="form-select" required>
                                <option value="approved">Approve</option>
                                <option value="clarification">Return for clarification</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Approved budget (₹)</label>
                            <input type="number" min="0" step="0.01" name="approved_budget" class="form-control" value="{{ old('approved_budget', $ticket->estimated_cost) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Decision remarks (required) <span class="text-danger">*</span></label>
                            <textarea name="approval_remarks" class="form-control" rows="2" required>{{ old('approval_remarks') }}</textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button class="btn btn-primary px-4">Record Decision</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Stage 4 — Start Repair -->
        @if($ticket->status === 'approved_for_repair')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Stage 4 — Start Repair</div>
            <div class="card-body p-4">
                <p class="text-muted mb-3">Approved budget: <strong class="text-dark">₹{{ number_format((float)$ticket->approved_budget, 2) }}</strong></p>
                <form method="POST" action="{{ route('maintenance-tickets.start-repair', $ticket) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Technician / assigned person <span class="text-danger">*</span></label>
                            <input name="technician_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start date/time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="repair_started_at" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Initial repair notes</label>
                            <textarea name="repair_work_done" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button class="btn btn-primary px-4">Start Repair</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- In Repair / Completion -->
        @if($ticket->status === 'in_repair')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Repair Completion</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('maintenance-tickets.complete-repair', $ticket) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Technician name <span class="text-danger">*</span></label>
                            <input name="technician_name" class="form-control" required value="{{ old('technician_name', $ticket->technician_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Completion date/time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="repair_completed_at" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Actual cost (₹) <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="0.01" name="actual_cost" class="form-control" required value="{{ old('actual_cost', 0) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Work performed / parts replaced <span class="text-danger">*</span></label>
                            <textarea name="repair_work_done" class="form-control" rows="3" required>{{ old('repair_work_done', $ticket->repair_work_done) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Actual downtime (hours)</label>
                            <input type="number" min="0" step="0.01" name="actual_downtime_hours" class="form-control" value="{{ old('actual_downtime_hours', $ticket->downtime_hours) }}">
                        </div>
                        <div class="col-12 mt-4">
                            <button class="btn btn-primary px-4">Complete Repair &rarr; Verification</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Verification and closure -->
        @if($ticket->status === 'pending_verification')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Verification and Closure</div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <p class="mb-1"><strong>Repair performed:</strong> {{ $ticket->repair_work_done }}</p>
                    <p class="mb-3"><strong>Actual cost:</strong> ₹{{ number_format((float)$ticket->actual_cost, 2) }}</p>
                </div>
                <form method="POST" action="{{ route('maintenance-tickets.verify', $ticket) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Verifier name <span class="text-danger">*</span></label>
                            <input name="verifier_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Result <span class="text-danger">*</span></label>
                            <select name="verification_result" class="form-select" required>
                                <option value="passed">Passed — close ticket</option>
                                <option value="failed">Failed — return to repair</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Trial / verification remarks <span class="text-danger">*</span></label>
                            <textarea name="verification_remarks" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button class="btn btn-primary px-4">Save Verification</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Workflow Details Summary Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Workflow Details Summary</div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @if($ticket->inspection_findings)
                        <div class="col-md-6"><span class="text-muted small d-block">Inspection findings</span><p class="mb-0">{{ $ticket->inspection_findings }}</p></div>
                    @endif
                    @if($ticket->suspected_cause)
                        <div class="col-md-6"><span class="text-muted small d-block">Suspected cause</span><p class="mb-0">{{ $ticket->suspected_cause }}</p></div>
                    @endif
                    @if($ticket->root_cause)
                        <div class="col-md-6"><span class="text-muted small d-block">Confirmed root cause</span><p class="mb-0">{{ $ticket->root_cause }}</p></div>
                    @endif
                    @if($ticket->corrective_action)
                        <div class="col-md-6"><span class="text-muted small d-block">Corrective action</span><p class="mb-0">{{ $ticket->corrective_action }}</p></div>
                    @endif
                    @if($ticket->preventive_action)
                        <div class="col-md-6"><span class="text-muted small d-block">Preventive action</span><p class="mb-0">{{ $ticket->preventive_action }}</p></div>
                    @endif
                    @if($ticket->approval_decision)
                        <div class="col-md-6"><span class="text-muted small d-block">Approval</span><p class="mb-0"><strong>{{ ucfirst($ticket->approval_decision) }}</strong> — {{$ticket->approval_remarks }}</p></div>
                    @endif
                    @if($ticket->verification_remarks)
                        <div class="col-md-6"><span class="text-muted small d-block">Verification</span><p class="mb-0"><strong>{{ ucfirst($ticket->verification_result) }}</strong> — {{$ticket->verification_remarks }}</p></div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Ticket History Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-bold border-bottom">Ticket History</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Date/Time</th>
                            <th>Action</th>
                            <th>By</th>
                            <th>Status Transition</th>
                            <th class="pe-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ticket->histories as $history)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $history->created_at?->format('d-m-Y H:i') }}</td>
                                <td class="fw-medium">{{ $history->action }}</td>
                                <td>{{ $history->performed_by }}</td>
                                <td>
                                    <span class="small text-muted">
                                        {{ $history->from_status ? str_replace('_', ' ', $history->from_status).' & ' : '' }}
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        {{ str_replace('_', ' ', $history->to_status) }}
                                    </span>
                                </td>
                                <td class="pe-3 text-muted">{{ $history->remarks ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted text-center py-4">No history records found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection