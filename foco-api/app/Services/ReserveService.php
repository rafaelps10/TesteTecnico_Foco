<?php

namespace App\Services;

use App\Models\Reserve;
use App\Models\Room;

class ReserveService
{
    public function roomBelongsToHotel(
        int $roomId,
        int $hotelId
    ): bool {
        return Room::where('id', $roomId)
            ->where('hotel_id', $hotelId)
            ->exists();
    }

    public function hasConflict(
        int $roomId,
        string $checkIn,
        string $checkOut
    ): bool {
        return Reserve::where('room_id', $roomId)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->exists();
    }

    public function create(array $data): Reserve
    {
        return Reserve::create([
            'hotel_id' => $data['hotel_id'],
            'room_id' => $data['room_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'total' => $data['total'],
        ]);
    }
}
