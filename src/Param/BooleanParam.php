<?php

namespace aportela\DatabaseWrapper\Param;

final class BooleanParam implements InterfaceParam
{
    private string $name;
    private bool $value;

    public function __construct(string $name, mixed $value)
    {
        $this->name = $name;
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
