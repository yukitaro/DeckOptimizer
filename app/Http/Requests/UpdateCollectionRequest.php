<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Return true or connect to your Collection Policy
        return $this->user()?->can('update', $this->route('collection'));
    }

    public function rules(): array
    {
        return [
            'collection_name'      => ['sometimes', 'required', 'string', 'max:255'],
            'description'          => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_favorite'          => ['sometimes', 'boolean'],
            'include_in_inventory' => ['sometimes', 'boolean'],
            'type'                 => ['sometimes', 'string', Rule::in(['collection', 'binder', 'deck', 'wishlist'])],
            'game_type'            => ['sometimes', 'string', Rule::in(['mtg', 'pokemon', 'lorcana', 'yugioh'])],
            'visibility'           => ['sometimes', 'string', Rule::in(['public', 'private', 'unlisted'])],
        ];
    }
}