<?php

namespace App\Utilities;

use Illuminate\Support\Str;

class MtgStringUtilities
{
    public static function normalizeCardNameToKebabCase(string $name): string
    {
        // Use only the front face (before any "//")
        $name = explode('//', $name)[0];
        $name = trim($name);

        // Normalize Unicode (e.g. Æ → AE)
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

        // Remove apostrophes and punctuation except slashes and hyphens
        $name = preg_replace('/[’\'"`]/u', '', $name); // remove apostrophes
        $name = preg_replace('/[^a-zA-Z0-9\/\- ]+/u', '', $name); // remove other punctuation

        // Collapse multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Trim and convert to kebab-case
        return Str::slug(trim($name), '-');
    }
}