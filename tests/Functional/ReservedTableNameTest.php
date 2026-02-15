<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Functional;

use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ReservedTableNameTest extends TestCase
{
    private static Kernel $kernel;

    public function setUp(): void
    {
        self::$kernel = new Kernel('prod', false);
    }

    public function tearDown(): void
    {
        self::$kernel->shutdown();
    }

    /**
     * Test that entities with reserved table names (escaped with backticks) work correctly.
     * This reproduces the issue where table name lookup fails due to backtick mismatch.
     *
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getEnumClass
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getTable
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getTables
     * @return void
     * @throws ExceptionInterface
     */
    public function testReservedTableNameWithBackticks()
    {
        self::$kernel->boot();
        $doctrine = self::$kernel->getContainer()->get('doctrine');
        $connection = $doctrine->getConnection();

        // Clean up any existing schema (both test entities)
        $connection->executeQuery('DROP TABLE IF EXISTS "order" CASCADE');
        $connection->executeQuery('DROP TABLE IF EXISTS HasEnumEntity CASCADE');
        $connection->executeQuery('DROP TYPE IF EXISTS order_status CASCADE');
        $connection->executeQuery('DROP TYPE IF EXISTS example CASCADE');
        $connection->executeQuery('DROP TYPE IF EXISTS another_example CASCADE');

        $application = new Application(self::$kernel);
        $command = $application->get('doctrine:schema:update');
        $output = new BufferedOutput();

        // This should fail when MetaDataProvider::getEnumClass() is called
        // because the table lookup uses '`order`' (with backticks) as the key,
        // but the schema manager queries with 'order' (without backticks)
        $command->run(new ArrayInput(['--dump-sql' => true]), $output);

        $outputText = $output->fetch();
        $sql = explode(PHP_EOL, $outputText);
        array_pop($sql); // Remove empty line

        // We expect to see CREATE TYPE and CREATE TABLE statements
        $this->assertContains("CREATE TYPE order_status AS ENUM ('pending','completed','cancelled');", $sql);
        $this->assertStringContainsString('CREATE TABLE "order"', implode(' ', $sql));

        // Actually execute the schema
        foreach ($sql as $row) {
            if (!empty(trim($row))) {
                $connection->executeQuery($row);
            }
        }

        // Verify schema was created successfully by checking if the table exists
        $result = $connection->executeQuery(
            "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'order')"
        )->fetchOne();
        $this->assertTrue((bool)$result, 'Table "order" should exist after schema creation');
    }
}
