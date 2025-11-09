<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper;

final class DB
{
    public function __construct(private ?\aportela\DatabaseWrapper\Adapter\InterfaceAdapter $interfaceAdapter, private readonly \Psr\Log\LoggerInterface $logger)
    {
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
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            try {
                $activeTransaction = $this->interfaceAdapter->inTransaction();
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $this->logger->error("DatabaseWrapper::inTransaction FAILED");
                throw $e;
            }
        }

        return ($activeTransaction);
    }

    public function beginTransaction(): bool
    {
        $this->logger->debug("DatabaseWrapper::beginTransaction");
        $success = false;
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            try {
                $success = $this->interfaceAdapter->beginTransaction();
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $this->logger->error("DatabaseWrapper::beginTransaction FAILED");
                throw $e;
            }
        }

        return ($success);
    }

    public function commit(): bool
    {
        $this->logger->debug("DatabaseWrapper::commit");
        $success = false;
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            try {
                $success = $this->interfaceAdapter->commit();
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $this->logger->error("DatabaseWrapper::commit FAILED");
                throw $e;
            }
        }

        return ($success);
    }

    public function rollBack(): bool
    {
        $this->logger->debug("DatabaseWrapper::rollBack");
        $success = false;
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            try {
                $success = $this->interfaceAdapter->rollBack();
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $this->logger->error("DatabaseWrapper::rollBack FAILED");
                throw $e;
            }
        }

        return ($success);
    }

    /**
     * @param array<\aportela\DatabaseWrapper\Param\InterfaceParam> $params
     */
    private function parseQuery(string $query, array $params = []): string
    {
        foreach ($params as $param) {
            if ($param::class == "aportela\DatabaseWrapper\Param\StringParam") {
                $query = str_replace($param->getName(), "'" . ($param->getValue() ?? "") . "'", $query);
            } else {
                $query = str_replace($param->getName(), strval($param->getValue() ?? ""), $query);
            }
        }

        $expression = '/[\r\n\t]/';
        $query = preg_replace($expression, " ", $query);
        $expression = '/\s+/';
        $query = preg_replace($expression, " ", strval($query));
        return (strval($query));
    }

    public function exec(string $query): int|false
    {
        $this->logger->debug("DatabaseWrapper::exec", ["SQL" => $this->parseQuery($query)]);
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            $rowCount = 0;
            try {
                $rowCount = $this->interfaceAdapter->exec($query);
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $previousException = $e->getPrevious();
                $this->logger->error("DatabaseWrapper::exec FAILED", ["ERROR" => $previousException instanceof \Throwable ? $previousException->getMessage() : $e->getMessage()]);
                throw $e;
            }

            return ($rowCount);
        } else {
            return (false);
        }
    }

    /**
     * @param array<\aportela\DatabaseWrapper\Param\InterfaceParam> $params
     */
    public function execute(string $query, array $params = []): bool
    {
        $this->logger->debug("DatabaseWrapper::execute", ["SQL" => $this->parseQuery($query, $params)]);
        $success = false;
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            try {
                $success = $this->interfaceAdapter->execute($query, $params);
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $previousException = $e->getPrevious();
                $this->logger->error("DatabaseWrapper::execute FAILED", ["ERROR" => $previousException instanceof \Throwable ? $previousException->getMessage() : $e->getMessage()]);
                throw $e;
            }
        }

        return ($success);
    }

    /**
     * @param array<\aportela\DatabaseWrapper\Param\InterfaceParam> $params
     * @return array<Object>
     */
    public function query(string $query, array $params = [], ?callable $afterQueryFunction = null): array
    {
        $this->logger->debug("DatabaseWrapper::query", ["SQL" => $this->parseQuery($query, $params)]);
        $rows = [];
        // TODO: change return types to array|false ?
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            try {
                $rows = $this->interfaceAdapter->query($query, $params);
                if ($afterQueryFunction != null) {
                    call_user_func($afterQueryFunction, $rows);
                }
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $previousException = $e->getPrevious();
                $this->logger->error("DatabaseWrapper::query FAILED", ["ERROR" => $previousException instanceof \Throwable ? $previousException->getMessage() : $e->getMessage()]);
                throw $e;
            }
        }

        return ($rows);
    }

    public function close(): void
    {
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            $this->interfaceAdapter->close();
            $this->interfaceAdapter = null;
        }
    }

    public function isSchemaInstalled(): bool
    {
        $this->logger->info("DatabaseWrapper::isSchemaInstalled");
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            return ($this->interfaceAdapter->isSchemaInstalled());
        } else {
            return (false);
        }
    }

    public function installSchema(): bool
    {
        $this->logger->info("DatabaseWrapper::installSchema");
        // allow transactions only for sqlite adapters
        // FROM php documentation:
        // Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
        // The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
        $success = false;
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
            $success = $this->beginTransaction();
        } elseif ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter) {
            $success = true;
        } else {
            throw new \aportela\DatabaseWrapper\Exception\DBException("DB::isSchemaInstalled FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_ADAPTER->value);
        }

        if ($success) {
            $installed = false;
            try {
                foreach ($this->interfaceAdapter->getSchema()->getInstallQueries() as $query) {
                    $this->exec($query);
                }

                $installed = true;
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
                $this->logger->error("DatabaseWrapper::installSchema", [$e->getMessage()]);
            } finally {
                if ($installed) {
                    if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                        $this->commit();
                    }

                    $this->logger->info("DatabaseWrapper::installSchema SUCCESS");
                } else {
                    if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
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
        $results = $this->query($this->interfaceAdapter->getSchema()->getLastVersionQuery());
        if (count($results) === 1 && isset($results[0]->release_number)) {
            return ($results[0]->release_number);
        } else {
            return (-1);
        }
    }

    public function getUpgradeSchemaVersion(): int
    {
        $this->logger->info("DatabaseWrapper::getUpgradeSchemaVersion");
        $lastVersion = -1;
        foreach (array_keys($this->interfaceAdapter->getSchema()->getUpgradeQueries()) as $version) {
            if ($version > $lastVersion) {
                $lastVersion = $version;
            }
        }

        return ($lastVersion);
    }

    public function upgradeSchema(bool $backup = true): int
    {
        $this->logger->info("DatabaseWrapper::upgradeSchema");
        $results = $this->query($this->interfaceAdapter->getSchema()->getLastVersionQuery());
        if (count($results) === 1) {
            if ($backup && $this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter) {
                $this->interfaceAdapter->backup("");
            }

            // allow transactions only for sqlite adapters
            // FROM php documentation:
            // Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
            // The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
            $success = false;
            if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                $success = $this->beginTransaction();
            } elseif ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter) {
                $success = true;
            } else {
                throw new \aportela\DatabaseWrapper\Exception\DBException("DB::isSchemaInstalled FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_ADAPTER->value);
            }

            if ($success && isset($results[0]->release_number)) {
                $success = false;
                $currentVersion = $results[0]->release_number;
                try {
                    foreach ($this->interfaceAdapter->getSchema()->getUpgradeQueries() as $version => $queries) {
                        if ($version > $currentVersion) {
                            foreach ($queries as $query) {
                                $this->exec($query);
                            }

                            $this->execute(
                                $this->interfaceAdapter->getSchema()->getSetVersionQuery(),
                                [
                                    new \aportela\DatabaseWrapper\Param\IntegerParam(":release_number", $version)
                                ]
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
                        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
                            $this->commit();
                        }

                        $this->logger->info("DatabaseWrapper::upgradeSchema SUCCESS");
                    } else {
                        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter || $this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter) {
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
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter) {
            return ($this->interfaceAdapter->backup($path));
        } else {
            throw new \aportela\DatabaseWrapper\Exception\DBException("DB::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_ADAPTER->value);
        }
    }

    public function getAdapterType(): \aportela\DatabaseWrapper\Adapter\AdapterType
    {
        if ($this->interfaceAdapter instanceof \aportela\DatabaseWrapper\Adapter\InterfaceAdapter) {
            switch ($this->interfaceAdapter::class) {
                case "aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter":
                    return \aportela\DatabaseWrapper\Adapter\AdapterType::PDO_MariaDB;
                case "aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter":
                    return \aportela\DatabaseWrapper\Adapter\AdapterType::PDO_PostgreSQL;
                case "aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter":
                    return \aportela\DatabaseWrapper\Adapter\AdapterType::PDO_SQLite;
                default:
                    return \aportela\DatabaseWrapper\Adapter\AdapterType::NONE;
            }
        } else {
            return \aportela\DatabaseWrapper\Adapter\AdapterType::NONE;
        }
    }
}
