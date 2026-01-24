<?php

declare(strict_types=1);

namespace App\Http\Requests\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWishlistItemRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
