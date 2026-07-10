<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmailAttachmentResolver
{
    /**
     * @param  list<array{path: string, name?: string}|string>  $records
     * @return list<array{disk: string, path: string, full_path: string, name: string, mime: string}>
     */
    public function resolveMany(array $records): array
    {
        $resolved = [];

        foreach ($records as $record) {
            $path = is_array($record) ? ($record['path'] ?? null) : $record;
            $preferredName = is_array($record) ? ($record['name'] ?? null) : null;

            $attachment = $this->resolve((string) $path, $preferredName);

            if ($attachment) {
                $resolved[] = $attachment;
            }
        }

        return $resolved;
    }

    /**
     * @return array{disk: string, path: string, full_path: string, name: string, mime: string}|null
     */
    public function resolve(string $path, ?string $preferredName = null): ?array
    {
        $path = trim(str_replace('\\', '/', $path));

        if (blank($path)) {
            return null;
        }

        if ($this->isReadableAbsolutePath($path)) {
            return $this->buildResult('public', $this->relativeFromAbsolute($path) ?? basename($path), $path, $preferredName);
        }

        foreach ($this->candidatePaths($path) as $diskPath) {
            if (! Storage::disk('public')->exists($diskPath)) {
                continue;
            }

            $fullPath = Storage::disk('public')->path($diskPath);

            if (! is_readable($fullPath)) {
                continue;
            }

            return $this->buildResult('public', $diskPath, $fullPath, $preferredName);
        }

        Log::warning('Anexo de e-mail não encontrado no disco.', [
            'path' => $path,
            'candidates' => $this->candidatePaths($path),
        ]);

        return null;
    }

    public function normalizeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if (blank($path)) {
            return '';
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = (string) (parse_url($path, PHP_URL_PATH) ?? $path);
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/app/public/')) {
            $path = substr($path, strlen('storage/app/public/'));
        }

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        $publicRoot = str_replace('\\', '/', Storage::disk('public')->path(''));

        if (str_starts_with($path, $publicRoot)) {
            $path = substr($path, strlen($publicRoot));
        }

        return ltrim($path, '/');
    }

    /**
     * @return array{disk: string, path: string, full_path: string, name: string, mime: string}
     */
    private function buildResult(string $disk, string $diskPath, string $fullPath, ?string $preferredName): array
    {
        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';

        return [
            'disk' => $disk,
            'path' => ltrim($diskPath, '/'),
            'full_path' => $fullPath,
            'name' => $preferredName ?: basename($diskPath),
            'mime' => $mime,
        ];
    }

    private function isReadableAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            && is_file($path)
            && is_readable($path);
    }

    private function relativeFromAbsolute(string $absolutePath): ?string
    {
        $publicRoot = str_replace('\\', '/', Storage::disk('public')->path(''));

        if (! str_starts_with($absolutePath, $publicRoot)) {
            return null;
        }

        return ltrim(substr($absolutePath, strlen($publicRoot)), '/');
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(string $path): array
    {
        $candidates = [$this->normalizeRelativePath($path)];

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if ($normalized !== $candidates[0]) {
            $candidates[] = $normalized;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $urlPath = parse_url($path, PHP_URL_PATH);

            if (is_string($urlPath) && filled($urlPath)) {
                $candidates[] = $this->normalizeRelativePath($urlPath);
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }
}
