<?php

namespace App\Http\Requests\Library;

use App\Enums\ReadingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLibraryEntryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::enum(ReadingStatus::class)],
            'rating' => ['sometimes', 'nullable', 'integer', 'between:1,5'],
        ];
    }
}
