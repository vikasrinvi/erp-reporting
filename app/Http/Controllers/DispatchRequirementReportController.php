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

        $fromDate = $validated['from_date'];
        $toDate = $validated['to_date'];

        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        // 1. Build month keys dynamically from the selected date range
        $months = [];
        $cursor = $from->copy()->startOfMonth();
        $lastMonth = $to->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $months[] = [
                'key' => $cursor->format('Y-m'),          // e.g., '2025-07'
                'col_suffix' => $cursor->format('Y_m'),   // e.g., '2025_07'
                'label' => $cursor->format('M Y'),        // e.g., 'Jul 2025'
            ];
            $cursor->addMonth();
        }

        // Fetch overrides for stock adjustments
        $overrides = DispatchStockOverride::query()->get()->keyBy('item_code');

        // 2. Dynamically build SQL SELECT snippets for each month and division
        $jhSumParts = [];
        $bpSumParts = [];
        $dynamicSelects = [];

        foreach ($months as $m) {
            $monthKey = $m['key'];
            $suffix = $m['col_suffix'];

            // JH month dispatch column
            $dynamicSelects[] = "SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='{$monthKey}' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_{$suffix}_jh";
            $jhSumParts[] = "SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='{$monthKey}' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END)";

            // BPJH month dispatch column
            $dynamicSelects[] = "SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='{$monthKey}' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_{$suffix}_bp";
            $bpSumParts[] = "SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='{$monthKey}' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END)";
        }

        $jhTotalSql = !empty($jhSumParts) ? implode(' + ', $jhSumParts) : '0';
        $bpTotalSql = !empty($bpSumParts) ? implode(' + ', $bpSumParts) : '0';
        $selectString = !empty($dynamicSelects) ? ', ' . implode(', ', $dynamicSelects) : '';

        // 3. Construct and execute the dynamic SQL query
        $sql = "
            SELECT 
                t.*,
                (t.TOTAL_jh + t.TOTAL_BP) AS TOTAL_DIS,
                GREATEST(
                    (t.TOTAL_jh - IFNULL(t.stock_JH,0)) + (t.TOTAL_BP - IFNULL(t.stock_BP,0)),
                    0
                ) AS TOTAL_REQUIRED
            FROM (
                SELECT 
                    l.item_code,
                    (SELECT item_name FROM item_mast WHERE item_code = l.item_code LIMIT 1) AS item_name
                    {$selectString},

                    ({$jhTotalSql}) AS TOTAL_jh,

                    (
                        SELECT IFNULL(SUM(yropqty),0) + IFNULL(SUM(rqtyrecd),0) - IFNULL(SUM(rqtyissued),0)
                        FROM itembal_mast b
                        WHERE b.acc_year = ?
                          AND b.item_code = l.item_code
                          AND b.div_code = 'JH'
                    ) AS stock_JH,

                    ({$bpTotalSql}) AS TOTAL_BP,

                    (
                        SELECT IFNULL(SUM(yropqty),0) + IFNULL(SUM(rqtyrecd),0) - IFNULL(SUM(rqtyissued),0)
                        FROM itembal_mast b
                        WHERE b.acc_year = ?
                          AND b.item_code = l.item_code
                          AND b.div_code = 'BPJH'
                    ) AS stock_BP

                FROM `itemtran_body_25-26` l
                WHERE l.vrdate BETWEEN ? AND ?
                  AND l.item_code IN (
                      SELECT item_code 
                      FROM item_mast 
                      WHERE godown_code = 'JH060' 
                        AND defunctional_flag = 0
                  )
                GROUP BY l.item_code
            ) t
            ORDER BY t.item_code
        ";

        $results = DB::select($sql, [$this->stockYear, $this->stockYear, $fromDate, $toDate]);

        // 4. Map results and handle overrides
        $rows = collect($results)->map(function ($row) use ($overrides, $months) {
            $itemCode = $row->item_code;
            $override = $overrides->get($itemCode);

            if ($override) {
                if ($override->stock_jh !== null) {
                    $row->stock_JH = (float) $override->stock_jh;
                }
                if ($override->stock_bp !== null) {
                    $row->stock_BP = (float) $override->stock_bp;
                }
                $row->TOTAL_REQUIRED = max(($row->TOTAL_jh - $row->stock_JH) + ($row->TOTAL_BP - $row->stock_BP), 0);
            }

            $row->REQUIRED_JH = max($row->TOTAL_jh - $row->stock_JH, 0);
            $row->REQUIRED_BP = max($row->TOTAL_BP - $row->stock_BP, 0);

            // Build dynamic row data attributes matching the dynamic months
            $mappedRow = [
                'item_code' => $row->item_code,
                'item_name' => $row->item_name,
                'stock_jh' => (float) $row->stock_JH,
                'stock_bp' => (float) $row->stock_BP,
                'stock_jh_override' => $override?->stock_jh,
                'stock_bp_override' => $override?->stock_bp,
                'total_jh' => (float) $row->TOTAL_jh,
                'total_bp' => (float) $row->TOTAL_BP,
                'required_jh' => (float) $row->REQUIRED_JH,
                'required_bp' => (float) $row->REQUIRED_BP,
                'total_dis' => (float) $row->TOTAL_DIS,
                'total_required' => (float) $row->TOTAL_REQUIRED,
            ];

            // Dynamically assign dispatch columns for each month in the range
            foreach ($months as $m) {
                $suffix = $m['col_suffix'];
                $jhKey = "dispatch_{$suffix}_jh";
                $bpKey = "dispatch_{$suffix}_bp";

                $mappedRow[$jhKey] = (float) ($row->$jhKey ?? 0);
                $mappedRow[$bpKey] = (float) ($row->$bpKey ?? 0);
            }

            return $mappedRow;
        });

        return [
            'months' => $months,
            'rows' => $rows,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ];
    }
    private function getReportData45252(Request $request): array
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);

        $fromDate = $validated['from_date'];
        $toDate = $validated['to_date'];

        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        // Build month keys dynamically for the Blade view headers
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

        // Fetch overrides for stock adjustments
        $overrides = DispatchStockOverride::query()->get()->keyBy('item_code');

        // Run the precise SQL query directly, parameterized with dynamic dates
        $results = DB::select("
            SELECT 
                t.*,
                (t.TOTAL_jh + t.TOTAL_BP) AS TOTAL_DIS,
                GREATEST(
                    (t.TOTAL_jh - IFNULL(t.stock_JH,0)) + (t.TOTAL_BP - IFNULL(t.stock_BP,0)),
                    0
                ) AS TOTAL_REQUIRED
            FROM (
                SELECT 
                    l.item_code,
                    (SELECT item_name FROM item_mast WHERE item_code = l.item_code LIMIT 1) AS item_name,

                    SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-07' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_jul_JH,
                    SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-08' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_aug_JH,
                    SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-09' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_sep_JH,

                    (
                        SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-07' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) +
                        SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-08' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) +
                        SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-09' AND l.div_code='JH' THEN IFNULL(l.qtyissued,0) ELSE 0 END)
                    ) AS TOTAL_jh,

                    (
                        SELECT IFNULL(SUM(yropqty),0) + IFNULL(SUM(rqtyrecd),0) - IFNULL(SUM(rqtyissued),0)
                        FROM itembal_mast b
                        WHERE b.acc_year = ?
                          AND b.item_code = l.item_code
                          AND b.div_code = 'JH'
                    ) AS stock_JH,

                    SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-07' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_jul_BP,
                    SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-08' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_aug_BP,
                    SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-09' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) AS dispatch_sep_BP,

                    (
                        SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-07' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) +
                        SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-08' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END) +
                        SUM(CASE WHEN DATE_FORMAT(l.vrdate,'%Y-%m')='2025-09' AND l.div_code='BPJH' THEN IFNULL(l.qtyissued,0) ELSE 0 END)
                    ) AS TOTAL_BP,

                    (
                        SELECT IFNULL(SUM(yropqty),0) + IFNULL(SUM(rqtyrecd),0) - IFNULL(SUM(rqtyissued),0)
                        FROM itembal_mast b
                        WHERE b.acc_year = ?
                          AND b.item_code = l.item_code
                          AND b.div_code = 'BPJH'
                    ) AS stock_BP

                FROM `itemtran_body_25-26` l
                WHERE l.vrdate BETWEEN ? AND ?
                  AND l.item_code IN (
                      SELECT item_code 
                      FROM item_mast 
                      WHERE godown_code = 'JH060' 
                        AND defunctional_flag = 0
                  )
                GROUP BY l.item_code
            ) t
            ORDER BY t.item_code
        ", [$this->stockYear, $this->stockYear, $fromDate, $toDate]);

        $rows = collect($results)->map(function ($row) use ($overrides) {
            $itemCode = $row->item_code;
            $override = $overrides->get($itemCode);

            // Apply stock overrides if they exist
            if ($override) {
                if ($override->stock_jh !== null) {
                    $row->stock_JH = (float) $override->stock_jh;
                    $row->REQUIRED_JH = max($row->TOTAL_jh - $row->stock_JH, 0);
                }
                if ($override->stock_bp !== null) {
                    $row->stock_BP = (float) $override->stock_bp;
                    $row->REQUIRED_BP = max($row->TOTAL_BP - $row->stock_BP, 0);
                }
                // Recalculate total required with override values
                $row->TOTAL_REQUIRED = max(($row->TOTAL_jh - $row->stock_JH) + ($row->TOTAL_BP - $row->stock_BP), 0);
            } else {
                $row->REQUIRED_JH = max($row->TOTAL_jh - $row->stock_JH, 0);
                $row->REQUIRED_BP = max($row->TOTAL_BP - $row->stock_BP, 0);
            }

            // Map standard array keys for compatibility with your views/exports
            return [
                'item_code' => $row->item_code,
                'item_name' => $row->item_name,
                'stock_jh' => (float) $row->stock_JH,
                'stock_bp' => (float) $row->stock_BP,
                'stock_jh_override' => $override?->stock_jh,
                'stock_bp_override' => $override?->stock_bp,
                'dispatch_2025_07_jh' => (float) $row->dispatch_jul_JH,
                'dispatch_2025_08_jh' => (float) $row->dispatch_aug_JH,
                'dispatch_2025_09_jh' => (float) $row->dispatch_sep_JH,
                'dispatch_2025_07_bp' => (float) $row->dispatch_jul_BP,
                'dispatch_2025_08_bp' => (float) $row->dispatch_aug_BP,
                'dispatch_2025_09_bp' => (float) $row->dispatch_sep_BP,
                'total_jh' => (float) $row->TOTAL_jh,
                'total_bp' => (float) $row->TOTAL_BP,
                'required_jh' => (float) $row->REQUIRED_JH,
                'required_bp' => (float) $row->REQUIRED_BP,
                'total_dis' => (float) $row->TOTAL_DIS,
                'total_required' => (float) $row->TOTAL_REQUIRED,
            ];
        });

        return [
            'months' => $months,
            'rows' => $rows,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ];
    }

    private function getReportData4534(Request $request): array
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

        // Base item list matching the SQL subquery filter
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

        // 1. Fetch Dispatches grouped by item, month, and division
        $dispatch = DB::table($this->dispatchTable . ' as l')
            ->select('l.item_code', 'l.div_code')
            ->selectRaw("DATE_FORMAT(l.vrdate, '%Y-%m') AS dispatch_month")
            ->selectRaw('SUM(COALESCE(l.qtyissued, 0)) AS dispatched_qty')
            ->where('l.vrdate', '>=', $from->format('Y-m-d'))
            ->where('l.vrdate', '<', $toExclusive)
            ->whereIn('l.div_code', ['JH', 'BPJH'])
            ->whereIn('l.item_code', $itemCodes)
            // ->groupBy('l.item_code', "DATE_FORMAT(l.vrdate, '%Y-%m')", 'l.div_code')
            ->groupBy('l.item_code','l.VRDATE', 'l.div_code')
            ->get();

        // 2. Fetch Base Stocks using subquery logic (matching itembal_mast)
        $stocks = DB::table('itembal_mast as b')
            ->select('b.item_code', 'b.div_code')
            ->selectRaw("SUM(COALESCE(b.yropqty, 0) + COALESCE(b.rqtyrecd, 0) - COALESCE(b.rqtyissued, 0)) AS stock_qty")
            ->where('b.acc_year', $this->stockYear)
            ->whereIn('b.div_code', ['JH', 'BPJH'])
            ->whereIn('b.item_code', $itemCodes)
            ->groupBy('b.item_code', 'b.div_code')
            ->get()
            ->groupBy('item_code');

        // 3. Fetch Overrides
        $overrides = DispatchStockOverride::query()
            ->whereIn('item_code', $itemCodes)
            ->get()
            ->keyBy('item_code');

        // Pivot monthly dispatch results into an easy-to-read map
        $dispatchMap = [];
        foreach ($dispatch as $record) {
            $dispatchMap[$record->item_code][$record->dispatch_month][$record->div_code] = (float) $record->dispatched_qty;
        }

        $rows = collect();

        foreach ($items as $item) {
            $itemCode = $item->item_code;
            $itemStocks = $stocks->get($itemCode, collect());
            $override = $overrides->get($itemCode);

            // Determine Stock JH (Base or Override)
            $baseStockJH = (float) ($itemStocks->firstWhere('div_code', 'JH')?->stock_qty ?? 0);
            $stockJH = $override && $override->stock_jh !== null
                ? (float) $override->stock_jh
                : $baseStockJH;

            // Determine Stock BP (Base or Override)
            $baseStockBP = (float) ($itemStocks->firstWhere('div_code', 'BPJH')?->stock_qty ?? 0);
            $stockBP = $override && $override->stock_bp !== null
                ? (float) $override->stock_bp
                : $baseStockBP;

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

                $qtyJH = (float) ($dispatchMap[$itemCode][$monthKey]['JH'] ?? 0);
                $qtyBP = (float) ($dispatchMap[$itemCode][$monthKey]['BPJH'] ?? 0);

                $row['dispatch_' . str_replace('-', '_', $monthKey) . '_jh'] = $qtyJH;
                $row['dispatch_' . str_replace('-', '_', $monthKey) . '_bp'] = $qtyBP;

                $totalJH += $qtyJH;
                $totalBP += $qtyBP;
            }

            $row['total_jh'] = $totalJH;
            $row['total_bp'] = $totalBP;
            
            // Division-wise required quantities
            $row['required_jh'] = max($totalJH - $stockJH, 0);
            $row['required_bp'] = max($totalBP - $stockBP, 0);
            
            // Totals matching SQL GREATEST logic
            $row['total_dis'] = $totalJH + $totalBP;
            $row['total_required'] = max(($totalJH - $stockJH) + ($totalBP - $stockBP), 0);

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