<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $admins = [
            ['name' => 'Admin Principal', 'email' => 'admin@test.com'],
            ['name' => 'Ana Martínez',    'email' => 'ana.admin@test.com'],
        ];

        $agentes = [
            ['name' => 'Carlos Rodríguez', 'email' => 'carlos@test.com'],
            ['name' => 'Laura Gómez',      'email' => 'laura@test.com'],
            ['name' => 'Andrés Torres',    'email' => 'andres@test.com'],
            ['name' => 'Sofía Herrera',    'email' => 'sofia@test.com'],
            ['name' => 'Miguel Ángel',     'email' => 'miguel@test.com'],
            ['name' => 'Valentina Cruz',   'email' => 'valentina@test.com'],
        ];

        $clientes = [
            ['name' => 'Juan Pérez',       'email' => 'juan@test.com'],
            ['name' => 'María López',      'email' => 'maria@test.com'],
            ['name' => 'Pedro Sánchez',    'email' => 'pedro@test.com'],
            ['name' => 'Lucía Ramírez',    'email' => 'lucia@test.com'],
            ['name' => 'Diego Morales',    'email' => 'diego@test.com'],
            ['name' => 'Camila Vargas',    'email' => 'camila@test.com'],
            ['name' => 'Sebastián Ruiz',   'email' => 'sebastian@test.com'],
            ['name' => 'Isabella Díaz',    'email' => 'isabella@test.com'],
            ['name' => 'Felipe Castillo',  'email' => 'felipe@test.com'],
            ['name' => 'Natalia Ortiz',    'email' => 'natalia@test.com'],
            ['name' => 'Juliana Silva',    'email' => 'juliana@test.com'],
            ['name' => 'Mateo Jiménez',    'email' => 'mateo@test.com'],
        ];

        foreach ($admins as $data) {
            $u = User::firstOrCreate(['email' => $data['email']], array_merge($data, ['password' => $password]));
            $u->syncRoles(['admin']);
        }

        foreach ($agentes as $data) {
            $u = User::firstOrCreate(['email' => $data['email']], array_merge($data, ['password' => $password]));
            $u->syncRoles(['agente']);
        }

        foreach ($clientes as $data) {
            $u = User::firstOrCreate(['email' => $data['email']], array_merge($data, ['password' => $password]));
            $u->syncRoles(['cliente']);
        }

        $this->command->info('Usuarios creados: 2 admins, 6 agentes, 12 clientes (password: password)');
    }
}
