<?php

namespace aportela\DatabaseWrapper\Schema;

final class PDOPostgreSQLSchema extends PDOBaseSchema
{
    public const INSTALL_QUERIES = array(
        '
            CREATE TABLE "VERSION"  (
                release_number INTEGER NOT NULL PRIMARY KEY,
                release_date TIMESTAMP NOT NULL
            );
        ',
        '
            INSERT INTO "VERSION" (release_number, release_date) VALUES (0, CURRENT_TIMESTAMP);
        '
    );

    public const SET_CURRENT_VERSION_QUERY = ' INSERT INTO "VERSION" (release_number, release_date) VALUES (:release_number, CURRENT_TIMESTAMP); ';

    public const GET_CURRENT_VERSION_QUERY = ' SELECT release_number FROM "VERSION" ORDER BY release_number DESC LIMIT 1; ';
}
