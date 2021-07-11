<?php

namespace Rabble\ContentBundle\Exception;

class ContentTypeNotFoundException extends \RuntimeException
{
    public function __construct(string $contentTypeName, $message = 'Content type %s does not exist', $code = 0, \Throwable $previous = null)
    {
        $message = sprintf($message, $contentTypeName);
        parent::__construct($message, $code, $previous);
    }
}
