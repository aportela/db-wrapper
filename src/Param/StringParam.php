<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Param;

final class StringParam implements InterfaceParam
{
    private string $value;

    public function __construct(private readonly string $name, mixed $value)
    {
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

    public function getValue(): string
    {
        return ($this->value);
    }
}
