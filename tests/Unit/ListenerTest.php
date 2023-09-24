<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Driver\PDO;
use Doctrine\DBAL\Schema\TableDiff;
use Exception;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Exception\UnsupportedPlatformException;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Listener\DoctrineEnumColumnListener;
use HeyMoon\DoctrinePostgresEnum\Tests\BaseTestCase;

final class ListenerTest extends BaseTestCase
{
    /**
     * @throws SchemaException
     * @throws UnsupportedPlatformException
     * @throws DBALException
     * @covers DoctrineEnumColumnListener::postConnect
     * @covers DoctrineEnumColumnListener::checkPlatform
     */
    public function testUnsupportedPlatform(): void
    {
        $event = new ConnectionEventArgs(
            new Connection([], new PDO\SQLite\Driver)
        );
        $this->expectException(UnsupportedPlatformException::class);
        $this->getListener()->postConnect($event);
    }

    /**
     * @covers DoctrineEnumColumnListener::getSubscribedEvents
     */
    public function testListener(): void
    {
        $this->assertCount(6, $this->getListener()->getSubscribedEvents());
    }

    /**
     * @covers DoctrineEnumColumnListener::onSchemaCreateTable
     * @throws Exception
     */
    public function testSchemaCreate(): void
    {
        $columns = [$this->getColumn('column')];
        $event = new SchemaCreateTableEventArgs(
            new Table('test', $columns), array_map(fn(Column $c) => $c->toArray(), $columns), [], $this->getPlatform()
        );
        $this->assertCount(0, $event->getSql());
        $this->assertFalse($event->isDefaultPrevented());
        $this->getListener()->onSchemaCreateTable($event);
        $this->assertTrue($event->isDefaultPrevented());
        $this->assertCount(3, $event->getSql());
        $sql = $event->getSql();
        $this->assertStringStartsWith('DROP TYPE IF EXISTS', array_shift($sql));
        $this->assertStringStartsWith('CREATE TYPE', array_shift($sql));
        $this->assertStringStartsWith('CREATE TABLE', array_shift($sql));
    }

    /**
     * @covers DoctrineEnumColumnListener::onSchemaAlterTableAddColumn
     * @throws DBALException
     */
    public function testSchemaAlter(): void
    {
        $args = [new TableDiff('test'), $this->getPlatform()];
        $event = new SchemaAlterTableAddColumnEventArgs(
            $this->getColumn('column'),
            ...$args
        );
        $this->assertFalse($event->isDefaultPrevented());
        $this->getListener()->onSchemaAlterTableAddColumn($event);
        $this->assertTrue($event->isDefaultPrevented());
        $event = new SchemaAlterTableChangeColumnEventArgs(
            new ColumnDiff('column', $this->getColumn('column')),
            ...$args
        );
        $this->assertFalse($event->isDefaultPrevented());
        $this->getListener()->onSchemaAlterTableChangeColumn($event);
        $this->assertFalse($event->isDefaultPrevented());
        $this->getListener()->toggleNested();
        $this->getListener()->onSchemaAlterTableChangeColumn($event);
        $this->assertTrue($event->isDefaultPrevented());
    }
}
