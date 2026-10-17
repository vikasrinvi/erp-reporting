@extends('layouts.app')

@section('content')
<div class="bg-light min-vh-100 py-4">
    <div class="container" style="max-width: 1000px;">
        
        <!-- Navigation & Header -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('maintenance-tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Tickets
            </a>
        </div>

        <div class="mb-4">
            <h2 class="fw-bold text-dark mb-1">Raise Machine Maintenance Ticket</h2>
            <p class="text-muted small mb-0">Fill out the details below to log a new equipment breakdown or service request.</p>
        </div>

        <!-- Errors Alert -->
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

        <!-- Form Card -->
        <form method="POST" action="{{ route('maintenance-tickets.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-body p-4">
                
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">Machine &amp; Requester Details</h5>
                
                <div class="row g-3">
                    <!-- Machine Selector -->
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Machine <span class="text-danger">*</span></label>
                        <select name="equipment_code" class="form-select" required>
                            <option value="">Select machine...</option>
                            @foreach($machines as $machine)
                                <option value="{{ $machine->eqpt_code }}" @selected(old('equipment_code') === $machine->eqpt_code)>
                                    {{ $machine->eqpt_code }} — {{ $machine->eqpt_name }}{{ $machine->dept_id ? ' ('.$machine->dept_id.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Loaded from equipment masters (active status = 1).</div>
                    </div>

                    <!-- Department Display (Disabled) -->
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <input class="form-control bg-light" name="department_display" value="" placeholder="Copied from machine record" disabled>
                        <div class="form-text">Auto-populated from selected equipment.</div>
                    </div>

                    <!-- Requester Info -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Requester Name <span class="text-danger">*</span></label>
                        <input class="form-control" name="requester_name" value="{{ old('requester_name') }}" required maxlength="255" placeholder="Enter full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contact / Extension</label>
                        <input class="form-control" name="requester_contact" value="{{ old('requester_contact') }}" maxlength="255" placeholder="e.g., Ext. 402 or mobile number">
                    </div>

                    <!-- Ticket Date, Category, Priority -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Ticket Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="raised_at" value="{{ old('raised_at', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Problem Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="problem_category" required>
                            @foreach(['breakdown'=>'Breakdown / not working','spare'=>'Spare part required','adjustment'=>'Adjustment / change required','inspection'=>'Inspection required','other'=>'Other'] as $value=>$label)
                                <option value="{{ $value }}" @selected(old('problem_category')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" name="priority" required>
                            @foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','critical'=>'Critical / production stopped'] as $value=>$label)
                                <option value="{{ $value }}" @selected(old('priority','normal')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Problem Description -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Problem Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="problem_description" rows="4" required placeholder="Describe the issue in detail...">{{ old('problem_description') }}</textarea>
                    </div>

                    <!-- Production Affected Checkbox -->
                    <div class="col-12">
                        <div class="form-check p-3 bg-light rounded border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="production_affected" value="1" id="production_affected" @checked(old('production_affected'))>
                            <label class="form-check-label fw-medium text-dark" for="production_affected">
                                Production is affected / machine stopped completely
                            </label>
                        </div>
                    </div>

                    <!-- Initial Remarks -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">Initial Remarks</label>
                        <textarea class="form-control" name="request_remarks" rows="2" placeholder="Any additional notes or observations...">{{ old('request_remarks') }}</textarea>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- Administrative / Entry Info -->
                <h5 class="fw-bold text-dark mb-3">Verification Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Entered By (Staff Name) <span class="text-danger">*</span></label>
                        <input class="form-control" name="performed_by" value="{{ old('performed_by', old('requester_name')) }}" required maxlength="255">
                        <div class="form-text text-warning small mt-1">Temporary field until application authentication is integrated.</div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex align-items-center gap-2 mt-4 pt-2 border-top">
                    <button type="submit" class="btn btn-primary px-4">Submit Ticket</button>
                    <a href="{{ route('maintenance-tickets.index') }}" class="btn btn-outline-secondary px-3">Cancel</a>
                </div>

            </div>
        </form>

    </div>
</div>
@endsection