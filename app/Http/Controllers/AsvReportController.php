<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsvReportController extends Controller
{
    private array $divisions = [
        'AG',
        'AH',
        'AM',
        'DL',
        'GZ',
        'HL',
        'ZP',
        'JH',
    ];

    public function index(Request $request)
    {
        $from = $request->input('from', '2026-04');
        $to = $request->input('to', '2026-09');
    
        $search = trim($request->input('search', ''));
    
        $selectedDivisions = $request->input(
            'divisions',
            $this->divisions
        );
    
        if (!is_array($selectedDivisions)) {
            $selectedDivisions = [$selectedDivisions];
        }
    
        /*
        |--------------------------------------------------------------------------
        | Only allow valid divisions
        |--------------------------------------------------------------------------
        */
    
        $selectedDivisions = array_values(
            array_intersect(
                $selectedDivisions,
                $this->divisions
            )
        );
    
        // If no division selected, use all
        if (empty($selectedDivisions)) {
            $selectedDivisions = $this->divisions;
        }
    
        /*
        |--------------------------------------------------------------------------
        | Validate dates
        |--------------------------------------------------------------------------
        */
    
        try {
    
            $fromDate = Carbon::createFromFormat(
                'Y-m',
                $from
            )->startOfMonth();
    
            $toDate = Carbon::createFromFormat(
                'Y-m',
                $to
            )->startOfMonth();
    
        } catch (\Exception $e) {
    
            $from = '2026-04';
            $to = '2026-09';
    
            $fromDate = Carbon::createFromFormat(
                'Y-m',
                $from
            )->startOfMonth();
    
            $toDate = Carbon::createFromFormat(
                'Y-m',
                $to
            )->startOfMonth();
        }
    
        /*
        |--------------------------------------------------------------------------
        | Prevent wrong date range
        |--------------------------------------------------------------------------
        */
    
        if ($fromDate->greaterThan($toDate)) {
    
            [$fromDate, $toDate] = [
                $toDate,
                $fromDate
            ];
    
            $from = $fromDate->format('Y-m');
            $to = $toDate->format('Y-m');
        }
    
        /*
        |--------------------------------------------------------------------------
        | Generate months
        |--------------------------------------------------------------------------
        */
    
        $months = [];
    
        $current = $fromDate->copy();
    
        while ($current->lessThanOrEqualTo($toDate)) {
    
            $months[] = [
                'value' => $current->format('Y-m'),
                'label' => strtoupper(
                    $current->format('M Y')
                ),
                'short' => strtoupper(
                    $current->format('M')
                ),
            ];
    
            $current->addMonth();
        }
    
        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */
    
        $query = DB::table('itemtran_body as ib')
    
            ->join(
                'item_mast as im',
                'im.ITEM_CODE',
                '=',
                'ib.ITEM_CODE'
            )
    
            ->join(
                'prod_mast as pm',
                'pm.PROD_CODE',
                '=',
                'im.PROD_CODE'
            )
    
            ->where(
                'ib.VRDATE',
                '>=',
                $fromDate->format('Y-m-d')
            )
    
            ->where(
                'ib.VRDATE',
                '<',
                $toDate
                    ->copy()
                    ->addMonth()
                    ->format('Y-m-d')
            )
    
            ->whereNotNull('ib.QTYISSUED')
    
            ->where(
                'pm.MKTG_CATG',
                'ASV'
            )
    
            ->whereIn(
                'ib.DIV_CODE',
                $selectedDivisions
            );
    
        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
    
        if ($search !== '') {
    
            $query->where(function ($q) use ($search) {
    
                $q->where(
                    'ib.ITEM_CODE',
                    'LIKE',
                    '%' . $search . '%'
                )
    
                ->orWhere(
                    'im.ITEM_NAME',
                    'LIKE',
                    '%' . $search . '%'
                );
            });
        }
    
        /*
        |--------------------------------------------------------------------------
        | Base SELECT
        |--------------------------------------------------------------------------
        */
    
        $query->select([
            'ib.ITEM_CODE',
            'im.ITEM_NAME',
            'im.ITEM_CATG',
            'pm.MKTG_CATG',
        ]);
    
        /*
        |--------------------------------------------------------------------------
        | Dynamic Month + Division Columns
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | selectRaw() is used instead of DB::raw()
        | because selectRaw() accepts bindings.
        |
        */
    
        foreach ($months as $month) {
    
            foreach ($selectedDivisions as $division) {
    
                $alias = strtolower(
                    $month['short'] . '_' . $division
                );
    
                $query->selectRaw(
                    "
                    SUM(
                        CASE
                            WHEN DATE_FORMAT(ib.VRDATE, '%Y-%m') = ?
                            AND ib.DIV_CODE = ?
                            THEN COALESCE(ib.QTYISSUED, 0)
                            ELSE 0
                        END
                    ) AS `{$alias}`
                    ",
                    [
                        $month['value'],
                        $division
                    ]
                );
            }
        }
    
        /*
        |--------------------------------------------------------------------------
        | Group By
        |--------------------------------------------------------------------------
        */
    
        $query->groupBy(
            'ib.ITEM_CODE',
            'im.ITEM_NAME',
            'im.ITEM_CATG',
            'pm.MKTG_CATG'
        );
    
        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */
    
        $query
            ->orderBy('im.ITEM_CATG')
            ->orderBy('ib.ITEM_CODE');
    
        /*
        |--------------------------------------------------------------------------
        | Execute Query
        |--------------------------------------------------------------------------
        */
    
        $rows = $query->get();
    
        /*
        |--------------------------------------------------------------------------
        | CSV Export
        |--------------------------------------------------------------------------
        */
    
        if ($request->input('export') === 'csv') {
    
            $filename =
                'ASV_Report_' .
                $from .
                '_to_' .
                $to .
                '.csv';
    
            return response()->streamDownload(
    
                function () use (
                    $rows,
                    $months,
                    $selectedDivisions
                ) {
    
                    $handle = fopen(
                        'php://output',
                        'w'
                    );
    
                    /*
                    |--------------------------------------------------------------------------
                    | Header
                    |--------------------------------------------------------------------------
                    */
    
                    $header = [
                        'Item Code',
                        'Item Name',
                        'Item Category',
                        'Marketing Category',
                    ];
    
                    foreach ($months as $month) {
    
                        foreach (
                            $selectedDivisions
                            as $division
                        ) {
    
                            $header[] =
                                $month['short'] .
                                ' ' .
                                $division;
                        }
                    }
    
                    fputcsv(
                        $handle,
                        $header
                    );
    
                    /*
                    |--------------------------------------------------------------------------
                    | Data
                    |--------------------------------------------------------------------------
                    */
    
                    foreach ($rows as $row) {
    
                        $data = [
                            $row->ITEM_CODE,
                            $row->ITEM_NAME,
                            $row->ITEM_CATG,
                            $row->MKTG_CATG,
                        ];
    
                        foreach ($months as $month) {
    
                            foreach (
                                $selectedDivisions
                                as $division
                            ) {
    
                                $alias = strtolower(
                                    $month['short'] .
                                    '_' .
                                    $division
                                );
    
                                $data[] =
                                    $row->$alias ?? 0;
                            }
                        }
    
                        fputcsv(
                            $handle,
                            $data
                        );
                    }
    
                    fclose($handle);
    
                },
    
                $filename,
    
                [
                    'Content-Type' =>
                        'text/csv; charset=UTF-8',
    
                    'Content-Disposition' =>
                        'attachment; filename="' .
                        $filename .
                        '"',
                ]
            );
        }
    
        /*
        |--------------------------------------------------------------------------
        | Calculate Totals
        |--------------------------------------------------------------------------
        */
    
        $totals = [];
    
        foreach ($months as $month) {
    
            foreach ($selectedDivisions as $division) {
    
                $alias = strtolower(
                    $month['short'] .
                    '_' .
                    $division
                );
    
                $totals[$alias] = $rows->sum(
                    function ($row) use ($alias) {
    
                        return (float) (
                            $row->$alias ?? 0
                        );
                    }
                );
            }
        }
    
        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */
    
        return view(
            'asv-report',
            compact(
                'rows',
                'months',
                'selectedDivisions',
                'from',
                'to',
                'search',
                'totals'
            )
        );
    }
}