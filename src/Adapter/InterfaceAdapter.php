<?php

    namespace aportela\DatabaseWrapper\Adapter;

    interface InterfaceAdapter
    {
        const EXCEPTION_CODE_CONSTRUCTOR = 1;
        const EXCEPTION_CODE_BEGIN_TRANSACTION = 2;
        const EXCEPTION_CODE_COMMIT_TRANSACTION = 3;
        const EXCEPTION_CODE_ROLLBACK_TRANSACTION = 4;
        const EXCEPTION_CODE_EXECUTE = 5;
        const EXCEPTION_CODE_QUERY = 6;

        public function beginTransaction (): bool;

        public function commit(): bool;

        public function rollBack (): bool;

        public function exec(string $query, $params = array()): int;

        public function query(string $query, $params = array()): array;
    }

?>