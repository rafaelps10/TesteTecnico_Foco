<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use OpenApi\Attributes as OA;

class RoomController extends Controller
{
    #[OA\Get(
        path: '/api/rooms',
        summary: 'Lista os quartos',
        description: 'Retorna todos os quartos cadastrados, incluindo o hotel relacionado.',
        tags: ['Rooms'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de quartos retornada com sucesso'
            )
        ]
    )]
    public function index()
    {
        $rooms = Room::with('hotel')->get();

        return response()->json([
            'data' => $rooms,
        ]);
    }

    #[OA\Post(
        path: '/api/rooms',
        summary: 'Cadastra um quarto',
        description: 'Cria um novo quarto associado a um hotel existente.',
        tags: ['Rooms'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['hotel_id', 'name'],
                properties: [
                    new OA\Property(
                        property: 'hotel_id',
                        type: 'integer',
                        example: 1,
                        description: 'ID do hotel ao qual o quarto pertence.'
                    ),
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Room 3 Hotel 1',
                        description: 'Nome do quarto.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Quarto criado com sucesso.'
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos ou hotel inexistente.'
            ),
        ]
    )]
    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return response()->json([
            'data' => $room->load('hotel'),
        ], 201);
    }

    #[OA\Get(
        path: '/api/rooms/{id}',
        summary: 'Consulta um quarto',
        description: 'Retorna os dados de um quarto específico, incluindo o hotel relacionado.',
        tags: ['Rooms'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID do quarto.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Quarto encontrado com sucesso.'
            ),
            new OA\Response(
                response: 404,
                description: 'Quarto não encontrado.'
            ),
        ]
    )]
    public function show(string $id)
    {
        $room = Room::with('hotel')->find($id);

        if ($room === null) {
            return response()->json([
                'message' => 'Quarto não encontrado.',
            ], 404);
        }

        return response()->json([
            'data' => $room,
        ]);
    }

    #[OA\Put(
        path: '/api/rooms/{id}',
        summary: 'Atualiza um quarto',
        description: 'Atualiza os dados de um quarto existente.',
        tags: ['Rooms'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID do quarto.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['hotel_id', 'name'],
                properties: [
                    new OA\Property(
                        property: 'hotel_id',
                        type: 'integer',
                        example: 1,
                        description: 'ID do hotel ao qual o quarto pertence.'
                    ),
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Room 1 Hotel 1 - Updated',
                        description: 'Novo nome do quarto.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Quarto atualizado com sucesso.'
            ),
            new OA\Response(
                response: 404,
                description: 'Quarto não encontrado.'
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos ou hotel inexistente.'
            ),
        ]
    )]
    public function update(UpdateRoomRequest $request, string $id)
    {
        $room = Room::find($id);

        if ($room === null) {
            return response()->json([
                'message' => 'Quarto não encontrado.',
            ], 404);
        }

        $room->update($request->validated());

        return response()->json([
            'data' => $room->load('hotel'),
        ]);
    }

    #[OA\Delete(
        path: '/api/rooms/{id}',
        summary: 'Exclui um quarto',
        description: 'Exclui um quarto existente.',
        tags: ['Rooms'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID do quarto.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Quarto excluído com sucesso.'
            ),
            new OA\Response(
                response: 404,
                description: 'Quarto não encontrado.'
            ),
        ]
    )]
    public function destroy(string $id)
    {
        $room = Room::find($id);

        if ($room === null) {
            return response()->json([
                'message' => 'Quarto não encontrado.',
            ], 404);
        }

        $room->delete();

        return response()->json([
            'message' => 'Quarto excluído com sucesso.',
        ]);
    }
}
