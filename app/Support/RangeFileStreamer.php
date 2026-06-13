<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RangeFileStreamer
{
    public static function stream(Request $request, string $absolutePath, string $mimeType, string $downloadName): StreamedResponse
    {
        $size = filesize($absolutePath);

        if ($size === false) {
            abort(404);
        }

        $start = 0;
        $end = $size - 1;
        $status = 200;

        if ($request->headers->has('Range') && preg_match('/bytes=(\d+)-(\d*)/', (string) $request->header('Range'), $matches)) {
            $start = (int) $matches[1];

            if ($matches[2] !== '') {
                $end = min((int) $matches[2], $size - 1);
            }

            if ($start > $end || $start >= $size) {
                return response()->stream(static fn () => null, 416, [
                    'Content-Range' => "bytes */{$size}",
                ]);
            }

            $status = 206;
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="'.addslashes($downloadName).'"',
            'Cache-Control' => 'private, no-transform',
        ];

        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($absolutePath, $start, $length): void {
            $handle = fopen($absolutePath, 'rb');

            if ($handle === false) {
                return;
            }

            fseek($handle, $start);

            $remaining = $length;

            while ($remaining > 0 && ! feof($handle)) {
                $chunk = fread($handle, min(8192, $remaining));

                if ($chunk === false) {
                    break;
                }

                echo $chunk;
                $remaining -= strlen($chunk);
            }

            fclose($handle);
        }, $status, $headers);
    }
}
