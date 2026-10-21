@extends('layouts.app')

@section('content')
<div class="bg-light min-vh-100 py-4">
    <div class="container">

        <!-- Flash Success Alert -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Machine Maintenance Tickets</h2>
                <p class="text-muted small mb-0">Manage and track sequential maintenance workflow stages.</p>
            </div>
            <a href="{{ route('maintenance-tickets.create') }}" class="btn btn-primary px-3 shadow-sm">
                + Raise Ticket
            </a>
        </div>

        <!-- Search & Filter Card -->
        
<!-- Search & Filter Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Maintenance Ticket Report</h5>
        <small class="text-muted">
            Filter tickets by date, machine, department and workflow status.
        </small>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('maintenance-tickets.index') }}">
            <div class="row g-3">

                <div class="col-md-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input
                        type="date"
                        id="from_date"
                        name="from_date"
                        class="form-control"
                        value="{{ request('from_date') }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="to_date" class="form-label">To Date</label>
                    <input
                        type="date"
                        id="to_date"
                        name="to_date"
                        class="form-control"
                        value="{{ request('to_date') }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="ticket_no" class="form-label">Ticket Number</label>
                    <input
                        type="text"
                        id="ticket_no"
                        name="ticket_no"
                        class="form-control"
                        placeholder="e.g. MT-..."
                        value="{{ request('ticket_no') }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="machine" class="form-label">Machine</label>
                    <input
                        type="text"
                        id="machine"
                        name="machine"
                        class="form-control"
                        placeholder="Machine name or code"
                        value="{{ request('machine') }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="department" class="form-label">Department</label>
                    <input
                        type="text"
                        id="department"
                        name="department"
                        class="form-control"
                        value="{{ request('department') }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="requester" class="form-label">Requested By</label>
                    <input
                        type="text"
                        id="requester"
                        name="requester"
                        class="form-control"
                        value="{{ request('requester') }}"
                    >
                </div>

                <div class="col-md-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select id="priority" name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('priority') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="category" class="form-label">Problem Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach (['breakdown' => 'Breakdown', 'spare' => 'Spare Part', 'adjustment' => 'Adjustment', 'inspection' => 'Inspection', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('category') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">Workflow Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach ([
                            'pending_inspection' => 'Pending Inspection',
                            'pending_expert' => 'Pending Expert Assessment',
                            'pending_approval' => 'Pending Approval',
                            'approved_for_repair' => 'Approved for Repair',
                            'in_repair' => 'In Repair',
                            'pending_verification' => 'Pending Verification',
                            'closed' => 'Closed',
                            'rejected' => 'Rejected',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Apply Filters
                </button>

                <a href="{{ route('maintenance-tickets.index') }}"
                   class="btn btn-outline-secondary">
                    Reset
                </a>

                <button
                    type="button"
                    class="btn btn-success"
                    onclick="window.location.href = '{{ route('maintenance-tickets.export') }}' + window.location.search"
                >
                    <i class="bi bi-file-earmark-excel"></i>
                    Export Excel Workbook
                </button>
            </div>
        </form>
    </div>
</div>


        <!-- Tickets Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Ticket</th>
                            <th>Date</th>
                            <th>Machine</th>
                            <th>Department</th>
                            <th>Requester</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <!-- Ticket No -->
                                <td class="ps-3">
                                    <span class="fw-bold text-dark d-block">{{ $ticket->ticket_no }}</span>
                                </td>

                                <!-- Date -->
                                <td class="text-muted small">
                                    {{ $ticket->raised_at?->format('d-m-Y') ?: '—' }}
                                </td>

                                <!-- Machine Details -->
                                <td>
                                    <div class="fw-medium text-dark">{{ $ticket->machine_name }}</div>
                                    <div class="small text-muted">{{ $ticket->equipment_code }}</div>
                                </td>

                                <!-- Department -->
                                <td class="text-muted">
                                    {{ $ticket->department_name ?: '—' }}
                                </td>

                                <!-- Requester -->
                                <td class="fw-medium">
                                    {{ $ticket->requester_name }}
                                </td>

                                <!-- Priority Badge -->
                                <td>
                                    @php
                                        $priorityClass = match(strtolower($ticket->priority)) {
                                            'critical', 'urgent' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            'high' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                            'normal' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                            default => 'bg-light text-secondary border'
                                        };
                                    @endphp
                                    <span class="badge {{ $priorityClass }} px-2 py-1">
                                        {{ strtoupper($ticket->priority) }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td>
                                    @php
                                        $statusClass = match($ticket->status) {
                                            'pending_inspection' => 'bg-warning text-dark',
                                            'pending_expert' => 'bg-info text-dark',
                                            'pending_approval' => 'bg-primary',
                                            'approved_for_repair', 'in_repair' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'pending_verification' => 'bg-secondary',
                                            'closed' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }} px-2 py-1">
                                        {{ ucwords(str_replace('_',' ',$ticket->status)) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="text-end pe-3">
                                    <a class="btn btn-sm btn-outline-primary px-3" href="{{ route('maintenance-tickets.show', $ticket) }}">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox display-6 d-block mb-2 text-muted opacity-50"></i>
                                    No maintenance tickets found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-end mt-3">
            {{ $tickets->links() }}
        </div>

    </div>
</div>
@endsection