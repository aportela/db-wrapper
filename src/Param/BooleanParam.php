<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Param;

final class BooleanParam implements InterfaceParam
{
    private bool $value;

    public function __construct(private readonly string $name, mixed $value)
    {
        if (is_bool($value)) {
            $this->value = $value;
        } else {
            throw new \InvalidArgumentException("boolean type required");
        }
    }

    public function getName(): string
    {
        return ($this->name);
    }

    public function getValue(): bool
    {
        return ($this->value);
    }
}
