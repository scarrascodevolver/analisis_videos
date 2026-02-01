<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlayersSeeder extends Seeder
{
    public function run()
    {
        // Jugadores de Los Troncos - Primera Línea (Forwards)
        $players = [
            // Primera Línea (Front Row)
            [
                'name' => 'Diego Morales',
                'email' => 'diego.morales@lostroncos.cl',
                'position' => 'Hooker (#2)',
                'experience' => 'avanzado',
            ],
            [
                'name' => 'Sebastián Torres',
                'email' => 'sebastian.torres@lostroncos.cl',
                'position' => 'Prop Izquierdo (#1)',
                'experience' => 'profesional',
            ],
            [
                'name' => 'Felipe Castillo',
                'email' => 'felipe.castillo@lostroncos.cl',
                'position' => 'Prop Derecho (#3)',
                'experience' => 'avanzado',
            ],

            // Segunda Línea (Second Row)
            [
                'name' => 'Matías Herrera',
                'email' => 'matias.herrera@lostroncos.cl',
                'position' => 'Segunda Línea (#4)',
                'experience' => 'profesional',
            ],
            [
                'name' => 'Gonzalo Ruiz',
                'email' => 'gonzalo.ruiz@lostroncos.cl',
                'position' => 'Segunda Línea (#5)',
                'experience' => 'avanzado',
            ],

            // Tercera Línea (Back Row)
            [
                'name' => 'Cristóbal Mendoza',
                'email' => 'cristobal.mendoza@lostroncos.cl',
                'position' => 'Ala Ciega (#6)',
                'experience' => 'profesional',
            ],
            [
                'name' => 'Nicolás Vargas',
                'email' => 'nicolas.vargas@lostroncos.cl',
                'position' => 'Ala Abierta (#7)',
                'experience' => 'avanzado',
            ],
            [
                'name' => 'Rodrigo Silva',
                'email' => 'rodrigo.silva@lostroncos.cl',
                'position' => 'Octavo (#8)',
                'experience' => 'profesional',
            ],

            // Medio Scrum y Apertura
            [
                'name' => 'Ignacio Paredes',
                'email' => 'ignacio.paredes@lostroncos.cl',
                'position' => 'Medio Scrum (#9)',
                'experience' => 'profesional',
            ],
            [
                'name' => 'Tomás Reyes',
                'email' => 'tomas.reyes@lostroncos.cl',
                'position' => 'Apertura (#10)',
                'experience' => 'profesional',
            ],

            // Tres Cuartos (Backs)
            [
                'name' => 'Joaquín Guerrero',
                'email' => 'joaquin.guerrero@lostroncos.cl',
                'position' => 'Ala Izquierda (#11)',
                'experience' => 'avanzado',
            ],
            [
                'name' => 'Andrés Campos',
                'email' => 'andres.campos@lostroncos.cl',
                'position' => 'Centro Interior (#12)',
                'experience' => 'profesional',
            ],
            [
                'name' => 'Maximiliano León',
                'email' => 'maximiliano.leon@lostroncos.cl',
                'position' => 'Centro Exterior (#13)',
                'experience' => 'avanzado',
            ],
            [
                'name' => 'Vicente Soto',
                'email' => 'vicente.soto@lostroncos.cl',
                'position' => 'Ala Derecha (#14)',
                'experience' => 'avanzado',
            ],
            [
                'name' => 'Gabriel Miranda',
                'email' => 'gabriel.miranda@lostroncos.cl',
                'position' => 'Fullback (#15)',
                'experience' => 'profesional',
            ],

            // Suplentes Forwards
            [
                'name' => 'Pablo Contreras',
                'email' => 'pablo.contreras@lostroncos.cl',
                'position' => 'Hooker/Prop Suplente',
                'experience' => 'intermedio',
            ],
            [
                'name' => 'Javier Santander',
                'email' => 'javier.santander@lostroncos.cl',
                'position' => 'Segunda Línea Suplente',
                'experience' => 'intermedio',
            ],
            [
                'name' => 'Claudio Ramírez',
                'email' => 'claudio.ramirez@lostroncos.cl',
                'position' => 'Tercera Línea Suplente',
                'experience' => 'avanzado',
            ],

            // Suplentes Backs
            [
                'name' => 'Francisco Muñoz',
                'email' => 'francisco.munoz@lostroncos.cl',
                'position' => 'Medio/Apertura Suplente',
                'experience' => 'intermedio',
            ],
            [
                'name' => 'Bastián Espinoza',
                'email' => 'bastian.espinoza@lostroncos.cl',
                'position' => 'Tres Cuartos Suplente',
                'experience' => 'intermedio',
            ],

            // Juveniles prometedores
            [
                'name' => 'Martín González',
                'email' => 'martin.gonzalez@lostroncos.cl',
                'position' => 'Apertura Juvenil',
                'experience' => 'principiante',
            ],
            [
                'name' => 'Bruno Aguirre',
                'email' => 'bruno.aguirre@lostroncos.cl',
                'position' => 'Ala/Centro Juvenil',
                'experience' => 'principiante',
            ],
        ];

        foreach ($players as $playerData) {
            $user = User::create([
                'name' => $playerData['name'],
                'email' => $playerData['email'],
                'password' => Hash::make('password'),
                'phone' => '+569'.rand(10000000, 99999999),
                'role' => 'jugador',
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'position' => $playerData['position'],
                'club_team_organization' => 'Los Troncos Rugby Club',
                'division_category' => 'Primera División',
            ]);
        }
    }
}
