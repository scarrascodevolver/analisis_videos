<?php

namespace App\Console\Commands;

use App\Models\VideoAssignment;
use Illuminate\Console\Command;

class CleanOrphanAssignments extends Command
{
    protected $signature = 'assignments:clean-orphans';

    protected $description = 'Elimina asignaciones de videos que ya no existen';

    public function handle()
    {
        $this->info('Buscando asignaciones huérfanas...');

        // Buscar asignaciones cuyos videos no existen
        $orphanAssignments = VideoAssignment::whereDoesntHave('video')->get();

        $count = $orphanAssignments->count();

        if ($count === 0) {
            $this->info('✅ No se encontraron asignaciones huérfanas.');

            return 0;
        }

        $this->warn("Se encontraron {$count} asignaciones huérfanas.");

        if ($this->confirm('¿Deseas eliminar estas asignaciones?', true)) {
            VideoAssignment::whereDoesntHave('video')->delete();
            $this->info("✅ Se eliminaron {$count} asignaciones huérfanas correctamente.");
        } else {
            $this->info('Operación cancelada.');
        }

        return 0;
    }
}
