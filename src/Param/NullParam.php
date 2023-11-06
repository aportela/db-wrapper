<?php

namespace aportela\DatabaseWrapper\Param;

final class NullParam implements InterfaceParam
{
    private string $name;

    public function __construct(string $name, mixed $value = null)
    {
        $this->name = $name;
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
