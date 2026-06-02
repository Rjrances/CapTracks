<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final class CoordinatorFinalGradesExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly Collection $lines
    ) {}

    public function collection(): Collection
    {
        return $this->lines;
    }

    public function headings(): array
    {
        return [
            'Defense stage',
            'Student',
            'Student ID',
            'Group',
            'Offering',
            'Chair',
            'Member',
            'Coordinator',
            'Average',
            'Status',
            'Finalized',
        ];
    }
}
