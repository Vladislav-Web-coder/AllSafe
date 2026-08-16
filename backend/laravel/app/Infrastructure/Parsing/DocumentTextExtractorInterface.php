<?php

namespace App\Infrastructure\Parsing;

interface DocumentTextExtractorInterface
{
    public function extract(string $content, string $extension): string;
}
