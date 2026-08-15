<?php

namespace App\Domain\Documents\ValueObjects;

class StoredFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $originalName,
        public readonly int $size,
        public readonly string $mimeType,
        public readonly string $hash,
        public readonly string $disk,
    ) {}
}
