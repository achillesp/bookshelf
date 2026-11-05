<?php

namespace App\Http\Requests\Library;

use App\Enums\ReadingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryIndexRequest extends FormRequest
{
    /**
     * Library sort options and the column each one orders by.
     *
     * @var array<string, array{column: string, direction: 'asc'|'desc'}>
     */
    public const array SORTS = [
        'updated' => ['column' => 'library_entries.updated_at', 'direction' => 'desc'],
        'title' => ['column' => 'books.title', 'direction' => 'asc'],
        'finished' => ['column' => 'library_entries.finished_on', 'direction' => 'desc'],
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ReadingStatus::class)],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ];
    }

    public function status(): ?ReadingStatus
    {
        return $this->enum('status', ReadingStatus::class);
    }

    public function sort(): string
    {
        return $this->string('sort', 'updated')->toString();
    }
}
