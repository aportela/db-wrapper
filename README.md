# db-wrapper

Custom php PDO database wrapper

## Requirements

- mininum php version 8.4

## Limitations

At this time only SQLite, MariaDB/MySQL, PostgreSQL adapters are supported.

# install

```Shell
composer require "aportela/db-wrapper"
```

# install / initializate (SQLite) database example

```php
<?php
    require ("vendor/autoload.php");

    $settings = array
    (
        "database" => array
        (
            // SQLite settings (filename & path)
            "filename" => "test.db",
            "path" => __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR
            // custom extra settings for other adapters (MariaDB/PostgreSQL)
            /*
            "host" => "127.0.0.1",
            "port" = > 3306,
            "username" => "foo",
            "password" => "bar",
            "db"=> "mydb"
            */
        ),
        /* uncoment next setting & comment NullLogger constructor if you want file logs with (my) custom rotating handler*/
        /*
        "logger" => array
        (
            "name" => "test",
            "filename" => "test-DEBUG.log",
            "path" => __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR,
            "level" => \Monolog\Logger::DEBUG
        )
        */
    );

    // create database directory if not found
    if (! file_exists($settings["database"]["path"]))
    {
        mkdir($settings["database"]["path"]);
    }

    /*
    // uncoment this & comment NullLogger constructor if you want file logs with (my) custom rotating handler
    // create log directory if not found
    if (! file_exists($settings["logger"]["path"]))
    {
        mkdir($settings["logger"]["path"]);
    }

    // logger (monolog) definition
    $logger = new \Monolog\Logger($settings["logger"]["name"]);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $handler = new \Monolog\Handler\RotatingFileHandler($settings["logger"]["path"] . $settings["logger"]["filename"], 0, $settings["logger"]["level"]);
    $handler->setFilenameFormat('{date}/{filename}', \Monolog\Handler\RotatingFileHandler::FILE_PER_DAY);
    $logger->pushHandler($handler);
    */

    // null logger (monolog) definition
    $logger = new \Psr\Log\NullLogger("");

    // we are using PDO sqlite adapter
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter(
        $settings["database"]["path"] . $settings["database"]["filename"]
    );
    /*
    // MariaDB adapter
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter(
        $settings["database"]["host"],
        $settings["database"]["port"],
        $settings["database"]["db"],
        $settings["database"]["username"],
        $settings["database"]["password"]
    );
    // PostgreSQL adapter
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter(
        $settings["database"]["host"],
        $settings["database"]["port"],
        $settings["database"]["db"],
        $settings["database"]["username"],
        $settings["database"]["password"]
    );
    */
    // main object
    $db = new \aportela\DatabaseWrapper\DB
    (
        $adapter,
        $logger
    );

    $success = true;
    // check if the database is already installed (install scheme with version table already exists)
    if (! $db->isSchemaInstalled())
    {
        if ($db->installSchema())
        {
            echo "Database install success" . PHP_EOL;
        } else
        {
            echo sprintf("Database install error, check logs (at %s)%s", $settings["logger"]["path"], PHP_EOL);
            $success = false;
        }
    } else
    {
        echo "Database already installed" . PHP_EOL;
    }

    if ($success)
    {
        $results = $db->query(" SELECT release_number, release_date FROM VERSION; ");
        if (is_array($results) && count($results) == 1)
        {
            echo sprintf("Current version: %s (installed on: %s)%s", $results[0]->release_number, $results[0]->release_date, PHP_EOL);
        }
        else
        {
            echo "SQL error" . PHP_EOL;
        }
    }
?>
```

# upgrade (SQLite) schema & exec some queries

