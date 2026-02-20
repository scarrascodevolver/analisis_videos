<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncBunnyWebhooks extends Command
{
    protected $signature   = 'bunny:sync-webhooks {--dry-run : Solo mostrar sin aplicar}';
    protected $description = 'Configura el webhook URL en todas las libraries de Bunny existentes';

    public function handle(): void
    {
        $accountApiKey = config('filesystems.bunny_stream.account_api_key');
        $webhookUrl    = config('app.url') . '/webhooks/bunny-stream';
        $dryRun        = $this->option('dry-run');

        $orgs = Organization::whereNotNull('bunny_library_id')->get();

        if ($orgs->isEmpty()) {
            $this->info('No hay organizaciones con Bunny library configurada.');
            return;
        }

        $this->info("Configurando webhook: {$webhookUrl}");
        $this->line('');

        foreach ($orgs as $org) {
            $this->line("  {$org->name} (library: {$org->bunny_library_id})");

            if ($dryRun) {
                continue;
            }

            try {
                $res = Http::withHeaders(['AccessKey' => $accountApiKey])
                    ->post("https://api.bunny.net/videolibrary/{$org->bunny_library_id}", [
                        'WebhookUrl' => $webhookUrl,
                    ]);

                if ($res->successful()) {
                    $this->info("    ✓ Webhook configurado");
                } else {
                    $this->error("    ✗ Error: " . $res->body());
                }
            } catch (\Throwable $e) {
                $this->error("    ✗ Excepción: " . $e->getMessage());
                Log::error("SyncBunnyWebhooks falló para org {$org->id}", ['error' => $e->getMessage()]);
            }
        }

        if ($dryRun) {
            $this->warn('Modo dry-run: ningún webhook fue configurado.');
        } else {
            $this->info('');
            $this->info("Completado para {$orgs->count()} organizaciones.");
        }
    }
}
