<?php

namespace aportela\DatabaseWrapper\Schema;

interface InterfaceSchema
{
    public function __construct(string $upgradeSchemaPath = "");

    /**
     * @return array<string>
     */
    public function getInstallQueries(): array;

    public function getSetVersionQuery(): string;

    public function getLastVersionQuery(): string;

    /**
     *  @return array<int, array<string>>
     */
    public function getUpgradeQueries(): array;
}
