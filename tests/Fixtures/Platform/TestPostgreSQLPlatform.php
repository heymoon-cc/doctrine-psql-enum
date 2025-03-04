<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Platform;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class TestPostgreSQLPlatform extends PostgreSQLPlatform
{
    /** @noinspection SqlNoDataSourceInspection */
    public function getCreateTableSQL(Table $table): array
    {
        $columns = implode(',',
            array_map(fn(Column $c) => "{$c->getName()} {$c->getType()->getSQLDeclaration($c->toArray(), $this)} NOT NULL",
                $table->getColumns()
            )
        );
        return [
            "CREATE TABLE {$table->getName()} ($columns)"
        ];
    }
}
