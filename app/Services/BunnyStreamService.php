<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BunnyStreamService
{
    private string $libraryId;

    private string $apiKey;

    private ?string $cdnHostname;

    private string $baseUrl;

    /**
     * @param  string|null  $libraryId  Library-level ID.  Falls back to global config.
     * @param  string|null  $apiKey  Library-level API key.  Falls back to global config.
     * @param  string|null  $cdnHostname  Pull-zone hostname (e.g. "vz-abc123.b-cdn.net").
     *                                    Falls back to global config.
     */
    public function __construct(
        ?string $libraryId = null,
        ?string $apiKey = null,
        ?string $cdnHostname = null,
    ) {
        $this->libraryId = $libraryId ?? config('filesystems.bunny_stream.library_id');
        $this->apiKey = $apiKey ?? config('filesystems.bunny_stream.api_key');
        $this->cdnHostname = $cdnHostname ?? config('filesystems.bunny_stream.cdn_hostname');
        $this->baseUrl = "https://video.bunnycdn.com/library/{$this->libraryId}";
    }

    /**
     * Build a BunnyStreamService instance scoped to a specific organization.
     *
     * If the organization has its own library credentials stored in the DB those
     * are used; otherwise the service falls back to the global .env config so
     * that existing organizations keep working without any data migration.
     */
    public static function forOrganization(Organization $org): self
    {
        // Usar credenciales de la org si tiene library_id + api_key,
        // aunque cdn_hostname sea null (se resuelve después del upload).
        if ($org->bunny_library_id && $org->bunny_api_key) {
            return new self(
                $org->bunny_library_id,
                $org->bunny_api_key,
                $org->bunny_cdn_hostname, // puede ser null si aún no está provisionado
            );
        }

        // Fallback: credenciales globales (orgs sin library asignada).
        return new self;
    }

    /**
     * Create a new Bunny Stream video library via the account-level API.
     *
     * Uses BUNNY_ACCOUNT_API_KEY (different from the per-library API key).
     *
     * Returns an array with the fields needed to persist on the Organization:
     *   - library_id   (string)
     *   - api_key      (string)
     *   - cdn_hostname (string|null)  — null when the pull-zone is not yet provisioned
     *
     * @throws \RuntimeException when the Bunny API request fails.
     */
    public static function createLibrary(string $name): array
    {
        $accountApiKey = config('filesystems.bunny_stream.account_api_key');

        $response = Http::withHeaders(['AccessKey' => $accountApiKey])
            ->post('https://api.bunny.net/videolibrary', [
                'Name'               => $name,
                // NY es la región más cercana a LATAM disponible en Bunny storage.
                // El streaming HLS llega via CDN global (incluye PoPs en LATAM).
                'ReplicationRegions' => ['NY'],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Failed to create Bunny library: '.$response->body()
            );
        }

        $data = $response->json();

        $libraryId  = $data['Id'];
        $apiKey     = $data['ApiKey'];
        $pullZoneId = $data['PullZoneId'] ?? null;

        // Bunny no devuelve el hostname directamente al crear la library.
        // Hay que consultarlo desde el Pull Zone asociado.
        $cdnHostname = null;
        if ($pullZoneId) {
            try {
                $pzResponse = Http::withHeaders(['AccessKey' => $accountApiKey])
                    ->get("https://api.bunny.net/pullzone/{$pullZoneId}");

                if ($pzResponse->successful()) {
                    $pzData = $pzResponse->json();
                    $cdnHostname = $pzData['Hostnames'][0]['Value'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('Could not fetch Bunny pull zone hostname', [
                    'pull_zone_id' => $pullZoneId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Configurar la library: webhook + resoluciones + archivos originales
        $webhookUrl = config('app.url') . '/webhooks/bunny-stream';
        try {
            Http::withHeaders(['AccessKey' => $accountApiKey])
                ->post("https://api.bunny.net/videolibrary/{$libraryId}", [
                    'WebhookUrl'        => $webhookUrl,
                    // Solo 480p, 720p y 1080p — evitar 240p/360p (baja calidad)
                    // y 1440p/2160p (innecesario para análisis de video rugby)
                    'EnabledResolutions' => '480p,720p,1080p',
                    // Mantener archivo original para acceso directo post-upload (TUS)
                    'KeepOriginalFiles'  => true,
                ]);

            Log::info('Bunny library configurada', [
                'library_id'  => $libraryId,
                'webhook_url' => $webhookUrl,
                'resolutions' => '480p,720p,1080p',
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo configurar la library de Bunny', [
                'library_id' => $libraryId,
                'error'      => $e->getMessage(),
            ]);
        }

        Log::info('Bunny library created', [
            'library_id'  => $libraryId,
            'pull_zone_id' => $pullZoneId,
            'cdn_hostname' => $cdnHostname,
        ]);

        return [
            'library_id'  => (string) $libraryId,
            'api_key'     => $apiKey,
            'cdn_hostname' => $cdnHostname,
        ];
    }

    /**
     * Elimina una library completa de Bunny Stream via la API de cuenta.
     * Usar al eliminar una organización.
     *
     * @throws \RuntimeException cuando la API de Bunny falla.
     */
    public static function deleteLibrary(string $libraryId): void
    {
        $accountApiKey = config('filesystems.bunny_stream.account_api_key');

        $response = Http::withHeaders(['AccessKey' => $accountApiKey])
            ->delete("https://api.bunny.net/videolibrary/{$libraryId}");

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Failed to delete Bunny library {$libraryId}: " . $response->body()
            );
        }

        Log::info('Bunny library deleted', ['library_id' => $libraryId]);
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
                'Failed to create Bunny Stream video: '.$response->body()
            );
        }

        $data = $response->json();
        $guid = $data['guid'];
        $expire = time() + 3600; // 1 hora para completar el upload

        // Firma HMAC-SHA256 requerida por Bunny TUS
        $signature = hash('sha256', $this->libraryId.$this->apiKey.$expire.$guid);

        return [
            'guid' => $guid,
            'upload_url' => 'https://video.bunnycdn.com/tusupload',
            'signature' => $signature,
            'expire' => $expire,
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
            throw new \RuntimeException('Failed to get Bunny video details: '.$response->body());
        }

        $data = $response->json();
        $rawStatus = $data['status'] ?? 0;
        $status = $this->mapStatus($rawStatus);

        Log::debug('Bunny video status', [
            'guid' => $guid,
            'raw_status' => $rawStatus,
            'mapped' => $status,
            'length' => $data['length'] ?? null,
        ]);

        return [
            'guid' => $guid,
            'status' => $status,
            'ready' => $status === 'ready',
            'hls_url' => $this->getHlsUrl($guid),
            'thumbnail_url' => $this->getThumbnailUrl($guid),
            'duration' => $data['length'] ?? null,
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
        $this->assertCdnHostname();
        return "https://{$this->cdnHostname}/{$guid}/playlist.m3u8";
    }

    /**
     * URL del thumbnail automático.
     */
    public function getThumbnailUrl(string $guid): string
    {
        $this->assertCdnHostname();
        return "https://{$this->cdnHostname}/{$guid}/thumbnail.jpg";
    }

    /**
     * URL del archivo original (disponible inmediatamente tras el upload TUS).
     * Requiere "Keep Original Files" habilitado en Bunny Stream Library Settings.
     */
    public function getOriginalUrl(string $guid): string
    {
        $this->assertCdnHostname();
        return "https://{$this->cdnHostname}/{$guid}/original";
    }

    /**
     * R-01: Garantiza que cdnHostname está configurado antes de construir URLs CDN.
     * Lanza RuntimeException con instrucciones claras en lugar de generar URLs inválidas.
     */
    private function assertCdnHostname(): void
    {
        if (! $this->cdnHostname) {
            throw new \RuntimeException(
                "CDN hostname not configured for library {$this->libraryId}. " .
                "Run 'php artisan bunny:sync-webhooks' or check the organization's bunny_cdn_hostname."
            );
        }
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
            0 => 'queued',
            1, 2 => 'processing',
            3, 4 => 'ready',      // 3=Finished, 4=ResolutionProcessing (ambos son reproducibles)
            5 => 'error',
            6 => 'pendingupload',
            default => 'unknown',
        };
    }
}
