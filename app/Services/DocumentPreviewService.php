<?php

namespace App\Services;

use App\Models\ProjectSubmission;
use Illuminate\Support\Facades\Storage;

class DocumentPreviewService
{
    public static function panelForSubmission(ProjectSubmission $submission, ?string $label = null): array
    {
        $path = $submission->file_path;

        return [
            'label' => $label ?? ('v' . ($submission->version ?? 1)),
            'filePath' => $path,
            'kind' => $path ? self::embedKind($path) : 'unknown',
            'iframeSrc' => $path ? self::iframeSrc($path) : null,
            'downloadUrl' => $path ? self::publicUrl($path) : '#',
        ];
    }

    public static function publicUrl(string $relativePath): string
    {
        return asset('storage/' . ltrim($relativePath, '/'));
    }

    public static function embedKind(string $relativePath): string
    {
        $mimeKind = self::embedKindFromMime($relativePath);
        if ($mimeKind !== null) {
            return $mimeKind;
        }

        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'pdf',
            'doc', 'docx', 'xls', 'xlsx' => 'office',
            'ppt', 'pptx' => 'ppt',
            'zip' => 'zip',
            default => 'unknown',
        };
    }

    /**
     * @return string|null URL suitable for an iframe, or null when preview must fall back to download
     */
    public static function iframeSrc(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $kind = self::embedKind($relativePath);
        $url = self::publicUrl($relativePath);

        if ($kind === 'pdf') {
            return $url . '#toolbar=1';
        }

        if ($kind === 'office' || $kind === 'ppt') {
            if (!self::isPubliclyReachableUrl($url)) {
                return null;
            }
            return 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($url);
        }

        return null;
    }

    private static function isPubliclyReachableUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        if (str_starts_with($host, '10.')
            || str_starts_with($host, '192.168.')
            || preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host) === 1) {
            return false;
        }

        return true;
    }

    private static function embedKindFromMime(string $relativePath): ?string
    {
        try {
            if (!Storage::disk('public')->exists($relativePath)) {
                return null;
            }

            $mime = strtolower((string) Storage::disk('public')->mimeType($relativePath));
            if ($mime === '') {
                return null;
            }

            if ($mime === 'application/pdf') {
                return 'pdf';
            }

            if (
                str_contains($mime, 'officedocument')
                || str_contains($mime, 'msword')
                || str_contains($mime, 'ms-excel')
                || str_contains($mime, 'ms-powerpoint')
            ) {
                return 'office';
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
