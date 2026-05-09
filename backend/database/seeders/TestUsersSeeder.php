<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $agente = User::firstOrCreate(
            ['email' => 'agente@test.com'],
            ['name' => 'Agente Test', 'password' => 'password']
        );
        $agente->syncRoles(['agente']);

        $cliente = User::firstOrCreate(
            ['email' => 'cliente@test.com'],
            ['name' => 'Cliente Test', 'password' => 'password']
        );
        $cliente->syncRoles(['cliente']);

        $this->command->info('Usuarios de prueba creados: agente@test.com / cliente@test.com (password: password)');
    }
}
