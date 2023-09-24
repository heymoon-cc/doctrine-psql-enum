<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Provider;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Exception;

interface MetaDataProviderInterface
{
    public function getComment(string $table, string $column): ?string;

    public function getTable(string $table): ?ClassMetadata;

    public function getRange(string $type): ?array;

    public function typeExists($type): bool;

    public function getRawType(string $table, string $column): ?string;

    /**
     * @throws Exception
     */
    public function getEnumClass(string $table, string $field): ?string;
}
