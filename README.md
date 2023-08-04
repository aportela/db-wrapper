# db-wrapper

Custom php PDO database wrapper

## Requirements

- mininum php version 8.x

## Limitations

At this time only SQLite is supported.

# install

> composer require "aportela/db-wrapper"

# install / initializate database example

```php
<?php
    require ("vendor/autoload.php");

    $settings = array
    (
        "database" => array
        (
            "filename" => "test.db",
            "path" => __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR
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

    // we are using PDO sqlite adapter (only available at this time)
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter($settings["database"]["path"] . $settings["database"]["filename"]);

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

# upgrade schema & exec some queries

```php
<?php
    require ("vendor/autoload.php");

    $settings = array
        (
        "database" => array
        (
            "filename" => "test.db",
            "path" => __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR,
            "upgradeSchemaPath" => __DIR__ . DIRECTORY_SEPARATOR . "upgrade.sql"
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

    if (! file_exists($settings["database"]["upgradeSchemaPath"]))
    {
        die(sprintf("Upgrade schema not found (at %s)%s", $settings["database"]["upgradeSchemaPath"], PHP_EOL));
    }

    // logger (monolog) definition
    $logger = new \Monolog\Logger($settings["logger"]["name"]);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $handler = new \Monolog\Handler\RotatingFileHandler($settings["logger"]["path"] . $settings["logger"]["filename"], 0, $settings["logger"]["level"]);
    $handler->setFilenameFormat('{date}/{filename}', \Monolog\Handler\RotatingFileHandler::FILE_PER_DAY);
    $logger->pushHandler($handler);

    // we are using PDO sqlite adapter (only available at this time), also set the upgrade scheme (point to a local file)
    $adapter = new \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter(
        $settings["database"]["path"] . $settings["database"]["filename"],
        // READ upgrade SQL schema file definition on next block of this README.md
        $settings["database"]["upgradeSchemaPath"]
    );

    // main object
    $db = new \aportela\DatabaseWrapper\DB
    (
        $adapter,
        $logger
    );

    // try to upgrade SQL schema to last version
    $currentVersion = $db->upgradeSchema();
    if ($currentVersion !== -1)
    {
        echo sprintf("Database upgrade success, current version: %s%s", $currentVersion, PHP_EOL);
        $db->query(" CREATE TABLE IF NOT EXISTS MYTABLE (id INTEGER PRIMARY KEY, name VARCHAR(32)); ");
        $db->query(" INSERT INTO MYTABLE (name) VALUES (:name); ",
            array
            (
                new \aportela\DatabaseWrapper\Param\StringParam(":name", "foobar-" .uniqid())
            )
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
