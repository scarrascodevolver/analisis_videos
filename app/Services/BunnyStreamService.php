<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BunnyStreamService
{
    private string $libraryId;
    private string $apiKey;
    private string $cdnHostname;
    private string $baseUrl;

    public function __construct()
    {
        $this->libraryId   = config('filesystems.bunny_stream.library_id');
        $this->apiKey      = config('filesystems.bunny_stream.api_key');
        $this->cdnHostname = config('filesystems.bunny_stream.cdn_hostname');
        $this->baseUrl     = "https://video.bunnycdn.com/library/{$this->libraryId}";
    }

    /**
     * Crea un video en Bunny Stream y devuelve el GUID + URL de upload TUS.
     * El browser sube directo a Bunny sin pasar por el servidor.
     *
     * @return array ['guid' => ..., 'upload_url' => ..., 'signature' => ..., 'expire' => ...]
     */
    public function createVideo(string $title): array
    {
        $response = Http::withHeaders(['AccessKey' => $this->apiKey])
            ->post("{$this->baseUrl}/videos", ['title' => $title]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Failed to create Bunny Stream video: ' . $response->body()
            );
        }

        $data   = $response->json();
        $guid   = $data['guid'];
        $expire = time() + 3600; // 1 hora para completar el upload

        // Firma HMAC-SHA256 requerida por Bunny TUS
        $signature = hash('sha256', $this->libraryId . $this->apiKey . $expire . $guid);

        return [
            'guid'       => $guid,
            'upload_url' => 'https://video.bunnycdn.com/tusupload',
            'signature'  => $signature,
            'expire'     => $expire,
            'library_id' => $this->libraryId,
        ];
    }

    /**
     * Obtiene los detalles actuales de un video.
     */
    public function getVideoDetails(string $guid): array
    {
        $response = Http::withHeaders(['AccessKey' => $this->apiKey])
            ->get("{$this->baseUrl}/videos/{$guid}");

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to get Bunny video details: ' . $response->body());
        }

        $data       = $response->json();
        $rawStatus  = $data['status'] ?? 0;
        $status     = $this->mapStatus($rawStatus);

        Log::debug('Bunny video status', [
            'guid'       => $guid,
            'raw_status' => $rawStatus,
            'mapped'     => $status,
            'length'     => $data['length'] ?? null,
        ]);

        return [
            'guid'          => $guid,
            'status'        => $status,
            'ready'         => $status === 'ready',
            'hls_url'       => $this->getHlsUrl($guid),
            'thumbnail_url' => $this->getThumbnailUrl($guid),
            'duration'      => $data['length'] ?? null,
        ];
    }

    /**
     * Elimina un video de Bunny Stream.
     */
    public function deleteVideo(string $guid): bool
    {
        $response = Http::withHeaders(['AccessKey' => $this->apiKey])
            ->delete("{$this->baseUrl}/videos/{$guid}");

        return $response->successful();
    }

    /**
     * URL HLS para reproducción.
     */
    public function getHlsUrl(string $guid): string
    {
        return "https://{$this->cdnHostname}/{$guid}/playlist.m3u8";
    }

    /**
     * URL del thumbnail automático.
     */
    public function getThumbnailUrl(string $guid): string
    {
        return "https://{$this->cdnHostname}/{$guid}/thumbnail.jpg";
    }

    /**
     * URL del archivo original (disponible inmediatamente tras el upload TUS).
     * Requiere "Keep Original Files" habilitado en Bunny Stream Library Settings.
     */
    public function getOriginalUrl(string $guid): string
    {
        return "https://{$this->cdnHostname}/{$guid}/original";
    }

    /**
     * Mapea el status numérico de Bunny a string legible.
     * 0=Queued, 1=Processing, 2=Encoding, 3=Finished, 4=ResolutionProcessing, 5=Failed, 6=PresignedUploadStarted
     *
     * Nota: Status 4 (ResolutionProcessing) significa que Bunny está generando
     * resoluciones adicionales (480p, 720p, etc.) pero el video YA es reproducible.
     * El dashboard de Bunny lo muestra como listo en este punto.
     */
    public function mapStatus(int $statusCode): string
    {
        return match ($statusCode) {
            0       => 'queued',
            1, 2    => 'processing',
            3, 4    => 'ready',      // 3=Finished, 4=ResolutionProcessing (ambos son reproducibles)
            5       => 'error',
            6       => 'pendingupload',
            default => 'unknown',
        };
    }
}
