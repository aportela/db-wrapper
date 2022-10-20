<?php

namespace aportela\DatabaseWrapper\Param;

class FloatParam implements InterfaceParam
{
    protected $name;
    protected $value;

    public function __construct(string $name, $value)
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

    public function getValue()
    {
        return ($this->value);
    }
}
