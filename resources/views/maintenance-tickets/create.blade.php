<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raise Maintenance Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 1000px">
    <a href="{{ route('maintenance-tickets.index') }}" class="btn btn-link px-0">← Back to tickets</a>
    <h2>Raise Machine Maintenance Ticket</h2>
    @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    <form method="POST" action="{{ route('maintenance-tickets.store') }}" class="card card-body">
        @csrf
        <h5>Machine and requester</h5>
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label">Machine *</label>
                <select name="equipment_code" class="form-select" required>
                    <option value="">Select machine</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->eqpt_code }}" @selected(old('equipment_code') === $machine->eqpt_code)>
                            {{ $machine->eqpt_code }} — {{ $machine->eqpt_name }}{{ $machine->dept_id ? ' ('.$machine->dept_id.')' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Loaded from equipment_masters (active status = 1).</div>
            </div>
            <div class="col-md-5"><label class="form-label">Department *</label>
                <input class="form-control" name="department_display" value="" placeholder="Department is copied from machine record" disabled>
                <div class="form-text">Ticket department is copied from equipment_masters.dept_id.</div>
            </div>
            <div class="col-md-6"><label class="form-label">Requester name *</label><input class="form-control" name="requester_name" value="{{ old('requester_name') }}" required maxlength="255"></div>
            <div class="col-md-6"><label class="form-label">Contact / extension</label><input class="form-control" name="requester_contact" value="{{ old('requester_contact') }}" maxlength="255"></div>
            <div class="col-md-4"><label class="form-label">Ticket date *</label><input type="date" class="form-control" name="raised_at" value="{{ old('raised_at', now()->toDateString()) }}" required></div>
            <div class="col-md-4"><label class="form-label">Problem category *</label>
                <select class="form-select" name="problem_category" required>
                    @foreach(['breakdown'=>'Breakdown / not working','spare'=>'Spare part required','adjustment'=>'Adjustment / change required','inspection'=>'Inspection required','other'=>'Other'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('problem_category')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Priority *</label>
                <select class="form-select" name="priority" required>
                    @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','critical'=>'Critical / production stopped'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('priority','normal')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12"><label class="form-label">Problem description *</label><textarea class="form-control" name="problem_description" rows="4" required>{{ old('problem_description') }}</textarea></div>
            <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="production_affected" value="1" id="production_affected" @checked(old('production_affected'))><label class="form-check-label" for="production_affected">Production is affected / machine stopped</label></div>
            <div class="col-12"><label class="form-label">Initial remarks</label><textarea class="form-control" name="request_remarks" rows="2">{{ old('request_remarks') }}</textarea></div>
            <hr>
            <div class="col-md-6"><label class="form-label">Entered by (staff name) *</label><input class="form-control" name="performed_by" value="{{ old('performed_by', old('requester_name')) }}" required maxlength="255"><div class="form-text text-warning">Temporary field until application login is implemented.</div></div>
            <div class="col-12"><button class="btn btn-primary">Submit Ticket</button><a href="{{ route('maintenance-tickets.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
        </div>
    </form>
</div>
</body>
</html>
