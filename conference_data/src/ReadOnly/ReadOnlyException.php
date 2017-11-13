<?php
namespace WorldSpeakers\ReadOnly;

use Throwable;

class ReadOnlyException extends \RuntimeException
{
    const MESSAGE = 'ReadOnly source does not support this method: %s';

    public function __construct($method, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf(self::MESSAGE, $method), $code, $previous);
    }
}