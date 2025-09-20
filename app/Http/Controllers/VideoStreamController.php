<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamController extends Controller
{
    public function stream(Video $video, Request $request)
    {
        // DEBUG: Log that we reached the method
        \Log::info('VideoStreamController::stream called for video: ' . $video->id);

        // Check if file is in DigitalOcean Spaces (new uploads)
        try {
            \Log::info('DEBUG: Checking if video exists in Spaces: ' . $video->file_path);

            if (Storage::disk('spaces')->exists($video->file_path)) {
                \Log::info('DEBUG: Video exists in Spaces, building CDN URL');

                // Simple direct redirect to CDN for maximum speed
                $cdnBaseUrl = config('filesystems.disks.spaces.url');
                \Log::info('DEBUG: CDN Base URL: ' . $cdnBaseUrl);

                if ($cdnBaseUrl) {
                    $cdnUrl = rtrim($cdnBaseUrl, '/') . '/' . ltrim($video->file_path, '/');
                    \Log::info('DEBUG: Generated CDN URL: ' . $cdnUrl);

                    // Log for monitoring
                    \Log::info('Direct redirect to CDN for maximum speed - video: ' . $video->id . ' -> ' . $cdnUrl);

                    // CloudFlare-compatible redirect with cookie prevention headers
                    return redirect($cdnUrl)->withHeaders([
                        'Cache-Control' => 'no-cache, no-store, must-revalidate',
                        'Pragma' => 'no-cache',
                        'Expires' => '0',
                        'Set-Cookie' => '', // Prevent cookie conflicts
                        'Access-Control-Allow-Credentials' => 'false'
                    ]);
                } else {
                    \Log::warning('DEBUG: No CDN URL configured');
                    // No CDN configured, stream directly from Spaces
                    \Log::info('No CDN URL configured, streaming directly from Spaces for video: ' . $video->id);
                    return $this->streamFromSpaces($video, $request);
                }
            } else {
                \Log::warning('DEBUG: Video does NOT exist in Spaces');
            }
        } catch (Exception $e) {
            // Log error and continue to local fallback
            \Log::error('DEBUG: Exception in Spaces block: ' . $e->getMessage());
            \Log::warning('DigitalOcean Spaces access failed: ' . $e->getMessage());
        }

        // Fallback to local storage for old videos
        $path = storage_path('app/public/' . $video->file_path);

        if (!file_exists($path)) {
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
            $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

            // Validate range
            if ($start > $fileSize - 1 || $end > $fileSize - 1) {
                return response('', 416, [
                    'Content-Range' => "bytes */$fileSize",
                ]);
            }

            $length = $end - $start + 1;

            return response()->stream(function () use ($path, $start, $length) {
                $file = fopen($path, 'rb');
                fseek($file, $start);
                echo fread($file, $length);
                fclose($file);
            }, 206, [
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$fileSize",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache',
            ]);
        }

        // No range request - serve full file
        return response()->stream(function () use ($path) {
            readfile($path);
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
                $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

                // Validate range
                if ($start > $fileSize - 1 || $end > $fileSize - 1) {
                    return response('', 416, [
                        'Content-Range' => "bytes */$fileSize",
                    ]);
                }

                $length = $end - $start + 1;

                // Stream partial content from Spaces
                return response()->stream(function () use ($video, $start, $length) {
                    $stream = Storage::disk('spaces')->readStream($video->file_path);
                    fseek($stream, $start);
                    echo fread($stream, $length);
                    fclose($stream);
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes $start-$end/$fileSize",
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'no-cache',
                ]);
            }

            // No range request - serve full file from Spaces
            return response()->stream(function () use ($video) {
                $stream = Storage::disk('spaces')->readStream($video->file_path);
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (Exception $e) {
            \Log::error('Spaces streaming failed: ' . $e->getMessage());
            // Return 404 to trigger local fallback
            abort(404, 'Video streaming failed');
        }
    }

    public function streamByPath($filename, Request $request)
    {
        // Check if file is in DigitalOcean Spaces (new uploads)
        try {
            $spacesPath = 'videos/' . $filename;
            if (Storage::disk('spaces')->exists($spacesPath)) {
                // Simple direct redirect to CDN for maximum speed
                $cdnBaseUrl = config('filesystems.disks.spaces.url');
                if ($cdnBaseUrl) {
                    $cdnUrl = rtrim($cdnBaseUrl, '/') . '/' . ltrim($spacesPath, '/');

                    // Log for monitoring
                    \Log::info('Direct redirect to CDN for maximum speed - file: ' . $filename . ' -> ' . $cdnUrl);

                    // CloudFlare-compatible redirect with cookie prevention headers
                    return redirect($cdnUrl)->withHeaders([
                        'Cache-Control' => 'no-cache, no-store, must-revalidate',
                        'Pragma' => 'no-cache',
                        'Expires' => '0',
                        'Set-Cookie' => '', // Prevent cookie conflicts
                        'Access-Control-Allow-Credentials' => 'false'
                    ]);
                } else {
                    // No CDN configured, stream directly from Spaces
                    \Log::info('No CDN URL configured, streaming directly from Spaces for file: ' . $filename);
                    return $this->streamFileFromSpaces($spacesPath, $request);
                }
            }
        } catch (Exception $e) {
            // Log error and continue to local fallback
            \Log::warning('DigitalOcean Spaces access failed for path: ' . $e->getMessage());
        }

        // Fallback to local storage for old videos
        $path = storage_path('app/public/videos/' . $filename);

        if (!file_exists($path)) {
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
            $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

            // Validate range
            if ($start > $fileSize - 1 || $end > $fileSize - 1) {
                return response('', 416, [
                    'Content-Range' => "bytes */$fileSize",
                ]);
            }

            $length = $end - $start + 1;

            return response()->stream(function () use ($path, $start, $length) {
                $file = fopen($path, 'rb');
                fseek($file, $start);
                echo fread($file, $length);
                fclose($file);
            }, 206, [
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$fileSize",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache',
            ]);
        }

        // No range request - serve full file
        return response()->stream(function () use ($path) {
            readfile($path);
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
                $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

                // Validate range
                if ($start > $fileSize - 1 || $end > $fileSize - 1) {
                    return response('', 416, [
                        'Content-Range' => "bytes */$fileSize",
                    ]);
                }

                $length = $end - $start + 1;

                // Stream partial content from Spaces
                return response()->stream(function () use ($spacesPath, $start, $length) {
                    $stream = Storage::disk('spaces')->readStream($spacesPath);
                    fseek($stream, $start);
                    echo fread($stream, $length);
                    fclose($stream);
                }, 206, [
                    'Content-Type' => $mimeType,
                    'Content-Length' => $length,
                    'Content-Range' => "bytes $start-$end/$fileSize",
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'no-cache',
                ]);
            }

            // No range request - serve full file from Spaces
            return response()->stream(function () use ($spacesPath) {
                $stream = Storage::disk('spaces')->readStream($spacesPath);
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (Exception $e) {
            \Log::error('Spaces file streaming failed: ' . $e->getMessage());
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
        \Log::info('Chrome-optimized raw redirect for: ' . $cdnUrl);

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
            'Referrer-Policy' => 'strict-origin-when-cross-origin'
        ]);
    }

    /**
     * Proxy stream video from CDN through Laravel
     * This avoids redirect issues while maintaining fast CDN delivery
     */
    private function proxyStreamFromCDN($cdnUrl, $video, Request $request)
    {
        try {
            // Get file info from CDN
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 10
                ]
            ]);

            $headers = get_headers($cdnUrl, 1, $context);
            if (!$headers || strpos($headers[0], '200') === false) {
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
                $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

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
                            'header' => "Range: bytes=$start-" . ($start + $length - 1) . "\r\n",
                            'timeout' => 30
                        ]
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
            \Log::error('CDN proxy streaming failed: ' . $e->getMessage());
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
               !preg_match('/Firefox|Safari(?!.*Chrome)/i', $userAgent);
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
        <source src="' . htmlspecialchars($cdnUrl) . '" type="' . $mimeType . '">
        Your browser does not support the video tag.
    </video>
    <script>
        // Redirect parent frame to CDN URL for seamless experience
        if (window.parent !== window) {
            window.parent.location.href = "' . htmlspecialchars($cdnUrl) . '";
        } else {
            window.location.href = "' . htmlspecialchars($cdnUrl) . '";
        }
    </script>
</body>
</html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
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