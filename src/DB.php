<?php

namespace aportela\DatabaseWrapper;

final class DB
{
    protected ?\aportela\DatabaseWrapper\Adapter\InterfaceAdapter $adapter;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(\aportela\DatabaseWrapper\Adapter\InterfaceAdapter $adapter, \Psr\Log\LoggerInterface $logger)
    {
        $this->adapter = $adapter;
        $this->logger = $logger;
        $this->logger->debug("DatabaseWrapper::__construct");
    }

    public function __destruct()
    {
        $this->logger->debug("DatabaseWrapper::__destruct");
    }

    public function inTransaction(): bool
    {
        $this->logger->debug("DatabaseWrapper::inTransaction");
        $activeTransaction = false;
        try {
            $activeTransaction = $this->adapter->inTransaction();
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::inTransaction FAILED");
            throw $e;
        }
        return ($activeTransaction);
    }

    public function beginTransaction(): bool
    {
        $this->logger->debug("DatabaseWrapper::beginTransaction");
        $success = false;
        try {
            $success = $this->adapter->beginTransaction();
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::beginTransaction FAILED");
            throw $e;
        }
        return ($success);
    }

    public function commit(): bool
    {
        $this->logger->debug("DatabaseWrapper::commit");
        $success = false;
        try {
            $success = $this->adapter->commit();
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::commit FAILED");
            throw $e;
        }
        return ($success);
    }

    public function rollBack(): bool
    {
        $this->logger->debug("DatabaseWrapper::rollBack");
        $success = false;
        try {
            $success = $this->adapter->rollBack();
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::rollBack FAILED");
            throw $e;
        }
        return ($success);
    }

    private function parseQuery(string $query, $params = array()): string
    {
        foreach ($params as $param) {
            if (get_class($param) == "aportela\DatabaseWrapper\Param\StringParam") {
                $query = str_replace($param->getName(), "'" . ($param->getValue() ?? "") . "'", $query);
            } else {
                $query = str_replace($param->getName(), $param->getValue() ?? "", $query);
            }
        }
        $expression = '/[\r\n\t]/';
        $query = preg_replace($expression, " ", $query);
        $expression = '/\s+/';
        $query = preg_replace($expression, " ", $query);
        return ($query);
    }

    public function exec(string $query, $params = array()): int
    {
        $this->logger->debug("DatabaseWrapper::exec", array("SQL" => $this->parseQuery($query, $params)));
        $rowCount = 0;
        try {
            $rowCount = $this->adapter->exec($query, $params);
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::exec FAILED", array("ERROR" => $e->getPrevious()->getMessage()));
            throw $e;
        }
        return ($rowCount);
    }

    public function query(string $query, $params = array()): array
    {
        $this->logger->debug("DatabaseWrapper::query", array("SQL" => $this->parseQuery($query, $params)));
        $rows = array();
        try {
            $rows = $this->adapter->query($query, $params);
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::query FAILED", array($params, "ERROR" => $e->getPrevious()->getMessage()));
            throw $e;
        }
        return ($rows);
    }

    public function close(): void
    {
        $this->adapter->close();
        $this->adapter = null;
    }

    public function isSchemaInstalled(): bool
    {
        $this->logger->info("DatabaseWrapper::isSchemaInstalled");
        return ($this->adapter->isSchemaInstalled());
    }

    public function installSchema(): bool
    {
        $this->logger->info("DatabaseWrapper::installSchema");
        // allow transactions only for sqlite adapters
        // FROM php documentation:
        // Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
        // The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
        $success = false;
        if ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
            $success = $this->beginTransaction();
        } elseif ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter) {
            $success = true;
        } else {
            throw new \aportela\DatabaseWrapper\Exception\DBException("DB::isSchemaInstalled FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_ADAPTER->value);
        }
        if ($success) {
            $installed = false;
            try {
                foreach ($this->adapter->schema->getInstallQueries() as $query) {
                    $this->exec($query);
                }
                $installed = true;
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $this->logger->error("DatabaseWrapper::installSchema", [$e->getMessage()]);
            } finally {
                if ($installed) {
                    if ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                        $this->commit();
                    }
                    $this->logger->info("DatabaseWrapper::installSchema SUCCESS");
                } else {
                    if ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                        $this->rollback();
                    }
                    $this->logger->emergency("DatabaseWrapper::installSchema FAILED");
                }
            }
            return ($installed);
        } else {
            $this->logger->emergency("DatabaseWrapper::installSchema FAILED (ERROR OPENING TRANSACTION)");
            return (false);
        }
    }

    public function getCurrentSchemaVersion(): int
    {
        $this->logger->info("DatabaseWrapper::getCurrentSchemaVersion");
        $results = $this->query($this->adapter->schema->getLastVersionQuery());
        if (count($results) == 1) {
            return ($results[0]->release_number);
        } else {
            return (-1);
        }
    }

    public function getUpgradeSchemaVersion(): int
    {
        $this->logger->info("DatabaseWrapper::getUpgradeSchemaVersion");
        $lastVersion = -1;
        foreach ($this->adapter->schema->getUpgradeQueries() as $version => $queries) {
            if ($version > $lastVersion) {
                $lastVersion = $version;
            }
        }
        return ($lastVersion);
    }

    public function upgradeSchema(bool $backup = true): int
    {
        $this->logger->info("DatabaseWrapper::upgradeSchema");
        $results = $this->query($this->adapter->schema->getLastVersionQuery());
        if (count($results) == 1) {
            if ($backup && $this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter) {
                $this->adapter->backup("");
            }
            // allow transactions only for sqlite adapters
            // FROM php documentation:
            // Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
            // The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
            $success = false;
            if ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                $success = $this->beginTransaction();
            } elseif ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter) {
                $success = true;
            } else {
                throw new \aportela\DatabaseWrapper\Exception\DBException("DB::isSchemaInstalled FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_ADAPTER->value);
            }
            if ($success) {
                $success = false;
                $currentVersion = $results[0]->release_number;
                try {
                    foreach ($this->adapter->schema->getUpgradeQueries() as $version => $queries) {
                        if ($version > $currentVersion) {
                            foreach ($queries as $query) {
                                $this->exec($query);
                            }
                            $this->exec(
                                $this->adapter->schema->getSetVersionQuery(),
                                array(
                                    new \aportela\DatabaseWrapper\Param\IntegerParam(":release_number", $version)
                                )
                            );
                            $currentVersion = $version;
                            $this->logger->info("DatabaseWrapper::upgradeSchema version upgraded to " . $currentVersion);
                        }
                    }
                    $success = true;
                } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                    throw $e;
                } finally {
                    if ($success) {
                        if ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                            $this->commit();
                        }
                        $this->logger->info("DatabaseWrapper::upgradeSchema SUCCESS");
                    } else {
                        if ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                            $this->rollback();
                        }
                        $this->logger->emergency("DatabaseWrapper::upgradeSchema FAILED");
                    }
                }
                return ($currentVersion);
            } else {
                $this->logger->emergency("DatabaseWrapper::upgradeSchema FAILED (ERROR OPENING TRANSACTION)");
                return (-1);
            }
        } else {
            $this->logger->emergency("DatabaseWrapper::upgradeSchema FAILED (NO PREVIOUS VERSION FOUND)");
            return (-1);
        }
    }

    public function backup(string $path = ""): string
    {
        if ($this->adapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter) {
            return ($this->adapter->backup($path));
        } else {
            throw new \aportela\DatabaseWrapper\Exception\DBException("DB::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_ADAPTER->value);
        }
    }
}
