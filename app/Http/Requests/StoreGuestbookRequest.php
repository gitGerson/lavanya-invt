<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:150'],
            'guest_address' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'attendance' => ['nullable', 'in:yes,no,maybe'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }
}
