<?php

namespace aportela\DatabaseWrapper\Param;

interface InterfaceParam
{
    public function __construct(string $name, mixed $value);

    public function getName(): string;

    public function getValue(): mixed;
}
