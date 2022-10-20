<?php

namespace aportela\DatabaseWrapper;

class DB
{
    protected $adapter;
    protected $logger;

    public function __construct(Adapter\InterfaceAdapter $adapter, \Psr\Log\LoggerInterface $logger)
    {
        $this->adapter = $adapter;
        $this->logger = $logger;
        $this->logger->debug("DatabaseWrapper::__construct");
    }

    public function __destruct()
    {
        $this->logger->debug("DatabaseWrapper::__destruct");
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
            $success = $this->adapter->commit();
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

    public function upgradeSchema(): int
    {
        $this->logger->info("DatabaseWrapper::upgradeSchema");
        $results = $this->query($this->adapter->schema->getLastVersionQuery());
        if (count($results) == 1) {
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
}
