<?php

namespace aportela\DatabaseWrapper\Param;

final class IntegerParam implements InterfaceParam
{
    private string $name;
    private int $value;

    public function __construct(string $name, mixed $value)
    {
        $this->name = $name;
        if (is_int($value)) {
            $this->value = $value;
        } else {
            throw new \InvalidArgumentException("integer type required");
        }
    }

    public function getName(): string
    {
        return ($this->name);
    }

    public function getValue(): int
    {
        return ($this->value);
    }
}
