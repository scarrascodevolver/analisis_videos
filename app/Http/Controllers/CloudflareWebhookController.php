<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\CloudflareStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CloudflareWebhookController extends Controller
{
    public function __construct(private CloudflareStreamService $stream) {}

    /**
     * Recibe notificación de Cloudflare cuando termina de procesar un video.
     *
     * POST /webhooks/cloudflare-stream
     */
    public function handle(Request $request)
    {
        // Verificar firma del webhook si está configurado el secret
        $webhookSecret = config('filesystems.cloudflare_stream.webhook_secret');
        if ($webhookSecret) {
            $signature = $request->header('Webhook-Signature');
            if (! $this->verifySignature($request->getContent(), $signature, $webhookSecret)) {
                Log::warning('Cloudflare webhook signature invalid');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->json()->all();
        $uid     = $payload['uid'] ?? null;
        $state   = $payload['status']['state'] ?? null;

        if (! $uid || ! $state) {
            return response()->json(['error' => 'Missing uid or state'], 400);
        }

        Log::info("Cloudflare Stream webhook received", ['uid' => $uid, 'state' => $state]);

        $video = Video::where('cloudflare_uid', $uid)->first();

        if (! $video) {
            Log::warning("Cloudflare webhook: video not found for uid {$uid}");
            return response()->json(['ok' => true]); // 200 para que Cloudflare no reintente
        }

        $updates = ['cloudflare_status' => $state];

        if ($state === 'ready') {
            $updates['cloudflare_playback_url'] = $payload['playback']['hls'] ?? $this->stream->getHlsUrl($uid);
            $updates['cloudflare_thumbnail']    = $payload['thumbnail'] ?? $this->stream->getThumbnailUrl($uid);
            $updates['processing_status']       = 'completed';
            $updates['status']                  = 'active';

            if (isset($payload['duration'])) {
                $updates['duration'] = (int) $payload['duration'];
            }

            Log::info("Video {$video->id} ready on Cloudflare Stream", ['uid' => $uid]);
        }

        if ($state === 'error') {
            $updates['processing_status'] = 'failed';
            Log::error("Video {$video->id} failed on Cloudflare Stream", [
                'uid'   => $uid,
                'error' => $payload['status']['errorReasonCode'] ?? 'unknown',
            ]);
        }

        $video->update($updates);

        return response()->json(['ok' => true]);
    }

    private function verifySignature(string $body, ?string $signature, string $secret): bool
    {
        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $body, $secret);

        return hash_equals($expected, $signature);
    }
}
