<?php

use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum');
});

test('cria uma reserva com dados válidos', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $room = Room::create([
        'hotel_id' => $hotel->id,
        'name' => 'Quarto Teste',
    ]);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-10',
        'check_out' => '2026-12-12',
        'total' => 500.00,
    ]);

    $response->assertStatus(201);

    $response->assertJson([
        'message' => 'Reserva criada com sucesso.',
    ]);

    $this->assertDatabaseHas('reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'total' => 500.00,
    ]);
});

test('não cria reserva quando o hotel não existe', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $room = Room::create([
        'hotel_id' => $hotel->id,
        'name' => 'Quarto Teste',
    ]);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => 9999,
        'room_id' => $room->id,
        'check_in' => '2026-12-10',
        'check_out' => '2026-12-12',
        'total' => 500.00,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'hotel_id',
    ]);

    $response->assertJson([
        'message' => 'O hotel informado não existe.',
    ]);
});

test('não cria reserva quando o quarto não existe', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => 9999,
        'check_in' => '2026-12-10',
        'check_out' => '2026-12-12',
        'total' => 500.00,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'room_id',
    ]);

    $response->assertJson([
        'message' => 'O quarto informado não existe.',
    ]);
});

test('não cria reserva quando o quarto não pertence ao hotel', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $outroHotel = Hotel::create([
        'name' => 'Outro Hotel',
    ]);

    $room = Room::create([
        'hotel_id' => $hotel->id,
        'name' => 'Quarto Teste',
    ]);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => $outroHotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-10',
        'check_out' => '2026-12-12',
        'total' => 500.00,
    ]);

    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'O quarto informado não pertence ao hotel informado.',
    ]);
});

test('não cria reserva quando a data de saída é anterior à data de entrada', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $room = Room::create([
        'hotel_id' => $hotel->id,
        'name' => 'Quarto Teste',
    ]);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-12',
        'check_out' => '2026-12-10',
        'total' => 500.00,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'check_out',
    ]);

    $response->assertJson([
        'message' => 'A data de saída deve ser posterior à data de entrada.',
    ]);
});

test('não cria reserva quando o valor total é negativo', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $room = Room::create([
        'hotel_id' => $hotel->id,
        'name' => 'Quarto Teste',
    ]);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-10',
        'check_out' => '2026-12-12',
        'total' => -100.00,
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors([
        'total',
    ]);

    $response->assertJson([
        'message' => 'O valor total não pode ser negativo.',
    ]);
});

test('não cria reserva quando o quarto já está reservado no período', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $room = Room::create([
        'hotel_id' => $hotel->id,
        'name' => 'Quarto Teste',
    ]);

    $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-10',
        'check_out' => '2026-12-12',
        'total' => 500.00,
    ])->assertStatus(201);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-11',
        'check_out' => '2026-12-13',
        'total' => 500.00,
    ]);

    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'O quarto não está disponível para o período informado.',
    ]);
});

test('cria reserva quando o quarto está disponível no período', function () {

    $hotel = Hotel::create([
        'name' => 'Hotel Teste',
    ]);

    $room = Room::create([
        'hotel_id' => $hotel->id,
        'name' => 'Quarto Teste',
    ]);

    $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-10',
        'check_out' => '2026-12-12',
        'total' => 500.00,
    ])->assertStatus(201);

    $response = $this->postJson('/api/reserves', [
        'hotel_id' => $hotel->id,
        'room_id' => $room->id,
        'check_in' => '2026-12-12',
        'check_out' => '2026-12-14',
        'total' => 500.00,
    ]);

    $response->assertStatus(201);

    $response->assertJson([
        'message' => 'Reserva criada com sucesso.',
    ]);
});
