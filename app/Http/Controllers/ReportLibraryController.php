<?php

namespace App\Http\Controllers;

use App\Models\ReportLibrary;
use Illuminate\Http\Request;

class ReportLibraryController extends Controller
{
    /**
     * Display Report Library
     */
    public function index(Request $request)
    {
        $query = ReportLibrary::query();

        // Search
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('report_name', 'like', "%{$search}%")
                    ->orWhere('report_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('sub_module', 'like', "%{$search}%");
            });
        }

        // Module filter
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query
            ->orderBy('module')
            ->orderBy('report_name')
            ->paginate(12)
            ->withQueryString();

        // Modules for filter
        $modules = ReportLibrary::query()
            ->whereNotNull('module')
            ->where('module', '!=', '')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        // Summary
        $totalReports = ReportLibrary::count();

        $activeReports = ReportLibrary::where('status', 'active')->count();

        $inactiveReports = ReportLibrary::where('status', 'inactive')->count();

        $moduleCount = ReportLibrary::query()
            ->whereNotNull('module')
            ->distinct('module')
            ->count('module');

        return view('reports.library.index', compact(
            'reports',
            'modules',
            'totalReports',
            'activeReports',
            'inactiveReports',
            'moduleCount'
        ));
    }


    /**
     * Show report documentation
     */
    public function show(ReportLibrary $report)
    {
        return view('reports.library.show', compact('report'));
    }


    /**
     * Create report
     */
    public function create()
    {
        return view('reports.library.create');
    }


    /**
     * Store report
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            // Basic
            'report_code' => 'required|string|max:50|unique:report_library,report_code',
            'report_name' => 'required|string|max:150',

            'module' => 'nullable|string|max:100',
            'sub_module' => 'nullable|string|max:100',
            'report_type' => 'nullable|string|max:50',

            'description' => 'nullable|string',
            'purpose' => 'nullable|string',
            'business_logic' => 'nullable|string',

            // Database
            'main_table' => 'nullable|string|max:150',
            'data_source' => 'nullable|string',
            'table_relationships' => 'nullable|string',

            // Logic
            'filters' => 'nullable|string',
            'default_filters' => 'nullable|string',
            'grouping_details' => 'nullable|string',
            'calculation_details' => 'nullable|string',
            'columns_displayed' => 'nullable|string',
            'sorting' => 'nullable|string',

            // Application
            'output_types' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'controller' => 'nullable|string|max:255',
            'view' => 'nullable|string|max:255',
            'procedure_function' => 'nullable|string',

            // Security
            'access_role' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'version' => 'nullable|string|max:20',

            // Notes
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        ReportLibrary::create($validated);

        return redirect()
            ->route('reports.library')
            ->with('success', 'Report added to Report Library successfully.');
    }


    /**
     * Edit report
     */
    public function edit(ReportLibrary $report)
    {
        return view('reports.library.edit', compact('report'));
    }


    /**
     * Update report
     */
    public function update(Request $request, ReportLibrary $report)
    {
        $validated = $request->validate([

            'report_code' => [
                'required',
                'string',
                'max:50',
                'unique:report_library,report_code,' . $report->id,
            ],

            'report_name' => 'required|string|max:150',

            'module' => 'nullable|string|max:100',
            'sub_module' => 'nullable|string|max:100',
            'report_type' => 'nullable|string|max:50',

            'description' => 'nullable|string',
            'purpose' => 'nullable|string',
            'business_logic' => 'nullable|string',

            'main_table' => 'nullable|string|max:150',
            'data_source' => 'nullable|string',
            'table_relationships' => 'nullable|string',

            'filters' => 'nullable|string',
            'default_filters' => 'nullable|string',
            'grouping_details' => 'nullable|string',
            'calculation_details' => 'nullable|string',
            'columns_displayed' => 'nullable|string',
            'sorting' => 'nullable|string',

            'output_types' => 'nullable|string|max:255',

            'route' => 'nullable|string|max:255',
            'controller' => 'nullable|string|max:255',
            'view' => 'nullable|string|max:255',

            'procedure_function' => 'nullable|string',

            'access_role' => 'nullable|string|max:255',

            'status' => 'required|in:active,inactive',

            'version' => 'nullable|string|max:20',

            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $report->update($validated);

        return redirect()
            ->route('reports.library.show', $report)
            ->with('success', 'Report documentation updated successfully.');
    }
}