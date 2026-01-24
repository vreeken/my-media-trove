<?php

declare(strict_types=1);

namespace App\Http\Requests\Wishlist;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWishlistItemRequest extends FormRequest
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
            // Media item data (used to find or create the media item)
            'type' => ['required', Rule::enum(MediaType::class)],
            'title' => ['required', 'string', 'max:500'],
            'year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'description' => ['nullable', 'string', 'max:10000'],
            'poster_url' => ['nullable', 'url', 'max:1000'],
            'external_id' => ['nullable', 'string', 'max:100'],
            'external_source' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],

            // Wishlist-specific data
            'notes' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
