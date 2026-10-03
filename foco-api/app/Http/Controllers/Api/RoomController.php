<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('hotel')->get();

        return response()->json([
            'data' => $rooms,
        ]);
    }

    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return response()->json([
            'data' => $room->load('hotel'),
        ], 201);
    }

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
