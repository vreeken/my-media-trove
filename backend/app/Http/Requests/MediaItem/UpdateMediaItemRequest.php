<?php

declare(strict_types=1);

namespace App\Http\Requests\MediaItem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaItemRequest extends FormRequest
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
     * Updates only affect user-specific collection data.
     * Media catalog data is immutable after creation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // User collection data (user-specific, updatable)
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
}
