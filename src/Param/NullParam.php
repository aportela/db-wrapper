<?php

    namespace aportela\DatabaseWrapper\Param;

    class NullParam implements InterfaceParam
    {
        protected $name;

        public function __construct(string $name, $value = null)
        {
            $this->name = $name;
        }

        public function getName(): string
        {
            return($this->name);
        }

        public function getValue()
        {
            return(null);
        }
    }

?>