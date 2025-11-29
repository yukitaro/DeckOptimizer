<?php
// app/Casts/JsonToArray.php
namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class JsonToArray implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}