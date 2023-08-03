<?php

namespace aportela\DatabaseWrapper\Param;

final class FloatParam implements InterfaceParam
{
    private string $name;
    private float $value;

    public function __construct(string $name, mixed $value)
    {
        $this->name = $name;
        if (is_float($value)) {
            $this->value = $value;
        } else {
            throw new \InvalidArgumentException("float type required");
        }
    }

    public function getName(): string
    {
        return ($this->name);
    }

    public function getValue(): float
    {
        return ($this->value);
    }
}
