<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Schema;

abstract class PDOBaseSchema implements InterfaceSchema
{
    /**
     * @param array<string> $installQueries
     */
    public function __construct(protected string $upgradeSchemaPath = "", protected array $installQueries = [], protected string $setCurrentVersionQuery = "", protected string $getCurrentVersionQuery = "")
    {
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
        if ($this->upgradeSchemaPath !== '' && $this->upgradeSchemaPath !== '0') {
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
            return ([]);
        }
    }
}
