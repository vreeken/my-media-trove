<?php

declare(strict_types=1);

namespace App\Http\Requests\MediaItem;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaItemRequest extends FormRequest
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
            // Media catalog data (shared)
            'type' => ['required', Rule::enum(MediaType::class)],
            'title' => ['required', 'string', 'max:500'],
            'original_title' => ['nullable', 'string', 'max:500'],
            'year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'poster_url' => ['nullable', 'url', 'max:1000'],
            'external_id' => ['nullable', 'string', 'max:100'],
            'external_source' => ['nullable', 'string', 'max:50'],
            'is_custom' => ['boolean'],
            'metadata' => ['nullable', 'array'],

            // User collection data (user-specific)
            'formats' => ['nullable', 'array'],
            'formats.*' => ['string', 'max:50'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],

            // Digital vs Physical
            'is_digital' => ['boolean'],
            'digital_platform' => ['nullable', 'string', 'max:100'],
            'digital_path' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the media catalog data from the request.
     */
    public function mediaItemData(): array
    {
        return $this->only([
            'type',
            'title',
            'original_title',
            'year',
            'description',
            'poster_url',
            'external_id',
            'external_source',
            'is_custom',
            'metadata',
        ]);
    }

    /**
     * Get the user collection data from the request.
     */
    public function userMediaItemData(): array
    {
        return $this->only([
            'formats',
            'rating',
            'notes',
            'location_id',
            'is_digital',
            'digital_platform',
            'digital_path',
        ]);
    }
}
