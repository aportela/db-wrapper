<?php

namespace aportela\DatabaseWrapper\Adapter;

interface InterfaceAdapter
{
    public function getSchema(): ?\aportela\DatabaseWrapper\Schema\InterfaceSchema;

    public function beginTransaction(): bool;

    public function inTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    public function exec(string $query): int;

    /**
     * @param array<\aportela\DatabaseWrapper\Param\InterfaceParam> $params
     */
    public function execute(string $query, array $params = array()): bool;

    /**
     * @param array<\aportela\DatabaseWrapper\Param\InterfaceParam> $params
     * @return array<Object>
     */
    public function query(string $query, array $params = array()): array;

    public function close(): void;

    public function isSchemaInstalled(): bool;
}