```php
<?php
    require ("vendor/autoload.php");

    $settings = array
        (
        "database" => array
        (
            // SQLite settings (filename & path)
            "filename" => "test.db",
            "path" => __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR,
            "upgradeSchemaPath" => __DIR__ . DIRECTORY_SEPARATOR . "upgrade.sql"
            // custom extra settings for other adapters (MariaDB/PostgreSQL)
            /*
            "host" => "127.0.0.1",
            "port" = > 3306,
            "username" => "foo",
            "password" => "bar",
            "db"=> "mydb"
            */
        ),
        /* uncoment next setting & comment NullLogger constructor if you want file logs with (my) custom rotating handler*/
        /*
        "logger" => array
        (
            "name" => "test",
            "filename" => "test-DEBUG.log",
            "path" => __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR,
            "level" => \Monolog\Logger::DEBUG
        )
        */
    );

    // create database directory if not found
    if (! file_exists($settings["database"]["path"]))
    {
        mkdir($settings["database"]["path"]);
    }

    if (! file_exists($settings["database"]["upgradeSchemaPath"]))
    {
        die(sprintf("Upgrade schema not found (at %s)%s", $settings["database"]["upgradeSchemaPath"], PHP_EOL));
    }

    /*
    // uncoment this & comment NullLogger constructor if you want file logs with (my) custom rotating handler
    // create log directory if not found
    if (! file_exists($settings["logger"]["path"]))
    {
        mkdir($settings["logger"]["path"]);
    }

    // logger (monolog) definition
    $logger = new \Monolog\Logger($settings["logger"]["name"]);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $handler = new \Monolog\Handler\RotatingFileHandler($settings["logger"]["path"] . $settings["logger"]["filename"], 0, $settings["logger"]["level"]);
    $handler->setFilenameFormat('{date}/{filename}', \Monolog\Handler\RotatingFileHandler::FILE_PER_DAY);
    $logger->pushHandler($handler);
    */

    // null logger (monolog) definition
    $logger = new \Psr\Log\NullLogger("");

    // we are using PDO sqlite adapter, also set the upgrade scheme (point to a local file)
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter(
        $settings["database"]["path"] . $settings["database"]["filename"],
        // READ upgrade SQL schema file definition on next block of this README.md
        $settings["database"]["upgradeSchemaPath"],
        // optional param, bitmask to set "PRAGMA journal_mode = WAL" && "PRAGMA foreign_keys = ON"
        \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter::FLAGS_PRAGMA_JOURNAL_WAL | \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter::FLAGS_PRAGMA_FOREIGN_KEYS_ON
    );

    /*
    // MariaDB adapter
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter(
        $settings["database"]["host"],
        $settings["database"]["port"],
        $settings["database"]["db"],
        $settings["database"]["username"],
        $settings["database"]["password"],
        $settings["database"]["upgradeSchemaPath"]
    );
    // PostgreSQL adapter
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOPostgreSQLAdapter(
        $settings["database"]["host"],
        $settings["database"]["port"],
        $settings["database"]["db"],
        $settings["database"]["username"],
        $settings["database"]["password"],
        $settings["database"]["upgradeSchemaPath"]
    );
    */

    // main object
    $db = new \aportela\DatabaseWrapper\DB
    (
        $adapter,
        $logger
    );

    // try to upgrade SQL schema to last version (making a backup before any modification, change parameter to false to skip creating the backup, NOT RECOMMENDED)
    $currentVersion = $db->upgradeSchema(true);
    if ($currentVersion !== -1)
    {
        echo sprintf("Database upgrade success, current version: %s%s", $currentVersion, PHP_EOL);
        $db->query(" CREATE TABLE IF NOT EXISTS MYTABLE (id INTEGER PRIMARY KEY, name VARCHAR(32)); ");
        $db->query(" INSERT INTO MYTABLE (id, name) VALUES (:id, :name); ",
            [
                new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 1),
                new \aportela\DatabaseWrapper\Param\StringParam(":name", "foobar-" .uniqid())
            ]
        );

        $results = $db->query(" SELECT id, name FROM MYTABLE ORDER BY id DESC LIMIT 1 ");
        if (is_array($results) && count($results) == 1)
        {
            echo sprintf("Last row was id: %s - name: %s%s", $results[0]->id, $results[0]->name, PHP_EOL);
        }
        else
        {
            echo sprintf("SQL error, check logs (at %s)%s", $settings["logger"]["path"], PHP_EOL);
        }

        // after query custom function example for "parsing" rows
        $afterQueryFunction = function ($rows) {
            array_map(
                function ($item) {
                    // duplicate name property into new customField
                    $item->customField = $item->name;
                    return ($item);
                },
                $rows
            );
        };
        // use custom params && (optional) after query function
        $results = $db->query(
            " SELECT id, name FROM MYTABLE WHERE id > :id ORDER BY id DESC LIMIT 1 ",
            [
                new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 0),
            ],
            $afterQueryFunction
        );
        if (is_array($results) && count($results) == 1)
        {
            echo sprintf("Last row was id: %s - name: %s - customField: $%s%s", $results[0]->id, $results[0]->name, $results[0]->customField, PHP_EOL);
        }
        else
        {
            echo sprintf("SQL error, check logs (at %s)%s", $settings["logger"]["path"], PHP_EOL);
        }
    }
    else
    {
        echo sprintf("Database upgrade error, check logs (at %s)%s", $settings["logger"]["path"], PHP_EOL);
    }
?>
```

# upgrade SQL schema file definition example

defined on $settings block of previous example

```php
"upgradeSchemaPath" => __DIR__ . DIRECTORY_SEPARATOR . "upgrade.sql"
```

```php
<?php

    return
    (
        array
        (
            1 => array
            (
                " CREATE TABLE IF NOT EXISTS TABLEV1 (id INTEGER PRIMARY KEY); ",
                " INSERT INTO TABLEV1 VALUES (1); "
            ),
            2 => array
            (
                " CREATE TABLE IF NOT EXISTS TABLEV2 (id INTEGER PRIMARY KEY); ",
            )
        )
    );

?>
```

![PHP Composer](https://github.com/aportela/db-wrapper/actions/workflows/php.yml/badge.svg)
