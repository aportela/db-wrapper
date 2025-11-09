<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Param;

final class IntegerParam implements InterfaceParam
{
    private int $value;

    public function __construct(private readonly string $name, mixed $value)
    {
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
