<?php

namespace aportela\DatabaseWrapper\Adapter;

final class PDOSQLiteAdapter implements InterfaceAdapter
{
    protected ?\PDO $dbh;
    public ?\aportela\DatabaseWrapper\Schema\PDOSQLiteSchema $schema;
    public ?string $databasePath;

    public function __construct(string $databasePath, string $upgradeSchemaPath = "")
    {
        try {
            $this->databasePath = $databasePath;
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
        $this->close();
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

    public function inTransaction(): bool
    {
        $activeTransaction = false;
        try {
            $activeTransaction = $this->dbh->inTransaction();
        } catch (\PDOException $e) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::beginTransaction FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::IN_TRANSACTION_CHECK->value, $e);
        }
        return ($activeTransaction);
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

    public function close(): void
    {
        $this->dbh = null;
    }

    public function hasSchemaInstalled(): bool
    {
        $results = $this->query(" SELECT COUNT(name) AS table_count FROM sqlite_master WHERE type='table' AND name='VERSION'; ");
        return (is_array($results) && count($results) == 1 && $results[0]->table_count == 1);
    }

    public function backup(string $path): string
    {
        if (file_exists(($this->databasePath))) {
            $backupFilePath = "";
            if (empty($path)) {
                $backupFilePath = dirname($this->databasePath) . DIRECTORY_SEPARATOR . "backup-" . time() . "-" . uniqid() . ".sqlite";
            } elseif (is_dir($path)) {
                $backupFilePath = realpath($path) . DIRECTORY_SEPARATOR . "backup-" . uniqid() . ".sqlite";
            } else {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_BACKUP_PATH->value);
            }
            $this->exec(" VACUUM main INTO :path", [new \aportela\DatabaseWrapper\Param\StringParam(":path", $backupFilePath)]);
            return ($backupFilePath);
        } else {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::DATABASE_NOT_FOUND->value);
        }
    }
}
