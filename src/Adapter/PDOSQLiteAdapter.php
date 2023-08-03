<?php

namespace aportela\DatabaseWrapper\Adapter;

final class PDOSQLiteAdapter implements InterfaceAdapter
{
    protected $dbh;
    public $schema;

    public function __construct(string $databasePath, string $upgradeSchemaPath = "")
    {
        try {
            $this->dbh = new \PDO(
                sprintf("sqlite:%s", $databasePath),
                null,
                null,
                array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                )
            );
            $this->schema = new \aportela\DatabaseWrapper\Schema\PDOSQLiteSchema($upgradeSchemaPath);
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::__construct FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::CONSTRUCTOR->value, $e);
        }
    }

    public function __destruct()
    {
        $this->dbh = null;
    }

    public function beginTransaction(): bool
    {
        $success = false;
        try {
            $success = $this->dbh->beginTransaction();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::beginTransaction FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::BEGIN_TRANSACTION->value, $e);
        }
        return ($success);
    }

    public function commit(): bool
    {
        $success = false;
        try {
            $success = $this->dbh->commit();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::commit FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::COMMIT_TRANSACTION->value, $e);
        }
        return ($success);
    }

    public function rollBack(): bool
    {
        $success = false;
        try {
            $success = $this->dbh->rollBack();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::rollBack FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::ROLLBACK_TRANSACTION->value, $e);
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
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::exec FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::EXECUTE->value, $e);
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
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::query FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::QUERY->value, $e);
        }
        return ($rows);
    }
}
