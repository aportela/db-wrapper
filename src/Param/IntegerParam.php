<?php

    namespace aportela\DatabaseWrapper\Param;

    class IntegerParam implements InterfaceParam
    {
        protected $name;
        protected $value;

        public function __construct(string $name, $value)
        {
            $this->name = $name;
            if (is_int($value))
            {
                $this->value = $value;
            }
            else
            {
                throw new \InvalidArgumentException("integer type required");
            }
        }

        public function getName(): string
        {
            return($this->name);
        }

        public function getValue()
        {
            return($this->value);
        }
    }

?>