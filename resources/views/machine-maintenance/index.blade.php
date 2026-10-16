@extends('layouts.app')

@section('content')

    <style>
        body { background:#f4f6fa; color:#202a3c; font-family:Inter,system-ui,sans-serif; }
        .topbar { background:linear-gradient(120deg,#172033,#2c4775); color:white; padding:26px 0 56px; }
        .page-title { font-size:25px; font-weight:750; }
        .muted-light { color:#cbd5e1; font-size:13px; }
        .content { margin-top:-35px; position:relative; }
        .panel { border:0; border-radius:14px; box-shadow:0 4px 18px #1720330b; }
        .panel-heading { font-weight:700; }
        .form-label { font-size:12px; text-transform:uppercase; font-weight:700; color:#5c6679; }
        .form-control,.form-select { min-height:39px; border-color:#dce2ec; border-radius:8px; }
        .btn { border-radius:8px; font-weight:600; }
        .machine-info { background:#f5f8ff; border:1px solid #e0e8f8; border-radius:10px; padding:14px; }
        .info-label { color:#718096; font-size:11px; text-transform:uppercase; font-weight:700; }
        .info-value { font-size:13px; font-weight:650; margin-top:3px; overflow-wrap:anywhere; }
        .stat { padding:15px 17px; }
        .stat-label { color:#748096; text-transform:uppercase; font-size:11px; font-weight:700; }
        .stat-number { font-size:24px; font-weight:750; margin-top:4px; }
        .table-wrap { overflow:auto; max-height:65vh; }
        .report-table { white-space:nowrap; font-size:12px; margin:0; }
        .report-table thead th { background:#263550; color:white; position:sticky; top:0; z-index:2; padding:12px 10px; vertical-align:middle; }
        .report-table td { padding:8px; vertical-align:middle; }
        .report-table input { min-width:90px; font-size:12px; padding:6px; }
        .report-table .date-input { min-width:130px; }
        .report-table .remarks-input { min-width:160px; }
        .vrno { color:#2e5aa7; font-weight:750; }
        .variance-positive { color:#198754; font-weight:750; }
        .variance-negative { color:#dc3545; font-weight:750; }
        .pill { border-radius:30px; padding:5px 9px; background:#e8efff; color:#3158a5; font-weight:700; font-size:11px; }
        .small-note { color:#738096; font-size:12px; }
        @media print {
            body { background:white; font-size:10px; }
            .topbar { background:white!important; color:black!important; padding:0 0 15px; }
            .muted-light { color:#555!important; }
            .no-print,.filters-panel,.entry-panel,.pagination { display:none!important; }
            .content { margin:0; }
            .panel { box-shadow:none; border:1px solid #ddd; }
            .table-wrap { max-height:none; overflow:visible; }
            .report-table { font-size:8px; }
            .report-table thead th { position:static; background:#eee!important; color:#111!important; }
            .report-table input { border:0; padding:0; min-width:0; }
            @page { size:landscape; margin:10mm; }
        }
    </style>

<div class="topbar">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center gap-3">
        <div>
            <div class="page-title"><i class="bi bi-gear-wide-connected me-2"></i>Machine Maintenance Register</div>
            <div class="muted-light mt-1">Machine capacity, working hours and daily production monitoring</div>
        </div>
        <div class="text-end d-none d-md-block">
            <span class="badge text-bg-light">ERP · Production</span>
            <div class="muted-light mt-2">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 content pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- NEW ENTRY -->
    <div class="card panel entry-panel mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="panel-heading mb-1"><i class="bi bi-plus-circle text-primary me-2"></i>New Machine Entry</h5>
                    <div class="small-note">Select a machine to load its master details automatically.</div>
                </div>
                <span class="pill">New Record</span>
            </div>

            <form method="POST" action="{{ route('machine-maintenance.store') }}" id="createForm">
                @csrf
                <div class="row g-3">
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Report date *</label>
                        <input type="date" name="report_date" class="form-control" value="{{ old('report_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-xl-4 col-md-8">
                        <label class="form-label">Machine name / code *</label>
                        <!-- Search input for filtering the machine dropdown -->
                        <input type="text" id="machineSearchFilter" class="form-control mb-2" placeholder="Type to filter machines...">
                        <select name="equipment_code" id="equipmentSelect" class="form-select" required>
                            <option value="">Select machine</option>
                            @foreach($equipment as $machine)
                                <option value="{{ $machine->equipment_code }}"
                                    data-name="{{ $machine->machine_name }}"
                                    data-section="{{ $machine->section_name }}"
                                    data-capacity="{{ $machine->machine_capacity }}"
                                    data-purchase="{{ $machine->purchase_date }}"
                                    data-last-service="{{ $machine->last_service_date }}"
                                    data-service-due="{{ $machine->service_due_date }}">
                                    {{ $machine->equipment_code }} — {{ $machine->machine_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Installed capacity / 8h *</label>
                        <input type="number" step="0.001" min="0" name="installed_capacity" id="installedCapacity" class="form-control" placeholder="e.g. 1000" required>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Working hours *</label>
                        <input type="number" step="0.25" min="0" max="24" name="working_hours" id="workingHours" class="form-control" value="8" required>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">Actual output *</label>
                        <input type="number" step="0.001" min="0" name="actual_output" id="actualOutput" class="form-control" placeholder="Enter output" required>
                    </div>
                </div>

                <div class="machine-info mt-3">
                    <div class="row g-3">
                        <div class="col-6 col-lg-2"><div class="info-label">Section</div><div class="info-value" id="infoSection">—</div></div>
                        <div class="col-6 col-lg-2"><div class="info-label">Machine capacity</div><div class="info-value" id="infoCapacity">—</div></div>
                        <div class="col-6 col-lg-2"><div class="info-label">Purchase date</div><div class="info-value" id="infoPurchase">—</div></div>
                        <div class="col-6 col-lg-2"><div class="info-label">Last service</div><div class="info-value" id="infoLastService">—</div></div>
                        <div class="col-6 col-lg-2"><div class="info-label">Service due</div><div class="info-value" id="infoServiceDue">—</div></div>
                        <div class="col-6 col-lg-2"><div class="info-label">Expected output</div><div class="info-value text-primary" id="expectedPreview">0</div></div>
                    </div>
                </div>

                <div class="row g-3 mt-1 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control" maxlength="5000" placeholder="Maintenance issue, downtime, service notes, etc.">
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary py-2"><i class="bi bi-save me-2"></i>Save Entry & Generate VRNO</button>
                    </div>
                </div>
                <div class="small-note mt-2">VRNO is assigned on save, for example ML26Y-1. Expected output = installed capacity × working hours ÷ 8.</div>
            </form>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card panel filters-panel mb-4">
        <div class="card-body p-4">
            <h6 class="panel-heading mb-3"><i class="bi bi-funnel me-2 text-primary"></i>Filter Saved Records</h6>
            <form method="GET" action="{{ route('machine-maintenance.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">From date</label>
                        <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <label class="form-label">To date</label>
                        <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <label class="form-label">Machine name / code</label>
                        <input type="text" name="machine" class="form-control" value="{{ $filters['machine'] ?? '' }}" placeholder="Search machine">
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">VRNO</label>
                        <input type="text" name="vrno" class="form-control" value="{{ $filters['vrno'] ?? '' }}" placeholder="e.g. ML26Y-1">
                    </div>
                    <div class="col-xl-2 col-md-6 d-flex gap-2">
                        <button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button>
                        <a href="{{ route('machine-maintenance.index') }}" class="btn btn-outline-secondary" title="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- SAVED REPORT -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card panel stat h-100">
                <div class="stat-label">Records on this page</div>
                <div class="stat-number">{{ $records->count() }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card panel stat h-100">
                <div class="stat-label">Current filter</div>
                <div class="fw-semibold mt-2">{{ ($filters['from'] ?? '') ?: 'Any date' }} — {{ ($filters['to'] ?? '') ?: 'Any date' }}</div>
            </div>
        </div>
        <div class="col-md-4 no-print">
            <div class="card panel stat h-100 d-flex justify-content-center">
                <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('machine-maintenance.export', request()->query()) }}"
                class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i>
                    Export Excel
                </a>
                <a href="{{ route('machine-maintenance.exportPdf', request()->query()) }}"
                class="btn btn-success">
                    <i class="bi bi-file-earmark-pdf me-1"></i>
                    Export PDF
                </a>

                
                    <button type="submit" form="bulkForm" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Save Changes</button>
                </div>
                <div class="small-note mt-2">Edit multiple rows, then save them together.</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('machine-maintenance.update') }}" id="bulkForm">
        @csrf
        <div class="card panel mb-4">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="panel-heading mb-1">Saved Machine Entries</h6>
                    <div class="small-note">Edit date, installed capacity, working hours, output and remarks directly.</div>
                </div>
                <span class="pill">{{ $records->total() }} total records</span>
            </div>

            <div class="table-wrap">
                <table class="table table-striped table-hover report-table">
                    <thead>
                        <tr>
                            <th>VRNO / Date</th>
                            <th>Section</th>
                            <th>Machine</th>
                            <th>Machine Capacity</th>
                            <th>Installed / 8h</th>
                            <th>Working Hours</th>
                            <th>Expected</th>
                            <th>Actual Output</th>
                            <th>Variance</th>
                            <th>Purchase Date</th>
                            <th>Last Service</th>
                            <th>Service Due</th>
                            <th>Remarks</th>
                            <th class="no-print">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>
                                <div class="vrno">{{ $record->vrno }}</div>
                                <input class="form-control date-input mt-1" type="date" name="rows[{{ $record->id }}][report_date]" value="{{ $record->report_date->format('Y-m-d') }}" required>
                                <input type="hidden" name="rows[{{ $record->id }}][id]" value="{{ $record->id }}">
                            </td>
                            <td>{{ $record->section_name ?: '—' }}</td>
                            <td>
                                <div class="fw-bold">{{ $record->machine_name }}</div>
                                <div class="small-note">{{ $record->equipment_code }}</div>
                            </td>
                            <td>{{ $record->machine_capacity ?: '—' }}</td>
                            <td><input class="form-control calc-input" type="number" step="0.001" min="0" name="rows[{{ $record->id }}][installed_capacity]" value="{{ $record->installed_capacity }}" required></td>
                            <td><input class="form-control calc-input" type="number" step="0.25" min="0" max="24" name="rows[{{ $record->id }}][working_hours]" value="{{ $record->working_hours }}" required></td>
                            <td class="expected-cell fw-semibold">{{ number_format($record->expected_output, 3) }}</td>
                            <td><input class="form-control calc-input" type="number" step="0.001" min="0" name="rows[{{ $record->id }}][actual_output]" value="{{ $record->actual_output }}" required></td>
                            <td class="variance-cell {{ $record->variance < 0 ? 'variance-negative' : 'variance-positive' }}">{{ number_format($record->variance, 3) }}</td>
                            <td>{{ $record->purchase_date ? $record->purchase_date->format('d-m-Y') : '—' }}</td>
                            <td>{{ $record->last_service_date ? $record->last_service_date->format('d-m-Y') : '—' }}</td>
                            <td>
                                @if($record->service_due_date)
                                    <span class="{{ $record->service_due_date->isPast() ? 'text-danger fw-bold' : '' }}">{{ $record->service_due_date->format('d-m-Y') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td><input class="form-control remarks-input" type="text" maxlength="5000" name="rows[{{ $record->id }}][remarks]" value="{{ $record->remarks }}"></td>
                            <td class="no-print">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRecord('{{ route('machine-maintenance.destroy', $record) }}', '{{ $record->vrno }}')" title="Delete record"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="14" class="text-center py-5 text-secondary">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No saved records found. Change your filters or create a new entry.
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small-note">Expected = installed capacity × working hours ÷ 8. Variance = output − expected.</div>
                <div class="no-print">{{ $records->links() }}</div>
            </div>
        </div>
    </form>

    <form id="deleteForm" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const machineSelect = document.getElementById('equipmentSelect');
        const machineSearchFilter = document.getElementById('machineSearchFilter');

        // Store original options for filtering
        const allOptions = Array.from(machineSelect.options).slice(1);

        if (machineSearchFilter) {
            machineSearchFilter.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                machineSelect.innerHTML = '<option value="">Select machine</option>';
                
                allOptions.forEach(opt => {
                    if (opt.textContent.toLowerCase().includes(term)) {
                        machineSelect.appendChild(opt.cloneNode(true));
                    }
                });
            });
        }

        function showMachineDetails() {
            const option = machineSelect.options[machineSelect.selectedIndex];
            const data = option && option.value ? option.dataset : {};

            document.getElementById('infoSection').textContent = data.section || '—';
            document.getElementById('infoCapacity').textContent = data.capacity || '—';
            document.getElementById('infoPurchase').textContent = data.purchase || '—';
            document.getElementById('infoLastService').textContent = data.lastService || '—';
            document.getElementById('infoServiceDue').textContent = data.serviceDue || '—';

            const capacityInput = document.getElementById('installedCapacity');
            if (data.capacity && capacityInput.value === '') {
                const parsed = parseFloat(data.capacity);
                if (!Number.isNaN(parsed)) capacityInput.value = parsed;
            }

            updateNewPreview();
        }

        function updateNewPreview() {
            const capacity = parseFloat(document.getElementById('installedCapacity').value) || 0;
            const hours = parseFloat(document.getElementById('workingHours').value) || 0;
            const expected = (capacity * hours) / 8;
            document.getElementById('expectedPreview').textContent = expected.toLocaleString(undefined, { maximumFractionDigits: 3 });
        }

        if (machineSelect) machineSelect.addEventListener('change', showMachineDetails);
        ['installedCapacity', 'workingHours'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updateNewPreview);
        });

        // Live calculation for every edited table row.
        document.querySelectorAll('.report-table tbody tr').forEach(row => {
            const inputs = row.querySelectorAll('.calc-input');
            if (inputs.length !== 3) return;

            function calculateRow() {
                const installed = parseFloat(inputs[0].value) || 0;
                const hours = parseFloat(inputs[1].value) || 0;
                const output = parseFloat(inputs[2].value) || 0;
                const expected = (installed * hours) / 8;
                const variance = output - expected;

                const expectedCell = row.querySelector('.expected-cell');
                const varianceCell = row.querySelector('.variance-cell');

                if (expectedCell) expectedCell.textContent = expected.toLocaleString(undefined, { maximumFractionDigits: 3 });
                if (varianceCell) {
                    varianceCell.textContent = variance.toLocaleString(undefined, { maximumFractionDigits: 3 });
                    varianceCell.classList.toggle('variance-negative', variance < 0);
                    varianceCell.classList.toggle('variance-positive', variance >= 0);
                }
            }

            inputs.forEach(input => input.addEventListener('input', calculateRow));
        });

        const createForm = document.getElementById('createForm');
        if (createForm) {
            createForm.addEventListener('submit', function () {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                }
            });
        }
    });

    function deleteRecord(url, vrno) {
        if (!confirm('Delete record ' + vrno + '? This cannot be undone.')) return;
        const form = document.getElementById('deleteForm');
        form.action = url;
        form.submit();
    }
</script>
@endsection