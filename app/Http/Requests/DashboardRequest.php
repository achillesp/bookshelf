<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'between:1900,'.now()->year],
        ];
    }

    /**
     * The year to chart; the current year unless the Reader chose another.
     */
    public function year(): int
    {
        return $this->filled('year') ? $this->integer('year') : now()->year;
    }
}
