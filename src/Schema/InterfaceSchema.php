<?php

    namespace aportela\DatabaseWrapper\Schema;

    interface InterfaceSchema
    {
        public function __construct(string $upgradeSchemaPath = "");

        public function getInstallQueries(): array;

        public function getSetVersionQuery(): string;

        public function getLastVersionQuery(): string;

        public function getUpgradeQueries(): array;
    }

?>