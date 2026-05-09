<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('dashboard retorna estructura correcta', function () {
    $user  = User::factory()->create();
    $token = auth('api')->login($user);

    Ticket::factory(3)->create(['status' => 'open',     'priority' => 'high']);
    Ticket::factory(2)->create(['status' => 'resolved', 'priority' => 'low']);

    $this->withToken($token)->getJson('/api/dashboard/stats')
         ->assertOk()
         ->assertJsonStructure([
             'total',
             'overdue',
             'by_status'   => ['open', 'in_progress', 'resolved', 'closed'],
             'by_priority' => ['low', 'medium', 'high', 'critical'],
         ])
         ->assertJsonPath('total', 5)
         ->assertJsonPath('by_status.open', 3)
         ->assertJsonPath('by_status.resolved', 2);
});

test('dashboard requiere autenticación', function () {
    $this->getJson('/api/dashboard/stats')->assertStatus(401);
});
