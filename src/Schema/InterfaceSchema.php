<?php

    namespace aportela\DatabaseWrapper\Schema;

    interface InterfaceSchema
    {
        public static function getInstallQueries(): array;

        public static function getSetVersionQuery(): string;

        public static function getLastVersionQuery(): string;

        public static function getUpgradeQueries(): array;
    }

?>