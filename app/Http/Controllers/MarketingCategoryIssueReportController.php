<?php

namespace App\Http\Controllers;

use App\Models\MarketingIssueRemark;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketingCategoryIssueReportController extends Controller
{
    private array $divisions = [
        'AG', 'AH', 'AM', 'DL',
        'GZ', 'HL', 'ZP', 'JH',
    ];

    public function index(Request $request)
    {
        $defaultFrom = now()->startOfMonth()->format('Y-m-d');
        $defaultTo = now()->format('Y-m-d');

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:150'],
            'export' => ['nullable', 'in:csv'],
        ]);

        $from = $validated['from'] ?? $defaultFrom;
        $to = $validated['to'] ?? $defaultTo;
        $search = trim($validated['search'] ?? '');

        $fromDate = Carbon::createFromFormat('!Y-m-d', $from);
        $toDate = Carbon::createFromFormat('!Y-m-d', $to);

        if ($fromDate->greaterThan($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];

            $from = $fromDate->format('Y-m-d');
            $to = $toDate->format('Y-m-d');
        }

        // Include the entire end date, even if VRDATE contains a time.
        $toExclusive = $toDate->copy()->addDay()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | ERP report query
        |--------------------------------------------------------------------------
        | All marketing categories and all eight divisions.
        | No monthly columns and no division-wise columns.
        */

        // $query = DB::table('itemtran_body as ib')
        //     ->join('item_mast as im', 'ib.ITEM_CODE', '=', 'im.ITEM_CODE')
        //     ->join('prod_mast as pm', 'im.PROD_CODE', '=', 'pm.PROD_CODE')
        //     ->where('ib.VRDATE', '>=', $from)
        //     ->where('ib.VRDATE', '<', $toExclusive)
        //     ->whereNotNull('ib.QTYISSUED')
        //     ->whereIn('ib.DIV_CODE', $this->divisions);

        // if ($search !== '') {
        //     $query->where(function ($q) use ($search) {
        //         $q->where('ib.ITEM_CODE', 'LIKE', "%{$search}%")
        //             ->orWhere('im.ITEM_NAME', 'LIKE', "%{$search}%")
        //             ->orWhere('im.PROD_CODE', 'LIKE', "%{$search}%")
        //             ->orWhere('pm.PROD_NAME', 'LIKE', "%{$search}%")
        //             ->orWhere('pm.MKTG_CATG', 'LIKE', "%{$search}%");
        //     });
        // }

        // $query->select([
        //     'im.PROD_CODE',
        //     'pm.PROD_NAME',
        //     'ib.ITEM_CODE',
        //     'im.ITEM_NAME',
        // ]);

        // $query->selectRaw("COALESCE(pm.MKTG_CATG, '') AS MKTG_CATG");

        // $query->selectRaw("
        //     SUM(
        //         CASE
        //             WHEN ib.UM = 'PCS'
        //             THEN COALESCE(ib.QTYISSUED, 0)
        //             ELSE 0
        //         END
        //     ) AS qty_issued_pcs
        // ");

        // $query->selectRaw("
        //     SUM(
        //         CASE
        //             WHEN ib.UM = 'BOX'
        //             THEN COALESCE(ib.QTYISSUED, 0)
        //             ELSE 0
        //         END
        //     ) AS qty_issued_box
        // ");

        // $query->selectRaw(
        //     'SUM(COALESCE(ib.VALISSUED, 0)) AS val_issued'
        // );

        // $query->groupBy(
        //     'pm.MKTG_CATG',
        //     'im.PROD_CODE',
        //     'pm.PROD_NAME',
        //     'ib.ITEM_CODE',
        //     'im.ITEM_NAME'
        // );

        // $query
        //     ->orderBy('pm.MKTG_CATG')
        //     ->orderBy('im.PROD_CODE')
        //     ->orderBy('ib.ITEM_CODE');

        // $rows = $query->get();


        
/*
|--------------------------------------------------------------------------
| Step 1: Aggregate transactions before joining master tables
|--------------------------------------------------------------------------
|
| Instead of joining every transaction row to both master tables before
| grouping, first aggregate by ITEM_CODE. Then join the smaller result.
|
*/

$aggregate = DB::table('itemtran_body as ib')
->select('ib.ITEM_CODE')

