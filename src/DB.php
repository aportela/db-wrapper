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
        //$this->close();
    }

    public function inTransaction(): bool
    {
        $this->logger->debug("DatabaseWrapper::inTransaction");
        $success = false;
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

    public function exec(string $query, $params = array()): int
    {
        $this->logger->debug("DatabaseWrapper::exec");
        $rowCount = 0;
        try {
            $rowCount = $this->adapter->exec($query, $params);
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::query FAILED", array("QUERY" => $query, "PARAMS" => $params, "ERROR" => $e->getPrevious()->getMessage()));
            throw $e;
        }
        return ($rowCount);
    }

    public function query(string $query, $params = array()): array
    {
        $this->logger->debug("DatabaseWrapper::query");
        $rows = array();
        try {
            $rows = $this->adapter->query($query, $params);
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            $this->logger->error("DatabaseWrapper::query FAILED", array("QUERY" => $query, "PARAMS" => $params, "ERROR" => $e->getPrevious()->getMessage()));
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
        $installed = false;
        try {
            $results = $this->query(" SELECT COUNT(name) AS table_count FROM sqlite_master WHERE type='table' AND name='VERSION'; ");
            $installed = is_array($results) && count($results) == 1 && $results[0]->table_count == 1;
        } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
        }
        return ($installed);
    }

    public function installSchema(): bool
    {
        $this->logger->info("DatabaseWrapper::installSchema");
        if ($this->beginTransaction()) {
            $installed = false;
            try {
                foreach ($this->adapter->schema->getInstallQueries() as $query) {
                    $this->exec($query);
                }
                $installed = true;
            } catch (\aportela\DatabaseWrapper\Exception\DBException $e) {
            } finally {
                if ($installed) {
                    $this->commit();
                    $this->logger->info("DatabaseWrapper::installSchema SUCCESS");
                } else {
                    $this->rollback();
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
            if ($backup) {
                $this->backup();
            }
            if ($this->beginTransaction()) {
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
                        $this->commit();
                        $this->logger->info("DatabaseWrapper::upgradeSchema SUCCESS");
                    } else {
                        $this->rollback();
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
        if (is_a($this->adapter, \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter::class)) {
            if (file_exists(($this->adapter->databasePath))) {
                $backupFilePath = "";
                if (empty($path)) {
                    $backupFilePath = dirname($this->adapter->databasePath) . DIRECTORY_SEPARATOR . "backup-" . time() . "-" . uniqid() . ".sqlite";
                } elseif (is_dir($path)) {
                    $backupFilePath = realpath($path) . DIRECTORY_SEPARATOR . "backup-" . uniqid() . ".sqlite";
                } else {
                    throw new \aportela\DatabaseWrapper\Exception\DBException("DB::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_BACKUP_PATH->value);
                }
                $this->exec(" VACUUM main INTO :path", [new \aportela\DatabaseWrapper\Param\StringParam(":path", $backupFilePath)]);
                return ($backupFilePath);
            } else {
                throw new \aportela\DatabaseWrapper\Exception\DBException("DB::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::DATABASE_NOT_FOUND->value);
            }
        } else {
            throw new \aportela\DatabaseWrapper\Exception\DBException("DB::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_ADAPTER->value);
        }
    }
}
