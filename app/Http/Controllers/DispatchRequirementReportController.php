<?php

namespace App\Http\Controllers;

use App\Exports\DispatchRequirementExport;
use App\Models\DispatchStockOverride;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DispatchRequirementReportController extends Controller
{
    private string $dispatchTable = 'itemtran_body_25-26';

    private string $stockYear = '26-27';

    private function getReportData(Request $request): array
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);

        $from = Carbon::parse($validated['from_date'])->startOfDay();
        $to = Carbon::parse($validated['to_date'])->startOfDay();
        $toExclusive = $to->copy()->addDay()->format('Y-m-d');

        // Build month keys dynamically from the selected date range.
        $months = [];
        $cursor = $from->copy()->startOfMonth();
        $lastMonth = $to->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $months[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
            ];

            $cursor->addMonth();
        }

        // Base item list: include eligible items even if they have no dispatch.
        $items = DB::table('item_mast')
            ->select('item_code', 'item_name')
            ->where('godown_code', 'JH060')
            ->where('defunctional_flag', 0)
            ->orderBy('item_code')
            ->get();

        $itemCodes = $items->pluck('item_code')->all();

        if (empty($itemCodes)) {
            return [
                'months' => $months,
                'rows' => collect(),
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ];
        }

        // Aggregate transactions once per item, month and division.
        // $dispatch = DB::table($this->dispatchTable . ' as l')
        //     ->select('l.item_code')
        //     ->selectRaw("DATE_FORMAT(l.vrdate, '%Y-%m') AS dispatch_month")
        //     ->select('l.div_code')
        //     ->selectRaw('SUM(COALESCE(l.qtyissued, 0)) AS dispatched_qty')
        //     ->where('l.vrdate', '>=', $from->format('Y-m-d'))
        //     ->where('l.vrdate', '<', $toExclusive)
        //     ->whereIn('l.div_code', ['JH', 'BPJH'])
        //     ->whereIn('l.item_code', $itemCodes)
        //     ->groupBy(
        //         'l.item_code',
        //         'l.vrdate',
        //         'l.div_code'
        //     )
        //     ->get();

            $dispatch = DB::table($this->dispatchTable . ' as l')
            ->select([
                'l.item_code',
                'l.div_code'
            ])
            ->selectRaw("DATE_FORMAT(l.vrdate, '%Y-%m') AS dispatch_month")
            ->selectRaw('SUM(COALESCE(l.qtyissued, 0)) AS dispatched_qty')
            ->where('l.vrdate', '>=', $from->format('Y-m-d'))
            ->where('l.vrdate', '<', $toExclusive)
            ->whereIn('l.div_code', ['JH', 'BPJH'])
            ->whereIn('l.item_code', $itemCodes)
            ->groupBy(
                'l.item_code',
                'l.vrdate',
                'l.div_code'
            )
            ->get();
        
        

        // Opening + receipts - issues, from the specified accounting year.
        $stocks = DB::table('itembal_mast as b')
            ->select('b.item_code')
            ->selectRaw("
                SUM(
                    CASE WHEN b.div_code = 'JH'
                    THEN COALESCE(b.yropqty, 0)
                       + COALESCE(b.rqtyrecd, 0)
                       - COALESCE(b.rqtyissued, 0)
                    ELSE 0 END
                ) AS stock_jh
            ")
            ->selectRaw("
                SUM(
                    CASE WHEN b.div_code = 'BPJH'
                    THEN COALESCE(b.yropqty, 0)
                       + COALESCE(b.rqtyrecd, 0)
                       - COALESCE(b.rqtyissued, 0)
                    ELSE 0 END
                ) AS stock_bp
            ")
            ->where('b.acc_year', $this->stockYear)
            ->whereIn('b.div_code', ['JH', 'BPJH'])
            ->whereIn('b.item_code', $itemCodes)
            ->groupBy('b.item_code')
            ->get()
            ->keyBy('item_code');

        $overrides = DispatchStockOverride::query()
            ->whereIn('item_code', $itemCodes)
            ->get()
            ->keyBy('item_code');

        // Pivot monthly dispatch results into columns.
        $dispatchMap = [];
        
        foreach ($dispatch as $record) {
            
            $dispatchMap[$record->item_code][$record->dispatch_month][$record->div_code]
                = (float) $record->dispatched_qty;
        }

        $rows = collect();

        foreach ($items as $item) {
            $itemCode = $item->item_code;
            $stock = $stocks->get($itemCode);
            $override = $overrides->get($itemCode);

            $stockJH = $override && $override->stock_jh !== null
                ? (float) $override->stock_jh
                : (float) ($stock->stock_jh ?? 0);

            $stockBP = $override && $override->stock_bp !== null
                ? (float) $override->stock_bp
                : (float) ($stock->stock_bp ?? 0);

            $row = [
                'item_code' => $itemCode,
                'item_name' => $item->item_name,
                'stock_jh' => $stockJH,
                'stock_bp' => $stockBP,
                'stock_jh_override' => $override?->stock_jh,
                'stock_bp_override' => $override?->stock_bp,
            ];

            $totalJH = 0;
            $totalBP = 0;

            foreach ($months as $month) {
                $monthKey = $month['key'];

                $qtyJH = (float) (
                    $dispatchMap[$itemCode][$monthKey]['JH'] ?? 0
                );

                $qtyBP = (float) (
                    $dispatchMap[$itemCode][$monthKey]['BPJH'] ?? 0
                );

                $row['dispatch_' . str_replace('-', '_', $monthKey) . '_jh'] = $qtyJH;
                $row['dispatch_' . str_replace('-', '_', $monthKey) . '_bp'] = $qtyBP;

                $totalJH += $qtyJH;
                $totalBP += $qtyBP;
            }

            $row['total_jh'] = $totalJH;
            $row['total_bp'] = $totalBP;
            $row['required_jh'] = max($totalJH - $stockJH, 0);
            $row['required_bp'] = max($totalBP - $stockBP, 0);
            $row['total_dis'] = $totalJH + $totalBP;
            $row['total_required'] = max(($totalJH - $stockJH) + ($totalBP - $stockBP), 0);
            // $row['total_required'] = $row['required_jh'] + $row['required_bp'];

            $rows->push($row);
        }

        return [
            'months' => $months,
            'rows' => $rows,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ];
    }

    public function index(Request $request)
    {
        if (!$request->filled('from_date') && !$request->filled('to_date')) {
            $request->merge([
                'from_date' => now()->startOfMonth()->format('Y-m-d'),
                'to_date' => now()->format('Y-m-d'),
            ]);
        }

        $report = $this->getReportData($request);

        return view('reports.dispatch-requirement-report', $report);
    }

    public function saveStock(Request $request)
    {
        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:100'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'stock_jh' => ['required', 'numeric', 'min:0', 'max:999999999999999.999'],
            'stock_bp' => ['required', 'numeric', 'min:0', 'max:999999999999999.999'],
        ]);

        $itemExists = DB::table('item_mast')
            ->where('item_code', $validated['item_code'])
            ->where('godown_code', 'JH060')
            ->where('defunctional_flag', 0)
            ->exists();

        if (!$itemExists) {
            return back()->withErrors([
                'item_code' => 'The selected item is not eligible for this report.',
            ]);
        }

        DispatchStockOverride::updateOrCreate(
            ['item_code' => $validated['item_code']],
            [
                'stock_jh' => $validated['stock_jh'],
                'stock_bp' => $validated['stock_bp'],
            ]
        );

        return redirect()->route('dispatch-requirement.index', [
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
        ])->with('success', 'Stock updated successfully.');
    }

    public function exportExcel(Request $request)
    {
        $report = $this->getReportData($request);

        $filename = 'dispatch_requirement_'
            . str_replace('-', '', $report['from'])
            . '_to_'
            . str_replace('-', '', $report['to'])
            . '.xlsx';

        return Excel::download(
            new DispatchRequirementExport(
                $report['rows'],
                $report['months']
            ),
            $filename
        );
    }
}
