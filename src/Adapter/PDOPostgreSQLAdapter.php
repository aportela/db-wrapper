<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Adapter;

final class PDOPostgreSQLAdapter extends PDOBaseAdapter
{
    public const int DEFAULT_PORT = 5432;

    public ?string $dbName;

    /**
     * @param array<int, bool|int> $options
     */
    public function __construct(string $host, int $port, string $dbName, string $username, string $password, array $options = [], string $upgradeSchemaPath = "")
    {
        try {
            $this->dbName = $dbName;
            $this->dbh = new \PDO(
                sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", $host, $port, $dbName, $username, $password),
                null,
                null,
                $options
            );
            $this->schema = new \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema(
                $upgradeSchemaPath,
                \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema::INSTALL_QUERIES,
                \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema::SET_CURRENT_VERSION_QUERY,
                \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema::GET_CURRENT_VERSION_QUERY
            );
        } catch (\PDOException $pdoException) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::__construct FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::CONSTRUCTOR->value, $pdoException);
        }
    }

    #[\Override]
    public function isSchemaInstalled(): bool
    {
        $results = $this->query(" SELECT COUNT(relname) AS table_count FROM pg_class WHERE relname = 'VERSION'; ");
        return (count($results) === 1 && isset($results[0]->table_count) && $results[0]->table_count == 1);
    }
}
