<?php

namespace Rabble\ContentBundle\Exception;

use Throwable;

class InvalidContentDocumentException extends \RuntimeException
{
    public function __construct($message = 'Invalid content document.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
