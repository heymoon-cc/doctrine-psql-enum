<?php

namespace Functional;

use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CustomSchemaTableNameTest extends TestCase
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
     * Test that entities with custom schema in table names work correctly.
     * This reproduces the issue where table name lookup fails due to missing schema prefix as the table keys.
     *
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getEnumClass
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getTable
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getTables
     * @covers \HeyMoon\DoctrinePostgresEnum\Attribute\EnumType::__construct
     * @covers \HeyMoon\DoctrinePostgresEnum\Attribute\EnumType::getName
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Driver\Driver::__construct
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Driver\Driver::getDatabasePlatform
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Middleware\DoctrineEnumColumnMiddleware::__construct
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Middleware\DoctrineEnumColumnMiddleware::wrap
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Platform\DoctrineEnumColumnPlatform::__construct
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Platform\DoctrineEnumColumnPlatform::_getCreateTableSQL
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Platform\DoctrineEnumColumnPlatform::createSchemaManager
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Platform\DoctrineEnumColumnPlatform::processColumn
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::__construct
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getRange
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::getRawType
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProvider::trimQuotes
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Schema\DoctrineEnumColumnSchemaManager::__construct
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Schema\DoctrineEnumColumnSchemaManager::_getPortableTableColumnDefinition
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Schema\DoctrineEnumColumnSchemaManager::_getPortableTableDefinition
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::getReflection
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::getSQLDeclaration
     * @covers \HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType::nameFromClass
     * @return void
     * @throws ExceptionInterface
     */
    public function testCustomSchemaTableName(): void
    {
        self::$kernel->boot();
        $doctrine = self::$kernel->getContainer()->get('doctrine');
        $connection = $doctrine->getConnection();

        // Clean up any existing schema (both test entities)
        $connection->executeQuery('DROP TABLE IF EXISTS "order" CASCADE');
        $connection->executeQuery('DROP TABLE IF EXISTS HasEnumEntity CASCADE');
        $connection->executeQuery('DROP TABLE IF EXISTS custom.custom_schema CASCADE');
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

        // We expect to see CREATE TABLE statements
        $this->assertStringContainsString('CREATE TABLE custom.custom_schema', implode(' ', $sql));

        // Actually execute the schema
        foreach ($sql as $row) {
            if (!empty(trim($row))) {
                $connection->executeQuery($row);
            }
        }

        // Verify schema was created successfully by checking if the table exists
        $result = $connection->executeQuery(
            "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'custom_schema' and table_schema = 'custom')"
        )->fetchOne();
        $this->assertTrue((bool)$result, 'Table "custom.custom_schema" should exist after schema creation');
    }
}
