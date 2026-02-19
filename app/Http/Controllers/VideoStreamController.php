<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\BunnyStreamService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class VideoStreamController extends Controller
{
    /**
     * CDN Health Check Cache Duration (in seconds)
     * Check CDN health every 5 minutes instead of every request
     */
    private const CDN_HEALTH_CACHE_SECONDS = 300;

    /**
     * CDN Health Check Timeout (in seconds)
     */
    private const CDN_HEALTH_TIMEOUT = 3;

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

    /**
     * Check if CDN is healthy (with cache to avoid checking every request)
     * Returns true if CDN is accessible, false otherwise
     */
    private function isCdnHealthy(): bool
    {
        $cacheKey = 'cdn_health_status';

        // Return cached status if available
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Check Worker URL if enabled, otherwise check CDN
        $workerEnabled = config('filesystems.cloudflare.worker_enabled', false);
        $checkUrl = $workerEnabled
            ? config('filesystems.cloudflare.worker_url')
            : config('filesystems.disks.spaces.url');

        if (! $checkUrl) {
            return false;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => self::CDN_HEALTH_TIMEOUT,
                    'user_agent' => 'HealthCheck/1.0',
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            // Check URL accessibility (Worker or CDN)
            $headers = @get_headers($checkUrl, true, $context);
            $isHealthy = $headers && (
                strpos($headers[0], '200') !== false ||
                strpos($headers[0], '403') !== false || // 403 is OK - means service is responding
                strpos($headers[0], '404') !== false    // 404 is OK - means service is responding
            );

            // Cache result
            Cache::put($cacheKey, $isHealthy, self::CDN_HEALTH_CACHE_SECONDS);

            if (! $isHealthy) {
                $service = $workerEnabled ? 'Worker' : 'CDN';
                \Log::warning("$service health check failed - Response: ".($headers[0] ?? 'No response'));
            }

            return $isHealthy;

        } catch (Exception $e) {
            $service = $workerEnabled ? 'Worker' : 'CDN';
            \Log::error("$service health check exception: ".$e->getMessage());
            Cache::put($cacheKey, false, 60); // Cache failure for 1 minute only

            return false;
        }
    }

    /**
     * Force CDN health check (clears cache and re-checks)
     */
    public function refreshCdnHealth(): bool
    {
        Cache::forget('cdn_health_status');

        return $this->isCdnHealthy();
    }

    /**
     * Get the optimal CDN URL (Worker if enabled, CDN otherwise)
     *
     * When Cloudflare Worker is enabled, returns the Worker URL which adds
     * CORS headers at the edge (0ms overhead vs Laravel proxy).
     *
     * @param  string  $filePath  Path to the file in storage
     * @return string Full URL to access the file
     */
    private function getOptimalCdnUrl(string $filePath): string
    {
        $workerEnabled = config('filesystems.cloudflare.worker_enabled', false);
        $workerUrl = config('filesystems.cloudflare.worker_url');
        $cdnUrl = config('filesystems.disks.spaces.url');

        if ($workerEnabled && $workerUrl) {
            // Use Cloudflare Worker (fast - 0ms overhead)
            return rtrim($workerUrl, '/').'/'.ltrim($filePath, '/');
        }

        // Fallback to CDN direct (will use proxy if needed)
        return rtrim($cdnUrl, '/').'/'.ltrim($filePath, '/');
    }

    /**
     * API endpoint to check CDN health status
     * Useful for monitoring and debugging
     */
    public function cdnStatus(Request $request)
    {
        $forceRefresh = $request->has('refresh');

        if ($forceRefresh) {
            $isHealthy = $this->refreshCdnHealth();
        } else {
            $isHealthy = $this->isCdnHealthy();
        }

        $workerEnabled = config('filesystems.cloudflare.worker_enabled', false);
        $workerUrl = config('filesystems.cloudflare.worker_url');
        $cdnUrl = config('filesystems.disks.spaces.url');

        return response()->json([
            'cdn_healthy' => $isHealthy,
            'cdn_url' => $cdnUrl,
            'worker_enabled' => $workerEnabled,
            'worker_url' => $workerUrl,
            'active_endpoint' => $workerEnabled ? $workerUrl : $cdnUrl,
            'cached' => ! $forceRefresh && Cache::has('cdn_health_status'),
            'cache_ttl_seconds' => self::CDN_HEALTH_CACHE_SECONDS,
            'fallback_available' => true,
            'fallback_method' => 'Spaces SDK Direct Streaming',
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function stream(Video $video, Request $request)
    {
        // Bunny Stream: redirigir directamente a HLS
        if ($video->bunny_video_id && $video->bunny_status === 'ready') {
            $service = BunnyStreamService::forOrganization($video->organization);

            return redirect($service->getHlsUrl($video->bunny_video_id));
        }

        // Production: Use Spaces/CDN as primary source
        if (config('app.env') === 'production') {
            // Check if file is in DigitalOcean Spaces (new uploads)
            try {
                if (Storage::disk('spaces')->exists($video->file_path)) {
                    $cdnBaseUrl = config('filesystems.disks.spaces.url');

                    // Try CDN first if configured and healthy
                    if ($cdnBaseUrl && $this->isCdnHealthy()) {
                        $cdnUrl = $this->getOptimalCdnUrl($video->file_path);

                        \Log::debug('CDN redirect via Worker - video: '.$video->id);

                        return redirect($cdnUrl);
                    }

                    // CDN not available - use direct Spaces streaming as fallback
                    if ($cdnBaseUrl && ! $this->isCdnHealthy()) {
                        \Log::warning('CDN unhealthy - using Spaces SDK fallback for video: '.$video->id);
                    }

                    return $this->streamFromSpaces($video, $request);
                }
            } catch (Exception $e) {
                // Log error and continue to local fallback
                \Log::warning('DigitalOcean Spaces access failed: '.$e->getMessage());
            }
        }

        // Local/Development: Try local storage first (fast), then Spaces as fallback (slow but works)
        if (config('app.env') === 'local') {
            $path = storage_path('app/public/'.$video->file_path);

            // Try local storage first (instant loading for new videos)
            if (file_exists($path)) {
                \Log::debug('Streaming from local storage - video: '.$video->id);
                // Continue to stream from local (code below)
            } else {
                // Local file doesn't exist, try Spaces as fallback (for old videos)
                try {
                    if (Storage::disk('spaces')->exists($video->file_path)) {
                        \Log::info('Local file not found, streaming from Spaces - video: '.$video->id);

                        $cdnBaseUrl = config('filesystems.disks.spaces.url');

                        // Try CDN first if configured and healthy
                        if ($cdnBaseUrl && $this->isCdnHealthy()) {
                            $cdnUrl = $this->getOptimalCdnUrl($video->file_path);

                            return redirect($cdnUrl);
                        }

                        // CDN not available - use direct Spaces streaming
                        return $this->streamFromSpaces($video, $request);
                    }
                } catch (Exception $e) {
                    \Log::warning('Spaces fallback failed in local environment: '.$e->getMessage());
                }

                // File not found in local or Spaces
                \Log::error('Video file not found anywhere - video: '.$video->id.' path: '.$video->file_path);
                abort(404, 'Video file not found');
            }
        }

        // Fallback to local storage (for other environments or production fallback)
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

    /**
     * Stream video directly from DigitalOcean Spaces through Laravel
     * This prevents redirect issues between mobile and desktop devices
     */
    private function streamFromSpaces(Video $video, Request $request)
    {
        try {
            // Get file info from Spaces
            $fileSize = Storage::disk('spaces')->size($video->file_path);
            $mimeType = $this->getCompatibleMimeType($video);

            // Handle Range requests for seeking compatibility
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

                // Stream partial content from Spaces using chunks
                return response()->stream(function () use ($video, $start, $length, $fileSize) {
                    $stream = Storage::disk('spaces')->readStream($video->file_path);
                    fseek($stream, $start);

                    $chunkSize = $this->getOptimalChunkSize($fileSize, true); // Adaptive chunk for seeking
                    $bytesRead = 0;

                    while (! feof($stream) && $bytesRead < $length) {
                        $remainingBytes = $length - $bytesRead;
                        $currentChunkSize = min($chunkSize, $remainingBytes);

                        $chunk = fread($stream, $currentChunkSize);
                        if ($chunk === false || strlen($chunk) === 0) {
                            break;
                        }

                        echo $chunk;
                        flush();
                        $bytesRead += strlen($chunk);
                    }

                    fclose($stream);
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes $start-$end/$fileSize",
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'no-cache',
                ]);
            }

            // No range request - serve full file from Spaces using chunks
            return response()->stream(function () use ($video, $fileSize) {
                $stream = Storage::disk('spaces')->readStream($video->file_path);

                $chunkSize = $this->getOptimalChunkSize($fileSize, false); // Adaptive chunk for full stream

                while (! feof($stream)) {
                    $chunk = fread($stream, $chunkSize);
                    if ($chunk === false || strlen($chunk) === 0) {
                        break;
                    }

                    echo $chunk;
                    flush();
                }

                fclose($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (Exception $e) {
            \Log::error('Spaces streaming failed: '.$e->getMessage());
            // Return 404 to trigger local fallback
            abort(404, 'Video streaming failed');
        }
    }

    public function streamByPath($filename, Request $request)
    {
        $spacesPath = 'videos/'.$filename;

        // Production: Use Spaces/CDN as primary source
        if (config('app.env') === 'production') {
            // Check if file is in DigitalOcean Spaces (new uploads)
            try {
                if (Storage::disk('spaces')->exists($spacesPath)) {
                    $cdnBaseUrl = config('filesystems.disks.spaces.url');

                    // Try CDN first if configured and healthy
                    if ($cdnBaseUrl && $this->isCdnHealthy()) {
                        $cdnUrl = $this->getOptimalCdnUrl($spacesPath);

                        \Log::debug('CDN redirect by path via Worker - file: '.$filename);

                        return redirect($cdnUrl);
                    }

                    // CDN not available - use direct Spaces streaming as fallback
                    if ($cdnBaseUrl && ! $this->isCdnHealthy()) {
                        \Log::warning('CDN unhealthy - using Spaces SDK fallback for file: '.$filename);
                    }

                    return $this->streamFileFromSpaces($spacesPath, $request);
                }
            } catch (Exception $e) {
                // Log error and continue to local fallback
                \Log::warning('DigitalOcean Spaces access failed for path: '.$e->getMessage());
            }
        }

        // Local/Development: Try local storage first (fast), then Spaces as fallback (slow but works)
        if (config('app.env') === 'local') {
            $path = storage_path('app/public/videos/'.$filename);

            // Try local storage first (instant loading for new videos)
            if (file_exists($path)) {
                \Log::debug('Streaming from local storage - file: '.$filename);
                // Continue to stream from local (code below)
            } else {
                // Local file doesn't exist, try Spaces as fallback (for old videos)
                try {
                    if (Storage::disk('spaces')->exists($spacesPath)) {
                        \Log::info('Local file not found, streaming from Spaces - file: '.$filename);

                        $cdnBaseUrl = config('filesystems.disks.spaces.url');

                        // Try CDN first if configured and healthy
                        if ($cdnBaseUrl && $this->isCdnHealthy()) {
                            $cdnUrl = $this->getOptimalCdnUrl($spacesPath);

                            return redirect($cdnUrl);
                        }

                        // CDN not available - use direct Spaces streaming
                        return $this->streamFileFromSpaces($spacesPath, $request);
                    }
                } catch (Exception $e) {
                    \Log::warning('Spaces fallback failed in local environment: '.$e->getMessage());
                }

                // File not found in local or Spaces
                abort(404, 'Video file not found');
            }
        }

        // Fallback to local storage for old videos (other environments or production fallback)
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
     * Stream file directly from DigitalOcean Spaces by path
     */
    private function streamFileFromSpaces($spacesPath, Request $request)
    {
        try {
            // Get file info from Spaces
            $fileSize = Storage::disk('spaces')->size($spacesPath);
            // Use video/mp4 for maximum browser compatibility
            $mimeType = 'video/mp4';

            // Handle Range requests for seeking compatibility
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

                // Stream partial content from Spaces using chunks
                return response()->stream(function () use ($spacesPath, $start, $length, $fileSize) {
                    $stream = Storage::disk('spaces')->readStream($spacesPath);
                    fseek($stream, $start);

                    $chunkSize = $this->getOptimalChunkSize($fileSize, true); // Adaptive chunk for seeking
                    $bytesRead = 0;

                    while (! feof($stream) && $bytesRead < $length) {
                        $remainingBytes = $length - $bytesRead;
                        $currentChunkSize = min($chunkSize, $remainingBytes);

                        $chunk = fread($stream, $currentChunkSize);
                        if ($chunk === false || strlen($chunk) === 0) {
                            break;
                        }

                        echo $chunk;
                        flush();
                        $bytesRead += strlen($chunk);
                    }

                    fclose($stream);
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes $start-$end/$fileSize",
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'no-cache',
                ]);
            }

            // No range request - serve full file from Spaces using chunks
            return response()->stream(function () use ($spacesPath, $fileSize) {
                $stream = Storage::disk('spaces')->readStream($spacesPath);

                $chunkSize = $this->getOptimalChunkSize($fileSize, false); // Adaptive chunk for full stream

                while (! feof($stream)) {
                    $chunk = fread($stream, $chunkSize);
                    if ($chunk === false || strlen($chunk) === 0) {
                        break;
                    }

                    echo $chunk;
                    flush();
                }

                fclose($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (Exception $e) {
            \Log::error('Spaces file streaming failed: '.$e->getMessage());
            // Return 404 to trigger local fallback
            abort(404, 'File streaming failed');
        }
    }

    /**
     * Raw HTTP redirect optimized specifically for Chrome compatibility
     * Maximum speed - no pre-verification, direct redirect with specific headers
     */
    private function optimizedRedirectToCDN($cdnUrl, $video, Request $request)
    {
        // Log for monitoring
        \Log::info('Chrome-optimized raw redirect for: '.$cdnUrl);

        // Use raw HTTP response instead of Laravel redirect for Chrome compatibility
        return response('', 302, [
            'Location' => $cdnUrl,
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Range, Content-Range, Accept-Ranges',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
            'Vary' => 'Accept-Encoding',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
    }

    /**
     * Optimized proxy stream for Chrome compatibility
     */
    private function optimizedProxyStreamFromCDN($cdnUrl, $video, Request $request)
    {
        try {
            // Force video/mp4 MIME type for Chrome compatibility
            $mimeType = 'video/mp4';

            // Get file info from CDN with timeout optimization
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (compatible; LaravelProxy/1.0)',
                ],
            ]);

            $headers = get_headers($cdnUrl, 1, $context);
            if (! $headers || strpos($headers[0], '200') === false) {
                throw new \Exception('CDN file not accessible');
            }

            $fileSize = intval($headers['Content-Length'] ?? $headers['content-length'] ?? 0);

            // Handle Range requests with Chrome optimization
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

                // Stream partial content from CDN with Chrome-optimized headers using chunks
                return response()->stream(function () use ($cdnUrl, $start, $length, $fileSize) {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => "Range: bytes=$start-".($start + $length - 1)."\r\n".
                                       "User-Agent: Mozilla/5.0 (compatible; LaravelProxy/1.0)\r\n",
                            'timeout' => 30,
                        ],
                    ]);

                    $stream = fopen($cdnUrl, 'r', false, $context);
                    if ($stream) {
                        $chunkSize = $this->getOptimalChunkSize($fileSize, true); // Adaptive chunk for seeking
                        $bytesRead = 0;

                        while (! feof($stream) && $bytesRead < $length) {
                            $remainingBytes = $length - $bytesRead;
                            $currentChunkSize = min($chunkSize, $remainingBytes);

                            $chunk = fread($stream, $currentChunkSize);
                            if ($chunk === false || strlen($chunk) === 0) {
                                break;
                            }

                            echo $chunk;
                            flush();
                            $bytesRead += strlen($chunk);
                        }

                        fclose($stream);
                    }
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes $start-$end/$fileSize",
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'no-cache',
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Headers' => 'Range, Content-Range, Accept-Ranges',
                    'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
                ]);
            }

            // No range request - stream full file with Chrome-optimized headers using chunks
            return response()->stream(function () use ($cdnUrl, $fileSize) {
                $stream = fopen($cdnUrl, 'r');
                if ($stream) {
                    $chunkSize = $this->getOptimalChunkSize($fileSize, false); // Adaptive chunk for full stream

                    while (! feof($stream)) {
                        $chunk = fread($stream, $chunkSize);
                        if ($chunk === false || strlen($chunk) === 0) {
                            break;
                        }

                        echo $chunk;
                        flush();
                    }

                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => 'Range, Content-Range, Accept-Ranges',
                'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
            ]);

        } catch (\Exception $e) {
            \Log::error('Optimized CDN proxy streaming failed: '.$e->getMessage());

            // Fallback to direct Spaces streaming
            return $this->streamFromSpaces($video, $request);
        }
    }

    /**
     * Original proxy stream video from CDN through Laravel
     * This avoids redirect issues while maintaining fast CDN delivery
     */
    private function proxyStreamFromCDN($cdnUrl, $video, Request $request)
    {
        try {
            // Get file info from CDN
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 10,
                ],
            ]);

            $headers = get_headers($cdnUrl, 1, $context);
            if (! $headers || strpos($headers[0], '200') === false) {
                throw new \Exception('CDN file not accessible');
            }

            $fileSize = intval($headers['Content-Length'] ?? $headers['content-length'] ?? 0);
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

                // Stream partial content from CDN
                return response()->stream(function () use ($cdnUrl, $start, $length) {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => "Range: bytes=$start-".($start + $length - 1)."\r\n",
                            'timeout' => 30,
                        ],
                    ]);

                    $stream = fopen($cdnUrl, 'r', false, $context);
                    if ($stream) {
                        fpassthru($stream);
                        fclose($stream);
                    }
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes $start-$end/$fileSize",
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'no-cache',
                ]);
            }

            // No range request - stream full file from CDN
            return response()->stream(function () use ($cdnUrl) {
                $stream = fopen($cdnUrl, 'r');
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (\Exception $e) {
            \Log::error('CDN proxy streaming failed: '.$e->getMessage());
            // Fallback to direct Spaces streaming
            if (is_object($video) && isset($video->file_path)) {
                return $this->streamFromSpaces($video, $request);
            } else {
                abort(404, 'Video streaming failed');
            }
        }
    }

    /**
     * Detect if browser is Chrome-based (Chrome, Edge, Opera, etc.)
     */
    private function isChromeBasedBrowser($userAgent)
    {
        return preg_match('/Chrome|Chromium|Edge|Opera/i', $userAgent) &&
               ! preg_match('/Firefox|Safari(?!.*Chrome)/i', $userAgent);
    }

    /**
     * Serve direct CDN URL for Chrome browsers using HTML video source
     * Chrome works perfectly with direct CDN URLs
     */
    private function serveDirectCDNForChrome($cdnUrl, $video)
    {
        $mimeType = $this->getCompatibleMimeType($video);

        // Return HTML that tells Chrome to use the direct CDN URL
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { margin: 0; padding: 0; background: #000; }
        video { width: 100%; height: 100vh; object-fit: contain; }
    </style>
</head>
<body>
    <video controls autoplay preload="metadata">
        <source src="'.htmlspecialchars($cdnUrl).'" type="'.$mimeType.'">
        Your browser does not support the video tag.
    </video>
    <script>
        // Redirect parent frame to CDN URL for seamless experience
        if (window.parent !== window) {
            window.parent.location.href = "'.htmlspecialchars($cdnUrl).'";
        } else {
            window.location.href = "'.htmlspecialchars($cdnUrl).'";
        }
    </script>
</body>
</html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
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
}
