<?php

namespace aportela\DatabaseWrapper\Adapter;

final class PDOMariaDBAdapter extends PDOBaseAdapter
{
    public const DEFAULT_PORT = 3306;
    public ?string $dbName;

    public function __construct(string $host, int $port, string $dbName, string $username, string $password, string $upgradeSchemaPath = "")
    {
        try {
            $this->dbName = $dbName;
            $this->dbh = new \PDO(
                sprintf("mysql:host=%s;port=%d;dbname=%s", $host, $port, $dbName),
                $username,
                $password,
                array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                )
            );
            $this->schema = new \aportela\DatabaseWrapper\Schema\PDOMariaDBSchema(
                $upgradeSchemaPath,
                \aportela\DatabaseWrapper\Schema\PDOMariaDBSchema::INSTALL_QUERIES,
                \aportela\DatabaseWrapper\Schema\PDOMariaDBSchema::SET_CURRENT_VERSION_QUERY,
                \aportela\DatabaseWrapper\Schema\PDOMariaDBSchema::GET_CURRENT_VERSION_QUERY
            );
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOMariaDBAdapter::__construct FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::CONSTRUCTOR->value, $e);
        }
    }

    public function isSchemaInstalled(): bool
    {
        $results = $this->query(
            " SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema = :dbName AND table_name = :tableName; ",
            [
                new \aportela\DatabaseWrapper\Param\StringParam(":dbName", $this->dbName),
                new \aportela\DatabaseWrapper\Param\StringParam(":tableName", "VERSION"),
            ]
        );
        return (count($results) == 1 && isset($results[0]->table_count) && $results[0]->table_count == 1);
    }
}
