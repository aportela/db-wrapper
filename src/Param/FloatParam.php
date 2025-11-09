<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Param;

final class FloatParam implements InterfaceParam
{
    private float $value;

    public function __construct(private readonly string $name, mixed $value)
    {
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
