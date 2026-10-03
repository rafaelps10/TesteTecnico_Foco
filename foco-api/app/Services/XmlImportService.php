<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class XmlImportService
{
    public function import(): void
    {
        DB::transaction(function () {
            $this->importHotels();
	    $this->importRooms();
        });
    }

    private function importHotels(): void
    {
        $xmlPath = dirname(base_path())
            . DIRECTORY_SEPARATOR
            . 'database'
            . DIRECTORY_SEPARATOR
            . 'xml'
            . DIRECTORY_SEPARATOR
            . 'hotels.xml';

        if (!file_exists($xmlPath)) {
            throw new \RuntimeException("Arquivo não encontrado: {$xmlPath}");
        }

        $xml = simplexml_load_file($xmlPath);

        if ($xml === false) {
            throw new \RuntimeException(
                "Não foi possível ler o XML de hotéis."
            );
        }

        foreach ($xml->Hotel as $hotelXml) {
            $hotelId = (int) $hotelXml['id'];

            $hotel = Hotel::find($hotelId);

            if ($hotel === null) {
                $hotel = new Hotel();
                $hotel->id = $hotelId;
            }

            $hotel->name = (string) $hotelXml->Name;

            $hotel->save();
        }
    }
	private function importRooms(): void
{
    $xmlPath = dirname(base_path())
        . DIRECTORY_SEPARATOR
        . 'database'
        . DIRECTORY_SEPARATOR
        . 'xml'
        . DIRECTORY_SEPARATOR
        . 'rooms.xml';

    if (!file_exists($xmlPath)) {
        throw new \RuntimeException("Arquivo não encontrado: {$xmlPath}");
    }

    $xml = simplexml_load_file($xmlPath);

    if ($xml === false) {
        throw new \RuntimeException(
            "Não foi possível ler o XML de quartos."
        );
    }

    foreach ($xml->Room as $roomXml) {
        $roomId = (int) $roomXml['id'];
        $hotelId = (int) $roomXml['hotelCode'];

        $hotel = Hotel::find($hotelId);

        if ($hotel === null) {
            throw new \RuntimeException(
                "Hotel {$hotelId} não encontrado para o quarto {$roomId}."
            );
        }

        $room = Room::find($roomId);

        if ($room === null) {
            $room = new Room();
            $room->id = $roomId;
        }

        $room->hotel_id = $hotelId;
        $room->name = (string) $roomXml->Name;

        $room->save();
    }
}
}