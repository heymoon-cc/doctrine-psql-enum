<?php

namespace HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Provider;

use Doctrine\Persistence\Mapping\ClassMetadata;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProviderInterface;
use HeyMoon\DoctrinePostgresEnum\Tests\Fixtures\Enum\ExampleEnum;

final class VoidMetaDataProvider implements MetaDataProviderInterface
{
    public function getComment(string $table, string $column): ?string
    {
        return null;
    }

    public function getTable(string $table): ?ClassMetadata
    {
        return null;
    }

    public function getRange(string $type): ?array
    {
        return null;
    }

    public function typeExists($type): bool
    {
        return false;
    }

    public function getEnumClass(string $table, string $field): ?string
    {
        return ExampleEnum::class;
    }

    public function getRawType(string $table, string $column): ?string
    {
        return null;
    }
}
