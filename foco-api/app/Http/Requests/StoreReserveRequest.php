<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReserveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => [
                'required',
                'integer',
                'exists:hotels,id',
            ],

            'room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
            ],

            'check_in' => [
                'required',
                'date',
            ],

            'check_out' => [
                'required',
                'date',
                'after:check_in',
            ],

            'total' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'hotel_id.required' => 'O hotel é obrigatório.',
            'hotel_id.integer' => 'O hotel deve ser um número inteiro.',
            'hotel_id.exists' => 'O hotel informado não existe.',

            'room_id.required' => 'O quarto é obrigatório.',
            'room_id.integer' => 'O quarto deve ser um número inteiro.',
            'room_id.exists' => 'O quarto informado não existe.',

            'check_in.required' => 'A data de entrada é obrigatória.',
            'check_in.date' => 'A data de entrada deve ser uma data válida.',

            'check_out.required' => 'A data de saída é obrigatória.',
            'check_out.date' => 'A data de saída deve ser uma data válida.',
            'check_out.after' => 'A data de saída deve ser posterior à data de entrada.',

            'total.required' => 'O valor total é obrigatório.',
            'total.numeric' => 'O valor total deve ser numérico.',
            'total.min' => 'O valor total não pode ser negativo.',
        ];
    }
}
