<?php

namespace App\Services;

use App\Models\Daily;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Reserve;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XmlImportService
{
    public function import(): void
    {
        DB::transaction(function () {
            $this->importHotels();
            $this->importRooms();
            $this->importReserves();
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
            throw new \RuntimeException(
                "Arquivo não encontrado: {$xmlPath}"
            );
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
            throw new \RuntimeException(
                "Arquivo não encontrado: {$xmlPath}"
            );
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

    private function importReserves(): void
    {
        $xmlPath = dirname(base_path())
            . DIRECTORY_SEPARATOR
            . 'database'
            . DIRECTORY_SEPARATOR
            . 'xml'
            . DIRECTORY_SEPARATOR
            . 'reserves.xml';

        if (!file_exists($xmlPath)) {
            throw new \RuntimeException(
                "Arquivo não encontrado: {$xmlPath}"
            );
        }

        $xml = simplexml_load_file($xmlPath);

        if ($xml === false) {
            throw new \RuntimeException(
                "Não foi possível ler o XML de reservas."
            );
        }

        foreach ($xml->Reserve as $reserveXml) {
            $reserveId = (int) $reserveXml['id'];
            $hotelId = (int) $reserveXml['hotelCode'];
            $roomId = (int) $reserveXml['roomCode'];

            $hotel = Hotel::find($hotelId);

            if ($hotel === null) {
                throw new \RuntimeException(
                    "Hotel {$hotelId} não encontrado para a reserva {$reserveId}."
                );
            }

            $room = Room::find($roomId);

            if ($room === null) {
                throw new \RuntimeException(
                    "Quarto {$roomId} não encontrado para a reserva {$reserveId}."
                );
            }

            if ((int) $room->hotel_id !== $hotelId) {
                throw new \RuntimeException(
                    "O quarto {$roomId} não pertence ao hotel {$hotelId}."
                );
            }

            $reserve = Reserve::find($reserveId);

            if ($reserve === null) {
                $reserve = new Reserve();
                $reserve->id = $reserveId;
            }

            $reserve->hotel_id = $hotelId;
            $reserve->room_id = $roomId;
            $reserve->check_in = (string) $reserveXml->CheckIn;
            $reserve->check_out = (string) $reserveXml->CheckOut;
            $reserve->total = (string) $reserveXml->Total;

            $reserve->save();

            $this->importGuests($reserve, $reserveXml);
            $this->importDailies($reserve, $reserveXml);
            $this->importPayments($reserve, $reserveXml);
        }
    }

    private function importGuests(
        Reserve $reserve,
        \SimpleXMLElement $reserveXml
    ): void {
        $reserve->guests()->delete();

        foreach ($reserveXml->Guests->Guest as $guestXml) {
            Guest::create([
                'reserve_id' => $reserve->id,
                'name' => (string) $guestXml->Name,
                'last_name' => (string) $guestXml->LastName,
                'phone' => (string) $guestXml->Phone,
            ]);
        }
    }

    private function importDailies(
        Reserve $reserve,
        \SimpleXMLElement $reserveXml
    ): void {
        $reserve->dailies()->delete();

        foreach ($reserveXml->Dailies->Daily as $dailyXml) {
            $date = (string) $dailyXml->Date;
            $value = (string) $dailyXml->Value;

            if (
                $date < $reserve->check_in->format('Y-m-d') ||
                $date >= $reserve->check_out->format('Y-m-d')
            ) {
                Log::warning('Diária fora do período da reserva.', [
                    'reserve_id' => $reserve->id,
                    'daily_date' => $date,
                    'check_in' => $reserve->check_in->format('Y-m-d'),
                    'check_out' => $reserve->check_out->format('Y-m-d'),
                ]);
            }

            Daily::create([
                'reserve_id' => $reserve->id,
                'date' => $date,
                'value' => $value,
            ]);
        }
    }

    private function importPayments(
        Reserve $reserve,
        \SimpleXMLElement $reserveXml
    ): void {
        $reserve->payments()->delete();

        if (!isset($reserveXml->Payments->Payment)) {
            return;
        }

        foreach ($reserveXml->Payments->Payment as $paymentXml) {
            Payment::create([
                'reserve_id' => $reserve->id,
                'method' => (int) $paymentXml->Method,
                'value' => (string) $paymentXml->Value,
            ]);
        }
    }
}