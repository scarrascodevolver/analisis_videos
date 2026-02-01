<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ShowUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:show {--role=} {--passwords}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostrar usuarios del sistema con sus credenciales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== USUARIOS DEL SISTEMA RUGBY "LOS TRONCOS" ===');
        $this->newLine();

        // Filtrar por rol si se especifica
        $role = $this->option('role');
        $showPasswords = $this->option('passwords');

        $query = User::with('profile');

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('role')->orderBy('name')->get();

        if ($users->isEmpty()) {
            $this->error('No se encontraron usuarios');

            return;
        }

        // Agrupar por rol
        $usersByRole = $users->groupBy('role');

        foreach ($usersByRole as $role => $roleUsers) {
            $this->info('🏉 '.strtoupper($role).'S:');
            $this->newLine();

            foreach ($roleUsers as $user) {
                $this->line("👤 <fg=green>{$user->name}</>");
                $this->line("   📧 Email: <fg=yellow>{$user->email}</>");

                if ($showPasswords) {
                    $password = $this->getKnownPassword($user->email);
                    if ($password) {
                        $this->line("   🔑 Password: <fg=red>{$password}</>");
                    } else {
                        $this->line('   🔑 Password: <fg=gray>No disponible (verificar seeder)</>');
                    }
                }

                if ($user->profile) {
                    $position = $user->profile->position ?? 'Sin posición';
                    $this->line("   💼 Posición: <fg=cyan>{$position}</>");

                    if ($user->profile->category) {
                        $this->line("   🏆 Categoría: <fg=magenta>{$user->profile->category->name}</>");
                    }
                }

                $this->newLine();
            }
        }

        if (! $showPasswords) {
            $this->warn('💡 Usa --passwords para ver las contraseñas');
        }

        $this->info('Total usuarios: '.$users->count());
    }

    /**
     * Get known password from seeder data
     */
    private function getKnownPassword($email)
    {
        $passwords = [
            'jere@clublostroncos.cl' => 'jere2025',
            'juancruz@clublostroncos.cl' => 'juancruz2025',
            'valentin@clublostroncos.cl' => 'valentin2025',
            'victor@clublostroncos.cl' => 'victor2025',
            'dt@clublostroncos.cl' => 'roberto2025',
            'juancarlos@clublostroncos.cl' => 'juancarlos2025',
            'jugador@rugby.com' => 'password123',
            'scout@rugby.com' => 'password123',
            'aficionado@rugby.com' => 'password123',
        ];

        return $passwords[$email] ?? null;
    }
}
