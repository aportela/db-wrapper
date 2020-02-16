<?php

    namespace aportela\DatabaseWrapper\Schema;

    class PDOSQLiteSchema implements InterfaceSchema
    {
        private const INSTALL_QUERIES = array
        (
            '
                CREATE TABLE "VERSION" (
                    "release_number"	INTEGER NOT NULL,
                    "release_date"	STRING NOT NULL,
                    PRIMARY KEY("release_number")
                );
            ',
            '
                INSERT INTO "VERSION" (release_number, release_date) VALUES (0, datetime());
            ',
            '
                PRAGMA journal_mode=WAL;
            '
        );

        private const SET_CURRENT_VERSION_QUERY =
        '
            INSERT INTO "VERSION" (release_number, release_date) VALUES (:release_number, datetime());
        ';

        private const GET_CURRENT_VERSION_QUERY =
        '
            SELECT release_number FROM "VERSION" ORDER BY release_number DESC LIMIT 1;
        ';

        private const UPGRADE_QUERIES = array
        (
            /*
            // UPGRADE QUERIES EXAMPLE: (REPLACE BY YOUR OWN QUERIES)
            1 => array
            (
                '
                    CREATE TABLE "xxx" ...
                ',
                '
                    INSERT INTO "xxx" ...
                '
            ),
            2 => array
            (
                '
                    CREATE TABLE "yyy" ...
                ',
                '
                    INSERT INTO "yyy" ...
                '
            )
            */
        );

        public static function getInstallQueries(): array
        {
            return(self::INSTALL_QUERIES);
        }

        public static function getSetVersionQuery(): string
        {
            return(self::SET_CURRENT_VERSION_QUERY);
        }

        public static function getLastVersionQuery(): string
        {
            return(self::GET_CURRENT_VERSION_QUERY);
        }

        public static function getUpgradeQueries(): array
        {
            return(self::UPGRADE_QUERIES);
        }
    }

?>