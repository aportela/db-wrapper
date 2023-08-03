<?php

namespace aportela\DatabaseWrapper\Param;

final class StringParam implements InterfaceParam
{
    private string $name;
    private string $value;

    public function __construct(string $name, mixed $value)
    {
        $this->name = $name;
        if (is_string($value)) {
            $this->value = $value;
        } else {
            throw new \InvalidArgumentException("string type required");
        }
    }

    public function getName(): string
    {
        return ($this->name);
    }

    public function getValue(): mixed
    {
        return ($this->value);
    }
}
