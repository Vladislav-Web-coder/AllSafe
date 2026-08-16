<?php

namespace Domain\Documents\Exceptions;



class UnsupportedDocumentFormatException extends \RuntimeException
{
    public function __construct(string $extension)
    {
        parent::__construct(
            "Формат '{$extension}' пока не поддерживается для извлечения текста."
        );
    }
}
