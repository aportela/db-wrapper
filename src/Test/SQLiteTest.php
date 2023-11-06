<?php

declare(strict_types=1);

namespace aportela\DatabaseWrapper\Test;

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
final class SQLiteTest extends \PHPUnit\Framework\TestCase
{
    protected static ?\aportela\DatabaseWrapper\DB $db;

    private static string $databasePath;
    private static string $upgradeSchemaPath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$databasePath = tempnam(sys_get_temp_dir(), 'sqlite');
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
                        )
                    )
                );
        ";
        file_put_contents(self::$upgradeSchemaPath, trim($upgradeSchema));
        // main object
        self::$db = new \aportela\DatabaseWrapper\DB(
            new \aportela\DatabaseWrapper\Adapter\PDOSQLiteAdapter(self::$databasePath, self::$upgradeSchemaPath),
            new \Psr\Log\NullLogger()
        );
    }

    /**
     * Initialize the test case
     * Called for every defined test
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Clean up the test case, called for every defined test
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        if (file_exists(self::$databasePath)) {
            unlink(self::$databasePath);
        }
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

    public function testExec(): void
    {
        $this->assertEquals(1, self::$db->exec(" INSERT INTO TABLEV2 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 1)]));
    }

    public function testExistentRow(): void
    {
        $rows = self::$db->query(" SELECT id FROM TABLEV1 WHERE id = :id ", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 1)]);
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertEquals(1, $rows[0]->id);
    }

    public function testGetMultipleRows(): void
    {
        $this->assertEquals(1, self::$db->exec(" INSERT INTO TABLEV1 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 2)]));
        $rows = self::$db->query(" SELECT id FROM TABLEV1 ", []);
        $this->assertIsArray($rows);
        $this->assertCount(2, $rows);
        $this->assertEquals(1, $rows[0]->id);
        $this->assertEquals(2, $rows[1]->id);
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
            $this->assertEquals(1, self::$db->exec(" INSERT INTO TABLEV2 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 2)]));
            if (self::$db->commit()) {
                $rows = self::$db->query(" SELECT id FROM TABLEV2 WHERE id = :id ", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 2)]);
                $this->assertIsArray($rows);
                $this->assertCount(1, $rows);
                $this->assertEquals(2, $rows[0]->id);
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
            $this->assertEquals(1, self::$db->exec(" INSERT INTO TABLEV2 (id) VALUES(:id)", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 3)]));
            if (self::$db->rollBack()) {
                $rows = self::$db->query(" SELECT id FROM TABLEV2 WHERE id = :id ", [new \aportela\DatabaseWrapper\Param\IntegerParam(":id", 3)]);
                $this->assertIsArray($rows);
                $this->assertCount(0, $rows);
            } else {
                $this->fail('rollBack failed');
            }
        } else {
            $this->fail('begin transaction failed');
        }
    }

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

    // this needs to be the final test
    public function testCloseAtEnd(): void
    {
        $this->expectNotToPerformAssertions();
        self::$db->close();
    }
}
