<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Exception;

class DBException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
