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

        // Normalize Unicode (e.g. Æ → AE) with fallback
        if (function_exists('iconv')) {
            $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        } else {
            // Fallback for when iconv is not available
            $name = self::transliterateUnicode($name);
        }

        // Remove apostrophes and punctuation except slashes and hyphens
        $name = preg_replace('/[\'"`]/u', '', $name); // remove apostrophes
        $name = preg_replace('/[^a-zA-Z0-9\/\- ]+/u', '', $name); // remove other punctuation

        // Collapse multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Trim and convert to kebab-case
        return Str::slug(trim($name), '-');
    }

    private static function transliterateUnicode(string $text): string
    {
        // Simple transliteration fallback for common MTG characters
        $replacements = [
            'Æ' => 'AE', 'æ' => 'ae',
            'Ø' => 'O', 'ø' => 'o',
            'Å' => 'A', 'å' => 'a',
            'Ä' => 'A', 'ä' => 'a',
            'Ö' => 'O', 'ö' => 'o',
            'Ü' => 'U', 'ü' => 'u',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];
        
        return strtr($text, $replacements);
    }
}
