<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BunnyWebhookController extends Controller
{
    public function __construct(private BunnyStreamService $bunny) {}

    /**
     * Recibe notificaciones de Bunny Stream cuando cambia el estado de un video.
     * POST /webhooks/bunny-stream
     */
    public function handle(Request $request)
    {
        // Verificar secret si está configurado
        $secret = config('filesystems.bunny_stream.webhook_secret');
        if ($secret) {
            $receivedSignature = $request->header('Bunny-Signature') ?? '';
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expectedSignature, $receivedSignature)) {
                Log::warning('Bunny webhook: invalid signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->json()->all();
        $guid    = $payload['VideoGuid'] ?? null;
        $status  = $payload['Status']    ?? null; // 3 = ready, 4 = failed

        if (! $guid) {
            return response()->json(['ok' => true]);
        }

        $video = Video::where('bunny_video_id', $guid)->first();
        if (! $video) {
            Log::warning('Bunny webhook: video not found', ['guid' => $guid]);
            return response()->json(['ok' => true]);
        }

        $statusStr = $this->bunny->mapStatus((int) $status);
        $updates   = ['bunny_status' => $statusStr];

        if ($statusStr === 'ready') {
            $updates['bunny_hls_url']    = $this->bunny->getHlsUrl($guid);
            $updates['bunny_thumbnail']  = $this->bunny->getThumbnailUrl($guid);
            $updates['processing_status'] = 'completed';
            $updates['status']            = 'active';
        } elseif ($statusStr === 'error') {
            $updates['processing_status'] = 'failed';
        }

        $video->update($updates);

        Log::info('Bunny webhook processed', [
            'video_id' => $video->id,
            'guid'     => $guid,
            'status'   => $statusStr,
        ]);

        return response()->json(['ok' => true]);
    }
}
