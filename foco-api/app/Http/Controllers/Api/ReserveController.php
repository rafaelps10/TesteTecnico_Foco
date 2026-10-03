<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReserveRequest;
use App\Services\ReserveService;

class ReserveController extends Controller
{
    public function store(
        StoreReserveRequest $request,
        ReserveService $reserveService
    ) {
        $data = $request->validated();

        $roomBelongsToHotel = $reserveService->roomBelongsToHotel(
            $data['room_id'],
            $data['hotel_id']
        );

        if (!$roomBelongsToHotel) {
            return response()->json([
                'message' => 'O quarto informado não pertence ao hotel informado.',
            ], 422);
        }

        $hasConflict = $reserveService->hasConflict(
            $data['room_id'],
            $data['check_in'],
            $data['check_out']
        );

        if ($hasConflict) {
            return response()->json([
                'message' => 'O quarto não está disponível para o período informado.',
            ], 422);
        }

        $reserve = $reserveService->create($data);

        return response()->json([
            'message' => 'Reserva criada com sucesso.',
            'data' => $reserve,
        ], 201);
    }
}