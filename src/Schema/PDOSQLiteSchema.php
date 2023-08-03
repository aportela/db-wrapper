<?php

namespace aportela\DatabaseWrapper\Schema;

final class PDOSQLiteSchema implements InterfaceSchema
{
    protected $upgradeSchemaPath;

    private const INSTALL_QUERIES = array(
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

    private const SET_CURRENT_VERSION_QUERY = ' INSERT INTO "VERSION" (release_number, release_date) VALUES (:release_number, datetime()); ';

    private const GET_CURRENT_VERSION_QUERY = ' SELECT release_number FROM "VERSION" ORDER BY release_number DESC LIMIT 1; ';

    public function __construct(string $upgradeSchemaPath = "")
    {
        $this->upgradeSchemaPath = $upgradeSchemaPath;
    }

    public function getInstallQueries(): array
    {
        return (self::INSTALL_QUERIES);
    }

    public function getSetVersionQuery(): string
    {
        return (self::SET_CURRENT_VERSION_QUERY);
    }

    public function getLastVersionQuery(): string
    {
        return (self::GET_CURRENT_VERSION_QUERY);
    }

    public function getUpgradeQueries(): array
    {
        if (!empty($this->upgradeSchemaPath)) {
            if (file_exists($this->upgradeSchemaPath)) {
                return (include $this->upgradeSchemaPath);
            } else {
                throw new \Exception("Upgrade database schema file not found at " . $this->upgradeSchemaPath);
            }
        } else {
            return (array());
        }
    }
}
