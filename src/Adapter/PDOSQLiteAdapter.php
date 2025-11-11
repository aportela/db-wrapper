<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Adapter;

final class PDOSQLiteAdapter extends PDOBaseAdapter
{
    public const int FLAGS_PRAGMA_JOURNAL_WAL = 1;

    public const int FLAGS_PRAGMA_FOREIGN_KEYS_ON = 2;

    public string $databasePath;

    /**
     * @param array<int, bool|int> $options
     */
    public function __construct(string $databasePath, string $upgradeSchemaPath = "", array $options = [], int $flags = 0)
    {
        try {
            $this->databasePath = $databasePath;
            $this->dbh = new \PDO(
                sprintf("sqlite:%s", $databasePath),
                null,
                null,
                $options,
            );
            if (($flags & self::FLAGS_PRAGMA_JOURNAL_WAL) !== 0) {
                $this->dbh->exec("PRAGMA journal_mode = WAL;");
            }

            if (($flags & self::FLAGS_PRAGMA_FOREIGN_KEYS_ON) !== 0) {
                $this->dbh->exec("PRAGMA foreign_keys = ON;");
            }

            $this->schema = new \aportela\DatabaseWrapper\Schema\PDOSQLiteSchema(
                $upgradeSchemaPath,
                \aportela\DatabaseWrapper\Schema\PDOSQLiteSchema::INSTALL_QUERIES,
                \aportela\DatabaseWrapper\Schema\PDOSQLiteSchema::SET_CURRENT_VERSION_QUERY,
                \aportela\DatabaseWrapper\Schema\PDOSQLiteSchema::GET_CURRENT_VERSION_QUERY
            );
        } catch (\PDOException $pdoException) {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::__construct FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::CONSTRUCTOR->value, $pdoException);
        }
    }

    #[\Override]
    public function isSchemaInstalled(): bool
    {
        $results = $this->query(" SELECT COUNT(name) AS table_count FROM sqlite_master WHERE type='table' AND name='VERSION'; ");
        return (count($results) === 1 && isset($results[0]->table_count) && $results[0]->table_count == 1);
    }

    public function backup(string $path): string
    {
        if (file_exists(($this->databasePath))) {
            $backupFilePath = "";
            if ($path === '' || $path === '0') {
                $backupFilePath = dirname($this->databasePath) . DIRECTORY_SEPARATOR . "backup-" . time() . "-" . uniqid() . ".sqlite";
            } elseif (is_dir($path)) {
                $backupFilePath = realpath($path) . DIRECTORY_SEPARATOR . "backup-" . uniqid() . ".sqlite";
            } else {
                throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_BACKUP_PATH->value);
            }

            $this->execute(" VACUUM main INTO :path", [new \aportela\DatabaseWrapper\Param\StringParam(":path", $backupFilePath)]);
            return ($backupFilePath);
        } else {
            throw new \aportela\DatabaseWrapper\Exception\DBException("PDOSQLiteAdapter::backup FAILED", \aportela\DatabaseWrapper\Exception\DBExceptionCode::DATABASE_NOT_FOUND->value);
        }
    }
}
