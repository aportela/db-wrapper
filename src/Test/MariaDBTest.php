<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Test;

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_mysql')]
final class MariaDBTest extends \PHPUnit\Framework\TestCase
{
    private static \aportela\DatabaseWrapper\DB $db;

    private static string $host;

    private static int $port;

    private static string $dbName;

    private static string $username;

    private static string $password;

    private static string $upgradeSchemaPath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $host = getenv('MARIADB_HOST', true);
        if (is_string($host)) {
            self::$host = $host;
        } else {
            self::markTestSkipped('Missing environment var MARIADB_HOST');
        }

        $port = getenv('MARIADB_PORT', true);
        self::$port = is_numeric($port) ? intval($port) : \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter::DEFAULT_PORT;

        $dbName = getenv('MARIADB_DBNAME', true);
        if (is_string($dbName)) {
            self::$dbName = $dbName;
        } else {
            self::markTestSkipped('Missing environment var MARIADB_DBNAME');
        }

        $username = getenv('MARIADB_USERNAME', true);
        self::$username = is_string($username) ? $username : "";

        $password = getenv('MARIADB_PASSWORD', true);
        self::$password = is_string($password) ? $password : "";

        self::$upgradeSchemaPath = tempnam(sys_get_temp_dir(), 'sql');
        $upgradeSchema = "
            <?php
                return
                (
                    array
                    (
                        1 => array
                        (
                            \" CREATE TABLE IF NOT EXISTS TABLEV1 (id INTEGER PRIMARY KEY); \",
                            \" INSERT INTO TABLEV1 VALUES (1); \"
                        ),
                        2 => array
                        (
                            \" CREATE TABLE IF NOT EXISTS TABLEV2 (id INTEGER PRIMARY KEY); \",
                            \" INSERT INTO TABLEV2 VALUES (-1); \"
                        )
                    )
                );
        ";
        if (!((self::$host === '' || self::$host === '0') || ((self::$dbName === '' || self::$dbName === '0')) || (self::$username === '' || self::$username === '0'))) {
            file_put_contents(self::$upgradeSchemaPath, trim($upgradeSchema));
            // main object
            self::$db = new \aportela\DatabaseWrapper\DB(
                new \aportela\DatabaseWrapper\Adapter\PDOMariaDBAdapter(self::$host, self::$port, self::$dbName, self::$username, self::$password, self::$upgradeSchemaPath),
                new \Psr\Log\NullLogger()
            );
            self::$db->execute(" DROP TABLE IF EXISTS `VERSION`; ");
            self::$db->execute(" DROP TABLE IF EXISTS `TABLEV1`; ");
            self::$db->execute(" DROP TABLE IF EXISTS `TABLEV2`; ");
        }
    }

    /**
     * Initialize the test case
     * Called for every defined test
     */
    protected function setUp(): void
    {
        if (!isset(self::$host) || (self::$host === '' || self::$host === '0') || (self::$dbName === '' || self::$dbName === '0') || (self::$username === '' || self::$username === '0')) {
            $this->markTestSkipped("MARIADB_HOST,MARIADB_DBNAME,MARIADB_USERNAME,MARIADB_PASSWORD environment variables NOT FOUND");
        } else {
            parent::setUp();
        }
    }

    /**
     * Clean up the test case, called for every defined test
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }

    public function testInstall(): void
    {
        // check if the database is already installed (install scheme with version table already exists)
        if (!self::$db->isSchemaInstalled()) {
            $this->assertTrue(self::$db->installSchema());
        } else {
            $this->fail('Schema already installed');
        }
    }

    public function testUpgradeSchema(): void
    {
        if (self::$db->isSchemaInstalled()) {
            $this->assertEquals(0, self::$db->getCurrentSchemaVersion());
            $this->assertEquals(2, self::$db->getUpgradeSchemaVersion());
            $this->assertEquals(2, self::$db->upgradeSchema(true));
        } else {
            $this->fail('Schema not installed');
        }
    }

    public function testExecWithAffectedRows(): void
    {
        $this->assertEquals(1, self::$db->exec(" UPDATE TABLEV2 SET id = 0 WHERE id = -1 "));
    }

    public function testExecWithoutAffectedRows(): void
    {
        $this->assertEquals(0, self::$db->exec(" UPDATE TABLEV2 SET id = -2 WHERE id = -1 "));
    }

    public function testExecute(): void
    {
        $this->assertTrue(self::$db->execute(" INSERT INTO TABLEV2 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 1)]));
    }

    public function testExistentRow(): void
    {
        $rows = self::$db->query(" SELECT id FROM TABLEV1 WHERE id = :id ", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 1)]);
        $this->assertCount(1, $rows);
        $this->assertEquals(1, $rows[0]->id ?? null);
    }

    public function testGetMultipleRows(): void
    {
        $this->assertEquals(1, self::$db->execute(" INSERT INTO TABLEV1 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 2)]));
        $rows = self::$db->query(" SELECT id FROM TABLEV1 ", []);
        $this->assertCount(2, $rows);
        $this->assertEquals(1, $rows[0]->id ?? null);
        $this->assertEquals(2, $rows[1]->id ?? null);
    }

    public function testGetMultipleRowsWithAfterQueryFunction(): void
    {
        $this->assertEquals(1, self::$db->execute(" INSERT INTO TABLEV1 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 3)]));
        $afterQueryFunction = function ($rows): void {
            array_map(
                function ($item) {
                    if (is_object($item) && isset($item->id) && is_numeric($item->id)) {
                        $item->id = intval($item->id);
                        $item->negativeId = $item->id * -1;
                    }

                    return ($item);
                },
                $rows
            );
        };
        $rows = self::$db->query(" SELECT id FROM TABLEV1 ", [], $afterQueryFunction);
        $this->assertCount(3, $rows);
        $this->assertEquals(1, $rows[0]->id ?? null);
        $this->assertEquals(2, $rows[1]->id ?? null);
        $this->assertEquals(3, $rows[2]->id ?? null);
        $this->assertEquals(-1, $rows[0]->negativeId ?? null);
        $this->assertEquals(-2, $rows[1]->negativeId ?? null);
        $this->assertEquals(-3, $rows[2]->negativeId ?? null);
    }

    public function testInTransactionWithTransaction(): void
    {
        if (self::$db->beginTransaction()) {
            $inTransaction = self::$db->inTransaction();
            if (self::$db->commit()) {
                $this->assertTrue($inTransaction);
            } else {
                $this->fail('commit failed');
            }
        } else {
            $this->fail('begin transaction failed');
        }
    }

    public function testInTransactionWithoutTransaction(): void
    {
        $this->assertFalse(self::$db->inTransaction());
    }

    public function testCommitTransaction(): void
    {
        if (self::$db->beginTransaction()) {
            $this->assertEquals(1, self::$db->execute(" INSERT INTO TABLEV2 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 2)]));
            if (self::$db->commit()) {
                $rows = self::$db->query(" SELECT id FROM TABLEV2 WHERE id = :id ", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 2)]);
                $this->assertCount(1, $rows);
                $this->assertEquals(2, $rows[0]->id ?? null);
            } else {
                $this->fail('commit failed');
            }
        } else {
            $this->fail('begin transaction failed');
        }
    }

    public function testRollbackTransaction(): void
    {
        if (self::$db->beginTransaction()) {
            $this->assertEquals(1, self::$db->execute(" INSERT INTO TABLEV2 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 3)]));
            if (self::$db->rollBack()) {
                $rows = self::$db->query(" SELECT id FROM TABLEV2 WHERE id = :id ", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 3)]);
                $this->assertCount(0, $rows);
            } else {
                $this->fail('rollBack failed');
            }
        } else {
            $this->fail('begin transaction failed');
        }
    }

    /*
    public function testBackupOnOriginalPath(): void
    {
        $backupFile = self::$db->backup();
        $this->assertNotEmpty($backupFile);
        $this->assertFileExists($backupFile);
    }


    public function testBackupOnCustomPath(): void
    {
        $backupFile = self::$db->backup(sys_get_temp_dir());
        $this->assertNotEmpty($backupFile);
        $this->assertFileExists($backupFile);
    }

    public function testBackupOnInvalidCustomPath(): void
    {
        $this->expectException(\aportela\DatabaseWrapper\Exception\DBException::class);
        $this->expectExceptionCode(\aportela\DatabaseWrapper\Exception\DBExceptionCode::INVALID_BACKUP_PATH->value);
        self::$db->backup(sys_get_temp_dir() . uniqid() . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR);
    }
    */

    public function testGetAdapterType(): void
    {
        $this->assertEquals(self::$db->getAdapterType(), \aportela\DatabaseWrapper\Adapter\AdapterType::PDO_MariaDB);
    }

    // this needs to be the final test
    public function testCloseAtEnd(): void
    {
        $this->expectNotToPerformAssertions();
        self::$db->close();
    }
}
