<?php

namespace App\Http\Requests\Library;

use App\Enums\ReadingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLibraryEntryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'openlibrary_id' => ['required', 'string', 'max:255', 'regex:/^OL\d+W$/'],
            'status' => ['nullable', Rule::enum(ReadingStatus::class)],
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
            'openlibrary_id' => 'book',
        ];
    }

    /**
     * The starting Reading Status; Want to Read unless the Reader chose another.
     */
    public function status(): ReadingStatus
    {
        return $this->enum('status', ReadingStatus::class) ?? ReadingStatus::WantToRead;
    }
}
