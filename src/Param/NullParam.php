<?php

namespace aportela\DatabaseWrapper\Param;

final readonly class NullParam implements InterfaceParam
{
    public function __construct(private string $name, mixed $value = null)
    {
    }

    public function getName(): string
    {
        return ($this->name);
    }

    public function getValue(): mixed
    {
        return (null);
    }
}
