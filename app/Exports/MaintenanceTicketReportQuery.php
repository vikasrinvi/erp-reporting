<?php

namespace App\Exports;

use App\Models\MaintenanceTicket;
use Illuminate\Http\Request;

class MaintenanceTicketReportQuery
{
    public static function build(Request $request)
    {
        $query = MaintenanceTicket::query();

        if ($request->filled('from_date')) {
            $query->whereDate('raised_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('raised_at', '<=', $request->input('to_date'));
        }

        if ($request->filled('ticket_no')) {
            $query->where('ticket_no', 'like', '%' . $request->input('ticket_no') . '%');
        }

        if ($request->filled('machine')) {
            $machine = $request->input('machine');

            $query->where(function ($q) use ($machine) {
                $q->where('machine_name', 'like', '%' . $machine . '%')
                    ->orWhere('equipment_code', 'like', '%' . $machine . '%');
            });
        }

        if ($request->filled('department')) {
            $query->where('department_name', 'like', '%' . $request->input('department') . '%');
        }

        if ($request->filled('requester')) {
            $query->where('requester_name', 'like', '%' . $request->input('requester') . '%');
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('category')) {
            $query->where('problem_category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }
}