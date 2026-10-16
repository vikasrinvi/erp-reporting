<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Machine Maintenance Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h2 class="mb-0">Machine Maintenance Tickets</h2><div class="text-muted">Sequential maintenance workflow</div></div>
        <a href="{{ route('maintenance-tickets.create') }}" class="btn btn-primary">+ Raise Ticket</a>
    </div>

    <form class="card card-body mb-3" method="GET">
        <div class="row g-2">
            <div class="col-md-3"><input class="form-control" name="ticket_no" value="{{ request('ticket_no') }}" placeholder="Ticket number"></div>
            <div class="col-md-3"><input class="form-control" name="machine" value="{{ request('machine') }}" placeholder="Machine code/name"></div>
            <div class="col-md-3"><input class="form-control" name="department" value="{{ request('department') }}" placeholder="Department"></div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach(['pending_inspection','pending_expert','pending_approval','approved_for_repair','in_repair','pending_verification','closed','rejected'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-grid"><button class="btn btn-dark">Go</button></div>
        </div>
    </form>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark"><tr><th>Ticket</th><th>Date</th><th>Machine</th><th>Department</th><th>Requester</th><th>Priority</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($tickets as $ticket)
                <tr>
                    <td><strong>{{ $ticket->ticket_no }}</strong></td>
                    <td>{{ $ticket->raised_at?->format('d-m-Y') }}</td>
                    <td>{{ $ticket->machine_name }}<div class="small text-muted">{{ $ticket->equipment_code }}</div></td>
                    <td>{{ $ticket->department_name ?: '—' }}</td>
                    <td>{{ $ticket->requester_name }}</td>
                    <td>{{ strtoupper($ticket->priority) }}</td>
                    <td><span class="badge text-bg-secondary">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('maintenance-tickets.show',$ticket) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-4">No tickets found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    <div class="mt-3">{{ $tickets->links() }}</div>
</div>
</body>
</html>
