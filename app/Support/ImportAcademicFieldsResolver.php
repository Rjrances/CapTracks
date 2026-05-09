<?php

namespace App\Support;

/**
 * Maps CSV academic columns (school_year + short semester, or full semester text)
 * to canonical semester strings matching academic_terms.semester.
 */
final class ImportAcademicFieldsResolver
{
    /**
     * @return array{semester: string, school_year: ?string, year_level: ?string}
     */
    public static function resolve(array $row): array
    {
        $yearLevel = trim((string) ($row['year_level'] ?? $row['year'] ?? ''));
        $schoolYear = trim((string) ($row['school_year'] ?? $row['academic_year'] ?? ''));
        $semRaw = trim((string) ($row['semester'] ?? ''));

        if ($semRaw === '') {
            return [
                'year_level' => $yearLevel === '' ? null : $yearLevel,
                'school_year' => $schoolYear === '' ? null : $schoolYear,
                'semester' => '',
            ];
        }

        if (self::isShortSemesterSlot($semRaw)) {
            if ($schoolYear === '') {
                return [
                    'year_level' => $yearLevel === '' ? null : $yearLevel,
                    'school_year' => null,
                    'semester' => '',
                ];
            }

            return [
                'year_level' => $yearLevel === '' ? null : $yearLevel,
                'school_year' => $schoolYear,
                'semester' => self::composeCanonicalSemester($schoolYear, $semRaw),
            ];
        }

        if (self::looksLikeFullSemesterString($semRaw)) {
            $canonical = self::normalizeFullSemesterSpacing($semRaw);
            $parsedSy = $schoolYear !== '' ? $schoolYear : self::parseSchoolYearFromCanonical($canonical);

            return [
                'year_level' => $yearLevel === '' ? null : $yearLevel,
                'school_year' => $parsedSy,
                'semester' => $canonical,
            ];
        }

        return [
            'year_level' => $yearLevel === '' ? null : $yearLevel,
            'school_year' => $schoolYear === '' ? null : $schoolYear,
            'semester' => self::normalizeFullSemesterSpacing($semRaw),
        ];
    }

    public static function isShortSemesterSlot(string $semester): bool
    {
        $s = strtolower(trim($semester));

        return (bool) preg_match('/^(1st|2nd|first|second|summer|sum|1|2)$/', $s);
    }

    public static function looksLikeFullSemesterString(string $semester): bool
    {
        return (bool) preg_match('/^\d{4}-\d{4}\s+\S/', trim($semester));
    }

    public static function composeCanonicalSemester(string $schoolYear, string $slot): string
    {
        $schoolYear = trim($schoolYear);
        $slotNorm = strtolower(trim($slot));
        $suffix = match (true) {
            in_array($slotNorm, ['1st', 'first', '1'], true) => 'First Semester',
            in_array($slotNorm, ['2nd', 'second', '2'], true) => 'Second Semester',
            in_array($slotNorm, ['summer', 'sum'], true) => 'Summer',
            default => 'First Semester',
        };

        return "{$schoolYear} {$suffix}";
    }

    public static function normalizeFullSemesterSpacing(string $semester): string
    {
        return trim((string) preg_replace('/\s+/', ' ', trim($semester)));
    }

    public static function parseSchoolYearFromCanonical(string $canonical): ?string
    {
        if (preg_match('/^(\d{4}-\d{4})\s+/', $canonical, $m)) {
            return $m[1];
        }

        return null;
    }
}
