<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requester_name'      => ['required', 'string', 'max:150'],
            'division_id'         => ['nullable', 'exists:divisions,id'],
            'division_other'      => ['nullable', 'required_without:division_id', 'string', 'max:150'],

            'participants'        => ['required', 'integer', 'min:1'],

            'purpose'             => ['required', 'in:Training/Seminar,Meeting,Others'],
            'purpose_title'       => ['required', 'string', 'max:200'],

            'extras'              => ['nullable', 'array'],
            'setup_requirement'   => ['nullable', 'string', 'max:100'],
            'other_needs'         => ['nullable', 'string', 'max:255'],

            'signature'           => ['required', 'string'],

            'schedule'            => ['required', 'array', 'min:1'],
            'schedule.*.date'     => ['required', 'date'],
            'schedule.*.from'     => ['required', 'date_format:H:i'],
            'schedule.*.to'       => ['required', 'date_format:H:i', 'after:schedule.*.from'],
        ];
    }
}
