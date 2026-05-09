<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class TicketSeeder extends Seeder
{
    private const TOTAL   = 550;
    private const CHUNK   = 50;

    private const STATUSES = [
        'open'        => 30,
        'in_progress' => 25,
        'resolved'    => 25,
        'closed'      => 20,
    ];

    private const PRIORITIES = [
        'low'      => 20,
        'medium'   => 35,
        'high'     => 30,
        'critical' => 15,
    ];

    private const TITLES = [
        'Solicitud de cita de seguimiento',
        'Problema con acceso al portal del paciente',
        'Cancelación de sesión programada',
        'Solicitud de historia clínica',
        'Inconveniente con facturación del servicio',
        'Reporte de error en plataforma de telemedicina',
        'Solicitud de cambio de terapeuta asignado',
        'Dificultad para acceder a resultados de evaluación',
        'Consulta sobre cobertura del seguro médico',
        'Requerimiento de certificado de asistencia',
        'Fallo en videollamada de sesión virtual',
        'Solicitud de referido a especialista',
        'Reclamo por cobro incorrecto',
        'Solicitud de reagendamiento urgente',
        'Problema con notificaciones del sistema',
        'Pérdida de contraseña del portal',
        'Solicitud de copia de diagnóstico',
        'Error al completar formulario de ingreso',
        'Solicitud de atención domiciliaria',
        'Pregunta sobre protocolo de emergencia',
    ];

    public function run(): void
    {
        $faker = Faker::create('es_CO');

        $clienteIds = User::role('cliente')->pluck('id')->toArray();
        $agenteIds  = User::role('agente')->pluck('id')->toArray();
        $adminIds   = User::role('admin')->pluck('id')->toArray();
        $assignable = array_merge($agenteIds, $adminIds);

        if (empty($clienteIds) || empty($assignable)) {
            $this->command->error('Ejecuta primero UserSeeder.');
            return;
        }

        $statuses   = $this->expandWeighted(self::STATUSES,   self::TOTAL);
        $priorities = $this->expandWeighted(self::PRIORITIES, self::TOTAL);
        shuffle($statuses);
        shuffle($priorities);

        $batch = [];
        $now   = now();

        for ($i = 0; $i < self::TOTAL; $i++) {
            $status   = $statuses[$i];
            $priority = $priorities[$i];

            $createdAt = $faker->dateTimeBetween('-8 months', '-1 day');
            $dueDate   = $this->randomDueDate($faker, $status, $createdAt);
            $assigned  = $faker->boolean(70) ? $faker->randomElement($assignable) : null;

            $batch[] = [
                'title'       => $faker->randomElement(self::TITLES),
                'description' => $faker->paragraphs(rand(1, 3), true),
                'priority'    => $priority,
                'status'      => $status,
                'creator_id'  => $faker->randomElement($clienteIds),
                'assigned_to' => $assigned,
                'due_date'    => $dueDate,
                'created_at'  => $createdAt,
                'updated_at'  => $faker->dateTimeBetween($createdAt, $now),
            ];

            if (count($batch) === self::CHUNK) {
                DB::table('tickets')->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('tickets')->insert($batch);
        }

        $this->command->info(self::TOTAL . ' tickets creados correctamente.');
    }

    private function expandWeighted(array $weighted, int $total): array
    {
        $result = [];
        foreach ($weighted as $value => $pct) {
            $count = (int) round($total * $pct / 100);
            $result = array_merge($result, array_fill(0, $count, $value));
        }
        while (count($result) < $total) {
            $result[] = array_key_first($weighted);
        }
        return array_slice($result, 0, $total);
    }

    private function randomDueDate(\Faker\Generator $faker, string $status, \DateTime $createdAt): ?string
    {
        if ($faker->boolean(20)) {
            return null;
        }

        return match (true) {
            in_array($status, ['resolved', 'closed']) => $faker->dateTimeBetween($createdAt, 'now')->format('Y-m-d'),
            $faker->boolean(30)                       => $faker->dateTimeBetween('-2 months', '-1 day')->format('Y-m-d'),
            default                                   => $faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
        };
    }
}
