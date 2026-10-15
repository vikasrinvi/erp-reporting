<?php

namespace App\Http\Controllers;

use App\Models\MachineMaintenanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MachineMaintenanceController extends Controller
{
    private function equipmentQuery()
    {
        return DB::table('equipment_masters')
            ->select([
                'eqpt_code as equipment_code',
                'eqpt_name as machine_name',
                'dept_id as section_name',
                'capacity as machine_capacity',
                DB::raw('NULL as purchase_date'),
                'from_service as last_service_date',
                'due_service as service_due_date',
            ]);
    }

    private function equipmentList()
    {
        return $this->equipmentQuery()
            ->orderBy('machine_name')
            ->get();
    }

    private function findEquipment(string $code)
    {
        return $this->equipmentQuery()
            ->where('eqpt_code', $code)
            ->first();
    }

    public function index(Request $request)
    {
        $query = MachineMaintenanceReport::query();

        if ($request->filled('from_date')) {
            $query->whereDate('report_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('report_date', '<=', $request->to_date);
        }

        if ($request->filled('machine')) {
            $machine = $request->machine;

            $query->where(function ($q) use ($machine) {
                $q->where('equipment_code', 'like', "%{$machine}%")
                  ->orWhere('machine_name', 'like', "%{$machine}%");
            });
        }

        if ($request->filled('vrno')) {
            $query->where('vrno', 'like', '%' . $request->vrno . '%');
        }

        $records = $query
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        
        return view('machine-maintenance.index', [
            'records' => $records,
            'equipment' => $this->equipmentList(),
            'filters' => $request->only([
                'from_date', 'to_date', 'machine', 'vrno',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_date' => ['required', 'date'],
            'equipment_code' => ['required', 'string', 'max:255'],
            'installed_capacity' => ['required', 'numeric', 'min:0'],
            'working_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'actual_output' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ]);

        $machine = $this->findEquipment($validated['equipment_code']);

        if (!$machine) {
            throw ValidationException::withMessages([
                'equipment_code' => 'Please select a valid machine.',
            ]);
        }

        $hours = (float) $validated['working_hours'];
        $capacity = (float) $validated['installed_capacity'];
        $actual = (float) $validated['actual_output'];

        // Installed capacity represents output for an 8-hour day.
        $expected = $capacity * $hours / 8;
        $variance = $actual - $expected;

        $year = date('y', strtotime($validated['report_date']));
        $prefix = "ML{$year}Y-";

        $report = DB::transaction(function () use (
            $validated,
            $machine,
            $hours,
            $capacity,
            $actual,
            $expected,
            $variance,
            $prefix
        ) {
            // Generate the next number for this year's prefix.
            $existingNumbers = MachineMaintenanceReport::query()
                ->where('vrno', 'like', $prefix . '%')
                ->pluck('vrno');

            $lastNumber = 0;

            foreach ($existingNumbers as $number) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $number, $matches)) {
                    $lastNumber = max($lastNumber, (int) $matches[1]);
                }
            }

            return MachineMaintenanceReport::create([
                'vrno' => $prefix . ($lastNumber + 1),
                'report_date' => $validated['report_date'],
                'equipment_code' => $machine->equipment_code,
                'machine_name' => $machine->machine_name,
                'section_name' => $machine->section_name,
                'machine_capacity' => $machine->machine_capacity,
                'purchase_date' => null,
                'last_service_date' => $machine->last_service_date,
                'service_due_date' => $machine->service_due_date,
                'installed_capacity' => $capacity,
                'working_hours' => $hours,
                'expected_output' => $expected,
                'actual_output' => $actual,
                'variance' => $variance,
                'remarks' => $validated['remarks'] ?? null,
            ]);
        });

        return redirect()
            ->route('machine-maintenance.index')
            ->with('success', 'Maintenance entry saved. VRNO: ' . $report->vrno);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.report_date' => ['required', 'date'],
            'rows.*.installed_capacity' => ['required', 'numeric', 'min:0'],
            'rows.*.working_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'rows.*.actual_output' => ['required', 'numeric', 'min:0'],
            'rows.*.remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['rows'] as $id => $row) {
                $record = MachineMaintenanceReport::findOrFail($id);

                $capacity = (float) $row['installed_capacity'];
                $hours = (float) $row['working_hours'];
                $actual = (float) $row['actual_output'];

                $expected = $capacity * $hours / 8;

                $record->update([
                    'report_date' => $row['report_date'],
                    'installed_capacity' => $capacity,
                    'working_hours' => $hours,
                    'expected_output' => $expected,
                    'actual_output' => $actual,
                    'variance' => $actual - $expected,
                    'remarks' => $row['remarks'] ?? null,
                ]);
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Changes saved successfully.');
    }

    public function destroy(MachineMaintenanceReport $machineMaintenance)
    {
        $machineMaintenance->delete();

        return redirect()
            ->back()
            ->with('success', 'Maintenance entry deleted.');
    }
}
