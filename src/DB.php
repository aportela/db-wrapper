<?php

    namespace aportela\DatabaseWrapper;

    class DB
    {
        protected $adapter;
        protected $logger;

        public function __construct(Adapter\InterfaceAdapter $adapter, \Monolog\Logger $logger)
        {
            $this->adapter = $adapter;
            $this->logger = $logger;
            $this->logger->debug("DatabaseWrapper::__construct");
        }

        public function __destruct()
        {
            $this->logger->debug("DatabaseWrapper::__destruct");
        }

        public function beginTransaction (): bool
        {
            $this->logger->debug("DatabaseWrapper::beginTransaction");
            $success = false;
            try
            {
                $success = $this->adapter->beginTransaction();
            }
            catch (\aportela\DatabaseWrapper\Exception\DBException $e)
            {
                $this->logger->critical("DatabaseWrapper::beginTransaction FAILED");
                throw $e;
            }
            return($success);
        }

        public function commit(): bool
        {
            $this->logger->debug("DatabaseWrapper::commit");
            $success = false;
            try
            {
                $success = $this->adapter->commit();
            }
            catch (\aportela\DatabaseWrapper\Exception\DBException $e)
            {
                $this->logger->critical("DatabaseWrapper::commit FAILED");
                throw $e;
            }
            return($success);
        }

        public function rollBack (): bool
        {
            $this->logger->debug("DatabaseWrapper::rollBack");
            $success = false;
            try
            {
                $success = $this->adapter->commit();
            }
            catch (\aportela\DatabaseWrapper\Exception\DBException $e)
            {
                $this->logger->critical("DatabaseWrapper::rollBack FAILED");
                throw $e;
            }
            return($success);
        }

        public function exec(string $query, $params = array()): int
        {
            $this->logger->debug("DatabaseWrapper::exec");
            $rowCount = 0;
            try
            {
                $rowCount = $this->adapter->exec($query, $params);
            }
            catch (\aportela\DatabaseWrapper\Exception\DBException $e)
            {
                $this->logger->critical("DatabaseWrapper::query FAILED");
                $this->logger->debug("DatabaseWrapper::query DETAILS", array("QUERY" => $query, "PARAMS" => $params));
                throw $e;
            }
            return($rowCount);
        }

        public function query(string $query, $params = array()): array
        {
            $this->logger->debug("DatabaseWrapper::query");
            $rows = array();
            try
            {
                $rows = $this->adapter->query($query, $params);
            }
            catch (\aportela\DatabaseWrapper\Exception\DBException $e)
            {

                $this->logger->critical("DatabaseWrapper::query FAILED");
                $this->logger->debug("DatabaseWrapper::query DETAILS", array("QUERY" => $query, "PARAMS" => $params));
                throw $e;
            }
            return($rows);
        }

    }

?>