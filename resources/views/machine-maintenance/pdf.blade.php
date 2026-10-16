<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Machine Log Book</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #202a3c; font-size: 9px; line-height: 1.3; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        
        /* Header Title Block */
        .log-title { font-size: 16px; font-weight: bold; text-transform: uppercase; text-align: center; background-color: #172033; color: white; padding: 10px; margin-bottom: 15px; }
        
        /* Metadata Block */
        .meta-box { margin-bottom: 15px; font-size: 10px; }
        .meta-row { margin-bottom: 5px; }
        
        /* Data Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #2c4775; color: white; font-weight: bold; font-size: 8px; text-transform: uppercase; padding: 6px 4px; text-align: center; border: 1px solid #172033; }
        td { padding: 5px 4px; border: 1px solid #dce2ec; font-size: 8px; text-align: center; }
        
        /* Alignments */
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        
        .variance-positive { color: #198754; font-weight: bold; }
        .variance-negative { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>

    <!-- Main Title Banner -->
    <div class="log-title">Machine Log Book</div>

    <!-- Metadata Section -->
    <div class="meta-box">
        <div class="meta-row"><strong>Month - From - To:</strong> 
            {{ $from ? date('F Y', strtotime($from)) . ' (' . $from . ')' : 'All' }} to {{ $to ?: 'All' }}
        </div>
        <div class="meta-row"><strong>Supervisor-sign-off:</strong> ___________________________</div>
    </div>

    <!-- Report Table -->
    <table>
        <thead>
            <tr>
                <th>Sr.No</th>
                <th>VRNO</th>
                <th>Date</th>
                <th>Section</th>
                <th>Machine Code</th>
                <th>Machine Name</th>
                <th>Capacity</th>
                <!-- <th>Purchase</th>
                <th>Last Service</th>
                <th>Service Due</th> -->
                <th>Installed (8h)</th>
                <th>Hours</th>
                <th>Expected</th>
                <th>Actual</th>
                <th>Variance</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="fw-bold" style="color: #2e5aa7;">{{ $record->vrno }}</td>
                    <td>{{ $record->report_date?->format('Y-m-d') }}</td>
                    <td class="text-left">{{ $record->section_name ?: '—' }}</td>
                    <td>{{ $record->equipment_code }}</td>
                    <td class="text-left fw-bold">{{ $record->machine_name }}</td>
                    <td>{{ $record->machine_capacity ?: '—' }}</td>
                    <!-- <td>{{ $record->purchase_date?->format('d-m-Y') ?: '—' }}</td>
                    <td>{{ $record->last_service_date?->format('d-m-Y') ?: '—' }}</td>
                    <td>{{ $record->service_due_date?->format('d-m-Y') ?: '—' }}</td> -->
                    <td>{{ $record->installed_capacity }}</td>
                    <td>{{ $record->working_hours }}</td>
                    <td>{{ number_format($record->expected_output, 3) }}</td>
                    <td>{{ $record->actual_output }}</td>
                    <td class="{{ $record->variance < 0 ? 'variance-negative' : 'variance-positive' }}">
                        {{ number_format($record->variance, 3) }}
                    </td>
                    <td class="text-left">{{ $record->remarks ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center" style="padding: 20px;">No records found for this filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>