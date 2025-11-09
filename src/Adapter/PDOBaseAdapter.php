<?php

namespace aportela\DatabaseWrapper\Adapter;

abstract class PDOBaseAdapter implements InterfaceAdapter
{
    protected ?\PDO $dbh = null;
    protected ?\aportela\DatabaseWrapper\Schema\InterfaceSchema $schema;

    public function getSchema(): ?\aportela\DatabaseWrapper\Schema\InterfaceSchema
    {
        return ($this->schema);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function beginTransaction(): bool
    {
        $success = false;
        if ($this->dbh !== null) {
            try {
                $success = $this->dbh->beginTransaction();
            } catch (\PDOException $e) {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOBaseAdapter::beginTransaction FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::BEGIN_TRANSACTION->value, $e);
            }
        }
        return ($success);
    }

    public function inTransaction(): bool
    {
        $activeTransaction = false;
        if ($this->dbh !== null) {
            try {
                $activeTransaction = $this->dbh->inTransaction();
            } catch (\PDOException $e) {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOBaseAdapter::beginTransaction FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::IN_TRANSACTION_CHECK->value, $e);
            }
        }
        return ($activeTransaction);
    }

    public function commit(): bool
    {
        $success = false;
        if ($this->dbh !== null) {
            try {
                $success = $this->dbh->commit();
            } catch (\PDOException $e) {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOBaseAdapter::commit FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::COMMIT_TRANSACTION->value, $e);
            }
        }
        return ($success);
    }

    public function rollBack(): bool
    {
        $success = false;
        if ($this->dbh !== null) {
            try {
                $success = $this->dbh->rollBack();
            } catch (\PDOException $e) {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOBaseAdapter::rollBack FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::ROLLBACK_TRANSACTION->value, $e);
            }
        }
        return ($success);
    }

    public function exec(string $query): int|false
    {
        if ($this->dbh !== null) {
            $affectedRows = 0;
            try {
                $affectedRows = $this->dbh->exec($query);
            } catch (\PDOException $e) {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOBaseAdapter::exec FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::EXEC->value, $e);
            }
            return ($affectedRows);
        } else {
            return (false);
        }
    }

    /**
     * @param array<\aportela\DatabaseWrapper\Param\InterfaceParam> $params
     */
    public function execute(string $query, array $params = array()): bool
    {
        $success = false;
        if ($this->dbh !== null) {
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
                $success = $stmt->execute();
            } catch (\PDOException $e) {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOBaseAdapter::execute FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::EXECUTE->value, $e);
            }
        }
        return ($success);
    }

    /**
     * @param array<\aportela\DatabaseWrapper\Param\InterfaceParam> $params
     * @return array<Object>
     */
    public function query(string $query, array $params = array()): array
    {
        $rows = array();
        // TODO: change return types to array|false ?
        if ($this->dbh !== null) {
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
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOBaseAdapter::query FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::QUERY->value, $e);
            }
        }
        return ($rows);
    }

    public function close(): void
    {
        $this->dbh = null;
    }

    public function isSchemaInstalled(): bool
    {
        return (false);
    }
}
