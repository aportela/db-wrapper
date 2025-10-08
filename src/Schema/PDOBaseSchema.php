<?php

namespace aportela\DatabaseWrapper\Schema;

class PDOBaseSchema implements InterfaceSchema
{
    protected string $upgradeSchemaPath;
    /**
     * @var array<string> $installQueries
     */
    protected array $installQueries;
    protected string $setCurrentVersionQuery;
    protected string $getCurrentVersionQuery;

    /**
     * @param array<string> $installQueries
     */
    public function __construct(string $upgradeSchemaPath = "", array $installQueries = [], string $setCurrentVersionQuery = "", string $getCurrentVersionQuery = "")
    {
        $this->upgradeSchemaPath = $upgradeSchemaPath;
        $this->installQueries = $installQueries;
        $this->setCurrentVersionQuery = $setCurrentVersionQuery;
        $this->getCurrentVersionQuery = $getCurrentVersionQuery;
    }

    /**
     * @return array<string>
     */
    public function getInstallQueries(): array
    {
        return ($this->installQueries);
    }

    public function getSetVersionQuery(): string
    {
        return ($this->setCurrentVersionQuery);
    }

    public function getLastVersionQuery(): string
    {
        return ($this->getCurrentVersionQuery);
    }

    /**
     *  @return array<int, array<string>>
     */
    public function getUpgradeQueries(): array
    {
        if (!empty($this->upgradeSchemaPath)) {
            if (file_exists($this->upgradeSchemaPath)) {
                $queries = include $this->upgradeSchemaPath;
                if (is_array($queries)) {
                    return $queries;
                } else {
                    throw new \Exception("Invalid schema file (not array) at " . $this->upgradeSchemaPath);
                }
            } else {
                throw new \Exception("Upgrade database schema file not found at " . $this->upgradeSchemaPath);
            }
        } else {
            return (array());
        }
    }
}
