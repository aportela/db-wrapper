<?php

namespace aportela\DatabaseWrapper\Schema;

final class PDOSQLiteSchema extends PDOBaseSchema
{
    public const INSTALL_QUERIES = array(
        '
            CREATE TABLE "VERSION" (
                "release_number" INTEGER NOT NULL,
                "release_date" STRING NOT NULL,
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

    public const SET_CURRENT_VERSION_QUERY = ' INSERT INTO "VERSION" (release_number, release_date) VALUES (:release_number, datetime()); ';

    public const GET_CURRENT_VERSION_QUERY = ' SELECT release_number FROM "VERSION" ORDER BY release_number DESC LIMIT 1; ';
}
