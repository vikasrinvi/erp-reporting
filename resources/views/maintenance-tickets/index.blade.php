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
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('maintenance-tickets.index') }}">
            <div class="row g-3">

                <div class="col-lg-3 col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date"
                           name="from_date"
                           class="form-control"
                           value="{{ request('from_date') }}">
                </div>

                <div class="col-lg-3 col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date"
                           name="to_date"
                           class="form-control"
                           value="{{ request('to_date') }}">
                </div>

                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Ticket Number</label>
                    <input class="form-control"
                           name="ticket_no"
                           value="{{ request('ticket_no') }}"
                           placeholder="Search ticket number">
                </div>

                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Machine</label>
                    <input class="form-control"
                           name="machine"
                           value="{{ request('machine') }}"
                           placeholder="Machine code or name">
                </div>

                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Department</label>
                    <input class="form-control"
                           name="department"
                           value="{{ request('department') }}"
                           placeholder="Department name">
                </div>

                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Requester</label>
                    <input class="form-control"
                           name="requester"
                           value="{{ request('requester') }}"
                           placeholder="Requester name">
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        @foreach(['low', 'normal', 'high', 'critical'] as $priority)
                            <option value="{{ $priority }}"
                                @selected(request('priority') === $priority)>
                                {{ ucfirst($priority) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach(['breakdown', 'spare', 'adjustment', 'inspection', 'other'] as $category)
                            <option value="{{ $category }}"
                                @selected(request('category') === $category)>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4 col-md-8">
                    <label class="form-label">Workflow Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach([
                            'pending_inspection',
                            'pending_expert',
                            'pending_approval',
                            'approved_for_repair',
                            'in_repair',
                            'pending_verification',
                            'closed',
                            'rejected'
                        ] as $status)
                            <option value="{{ $status }}"
                                @selected(request('status') === $status)>
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 align-items-end">
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>

                    <a href="{{ route('maintenance-tickets.index') }}"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>

                    <button type="button"
                            class="btn btn-success"
                            onclick="window.location.href =
                                '{{ route('maintenance-tickets.export') }}' +
                                window.location.search">
                        <i class="bi bi-file-earmark-excel"></i>
                        Export All Matching Tickets
                    </button>
                </div>

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