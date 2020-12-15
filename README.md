# db-wrapper
Custom php database wrapper

# install
> composer require "aportela/db-wrapper:dev-master"

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
        "logger" => array
        (
            "name" => "test",
            "filename" => "test-DEBUG.log",
            "path" => __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR,
            "level" => \Monolog\Logger::DEBUG
        )
    );

    // create database directory if not found
    if (! file_exists($settings["database"]["path"]))
    {
        mkdir($settings["database"]["path"]);
    }
    
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