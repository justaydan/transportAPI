<?php

namespace App\Http\Requests;

use App\Rules\ValidAddressCombination;
use Illuminate\Foundation\Http\FormRequest;

class TransportCalculationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'addresses' => ['required', 'min:2'],
            'addresses.*' => ['required', 'array', new  ValidAddressCombination()],
        ];
    }
}