->selectRaw("
    SUM(
        CASE
            WHEN ib.UM = 'PCS'
            THEN COALESCE(ib.QTYISSUED, 0)
            ELSE 0
        END
    ) AS qty_issued_pcs
")

->selectRaw("
    SUM(
        CASE
            WHEN ib.UM = 'BOX'
            THEN COALESCE(ib.QTYISSUED, 0)
            ELSE 0
        END
    ) AS qty_issued_box
")

->selectRaw("
    SUM(COALESCE(ib.VALISSUED, 0)) AS val_issued
")

->where('ib.VRDATE', '>=', $from)
->where('ib.VRDATE', '<', $toExclusive)
->whereNotNull('ib.QTYISSUED')
->whereIn('ib.DIV_CODE', $this->divisions)

->groupBy('ib.ITEM_CODE');


/*
|--------------------------------------------------------------------------
| Step 2: Join aggregated quantities to master tables
|--------------------------------------------------------------------------
*/

$query = DB::query()
->fromSub($aggregate, 'agg')

->join(
    'item_mast as im',
    'agg.ITEM_CODE',
    '=',
    'im.ITEM_CODE'
)

->join(
    'prod_mast as pm',
    'im.PROD_CODE',
    '=',
    'pm.PROD_CODE'
)

->select([
    'im.PROD_CODE',
    'pm.PROD_NAME',
    'agg.ITEM_CODE',
    'im.ITEM_NAME',
    'agg.qty_issued_pcs',
    'agg.qty_issued_box',
    'agg.val_issued',
])

->selectRaw("COALESCE(pm.MKTG_CATG, '') AS MKTG_CATG");


/*
|--------------------------------------------------------------------------
| Step 3: Apply search to the final aggregated results
|--------------------------------------------------------------------------
*/

if ($search !== '') {
$query->where(function ($q) use ($search) {
    $q->where('agg.ITEM_CODE', 'LIKE', "%{$search}%")
        ->orWhere('im.ITEM_NAME', 'LIKE', "%{$search}%")
        ->orWhere('im.PROD_CODE', 'LIKE', "%{$search}%")
        ->orWhere('pm.PROD_NAME', 'LIKE', "%{$search}%")
        ->orWhere('pm.MKTG_CATG', 'LIKE', "%{$search}%");
});
}


/*
|--------------------------------------------------------------------------
| Step 4: Sort and execute
|--------------------------------------------------------------------------
*/

$query
->orderBy('pm.MKTG_CATG')
->orderBy('im.PROD_CODE')
->orderBy('agg.ITEM_CODE');

$rows = $query->get();


        /*
        |--------------------------------------------------------------------------
        | Load saved remarks
        |--------------------------------------------------------------------------
        */


        $remarks = MarketingIssueRemark::query()
    ->whereIn(
        'item_code',
        $rows->pluck('ITEM_CODE')->unique()->values()->all()
    )
    ->get()
    ->keyBy(function ($remark) {
        return $this->remarkKey(
            $remark->marketing_category,
            $remark->prod_code,
            $remark->item_code
        );
    });

foreach ($rows as $row) {
    $key = $this->remarkKey(
        $row->MKTG_CATG,
        $row->PROD_CODE,
        $row->ITEM_CODE
    );

    $row->saved_remark = $remarks->get($key)?->remarks;
}

        // $remarks = MarketingIssueRemark::query()
        //     ->get()
        //     ->keyBy(function ($remark) {
        //         return $this->remarkKey(
        //             $remark->marketing_category,
        //             $remark->prod_code,
        //             $remark->item_code
        //         );
        //     });

        // foreach ($rows as $row) {
        //     $key = $this->remarkKey(
        //         $row->MKTG_CATG,
        //         $row->PROD_CODE,
        //         $row->ITEM_CODE
        //     );

        //     $row->saved_remark = $remarks->get($key)?->remarks;
        // }

        /*
        |--------------------------------------------------------------------------
        | CSV export
        |--------------------------------------------------------------------------
        */

        if (($validated['export'] ?? null) === 'csv') {
            $filename = 'Marketing_Category_Issue_Report_'
                . $from . '_to_' . $to . '.csv';

            return response()->streamDownload(
                function () use ($rows) {
                    $handle = fopen('php://output', 'w');

                    // Makes UTF-8 content display correctly in Excel.
                    fwrite($handle, "\xEF\xBB\xBF");

                    fputcsv($handle, [
                        'S.No',
                        'Marketing Category',
                        'PROD_CODE',
                        'PROD_NAME',
                        'ITEM_CODE',
                        'ITEM_NAME',
                        'Qtyissued in Pcs',
                        'Qtyissued in Box',
                        'valIssued',
                        'Remarks',
                    ]);

                    $serial = 1;

                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $serial++,
                            $row->MKTG_CATG,
                            $row->PROD_CODE,
                            $row->PROD_NAME,
                            $row->ITEM_CODE,
                            $row->ITEM_NAME,
                            $row->qty_issued_pcs,
                            $row->qty_issued_box,
                            $row->val_issued,
                            $row->saved_remark,
                        ]);
                    }

                    fclose($handle);
                },
                $filename,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */

        $totals = [
            'pcs' => $rows->sum(fn ($row) => (float) $row->qty_issued_pcs),
            'box' => $rows->sum(fn ($row) => (float) $row->qty_issued_box),
            'value' => $rows->sum(fn ($row) => (float) $row->val_issued),
        ];

        return view('reports.marketing-category-issue-report', compact(
            'rows',
            'from',
            'to',
            'search',
            'totals'
        ));
    }

    public function saveRemark(Request $request)
    {
        $validated = $request->validate([
            'marketing_category' => ['nullable', 'string', 'max:100'],
            'prod_code' => ['required', 'string', 'max:100'],
            'item_code' => ['required', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:10000'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $category = $validated['marketing_category'] ?? '';

        MarketingIssueRemark::updateOrCreate(
            [
                'marketing_category' => $category,
                'prod_code' => $validated['prod_code'],
                'item_code' => $validated['item_code'],
            ],
            [
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return redirect()
            ->route('marketing-issue-report.index', [
                'from' => $validated['from'],
                'to' => $validated['to'],
                'search' => $validated['search'] ?? '',
            ])
            ->with('success', 'Remark saved successfully.');
    }

    private function remarkKey(
        ?string $category,
        string $productCode,
        string $itemCode
    ): string {
        // Length-prefix each part so separators in values cannot cause collisions.
        $parts = [
            $category ?? '',
            $productCode,
            $itemCode,
        ];

        return implode('', array_map(
            fn ($part) => strlen($part) . ':' . $part,
            $parts
        ));
    }
}
