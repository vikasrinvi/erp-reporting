<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DispatchStockImportController extends Controller
{
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'division' => 'required|in:JH,BPJH',
            'file' => [
                'required',
                'file',
                'max:30720', // 30MB max
            ],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $stockColumn = $request->division === 'JH' ? 'stock_jh' : 'stock_bp';
        $updatedAtColumn = $request->division === 'JH' ? 'stock_jh_updated_at' : 'stock_bp_updated_at';
        $filePath = $request->file('file')->getRealPath();

        try {
            // Attempt standard IOFactory loading first (auto-detects Excel, CSV, etc.)
            $spreadsheet = IOFactory::load($filePath);
            $rows = collect($spreadsheet->getActiveSheet()->toArray());
        } catch (\Throwable $e) {
            // Fallback: If PhpSpreadsheet crashes (e.g., malformed HTML table disguised as .xls), parse manually
            $content = file_get_contents($filePath);
            
            if (str_contains(strtolower($content), '<table') || str_contains(strtolower($content), '<tr')) {
                $rows = $this->parseHtmlTableToCollection($content);
            } else {
                return back()->with(
                    'error',
                    'Unable to read the uploaded file. Details: ' . $e->getMessage()
                );
            }
        }

        $importRows = [];
        $invalidRows = 0;

        /*
         * HTML Table Structure mapping:
         * Column 1 = Combined Item Code & Name (e.g., "10001-AKIK PISHTI , 10 GM")
         * Column 7 = Balance Stock
         */
        // dd($rows);
        foreach ($rows as $index => $row) {
            $cells = is_array($row) ? array_values($row) : array_values($row->toArray());

            if (!isset($cells[1]) || !isset($cells[7])) {
                continue;
            }
            
            $rawItemCol = trim((string) $cells[1]);
            if($request->division === 'JH' ){
             $stockValue = trim((string) $cells[7]);
                
            } else {
              $stockValue = trim((string) $cells[15]);
            }

            // Ignore blank rows, headers, or sub-headers
            if (
                $rawItemCol === '' 
                || stripos($rawItemCol, 'item name') !== false 
                || strtoupper($rawItemCol) === 'CODE'
                || strtoupper($rawItemCol) === 'SR.NO'
            ) {
                continue;
            }

            // Extract item code from formats like "10001-AKIK PISHTI , 10 GM"
            $itemCode = $rawItemCol;
            if (str_contains($rawItemCol, '-')) {
                $parts = explode('-', $rawItemCol, 2);
                $itemCode = trim($parts[0]);
            }

            // Normalize numeric item codes (e.g., remove trailing .0 if any)
            if (is_numeric($itemCode) && str_ends_with($itemCode, '.0')) {
                $itemCode = substr($itemCode, 0, -2);
            }

            // Remove thousands separators from stock values.
            $stockValue = str_replace(',', '', $stockValue);

            if (!is_numeric($stockValue) || (float) $stockValue < 0) {
                $invalidRows++;
                continue;
            }

            $importRows[$itemCode] = round((float) $stockValue, 3);
        }

        if (empty($importRows)) {
            return back()->with(
                'error',
                'No valid item codes and stock quantities were found in the uploaded sheet.'
            );
        }

        $itemCodes = array_keys($importRows);
        $matchedCodes = collect();

        foreach (array_chunk($itemCodes, 1000) as $codeChunk) {
            $matchedCodes = $matchedCodes->merge(
                DB::table('item_mast')
                    ->whereIn('item_code', $codeChunk)
                    ->pluck('item_code')
            );
        }

        $matchedCodes = $matchedCodes
            ->map(fn ($code) => (string) $code)
            ->flip();

        $now = now();
        $records = [];
        $notFound = 0;

        foreach ($importRows as $itemCode => $stock) {
            if (!$matchedCodes->has((string) $itemCode)) {
                $notFound++;
                continue;
            }

            $records[] = [
                'item_code' => $itemCode,
                $stockColumn => $stock,
                $updatedAtColumn => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        // dd($records);
        if (empty($records)) {
            return back()->with(
                'error',
                "No matching item codes were found. Unmatched rows: {$notFound}."
            );
        }

        DB::transaction(function () use ($records, $stockColumn, $updatedAtColumn) {
            foreach (array_chunk($records, 500) as $batch) {
                DB::table('dispatch_stock_overrides')->upsert(
                    $batch,
                    ['item_code'],
                    [$stockColumn, $updatedAtColumn, 'updated_at']
                );
            }
        });

        $updated = count($records);

        return back()->with(
            'success',
            "Stock import completed successfully. "
            . "Division: {$request->division}. "
            . "Updated: {$updated}. "
            . "Item codes not found: {$notFound}. "
            . "Invalid rows: {$invalidRows}."
        );
    }

    /**
     * Fault-tolerant HTML table parser for malformed HTML exports.
     */
    private function parseHtmlTableToCollection(string $htmlContent): \Illuminate\Support\Collection
    {
        $dom = new \DOMDocument();
        // Suppress warnings and errors for malformed HTML tags
        @$dom->loadHTML($htmlContent, LIBXML_NOERROR | LIBXML_NOWARNING);

        $rows = collect();
        $tableRows = $dom->getElementsByTagName('tr');

        foreach ($tableRows as $tr) {
            $rowData = [];
            $cells = $tr->getElementsByTagName('td');
            if ($cells->length === 0) {
                $cells = $tr->getElementsByTagName('th');
            }

            foreach ($cells as $cell) {
                $rowData[] = trim($cell->textContent);
            }

            if (!empty($rowData)) {
                $rows->push($rowData);
            }
        }

        return $rows;
    }
}