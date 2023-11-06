<?php

namespace aportela\DatabaseWrapper\Adapter;

final class PDOPostgreSQLAdapter implements InterfaceAdapter
{
    protected ?\PDO $dbh;
    public ?string $dbName;
    public ?\aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema $schema;

    public function __construct(string $host, int $port = 5432, string $dbName, string $username, string $password, string $upgradeSchemaPath = "")
    {
        try {
            $this->dbName = $dbName;
            $this->dbh = new \PDO(
                sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", $host, $port, $dbName, $username, $password)
            );
            $this->schema = new \aportela\DatabaseWrapper\Schema\PDOPostgreSQLSchema($upgradeSchemaPath);
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::__construct FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::CONSTRUCTOR->value, $e);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function beginTransaction(): bool
    {
        $success = false;
        try {
            $success = $this->dbh->beginTransaction();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::beginTransaction FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::BEGIN_TRANSACTION->value, $e);
        }
        return ($success);
    }

    public function inTransaction(): bool
    {
        $activeTransaction = false;
        try {
            $activeTransaction = $this->dbh->inTransaction();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::beginTransaction FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::IN_TRANSACTION_CHECK->value, $e);
        }
        return ($activeTransaction);
    }


    public function commit(): bool
    {
        $success = false;
        try {
            $success = $this->dbh->commit();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::commit FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::COMMIT_TRANSACTION->value, $e);
        }
        return ($success);
    }

    public function rollBack(): bool
    {
        $success = false;
        try {
            $success = $this->dbh->rollBack();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::rollBack FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::ROLLBACK_TRANSACTION->value, $e);
        }
        return ($success);
    }

    public function exec(string $query, $params = array()): int
    {
        $affectedRows = 0;
        try {
            $stmt = $this->dbh->prepare($query);
            $totalParams = count($params);
            if ($totalParams > 0) {
                for ($i = 0; $i < $totalParams; $i++) {
                    switch (get_class($params[$i])) {
                        case "aportela\DatabaseWrapper\Param\NullParam":
                            $stmt->bindValue($params[$i]->getName(), null, \PDO::PARAM_NULL);
                            break;
                        case "aportela\DatabaseWrapper\Param\BooleanParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_BOOL);
                            break;
                        case "aportela\DatabaseWrapper\Param\IntegerParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_INT);
                            break;
                        case "aportela\DatabaseWrapper\Param\FloatParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_STR);
                            break;
                        case "aportela\DatabaseWrapper\Param\StringParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_STR);
                            break;
                    }
                }
            }
            $affectedRows = $stmt->execute();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::exec FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::EXECUTE->value, $e);
        }
        return ($affectedRows);
    }

    public function query(string $query, $params = array()): array
    {
        $rows = array();
        try {
            $stmt = $this->dbh->prepare($query);
            $totalParams = count($params);
            if ($totalParams > 0) {
                for ($i = 0; $i < $totalParams; $i++) {
                    switch (get_class($params[$i])) {
                        case "aportela\DatabaseWrapper\Param\NullParam":
                            $stmt->bindValue($params[$i]->getName(), null, \PDO::PARAM_NULL);
                            break;
                        case "aportela\DatabaseWrapper\Param\BooleanParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_BOOL);
                            break;
                        case "aportela\DatabaseWrapper\Param\IntegerParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_INT);
                            break;
                        case "aportela\DatabaseWrapper\Param\FloatParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_STR);
                            break;
                        case "aportela\DatabaseWrapper\Param\StringParam":
                            $stmt->bindValue($params[$i]->getName(), $params[$i]->getValue(), \PDO::PARAM_STR);
                            break;
                    }
                }
            }
            $rows = array();
            if ($stmt->execute()) {
                while ($row = $stmt->fetchObject()) {
                    $rows[] = $row;
                }
            }
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOPostgreSQLAdapter::query FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::QUERY->value, $e);
        }
        return ($rows);
    }

    public function close(): void
    {
        $this->dbh = null;
    }

    public function isSchemaInstalled(): bool
    {
        $results = $this->query(" SELECT COUNT(relname) AS table_count FROM pg_class WHERE relname = 'VERSION'; ");
        return (is_array($results) && count($results) == 1 && $results[0]->table_count == 1);
    }
}
