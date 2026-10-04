<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReserveRequest;
use App\Services\ReserveService;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ReserveController extends Controller
{
    #[OA\Post(
        path: '/api/reserves',
        summary: 'Cria uma reserva',
        description: 'Cria uma nova reserva após validar o hotel, o quarto, o período informado e a disponibilidade do quarto.',
        tags: ['Reserves'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'hotel_id',
                    'room_id',
                    'check_in',
                    'check_out',
                    'total'
                ],
                properties: [
                    new OA\Property(
                        property: 'hotel_id',
                        type: 'integer',
                        example: 1,
                        description: 'ID do hotel.'
                    ),
                    new OA\Property(
                        property: 'room_id',
                        type: 'integer',
                        example: 1,
                        description: 'ID do quarto.'
                    ),
                    new OA\Property(
                        property: 'check_in',
                        type: 'string',
                        format: 'date',
                        example: '2026-10-10',
                        description: 'Data de entrada.'
                    ),
                    new OA\Property(
                        property: 'check_out',
                        type: 'string',
                        format: 'date',
                        example: '2026-10-15',
                        description: 'Data de saída.'
                    ),
                    new OA\Property(
                        property: 'total',
                        type: 'number',
                        format: 'float',
                        example: 500.00,
                        description: 'Valor total da reserva.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Reserva criada com sucesso.'
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos, quarto incompatível com o hotel ou quarto indisponível.'
            ),
        ]
    )]
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
            Log::warning('Tentativa de reserva com quarto pertencente a outro hotel.', [
                'hotel_id' => $data['hotel_id'],
                'room_id' => $data['room_id'],
            ]);

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
            Log::warning('Tentativa de reserva em quarto já ocupado.', [
                'room_id' => $data['room_id'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
            ]);

            return response()->json([
                'message' => 'O quarto não está disponível para o período informado.',
            ], 422);
        }

        $reserve = $reserveService->create($data);

        Log::info('Reserva criada.', [
            'reserve_id' => $reserve->id,
            'hotel_id' => $reserve->hotel_id,
            'room_id' => $reserve->room_id,
            'check_in' => $reserve->check_in->toDateString(),
            'check_out' => $reserve->check_out->toDateString(),
        ]);

        return response()->json([
            'message' => 'Reserva criada com sucesso.',
            'data' => $reserve,
        ], 201);
    }
}
