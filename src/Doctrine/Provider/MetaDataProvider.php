<?php

/** @noinspection SqlDialectInspection */
/** @noinspection SqlNoDataSourceInspection */

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Provider;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping as ORM;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;

final class MetaDataProvider implements MetaDataProviderInterface
{
    private ?array $tables = null;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws Exception
     */
    public function getComment(string $table, string $column): ?string
    {
        return $this->entityManager->getConnection()->executeQuery("select pgd.description
                    from pg_catalog.pg_statio_all_tables as st
                    inner join pg_catalog.pg_description pgd on (
                        pgd.objoid = st.relid
                    )
                    inner join information_schema.columns c on (
                        pgd.objsubid   = c.ordinal_position and
                        c.table_name   = st.relname
                    ) WHERE c.table_name = :table AND c.column_name = :column", [
            'table' => $table,
            'column' => $column
        ])->fetchOne() ?: null;
    }

    public function getRawType(string $table, string $column): ?string
    {
        return $this->entityManager->getConnection()->executeQuery(
            "select case when data_type = 'USER-DEFINED' then udt_name else data_type end
                from information_schema.columns where table_name = :table and column_name = :column",
            [
                'table' => $table,
                'column' => $column
            ]
        )->fetchOne() ?: null;
    }

    public function getTable(string $table): ?ClassMetadata
    {
        return $this->getTables()[$table] ?? null;
    }

    protected function getTables(): array
    {
        if (is_array($this->tables)) {
            return $this->tables;
        }
        $names = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
        $this->tables = [];
        foreach ($names as $name) {
            $data = $this->entityManager->getClassMetadata($name);
            $this->tables[$data->getTableName()] = $data;
        }
        return $this->tables;
    }


    public function getRange(string $type): ?array
    {
        $connection = $this->entityManager->getConnection();
        try {
            $enumRange = $connection->executeQuery("SELECT unnest(enum_range(NULL::$type))")
                ->fetchAllNumeric();
            if (!$enumRange) {
                return null;
            }
            $platform = $connection->getDatabasePlatform();
            if (!$platform->hasDoctrineTypeMappingFor($type)) {
                if (!Type::hasType($type)) {
                    Type::addType($type, EnumType::class);
                }
                $platform->registerDoctrineTypeMapping($type, EnumType::getDefaultName());
            }
        } catch (Exception\DriverException | Exception) {
            return null;
        }

        return array_map(fn($v) => $v[0], $enumRange);
    }

    /**
     * @throws Exception
     */
    public function typeExists($type): bool
    {
        return $this->entityManager->getConnection()
            ->getDatabasePlatform()
            ->hasDoctrineTypeMappingFor($type);
    }

    /**
     * @throws Exception|ORM\MappingException
     */
    public function getEnumClass(string $table, string $field): ?string
    {
        $data = $this->getTable($table);
        $reflection = $data->getReflectionProperty($data->getFieldForColumn($field));
        $class = $reflection->getType()?->getName();
        if (!enum_exists($class)) {
            return null;
        }
        $arguments = [];
        foreach ($reflection->getAttributes() as $attribute) {
            if ($attribute->getName() !== ORM\Column::class) {
                continue;
            }
            $arguments = array_merge($arguments, $attribute->getArguments());
        }
        if (!array_key_exists('enumType', $arguments)) {
            return null;
        }
        $enumType = $arguments['enumType'] ?? $class;
        if ($class !== $enumType) {
            throw new Exception("enumType $enumType differs from column class $class");
        }
        return $class;
    }
}
