<?php

namespace aportela\DatabaseWrapper\Param;

class BooleanParam implements InterfaceParam
{
    protected $name;
    protected $value;

    public function __construct(string $name, $value)
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

    public function getValue()
    {
        return ($this->value);
    }
}
