<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareStreamService
{
    private string $accountId;
    private string $apiToken;
    private string $customerSubdomain;
    private string $baseUrl;

    public function __construct()
    {
        $this->accountId = config('filesystems.cloudflare_stream.account_id');
        $this->apiToken = config('filesystems.cloudflare_stream.api_token');
        $this->customerSubdomain = config('filesystems.cloudflare_stream.customer_subdomain');
        $this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream";
    }

    /**
     * Crea un endpoint TUS en Cloudflare para que el browser suba directo.
     * Retorna ['upload_url' => ..., 'uid' => ...]
     */
    public function createDirectUpload(int $maxDurationSeconds, string $videoTitle, array $meta = []): array
    {
        $response = Http::withToken($this->apiToken)
            ->withHeaders([
                'Tus-Resumable' => '1.0.0',
                'Upload-Length' => $meta['file_size'] ?? 0,
                'Upload-Metadata' => $this->buildTusMetadata(array_merge([
                    'name' => $videoTitle,
                    'maxDurationSeconds' => $maxDurationSeconds,
                ], $meta)),
            ])
            ->post("{$this->baseUrl}?direct_user=true");

        if (! $response->successful()) {
            Log::error('Cloudflare Stream createDirectUpload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to create Cloudflare Stream upload: ' . $response->body());
        }

        $uid = $response->header('stream-media-id');
        $uploadUrl = $response->header('Location');

        return [
            'uid' => $uid,
            'upload_url' => $uploadUrl,
        ];
    }

    /**
     * Consulta el estado actual del video en Cloudflare.
     */
    public function getVideoDetails(string $uid): array
    {
        $response = Http::withToken($this->apiToken)
            ->get("{$this->baseUrl}/{$uid}");

        if (! $response->successful()) {
            throw new \RuntimeException("Failed to get Cloudflare Stream video details for {$uid}");
        }

        $result = $response->json('result');

        return [
            'uid'           => $result['uid'],
            'status'        => $result['status']['state'] ?? 'unknown',
            'ready'         => ($result['status']['state'] ?? '') === 'ready',
            'hls_url'       => $result['playback']['hls'] ?? null,
            'dash_url'      => $result['playback']['dash'] ?? null,
            'thumbnail_url' => $result['thumbnail'] ?? null,
            'duration'      => $result['duration'] ?? null,
        ];
    }

    /**
     * Elimina un video de Cloudflare Stream.
     */
    public function deleteVideo(string $uid): void
    {
        $response = Http::withToken($this->apiToken)
            ->delete("{$this->baseUrl}/{$uid}");

        if (! $response->successful()) {
            throw new \RuntimeException("Failed to delete Cloudflare Stream video {$uid}");
        }
    }

    /**
     * Retorna la URL HLS del video.
     */
    public function getHlsUrl(string $uid): string
    {
        return "https://customer-{$this->customerSubdomain}.cloudflarestream.com/{$uid}/manifest/video.m3u8";
    }

    /**
     * Retorna la URL DASH del video.
     */
    public function getDashUrl(string $uid): string
    {
        return "https://customer-{$this->customerSubdomain}.cloudflarestream.com/{$uid}/manifest/video.mpd";
    }

    /**
     * Retorna la URL del thumbnail. $time en segundos.
     */
    public function getThumbnailUrl(string $uid, float $time = 0): string
    {
        return "https://customer-{$this->customerSubdomain}.cloudflarestream.com/{$uid}/thumbnails/thumbnail.jpg?time={$time}s";
    }

    /**
     * Retorna la URL del iframe embed.
     */
    public function getEmbedUrl(string $uid): string
    {
        return "https://customer-{$this->customerSubdomain}.cloudflarestream.com/{$uid}/iframe";
    }

    /**
     * Construye el header Upload-Metadata para TUS.
     */
    private function buildTusMetadata(array $meta): string
    {
        $parts = [];
        foreach ($meta as $key => $value) {
            $parts[] = $key . ' ' . base64_encode((string) $value);
        }

        return implode(',', $parts);
    }
}
