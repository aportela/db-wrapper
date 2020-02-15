<?php

    //namespace aportela\DatabaseWrapper;

    use \Monolog\Logger;
    use \Monolog\Handler\StreamHandler;
    use \Monolog\Formatter\LineFormatter;

    require __DIR__ . '/../vendor/autoload.php';

    $logger = new \Monolog\Logger('mylog');

    $fileHandler = new StreamHandler('your.log', Logger::DEBUG);
    $fileHandler->setFormatter(new LineFormatter());
    $logger->pushHandler($fileHandler);

    $db = new aportela\DatabaseWrapper\DB(
        new aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter(
            "./db.sqlite3"
        ),
        $logger
    );

    $db->beginTransaction();

    $db->query(" SELECT NULL WHERE A = :nn ", array(
        new \aportela\DatabaseWrapper\Param\IntegerParam(":nn", 2.2)
    ));
    $db->commit();

?>