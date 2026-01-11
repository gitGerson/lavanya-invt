<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'attendance' => ['required', 'in:yes,no,maybe'],
            'pax' => ['required', 'integer', 'min:1', 'max:10'],
            'note' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }
}
