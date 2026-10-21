<?php

namespace App\Exports\Concerns;

use Illuminate\Support\Carbon;

trait FormatsMaintenanceTicketReport
{
    protected function formatDate($value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format(
                $withTime ? 'd-m-Y H:i' : 'd-m-Y'
            );
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    protected function formatYesNo($value): string
    {
        return $value ? 'Yes' : 'No';
    }

    protected function reportStyles(): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F4E78'],
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}