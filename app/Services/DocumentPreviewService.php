<?php

namespace App\Services;

use App\Models\ProjectSubmission;

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
            return 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($url);
        }

        return null;
    }
}
