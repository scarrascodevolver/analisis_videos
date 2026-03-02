<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\BunnyStreamService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class VideoStreamController extends Controller
{
    /**
     * Get optimal chunk size based on file size and operation type
     */
    private function getOptimalChunkSize($fileSize, $isRangeRequest = false)
    {
        // For small files (< 100MB): use larger chunks for speed
        if ($fileSize < 100 * 1024 * 1024) {
            return $isRangeRequest ? 131072 : 65536; // 128KB for seeking, 64KB for full stream
        }

        // For medium files (100MB - 500MB): balanced chunks
        if ($fileSize < 500 * 1024 * 1024) {
            return $isRangeRequest ? 65536 : 32768; // 64KB for seeking, 32KB for full stream
        }

        // For large files (> 500MB): smaller chunks for memory safety
        return $isRangeRequest ? 32768 : 8192; // 32KB for seeking, 8KB for full stream
    }

    public function stream(Video $video, Request $request)
    {
        // YouTube: no hay archivo local que streamear
        if ($video->is_youtube_video) {
            return response()->json([
                'error' => 'Este video es de YouTube. Usa youtube_video_id para reproducirlo.',
                'youtube_video_id' => $video->youtube_video_id,
            ], 422);
        }

        // Bunny Stream: redirigir directamente a HLS
        if ($video->bunny_video_id && $video->bunny_status === 'ready') {
            $service = BunnyStreamService::forOrganization($video->organization);

            return redirect($service->getHlsUrl($video->bunny_video_id));
        }

        // Fallback: stream desde almacenamiento local
        $path = storage_path('app/public/'.$video->file_path);

        if (! file_exists($path)) {
            \Log::error('Video file not found anywhere - video: '.$video->id.' path: '.$video->file_path);
            abort(404, 'Video file not found');
        }

        $fileSize = filesize($path);

        // Fix MIME type compatibility: Force video/mp4 for browser compatibility
        // Many videos are QuickTime content in MP4 containers - browsers prefer video/mp4
        $mimeType = $this->getCompatibleMimeType($video);

        // Handle Range requests for seeking
        $range = $request->header('Range');

        if ($range) {
            // Parse range header
            preg_match('/bytes=(\d+)-(\d*)/i', $range, $matches);
            $start = intval($matches[1]);
            $end = ! empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

            // Validate range
            if ($start > $fileSize - 1 || $end > $fileSize - 1) {
                return response('', 416, [
                    'Content-Range' => "bytes */$fileSize",
                ]);
            }

            $length = $end - $start + 1;

            return response()->stream(function () use ($path, $start, $length, $fileSize) {
                $file = fopen($path, 'rb');
                fseek($file, $start);

                $chunkSize = $this->getOptimalChunkSize($fileSize, true);
                $bytesRead = 0;

                while (! feof($file) && $bytesRead < $length) {
                    $remainingBytes = $length - $bytesRead;
                    $currentChunkSize = min($chunkSize, $remainingBytes);

                    $chunk = fread($file, $currentChunkSize);
                    if ($chunk === false || strlen($chunk) === 0) {
                        break;
                    }

                    echo $chunk;
                    flush();
                    $bytesRead += strlen($chunk);
                }

                fclose($file);
            }, 206, [
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$fileSize",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache',
            ]);
        }

        // No range request - serve full file using chunks
        return response()->stream(function () use ($path, $fileSize) {
            $file = fopen($path, 'rb');

            $chunkSize = $this->getOptimalChunkSize($fileSize, false);

            while (! feof($file)) {
                $chunk = fread($file, $chunkSize);
                if ($chunk === false || strlen($chunk) === 0) {
                    break;
                }

                echo $chunk;
                flush();
            }

            fclose($file);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function streamByPath($filename, Request $request)
    {
        // Fallback: stream desde almacenamiento local
        $path = storage_path('app/public/videos/'.$filename);

        if (! file_exists($path)) {
            abort(404, 'Video file not found');
        }

        $fileSize = filesize($path);
        $mimeType = mime_content_type($path) ?: 'video/mp4';

        // Handle Range requests for seeking
        $range = $request->header('Range');

        if ($range) {
            // Parse range header
            preg_match('/bytes=(\d+)-(\d*)/i', $range, $matches);
            $start = intval($matches[1]);
            $end = ! empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

            // Validate range
            if ($start > $fileSize - 1 || $end > $fileSize - 1) {
                return response('', 416, [
                    'Content-Range' => "bytes */$fileSize",
                ]);
            }

            $length = $end - $start + 1;

            return response()->stream(function () use ($path, $start, $length, $fileSize) {
                $file = fopen($path, 'rb');
                fseek($file, $start);

                $chunkSize = $this->getOptimalChunkSize($fileSize, true);
                $bytesRead = 0;

                while (! feof($file) && $bytesRead < $length) {
                    $remainingBytes = $length - $bytesRead;
                    $currentChunkSize = min($chunkSize, $remainingBytes);

                    $chunk = fread($file, $currentChunkSize);
                    if ($chunk === false || strlen($chunk) === 0) {
                        break;
                    }

                    echo $chunk;
                    flush();
                    $bytesRead += strlen($chunk);
                }

                fclose($file);
            }, 206, [
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$fileSize",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache',
            ]);
        }

        // No range request - serve full file using chunks
        return response()->stream(function () use ($path, $fileSize) {
            $file = fopen($path, 'rb');

            $chunkSize = $this->getOptimalChunkSize($fileSize, false);

            while (! feof($file)) {
                $chunk = fread($file, $chunkSize);
                if ($chunk === false || strlen($chunk) === 0) {
                    break;
                }

                echo $chunk;
                flush();
            }

            fclose($file);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Get browser-compatible MIME type for video
     */
    private function getCompatibleMimeType($video)
    {
        // Force video/mp4 for better browser compatibility
        // Even QuickTime content in MP4 containers works better with video/mp4 MIME type
        if (isset($video->file_name) && str_ends_with(strtolower($video->file_name), '.mp4')) {
            return 'video/mp4';
        }

        // Map other QuickTime variants to MP4
        if (isset($video->mime_type) && in_array($video->mime_type, ['video/quicktime', 'video/mov'])) {
            return 'video/mp4';
        }

        // Keep original for other formats
        return $video->mime_type ?? 'video/mp4';
    }

    public function download(Video $video)
    {
        if ($video->is_youtube_video) {
            abort(404, 'Los videos de YouTube no se pueden descargar.');
        }

        // Determinar la URL fuente
        $url = null;

        if ($video->bunny_mp4_url) {
            $url = $video->bunny_mp4_url;
        } elseif ($video->bunny_video_id && $video->organization) {
            try {
                $service = \App\Services\BunnyStreamService::forOrganization($video->organization);
                $url = $service->getOriginalUrl($video->bunny_video_id);
            } catch (\Throwable $e) {
                \Log::warning("Download: could not build Bunny URL for video {$video->id}: {$e->getMessage()}");
            }
        }

        if (! $url) {
            abort(404, 'Archivo no disponible para descarga.');
        }

        $filename = \Illuminate\Support\Str::slug($video->title ?: 'video').'.mp4';

        // Headers que simularán un navegador para evitar bloqueos de Bunny CDN
        $browserHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: video/mp4,video/*;q=0.9,*/*;q=0.8',
            'Referer: '.config('app.url').'/',
        ];

        // Proxy via curl escribiendo directo a php://output
        return response()->stream(function () use ($url, $browserHeaders) {
            set_time_limit(0);
            $fp = fopen('php://output', 'wb');
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_BUFFERSIZE     => 65536,
                CURLOPT_HTTPHEADER     => $browserHeaders,
            ]);
            $ok = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            \Log::info('Download stream result', [
                'video_url'  => $url,
                'http_code'  => $httpCode,
                'curl_error' => $error ?: null,
                'curl_ok'    => $ok,
            ]);
            curl_close($ch);
            fclose($fp);
        }, 200, [
            'Content-Type'        => 'video/mp4',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'no-cache',
            'X-Accel-Buffering'   => 'no',
        ]);
    }
}
