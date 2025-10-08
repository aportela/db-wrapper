<?php

namespace aportela\DatabaseWrapper\Adapter;

final class PDOPostgreSQLAdapter extends PDOBaseAdapter
{
    public const DEFAULT_PORT = 5432;
    public ?string $dbName;

    public function __construct(string $host, int $port, string $dbName, string $username, string $password, string $upgradeSchemaPath = "")
    {
        try {
            $this->dbName = $dbName;
            $this->dbh = new \PDO(
                sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", $host, $port, $dbName, $username, $password)
            );
            $this->schema = new \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema(
                $upgradeSchemaPath,
                \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema::INSTALL_QUERIES,
                \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema::SET_CURRENT_VERSION_QUERY,
                \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema::GET_CURRENT_VERSION_QUERY
            );
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::__construct FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::CONSTRUCTOR->value, $e);
        }
    }

    public function isSchemaInstalled(): bool
    {
        $results = $this->query(" SELECT COUNT(relname) AS table_count FROM pg_class WHERE relname = 'VERSION'; ");
        return (count($results) == 1 && $results[0]->table_count == 1);
    }
}
