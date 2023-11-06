<?php

namespace aportela\DatabaseWrapper\Adapter;

interface InterfaceAdapter
{
    public function beginTransaction(): bool;

    public function inTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    public function exec(string $query, $params = array()): int;

    public function query(string $query, $params = array()): array;

    public function close(): void;

    public function isSchemaInstalled(): bool;
}
