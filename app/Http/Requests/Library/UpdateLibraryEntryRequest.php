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
            'notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'started_on' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'finished_on' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'notes' => 'Notes',
            'started_on' => 'Started Date',
            'finished_on' => 'Finished Date',
        ];
    }
}
