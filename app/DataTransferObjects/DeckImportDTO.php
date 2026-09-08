<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;

class DeckImportDTO implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public array $mainboard,
        public ?string $format,
        public ?string $description,
        public ?array $sideboard,
        public ?string $sourceUrl = null,
        public ?string $archetype = null,
        public ?array $tags = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'format' => $this->format,
            'description' => $this->description,
            'mainboard' => $this->mainboard,
            'sideboard' => $this->sideboard,
            'sourceUrl' => $this->sourceUrl,
            'archetype' => $this->archetype,
            'tags' => $this->tags,
        ];
    }

    public static function fromRequest(Request $request): self
        {
            return new self(
                name: $request->input('name'),
                mainboard: $request->input('mainboard', []),
                sideboard: $request->input('sideboard', []),
                format: $request->input('format'),
                description: $request->input('description'),
                sourceUrl: $request->input('sourceUrl'),
                archetype: $request->input('archetype'),
                tags: $request->input('tags', []),
            );
        }    
}